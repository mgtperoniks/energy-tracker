<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\EscalationRule;
use App\Models\IncidentEscalation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EscalationService
{
    /**
     * Check all open incidents for potential escalation
     */
    public function checkPendingEscalations()
    {
        $openIncidents = AuditLog::where('status', AuditLog::STATUS_OPEN)
            ->whereIn('severity', [AuditLog::SEVERITY_WARNING, AuditLog::SEVERITY_ERROR, AuditLog::SEVERITY_CRITICAL])
            ->get();

        foreach ($openIncidents as $incident) {
            $this->processEscalation($incident);
        }
    }

    protected function processEscalation(AuditLog $incident)
    {
        $rules = EscalationRule::where('severity', $incident->severity)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if ($this->shouldEscalate($incident, $rule)) {
                $this->sendEscalation($incident, $rule);
            }
        }
    }

    protected function shouldEscalate(AuditLog $incident, EscalationRule $rule): bool
    {
        // 1. Check time threshold
        $minutesOpen = $incident->detected_at->diffInMinutes(now());
        if ($minutesOpen < $rule->threshold_minutes) {
            return false;
        }

        // 2. Prevent duplicate escalation for same incident + channel
        $exists = IncidentEscalation::where('audit_log_id', $incident->id)
            ->where('rule_id', $rule->id)
            ->exists();

        return !$exists;
    }

    protected function sendEscalation(AuditLog $incident, EscalationRule $rule)
    {
        $channelClass = config("escalation.channels.{$rule->channel}");
        
        if (!$channelClass || !class_exists($channelClass)) {
            Log::error("Escalation channel [{$rule->channel}] not found for Rule #{$rule->id}");
            return;
        }

        $channel = app($channelClass);
        $success = $channel->send($incident, $rule->recipient);

        IncidentEscalation::create([
            'audit_log_id'    => $incident->id,
            'rule_id'         => $rule->id,
            'escalated_at'    => now(),
            'channel'         => $rule->channel,
            'recipient'       => $rule->recipient,
            'delivery_status' => $success ? 'sent' : 'failed'
        ]);
    }
}
