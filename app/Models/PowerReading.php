<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PowerReading extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    public $timestamps = false;

    protected $casts = [
        'recorded_at' => 'datetime',
        'kwh_total' => 'float',
        'power_kw' => 'float',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
