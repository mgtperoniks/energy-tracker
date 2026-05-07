<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\PowerReadingRaw;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class DetectDeviceRestart extends Command
{
    protected $signature = 'health:detect-device-restart';
    protected $description = 'Detect hardware resets by looking for kwh_total decreases';

    public function handle(AuditService $audit)
    {
        $devices = Device::where('type', 'power_meter')->get();
        
        foreach ($devices as $device) {
            // Compare last 2 readings
            $readings = PowerReadingRaw::where('device_id', $device->id)
                ->orderByDesc('recorded_at')
                ->limit(2)
                ->get();

            if ($readings->count() < 2) continue;

            $current = $readings[0];
            $previous = $readings[1];

            if ($current->kwh_total < $previous->kwh_total && !$current->is_offline) {
                $audit->logEvent(
                    $device->id,
                    'DEVICE_RESTART',
                    'DEVICE_EVENT',
                    AuditLog::SEVERITY_WARNING,
                    'Unexpected Hardware Reset',
                    "Cumulative kWh dropped from {$previous->kwh_total} to {$current->kwh_total}.",
                    ['prev_kwh' => $previous->kwh_total, 'curr_kwh' => $current->kwh_total],
                    'device'
                );
            }
        }
    }
}
