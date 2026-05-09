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
        'data_source' => 'string',
        'tariff_id_snapshot' => 'integer',
        'tariff_rate_snapshot' => 'decimal:2',
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

            // Always calculate energy cost if usage exists for today
            if ($this->kwh_usage > 0) {
                // Use snapshot if exists, otherwise fallback to live lookup
                if ($this->tariff_rate_snapshot) {
                    $activeRate = (float) $this->tariff_rate_snapshot;
                } else {
                    $activeRate = ElectricityTariff::getRateForDate($today);
                }
                
                $this->energy_cost = (float) ($this->kwh_usage * $activeRate);
            }
        }
        
        return $this;
    }

    /**
     * Return tailwind-styled badge based on data source
     */
    public function getDataSourceBadgeAttribute()
    {
        $source = $this->data_source ?? 'live';
        $styles = [
            'live' => 'bg-success/10 text-success border-success/20',
            'manual_backfill' => 'bg-warning/10 text-warning border-warning/20',
            'recovered' => 'bg-info/10 text-info border-info/20',
            'estimated' => 'bg-error/10 text-error border-error/20',
        ];
        
        $labels = [
            'live' => 'LIVE',
            'manual_backfill' => 'BACKFILLED',
            'recovered' => 'RECOVERED',
            'estimated' => 'ESTIMATED',
        ];
        
        $class = $styles[$source] ?? $styles['live'];
        $label = $labels[$source] ?? $labels['live'];
        
        return "<span class=\"px-1.5 py-0.5 rounded border {$class} text-[9px] font-black tracking-wider\">{$label}</span>";
    }

    /**
     * Get the rate applied to this record (snapshot or live fallback)
     */
    public function getAppliedRateAttribute()
    {
        if ($this->tariff_rate_snapshot) {
            return (float) $this->tariff_rate_snapshot;
        }

        // Fallback for legacy or un-aggregated data
        return ElectricityTariff::getRateForDate($this->recorded_date->toDateString());
    }
}
