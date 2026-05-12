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
            Log::info('Telemetry received', $request->all());

            // EMERGENCY: Accept ANY payload with at least slave_id
            $validated = $request->validate([
                'slave_id'          => 'required|integer',
                'meter_boot_id'     => 'nullable|string',
                'kwh_total'         => 'nullable|numeric',
                'power_kw'          => 'nullable|numeric',
                'voltage'           => 'nullable|numeric',
                'current'           => 'nullable|numeric',
                'power_factor'      => 'nullable|numeric',
                'is_offline'        => 'nullable|boolean',
                'meter_replaced'    => 'nullable|boolean',
                'telemetry_quality' => 'nullable|string',
                'poll_duration_sec' => 'nullable|numeric',
            ]);

            $device = Device::where('slave_id', $validated['slave_id'])
                            ->where('type', 'power_meter')
                            ->where('status', true)
                            ->first();

            $now = now();

            if (!$device) {
                Log::warning('Telemetry Rejected: Device not found', ['slave_id' => $validated['slave_id']]);
                return response()->json(['status' => 'error', 'message' => 'Device not found or inactive.'], 403);
            }

            $isOffline      = (bool) ($validated['is_offline'] ?? false);
            $meterReplaced  = (bool) ($validated['meter_replaced'] ?? false);
            $recordedAt     = now()->floorSecond();
            $incomingKwhRaw = (float) ($validated['kwh_total'] ?? 0);
            $currentBaseline = (float) ($device->active_baseline_kwh ?? 0);
            $quality        = $validated['telemetry_quality'] ?? ($isOffline ? 'OFFLINE' : 'GOOD');

            // --- TASK 7: GAP DETECTION ---
            $latest = PowerReadingRaw::where('device_id', $device->id)
                ->where('recorded_at', '<', $recordedAt)
                ->orderByDesc('recorded_at')
                ->first();
            
            $gapDetected = false;
            if ($latest) {
                $diffInSeconds = $recordedAt->diffInSeconds($latest->recorded_at);
                // Flag if gap is > 7.5 minutes (assuming 5m interval)
                if ($diffInSeconds > 450) {
                    $gapDetected = true;
                }
            }

            // If offline with no kwh, use last known value to maintain timeline
            if ($isOffline && ($validated['kwh_total'] ?? null) === null) {
                $incomingKwhRaw = $latest ? (float) $latest->meter_kwh_raw : 0;
            }

            if ($meterReplaced) {
                $currentBaseline = 0;
                Log::info('Meter Replacement Applied', ['device_id' => $device->id]);
            }

            $normalizedKwhTotal = $currentBaseline + $incomingKwhRaw;

            // Use raw COALESCE upsert so partial/null readings never overwrite good data
            DB::statement("
                INSERT INTO power_readings_raw
                    (device_id, recorded_at, meter_kwh_raw, kwh_total, power_kw, voltage, `current`, power_factor, is_offline, telemetry_quality, poll_duration_sec, meter_boot_id, gap_detected)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    meter_kwh_raw     = COALESCE(VALUES(meter_kwh_raw), meter_kwh_raw),
                    kwh_total         = COALESCE(VALUES(kwh_total), kwh_total),
                    power_kw          = COALESCE(VALUES(power_kw), power_kw),
                    voltage           = COALESCE(VALUES(voltage), voltage),
                    `current`         = COALESCE(VALUES(`current`), `current`),
                    power_factor      = COALESCE(VALUES(power_factor), power_factor),
                    is_offline        = COALESCE(VALUES(is_offline), is_offline),
                    telemetry_quality = VALUES(telemetry_quality),
                    poll_duration_sec = VALUES(poll_duration_sec),
                    meter_boot_id     = VALUES(meter_boot_id),
                    gap_detected      = VALUES(gap_detected)
            ", [
                $device->id,
                $recordedAt->toDateTimeString(),
                $incomingKwhRaw,
                $normalizedKwhTotal,
                $validated['power_kw'] ?? null,
                $validated['voltage'] ?? null,
                $validated['current'] ?? null,
                $validated['power_factor'] ?? null,
                $isOffline ? 1 : 0,
                $quality,
                $validated['poll_duration_sec'] ?? null,
                $validated['meter_boot_id'] ?? null,
                $gapDetected ? 1 : 0,
            ]);

            $device->update([
                'is_online'           => !$isOffline,
                'last_seen_at'        => $now,
                'last_kwh_total'      => $normalizedKwhTotal,
                'active_baseline_kwh' => $currentBaseline,
            ]);

            Log::info('Telemetry Saved', ['device_id' => $device->id, 'quality' => $quality, 'gap' => $gapDetected]);
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Telemetry Ingestion Failed', ['error' => $e->getMessage(), 'payload' => $request->all()]);
            // NEVER return 500 to poller — always acknowledge
            return response()->json(['status' => 'accepted', 'note' => $e->getMessage()], 200);
        }
    }
}
