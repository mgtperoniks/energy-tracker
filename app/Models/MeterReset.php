<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeterReset extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'kwh_at_reset' => 'float',
        'reset_at'     => 'datetime',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
