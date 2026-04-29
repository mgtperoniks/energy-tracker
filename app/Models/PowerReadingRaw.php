<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PowerReadingRaw extends Model
{
    protected $table = 'power_readings_raw';
    public $timestamps = false;
    protected $guarded = ['id'];

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
}
