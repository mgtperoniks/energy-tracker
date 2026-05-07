<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PowerReadingDaily extends Model
{
    protected $table = 'power_readings_daily';
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
        'recorded_date' => 'date',
        'total_sample_count' => 'integer',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Hydrate metrics for "today" records if they are missing/stale
     */
    public function hydrateLive()
    {
        $today = now()->toDateString();
        
        // Jika data hari ini DAN (belum ada sample atau peak load nol)
        if ($this->recorded_date->toDateString() === $today && ($this->total_sample_count == 0 || $this->max_power_kw == 0)) {
            $liveData = PowerReadingRaw::where('device_id', $this->device_id)
                ->whereDate('recorded_at', $today)
                ->selectRaw('
                    MAX(power_kw) as live_max_power,
                    AVG(power_kw) as live_avg_power,
                    AVG(voltage) as live_avg_voltage,
                    AVG(current) as live_avg_current,
                    AVG(power_factor) as live_avg_pf,
                    COUNT(*) as live_samples
                ')
                ->first();

            if ($liveData && $liveData->live_samples > 0) {
                $this->max_power_kw = (float) $liveData->live_max_power;
                $this->avg_power_kw = (float) $liveData->live_avg_power;
                $this->avg_voltage  = (float) $liveData->live_avg_voltage;
                $this->avg_current  = (float) $liveData->live_avg_current;
                $this->avg_power_factor = (float) $liveData->live_avg_pf;
                $this->total_sample_count = (int) $liveData->live_samples;
            }
        }
        
        return $this;
    }
}
