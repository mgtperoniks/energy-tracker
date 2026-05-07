<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PowerReadingRaw extends Model
{
    protected $table = 'power_readings_raw';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $appends = ['operational_status', 'status_badge'];

    protected $casts = [
        'meter_kwh_raw' => 'decimal:3',
        'kwh_total' => 'decimal:3',
        'power_kw' => 'decimal:3',
        'voltage' => 'decimal:2',
        'current' => 'decimal:2',
        'power_factor' => 'decimal:3',
        'recorded_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Determine semantic industrial status based on power levels.
     */
    public function getOperationalStatusAttribute()
    {
        // TODO: Restore OFFLINE check once is_offline column is added to database
        /*
        if ($this->is_offline) {
            return 'OFFLINE';
        }
        */

        // FAULT: Missing critical telemetry (while supposedly online)
        if ($this->voltage === null || $this->current === null) {
            return 'FAULT';
        }

        $power = (float) $this->power_kw;

        if ($power < 3) return 'IDLE';
        if ($power < 30) return 'STANDBY';
        if ($power < 250) return 'HEATING';
        if ($power <= 420) return 'MELTING';
        
        return 'OVERLOAD';
    }

    /**
     * Returns HTML badge based on operational status.
     */
    public function getStatusBadgeAttribute()
    {
        $status = $this->operational_status;
        $styles = [
            'OFFLINE'  => 'bg-surface-container-highest text-outline',
            'FAULT'    => 'bg-error text-on-error',
            'IDLE'     => 'bg-surface-container-highest text-outline',
            'STANDBY'  => 'bg-secondary-container text-on-secondary-container',
            'HEATING'  => 'bg-tertiary-container text-on-tertiary-container',
            'MELTING'  => 'bg-primary text-on-primary',
            'OVERLOAD' => 'bg-error-container text-error',
        ];

        $class = $styles[$status] ?? 'bg-surface-container text-on-surface';
        
        return "<span class=\"{$class} px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider\">{$status}</span>";
    }
}
