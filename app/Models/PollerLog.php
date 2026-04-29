<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollerLog extends Model
{
    const STATUS_ERROR = 'error';
    const STATUS_WARNING = 'warning';
    const STATUS_OFFLINE = 'offline';

    protected $table = 'poller_logs';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'event_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
