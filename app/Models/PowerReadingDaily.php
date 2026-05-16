<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        $today = now('Asia/Jakarta')->toDateString();
        $isToday = $this->recorded_date->toDateString() === $today;
        
        // Industrial Historian Mode: Always hydrate if it's today
        if ($isToday) {
            $cacheKey = "daily_live_{$this->device_id}_{$today}";
            $startTime = microtime(true);
            
            $liveData = Cache::remember($cacheKey, 300, function () use ($today) {
                return PowerReadingRaw::where('device_id', $this->device_id)
                    ->whereDate('recorded_at', $today)
                    ->selectRaw('
                        MAX(kwh_total) as live_kwh_total,
                        GREATEST(MAX(kwh_total) - MIN(kwh_total), 0) as live_kwh_usage,
                        MAX(power_kw) as live_max_power,
                        AVG(power_kw) as live_avg_power,
                        AVG(voltage) as live_avg_voltage,
                        AVG(current) as live_avg_current,
                        AVG(power_factor) as live_avg_pf,
                        COUNT(*) as live_samples
                    ')
                    ->first();
            });

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::debug("hydrateLive Execute", [
                'device_id' => $this->device_id,
                'date' => $today,
                'duration_ms' => $duration,
                'cache_hit' => $duration < 5 ? true : false
            ]);

            // Phase 5: Safe Hydration Guard - ONLY overwrite if we have real samples
            if ($liveData && $liveData->live_samples > 0) {
                $this->kwh_total = (float) $liveData->live_kwh_total;
                $this->kwh_usage = (float) $liveData->live_kwh_usage;
                $this->max_power_kw = (float) $liveData->live_max_power;
                $this->avg_power_kw = (float) $liveData->live_avg_power;
                $this->avg_voltage  = (float) $liveData->live_avg_voltage;
                $this->avg_current  = (float) $liveData->live_avg_current;
                $this->avg_power_factor = (float) $liveData->live_avg_pf;
                $this->total_sample_count = (int) $liveData->live_samples;
                $this->data_source = 'live';
            }

            // Always calculate energy cost for today
            if ($this->kwh_usage > 0) {
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
        $today = now('Asia/Jakarta')->toDateString();
        $isToday = $this->recorded_date->toDateString() === $today;
        $source = $this->data_source ?? 'live';
        
        if ($isToday) {
            return '<span class="px-1.5 py-0.5 rounded border bg-primary/10 text-primary border-primary/20 text-[9px] font-black tracking-wider">LIVE</span>';
        }

        if ($source === 'live' || $source === 'finalized') {
            return '<span class="px-1.5 py-0.5 rounded border bg-success/10 text-success border-success/20 text-[9px] font-black tracking-wider">FINALIZED</span>';
        }

        if ($source === 'manual_backfill' || $source === 'recovered') {
             return '<span class="px-1.5 py-0.5 rounded border bg-warning/10 text-warning border-warning/20 text-[9px] font-black tracking-wider">BACKFILLED</span>';
        }

        return '<span class="px-1.5 py-0.5 rounded border bg-error/10 text-error border-error/20 text-[9px] font-black tracking-wider">ESTIMATED</span>';
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
        return ElectricityTariff::getRateForDate($this->recorded_date->toDateString(), 'Asia/Jakarta');
    }
}
