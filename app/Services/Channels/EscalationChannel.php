<?php

namespace App\Services\Channels;

use App\Models\AuditLog;

interface EscalationChannel
{
    public function send(AuditLog $auditLog, string $recipient): bool;
}
