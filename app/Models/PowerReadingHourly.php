<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PowerReadingHourly extends Model
{
    protected $table = 'power_readings_hourly';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'kwh_total' => 'decimal:3',
        'kwh_usage' => 'decimal:3',
        'avg_power_kw' => 'decimal:3',
        'min_power_kw' => 'decimal:3',
        'max_power_kw' => 'decimal:3',
        'avg_voltage' => 'decimal:2',
        'avg_current' => 'decimal:2',
        'avg_power_factor' => 'decimal:3',
        'recorded_at' => 'datetime',
        'sample_count' => 'integer',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
