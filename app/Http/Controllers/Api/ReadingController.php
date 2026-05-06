<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\PowerReadingRaw;
use App\Models\PollerLog;
use Illuminate\Http\Request;

class ReadingController extends Controller
{
    public function store(Request $request)
    {
        // Menggunakan required_without agar logika murni deklaratif
        $validated = $request->validate([
            'slave_id' => 'required|integer',
            'kwh_total' => 'required_without:is_offline|numeric',
            'power_kw' => 'nullable|numeric',
            'voltage' => 'nullable|numeric',
            'current' => 'nullable|numeric',
            'power_factor' => 'nullable|numeric',
            'is_offline' => 'nullable|boolean'
        ]);

        $device = Device::where('slave_id', $validated['slave_id'])
                        ->where('type', 'power_meter')
                        ->where('status', true)
                        ->first();

        $now = now();

        // 1. Validasi Device (Found & Active)
        if (!$device) {
            // Cek apakah device ada tapi nonaktif untuk logging yang lebih akurat
            $inactiveDevice = Device::where('slave_id', $validated['slave_id'])->first();
            $errorMsg = $inactiveDevice 
                ? "Device ID {$inactiveDevice->id} (Slave {$validated['slave_id']}) is disabled."
                : "Device with Slave ID {$validated['slave_id']} not found.";

            PollerLog::create([
                'status' => PollerLog::STATUS_ERROR,
                'message' => $errorMsg,
                'event_at' => $now
            ]);
            
            return response()->json(['status' => 'error', 'message' => "Device inactive or not found."], 403);
        }

        // 2. Handling Status Offline (State Transition Only)
        $isOffline = $validated['is_offline'] ?? false;
        if ($isOffline) {
            if ($device->is_online) {
                // State Transition: ONLINE -> OFFLINE
                $device->update(['is_online' => false, 'last_seen_at' => $now]);
                
                PollerLog::create([
                    'device_id' => $device->id,
                    'status' => PollerLog::STATUS_OFFLINE,
                    'message' => 'Meter reported offline',
                    'event_at' => $now
                ]);
            } else {
                // State Tahan: OFFLINE -> OFFLINE
                $device->update(['last_seen_at' => $now]);
            }
            
            // Proceed to save an offline marker reading
        }

        // 3. Normalisasi Waktu (Cegah Duplicate Entry pada menit yang sama)
        $recordedAt = $now->copy()->startOfMinute();

        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $device, $now, $recordedAt, $isOffline) {
            $incomingKwhRaw = $validated['kwh_total'] ?? null;
            $currentBaseline = $device->active_baseline_kwh ?? 0;

            // IF OFFLINE: Use last known raw to keep baseline stable
            if ($isOffline && $incomingKwhRaw === null) {
                $latest = PowerReadingRaw::where('device_id', $device->id)->orderByDesc('recorded_at')->first();
                $incomingKwhRaw = $latest ? $latest->meter_kwh_raw : 0;
            }

            // 4. Ambil data mentah terakhir untuk perbandingan logic Auto Reset
            $latestReading = PowerReadingRaw::where('device_id', $device->id)
                                ->orderByDesc('recorded_at')
                                ->first();

            if (!$isOffline && $latestReading && $latestReading->meter_kwh_raw !== null && $incomingKwhRaw !== null) {
                $previousKwhRaw = (float) $latestReading->meter_kwh_raw;
                $difference = $previousKwhRaw - $incomingKwhRaw;
                
                $dropRatio = $previousKwhRaw > 0 ? ($difference / $previousKwhRaw) : 0;
                
                // HYBRID THRESHOLD: Drop lebih dari 1 kWh ATAU rasio turun lebih dari 30%
                if ($difference > 0 && ($difference > 1 || $dropRatio > 0.3)) {
                    
                    // Sanity Guard: Pastikan incoming_raw dalam range "startup safe" (misal meteran baru jalan dari nol)
                    $maxStartupRaw = config('energy.auto_reset_max_new_raw', 100);
                    
                    if ($incomingKwhRaw <= $maxStartupRaw) {
                        $newBaseline = $currentBaseline + $previousKwhRaw;
                        
                        // Idempotency: Cukup kombinasi device dan waktu reset
                        \Illuminate\Support\Facades\DB::table('meter_resets')->updateOrInsert(
                            [
                                'device_id' => $device->id,
                                'reset_at'  => $recordedAt->toDateTimeString(), 
                            ],
                            [
                                'previous_raw_kwh'      => $previousKwhRaw,
                                'new_raw_kwh'           => $incomingKwhRaw,
                                'previous_baseline_kwh' => $currentBaseline,
                                'new_baseline_kwh'      => $newBaseline,
                                'kwh_at_reset'          => $previousKwhRaw, // Legacy history
                                'reason'                => 'Auto reset detected. Drop: ' . round($difference, 2) . ' kWh (' . round($dropRatio * 100, 1) . '%)',
                                'created_at'            => $now,
                                'updated_at'            => $now,
                            ]
                        );

                        PollerLog::create([
                            'device_id' => $device->id,
                            'status'    => PollerLog::STATUS_WARNING,
                            'message'   => 'Meter hardware reset/overflow detected automatically.',
                            'event_at'  => $now
                        ]);

                        $currentBaseline = $newBaseline;
                    } else {
                        // Anomaly Guard: Drop terdeteksi tapi angka baru terlalu tinggi (corrupted packet)
                        PollerLog::create([
                            'device_id' => $device->id,
                            'status'    => PollerLog::STATUS_ERROR,
                            'message'   => 'Corrupted modbus packet anomaly. Invalid drop with large incoming raw: ' . $incomingKwhRaw . ' kWh',
                            'event_at'  => $now
                        ]);
                        // Baseline tidak di-update, tetap gunakan logic UPSERT normal
                    }
                }
            }

            $normalizedKwhTotal = $currentBaseline + $incomingKwhRaw;

            // 5. O(1) UPSERT OPERATION 
            PowerReadingRaw::upsert(
                [
                    [
                        'device_id'     => $device->id,
                        'recorded_at'   => $recordedAt->toDateTimeString(),
                        'meter_kwh_raw' => $incomingKwhRaw,
                        'kwh_total'     => $normalizedKwhTotal,
                        'power_kw'      => $validated['power_kw'] ?? null,
                        'voltage'       => $validated['voltage'] ?? null,
                        'current'       => $validated['current'] ?? null,
                        'power_factor'  => $validated['power_factor'] ?? null,
                        'is_offline'    => $isOffline,
                    ]
                ],
                ['device_id', 'recorded_at'], 
                ['meter_kwh_raw', 'kwh_total', 'power_kw', 'voltage', 'current', 'power_factor', 'is_offline'] 
            );

            // 6. Update Metadata Device
            $device->update([
                'is_online'           => true,
                'last_seen_at'        => $now,
                'last_kwh_total'      => $normalizedKwhTotal,
                'active_baseline_kwh' => $currentBaseline
            ]);

            return response()->json(['status' => 'success']);
        });
    }
}
