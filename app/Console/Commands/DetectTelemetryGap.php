<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\PowerReadingRaw;
use App\Models\AuditLog;
use App\Services\AuditService;
use Carbon\Carbon;

class DetectTelemetryGap extends Command
{
    protected $signature = 'health:detect-telemetry-gap';
    protected $description = 'Detect gaps in telemetry (> 3 minutes)';

    public function handle(AuditService $audit)
    {
        $devices = Device::where('type', 'power_meter')->get();
        $incidents = 0;

        foreach ($devices as $device) {
            $latest = PowerReadingRaw::where('device_id', $device->id)
                ->orderByDesc('recorded_at')
                ->first();

            if (!$latest) continue;

            $diffMinutes = $latest->recorded_at->diffInMinutes(now());

            if ($diffMinutes > 3) {
                $audit->logEvent(
                    $device->id,
                    'TELEMETRY_GAP',
                    'COMMUNICATION',
                    AuditLog::SEVERITY_ERROR,
                    'Telemetry Gap Detected',
                    "No data received for {$diffMinutes} minutes. Last seen: " . $latest->recorded_at->toDateTimeString(),
                    ['gap_minutes' => $diffMinutes, 'last_seen' => $latest->recorded_at],
                    'poller'
                );
                $incidents++;
            } else {
                // Auto-resolve if data is flowing again
                $audit->resolveEvent($device->id, 'TELEMETRY_GAP', 'Telemetry resumed');
            }
        }

        $this->info("Scan complete. Found {$incidents} active gaps.");
    }
}
