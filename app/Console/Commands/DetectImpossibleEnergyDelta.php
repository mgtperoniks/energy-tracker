<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\PowerReadingRaw;
use App\Models\AuditLog;
use App\Services\AuditService;

class DetectImpossibleEnergyDelta extends Command
{
    protected $signature = 'health:detect-impossible-energy-delta';
    protected $description = 'Detect impossible energy growth (physics violation)';

    public function handle(AuditService $audit)
    {
        $devices = Device::where('type', 'power_meter')->get();

        foreach ($devices as $device) {
            $readings = PowerReadingRaw::where('device_id', $device->id)
                ->orderByDesc('recorded_at')
                ->limit(2)
                ->get();

            if ($readings->count() < 2) continue;

            $curr = $readings[0];
            $prev = $readings[1];

            $deltaKwh = $curr->kwh_total - $prev->kwh_total;
            $deltaMinutes = max(0.5, $curr->recorded_at->diffInMinutes($prev->recorded_at));
            
            // Rule: Physics limit. A device cannot consume more than its max capacity.
            // Max possible kWh in X minutes = (Max kW Capacity / 60) * minutes
            $maxKw = 500; // Physical furnace limit
            $maxPossibleKwh = ($maxKw / 60) * $deltaMinutes;

            if ($deltaKwh > $maxPossibleKwh * 1.5) { // 50% safety margin
                $audit->logEvent(
                    $device->id,
                    'IMPOSSIBLE_DELTA',
                    'DATA_ANOMALY',
                    AuditLog::SEVERITY_CRITICAL,
                    'Impossible Energy Growth',
                    "Energy grew by {$deltaKwh} kWh in {$deltaMinutes} minutes. Physics limit: {$maxPossibleKwh} kWh.",
                    ['delta_kwh' => $deltaKwh, 'limit' => $maxPossibleKwh, 'minutes' => $deltaMinutes],
                    'api'
                );
            }
        }
    }
}
