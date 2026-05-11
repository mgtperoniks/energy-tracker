<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalEventTag extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'event_time' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function tagger()
    {
        return $this->belongsTo(User::class, 'tagged_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
