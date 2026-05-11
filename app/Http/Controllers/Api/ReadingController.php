<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\PowerReadingRaw;
use App\Models\PollerLog;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReadingController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'slave_id'        => 'required|integer',
                'meter_boot_id'   => 'nullable|string',
                'kwh_total'       => 'required_without:is_offline|numeric',
                'power_kw'        => 'nullable|numeric',
                'voltage'         => 'nullable|numeric',
                'current'         => 'nullable|numeric',
                'power_factor'    => 'nullable|numeric',
                'is_offline'      => 'nullable|boolean',
                'meter_replaced'  => 'nullable|boolean',
                'stale_telemetry' => 'nullable|boolean'
            ]);

            // Optimization: Cached device lookup can be added here if needed, 
            // but simple where is usually fast enough for 10 devices.
            $device = Device::where('slave_id', $validated['slave_id'])
                            ->where('type', 'power_meter')
                            ->where('status', true)
                            ->first();

            $now = now();

            if (!$device) {
                Log::warning('Telemetry Rejected: Device not found or inactive', [
                    'slave_id' => $validated['slave_id'],
                    'payload' => $request->all()
                ]);
                return response()->json(['status' => 'error', 'message' => "Device inactive or not found."], 403);
            }

            $isOffline = $validated['is_offline'] ?? false;
            $meterReplaced = $validated['meter_replaced'] ?? false;
            $recordedAt = $now->copy()->startOfMinute();

            return DB::transaction(function () use ($validated, $device, $now, $recordedAt, $isOffline, $meterReplaced) {
                $incomingKwhRaw = (float) ($validated['kwh_total'] ?? 0);
                $currentBaseline = (float) ($device->active_baseline_kwh ?? 0);

                // Handle Meter Replacement (Force Reset Logic)
                if ($meterReplaced) {
                    $currentBaseline = 0; // New meter starts fresh
                    Log::info('Meter Replacement Applied', ['device_id' => $device->id, 'new_raw' => $incomingKwhRaw]);
                    
                    app(AuditService::class)->logEvent(
                        $device->id,
                        'METER_REPLACED',
                        'HARDWARE_EVENT',
                        AuditLog::SEVERITY_INFO,
                        'Physical Meter Replaced',
                        'Meter replacement detected by boot ID change.',
                        ['new_raw' => $incomingKwhRaw],
                        'api'
                    );
                }

                // If offline and no new reading, fetch last raw to maintain continuity
                if ($isOffline && ($validated['kwh_total'] ?? null) === null) {
                    $latest = PowerReadingRaw::where('device_id', $device->id)->orderByDesc('recorded_at')->first();
                    $incomingKwhRaw = $latest ? (float)$latest->meter_kwh_raw : 0;
                }

                $normalizedKwhTotal = $currentBaseline + $incomingKwhRaw;

                // Idempotent UPSERT (Tolerates replay/duplicate timestamps)
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

                $device->update([
                    'is_online'           => !$isOffline,
                    'last_seen_at'        => $now,
                    'last_kwh_total'      => $normalizedKwhTotal,
                    'active_baseline_kwh' => $currentBaseline,
                    'last_boot_id'        => $validated['meter_boot_id'] ?? $device->last_boot_id
                ]);

                Log::info('Telemetry Received', [
                    'device_id' => $device->id,
                    'kw' => $validated['power_kw'] ?? 0,
                    'status' => $isOffline ? 'OFFLINE' : 'ONLINE'
                ]);

                return response()->json(['status' => 'success']);
            });

        } catch (\Exception $e) {
            Log::error('Telemetry Ingestion Failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
