<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function powerReadings()
    {
        return $this->hasMany(PowerReading::class);
    }

    public function environmentalReadings()
    {
        return $this->hasMany(EnvironmentalReading::class);
    }
}
