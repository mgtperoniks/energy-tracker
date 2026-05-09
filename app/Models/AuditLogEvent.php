<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLogEvent extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload_json' => 'array',
        'event_at' => 'datetime',
        'sequence_no' => 'integer',
    ];

    public function auditLog()
    {
        return $this->belongsTo(AuditLog::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
