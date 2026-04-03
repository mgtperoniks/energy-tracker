<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

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

    public function latestReading()
    {
        return $this->hasOneThrough(
            PowerReading::class,
            Device::class,
            'machine_id', // Foreign key on devices table
            'device_id',  // Foreign key on power_readings table
            'id',         // Local key on machines table
            'id'          // Local key on devices table
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
            PowerReading::class,
            Device::class,
            'machine_id',
            'device_id',
            'id',
            'id'
        )->orderBy('recorded_at', 'desc')->limit(10);
    }
}
