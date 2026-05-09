<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AuditLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload_json' => 'array',
        'detected_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    // Severities
    const SEVERITY_INFO     = 'INFO';
    const SEVERITY_WARNING  = 'WARNING';
    const SEVERITY_ERROR    = 'ERROR';
    const SEVERITY_CRITICAL = 'CRITICAL';

    // Statuses
    const STATUS_OPEN         = 'open';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_RESOLVED     = 'resolved';
    const STATUS_IGNORED      = 'ignored';

    public function acknowledger()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function reopener()
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function escalations()
    {
        return $this->hasMany(IncidentEscalation::class);
    }

    public function events()
    {
        return $this->hasMany(AuditLogEvent::class)->orderBy('sequence_no', 'asc');
    }

    /**
     * Log an immutable lifecycle event
     */
    public function logEvent($type, $userId = null, $payload = [])
    {
        $lastSequence = $this->events()->max('sequence_no') ?: 0;
        
        return $this->events()->create([
            'event_type' => $type,
            'performed_by' => $userId ?: auth()->id(),
            'event_at' => now(),
            'payload_json' => $payload,
            'sequence_no' => $lastSequence + 1,
        ]);
    }

    /**
     * Resolve an open event
     */
    public function resolve($rootCause = null)
    {
        if ($this->status === self::STATUS_RESOLVED) return;

        $now = Carbon::now();
        
        // Fix: Use reopened_at as anchor if exists to avoid counting days while incident was resolved
        $anchorTime = $this->reopened_at ?? $this->detected_at;

        $this->update([
            'status'           => self::STATUS_RESOLVED,
            'resolved_at'      => $now,
            'duration_minutes' => $anchorTime->diffInMinutes($now),
            'root_cause'       => $rootCause
        ]);

        $this->logEvent('resolved', auth()->id(), ['root_cause' => $rootCause]);
    }
}
