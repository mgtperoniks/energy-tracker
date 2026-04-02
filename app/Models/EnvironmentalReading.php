<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnvironmentalReading extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    public $timestamps = false;

    protected $casts = [
        'recorded_at' => 'datetime',
        'temperature' => 'float',
        'humidity' => 'float',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
