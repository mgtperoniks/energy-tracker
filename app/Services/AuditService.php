<?php

namespace App\Services;

use App\Models\AuditLog;
use Carbon\Carbon;

class AuditService
{
    /**
     * Log a new audit event or update if already open for the same device/code
     */
    public function logEvent($deviceId, $eventCode, $eventType, $severity, $title, $message, $payload = [], $source = 'system')
    {
        $fingerprint = md5($deviceId . $eventCode);

        // Check if an open event of the same type exists for this device
        $existing = AuditLog::where('device_id', $deviceId)
            ->where('fingerprint', $fingerprint)
            ->where('status', AuditLog::STATUS_OPEN)
            ->first();

        if ($existing) {
            // Update message or count in payload if needed
            $existing->update([
                'message'      => $message,
                'payload_json' => array_merge($existing->payload_json ?? [], $payload, ['updated_count' => ($existing->payload_json['updated_count'] ?? 0) + 1]),
            ]);
            return $existing;
        }

        $log = AuditLog::create([
            'device_id'    => $deviceId,
            'event_code'   => $eventCode,
            'event_type'   => $eventType,
            'severity'     => $severity,
            'fingerprint'  => $fingerprint,
            'title'        => $title,
            'message'      => $message,
            'payload_json' => $payload,
            'detected_at'  => Carbon::now(),
            'status'       => AuditLog::STATUS_OPEN,
            'source_layer' => $source
        ]);

        // Trigger Notification
        app(\App\Services\NotificationService::class)->createFromAudit($log);

        return $log;
    }

    /**
     * Resolve any open event for a specific device and code
     */
    public function resolveEvent($deviceId, $eventCode, $rootCause = 'System auto-resolved')
    {
        $events = AuditLog::where('device_id', $deviceId)
            ->where('event_code', $eventCode)
            ->where('status', AuditLog::STATUS_OPEN)
            ->get();

        foreach ($events as $event) {
            $event->resolve($rootCause);
        }
    }

    /**
     * Check if an open event exists
     */
    public function openEventExists($deviceId, $eventCode)
    {
        return AuditLog::where('device_id', $deviceId)
            ->where('event_code', $eventCode)
            ->where('status', AuditLog::STATUS_OPEN)
            ->exists();
    }
}
