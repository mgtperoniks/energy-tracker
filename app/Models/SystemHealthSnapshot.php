<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemHealthSnapshot extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'snapshot_date' => 'date',
        'payload_json' => 'array',
        'telemetry_score' => 'float',
        'incident_score' => 'float',
        'accounting_score' => 'float',
        'overall_score' => 'float',
    ];
}
