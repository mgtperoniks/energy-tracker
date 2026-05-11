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
                'slave_id'        => 'required|integer',
                'meter_boot_id'   => 'nullable|string',
                'kwh_total'       => 'nullable|numeric',
                'power_kw'        => 'nullable|numeric',
                'voltage'         => 'nullable|numeric',
                'current'         => 'nullable|numeric',
                'power_factor'    => 'nullable|numeric',
                'is_offline'      => 'nullable|boolean',
                'meter_replaced'  => 'nullable|boolean',
                'stale_telemetry' => 'nullable|boolean',
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
            $recordedAt     = $now->copy()->startOfMinute();
            $incomingKwhRaw = (float) ($validated['kwh_total'] ?? 0);
            $currentBaseline = (float) ($device->active_baseline_kwh ?? 0);

            // If offline with no kwh, use last known value to maintain timeline
            if ($isOffline && ($validated['kwh_total'] ?? null) === null) {
                $latest = PowerReadingRaw::where('device_id', $device->id)->orderByDesc('recorded_at')->first();
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
                    (device_id, recorded_at, meter_kwh_raw, kwh_total, power_kw, voltage, `current`, power_factor)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    meter_kwh_raw = COALESCE(VALUES(meter_kwh_raw), meter_kwh_raw),
                    kwh_total     = COALESCE(VALUES(kwh_total), kwh_total),
                    power_kw      = COALESCE(VALUES(power_kw), power_kw),
                    voltage       = COALESCE(VALUES(voltage), voltage),
                    `current`     = COALESCE(VALUES(`current`), `current`),
                    power_factor  = COALESCE(VALUES(power_factor), power_factor)
            ", [
                $device->id,
                $recordedAt->toDateTimeString(),
                $incomingKwhRaw,
                $normalizedKwhTotal,
                $validated['power_kw'] ?? null,
                $validated['voltage'] ?? null,
                $validated['current'] ?? null,
                $validated['power_factor'] ?? null,
            ]);

            $device->update([
                'is_online'           => !$isOffline,
                'last_seen_at'        => $now,
                'last_kwh_total'      => $normalizedKwhTotal,
                'active_baseline_kwh' => $currentBaseline,
            ]);

            Log::info('Telemetry Saved', ['device_id' => $device->id, 'kwh' => $normalizedKwhTotal, 'status' => $isOffline ? 'OFFLINE' : 'ONLINE']);
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Telemetry Ingestion Failed', ['error' => $e->getMessage(), 'payload' => $request->all()]);
            // NEVER return 500 to poller — always acknowledge
            return response()->json(['status' => 'accepted', 'note' => $e->getMessage()], 200);
        }
    }
}

