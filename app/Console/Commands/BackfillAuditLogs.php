<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use Carbon\Carbon;

class BackfillAuditLogs extends Command
{
    protected $signature = 'system:backfill-audit-logs';
    protected $description = 'Migrate legacy poller_logs into structured audit_logs';

    public function handle()
    {
        $oldLogs = DB::table('poller_logs')->get();
        $this->info("Found " . $oldLogs->count() . " legacy logs. Starting migration...");

        $migrated = 0;
        foreach ($oldLogs as $old) {
            // Infer Event Type & Code
            $eventType = 'SYSTEM';
            $eventCode = 'LEGACY_LOG';
            $title     = 'Legacy Poller Event';

            if (stripos($old->message, 'Corrupted modbus') !== false) {
                $eventType = 'DATA_ANOMALY';
                $eventCode = 'DATA_CORRUPTION';
                $title     = 'Corrupted Modbus Packet';
            } elseif (stripos($old->message, 'reset') !== false) {
                $eventType = 'DEVICE_EVENT';
                $eventCode = 'DEVICE_RESTART';
                $title     = 'Meter Hardware Reset';
            } elseif (stripos($old->message, 'voltage') !== false) {
                $eventType = 'POWER_QUALITY';
                $eventCode = 'LOW_VOLTAGE';
                $title     = 'Low Voltage Operation';
            }

            // Map Severity
            $severity = AuditLog::SEVERITY_INFO;
            if ($old->status === 'ERROR') $severity = AuditLog::SEVERITY_ERROR;
            if ($old->status === 'WARNING') $severity = AuditLog::SEVERITY_WARNING;
            if ($old->status === 'OFFLINE') $severity = AuditLog::SEVERITY_ERROR;

            $fingerprint = md5($old->device_id . $eventCode);

            AuditLog::create([
                'device_id'    => $old->device_id,
                'event_code'   => $eventCode,
                'event_type'   => $eventType,
                'severity'     => $severity,
                'fingerprint'  => $fingerprint,
                'title'        => $title,
                'message'      => $old->message,
                'detected_at'  => $old->event_at,
                'status'       => AuditLog::STATUS_RESOLVED, // Legacy is assumed resolved
                'resolved_at'  => $old->created_at,
                'source_layer' => 'poller',
                'created_at'   => $old->created_at,
                'updated_at'   => $old->updated_at,
            ]);

            $migrated++;
        }

        $this->info("Successfully migrated {$migrated} logs.");
    }
}
