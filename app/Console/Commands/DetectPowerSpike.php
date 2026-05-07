<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\PowerReadingRaw;
use App\Models\AuditLog;
use App\Services\AuditService;

class DetectPowerSpike extends Command
{
    protected $signature = 'health:detect-power-spike';
    protected $description = 'Detect sudden power spikes (> 40% in 1 minute)';

    public function handle(AuditService $audit)
    {
        $devices = Device::where('type', 'power_meter')->get();

        foreach ($devices as $device) {
            $readings = PowerReadingRaw::where('device_id', $device->id)
                ->where('recorded_at', '>=', now()->subMinutes(5))
                ->orderByDesc('recorded_at')
                ->limit(2)
                ->get();

            if ($readings->count() < 2) continue;

            $curr = (float) $readings[0]->power_kw;
            $prev = (float) $readings[1]->power_kw;

            if ($prev > 5) { // Threshold to avoid noise at low power
                $pctChange = (($curr - $prev) / $prev) * 100;

                if ($pctChange > 40) {
                    $audit->logEvent(
                        $device->id,
                        'POWER_SPIKE',
                        'POWER_QUALITY',
                        AuditLog::SEVERITY_WARNING,
                        'Sudden Power Spike',
                        "Power jumped by " . round($pctChange, 1) . "% ({$prev}kW -> {$curr}kW) in 1 minute.",
                        ['prev_kw' => $prev, 'curr_kw' => $curr, 'pct' => $pctChange],
                        'device'
                    );
                }
            }
        }
    }
}
