<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'kwh_baseline' => 'float',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function dailyEnergySummaries()
    {
        return $this->hasMany(DailyEnergySummary::class);
    }

    public function meterResets()
    {
        return $this->hasManyThrough(MeterReset::class, Device::class)->orderBy('reset_at', 'desc');
    }

    public function latestReading()
    {
        return $this->hasOneThrough(
            PowerReadingRaw::class,
            Device::class,
            'machine_id', // FK on devices
            'device_id',  // FK on power_readings
            'id',         // PK on machines
            'id'          // PK on devices
        )->latest('recorded_at');
    }

    public function todaySummary()
    {
        return $this->hasOne(DailyEnergySummary::class)
            ->whereDate('date', today());
    }

    public function recentReadings()
    {
        return $this->hasManyThrough(
            PowerReadingRaw::class,
            Device::class,
            'machine_id',
            'device_id',
            'id',
            'id'
        )->orderBy('recorded_at', 'desc')->limit(10);
    }

    /**
     * Lifetime cumulative kWh = baseline (from all past periods before resets)
     *                         + current meter reading (since last reset).
     *
     * This gives the same number as the physical meter would if it had
     * never been reset.
     */
    public function getLifetimeKwhAttribute(): float
    {
        $currentMeterKwh = $this->latestReading?->kwh_total ?? 0;
        return ($this->kwh_baseline ?? 0) + $currentMeterKwh;
    }
}
