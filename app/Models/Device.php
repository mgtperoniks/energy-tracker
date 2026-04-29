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
}
