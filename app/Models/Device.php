<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_online' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_kwh_total' => 'decimal:3',
        'active_baseline_kwh' => 'decimal:3',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function powerReadingsRaw()
    {
        return $this->hasMany(PowerReadingRaw::class);
    }

    public function powerReadingsHourly()
    {
        return $this->hasMany(PowerReadingHourly::class);
    }

    public function powerReadingsDaily()
    {
        return $this->hasMany(PowerReadingDaily::class);
    }

    public function pollerLogs()
    {
        return $this->hasMany(PollerLog::class);
    }

    public function environmentalReadings()
    {
        return $this->hasMany(EnvironmentalReading::class);
    }

    /**
     * Determine semantic industrial status for the current state.
     */
    public function getOperationalStatusAttribute()
    {
        if (!$this->is_online) {
            return 'OFFLINE';
        }

        $latest = $this->powerReadingsRaw()->orderByDesc('recorded_at')->first();
        if (!$latest || $latest->recorded_at->diffInMinutes(now()) > 15) {
            return 'OFFLINE';
        }

        return $latest->operational_status;
    }

    /**
     * Returns HTML badge for current status.
     */
    public function getStatusBadgeAttribute()
    {
        $status = $this->operational_status;
        
        if ($status === 'OFFLINE') {
            return '<span class="bg-surface-container-highest text-outline px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide">Offline</span>';
        }

        $latest = $this->powerReadingsRaw()->orderByDesc('recorded_at')->first();
        if ($latest) {
            return $latest->status_badge;
        }

        return '<span class="bg-surface-container-highest text-outline px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide">Unknown</span>';
    }
}
