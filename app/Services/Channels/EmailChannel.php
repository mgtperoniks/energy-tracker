<?php

namespace App\Services\Channels;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Mail;

class EmailChannel implements EscalationChannel
{
    public function send(AuditLog $auditLog, string $recipient): bool
    {
        // Conceptual email sending logic
        try {
            // Mail::to($recipient)->send(new \App\Mail\IncidentEscalationMail($auditLog));
            \Illuminate\Support\Facades\Log::info("ESCALATION EMAIL sent to {$recipient} for Audit #{$auditLog->id}");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
