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

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function escalations()
    {
        return $this->hasMany(IncidentEscalation::class);
    }

    /**
     * Resolve an open event
     */
    public function resolve($rootCause = null)
    {
        if ($this->status !== self::STATUS_OPEN) return;

        $now = Carbon::now();
        $this->update([
            'status'           => self::STATUS_RESOLVED,
            'resolved_at'      => $now,
            'duration_minutes' => $this->detected_at->diffInMinutes($now),
            'root_cause'       => $rootCause
        ]);
    }
}
