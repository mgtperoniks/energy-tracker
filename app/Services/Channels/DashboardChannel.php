<?php

namespace App\Services\Channels;

use App\Models\AuditLog;
use App\Services\NotificationService;

class DashboardChannel implements EscalationChannel
{
    public function send(AuditLog $auditLog, string $recipient): bool
    {
        // Re-trigger notification or create a persistent alert
        app(NotificationService::class)->createFromAudit($auditLog);
        return true;
    }
}
