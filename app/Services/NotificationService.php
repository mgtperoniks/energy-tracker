<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Create a notification record from an audit log
     */
    public function createFromAudit(AuditLog $auditLog)
    {
        // Only notify for Warning, Error, and Critical
        $importantSeverities = [
            AuditLog::SEVERITY_WARNING,
            AuditLog::SEVERITY_ERROR,
            AuditLog::SEVERITY_CRITICAL
        ];

        if (!in_array($auditLog->severity, $importantSeverities)) {
            return null;
        }

        return Notification::create([
            'audit_log_id' => $auditLog->id,
            'title'        => $auditLog->title,
            'message'      => $auditLog->message,
            'severity'     => $auditLog->severity,
            'is_read'      => false
        ]);
    }

    public function markAsRead($notificationId)
    {
        $notification = Notification::find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead()
    {
        Notification::where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    public function unreadCount()
    {
        return Notification::where('is_read', false)->count();
    }

    public function latestNotifications($limit = 10)
    {
        return Notification::with('auditLog.device')
            ->where('is_read', false)
            ->orderByRaw("FIELD(severity, 'CRITICAL', 'ERROR', 'WARNING')")
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
