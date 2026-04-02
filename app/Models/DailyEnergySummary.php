<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyEnergySummary extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'kwh_usage' => 'float',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
