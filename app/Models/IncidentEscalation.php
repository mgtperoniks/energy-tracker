<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentEscalation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'escalated_at' => 'datetime',
        'response_payload' => 'array'
    ];

    public function auditLog()
    {
        return $this->belongsTo(AuditLog::class);
    }

    public function rule()
    {
        return $this->belongsTo(EscalationRule::class);
    }
}
