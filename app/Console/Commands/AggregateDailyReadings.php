<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PowerReadingHourly;
use App\Models\PowerReadingDaily;
use Carbon\Carbon;

class AggregateDailyReadings extends Command
{
    protected $signature = 'energy:aggregate-daily {--date= : Waktu acuan (Y-m-d)}';
    protected $description = 'Agregasi power_readings_hourly ke daily menggunakan SQL Native';

    public function handle()
    {
        $startTime = microtime(true);
        try {
            $targetStr = $this->option('date') ?: now()->subDay()->toDateString();
            $targetDate = Carbon::parse($targetStr);
            
            $start = $targetDate->copy()->startOfDay()->toDateTimeString();
            $end = $targetDate->copy()->endOfDay()->toDateTimeString();

            $this->info("Aggregating daily data for window: {$start} to {$end}");

            $aggregates = PowerReadingHourly::whereBetween('recorded_at', [$start, $end])
                ->selectRaw('
                    device_id,
                    MAX(kwh_total) as last_kwh_total,
                    SUM(kwh_usage) as total_kwh_usage,
                    AVG(avg_power_kw) as daily_avg_power_kw,
                    MIN(min_power_kw) as daily_min_power_kw,
                    MAX(max_power_kw) as daily_max_power_kw,
                    AVG(avg_voltage) as daily_avg_voltage,
                    AVG(avg_current) as daily_avg_current,
                    AVG(avg_power_factor) as daily_avg_power_factor,
                    SUM(sample_count) as total_sample_count
                ')
                ->groupBy('device_id')
                ->get();

            if ($aggregates->isEmpty()) {
                $this->info("No hourly data found for this daily window.");
                \App\Models\SchedulerRun::log('energy:aggregate-daily', true, (int)((microtime(true) - $startTime) * 1000), 'No data found for date ' . $targetDate->toDateString());
                return;
            }

            // COST LAYER: Ambil rate aktif untuk tanggal target
            $activeTariff = \App\Models\ElectricityTariff::where('effective_date', '<=', $targetDate->toDateString())
                ->orderBy('effective_date', 'desc')
                ->first();
            
            $activeRate = $activeTariff ? (float) $activeTariff->rate_per_kwh : 0.0;
            $tariffId = $activeTariff ? $activeTariff->id : null;

            $upsertData = [];

            foreach ($aggregates as $agg) {
                $energyCost = $agg->total_kwh_usage * $activeRate;

                $upsertData[] = [
                    'device_id' => $agg->device_id,
                    'recorded_date' => $targetDate->toDateString(),
                    'kwh_total' => $agg->last_kwh_total,
                    'kwh_usage' => $agg->total_kwh_usage,
                    'energy_cost' => $energyCost,
                    'avg_power_kw' => $agg->daily_avg_power_kw,
                    'min_power_kw' => $agg->daily_min_power_kw,
                    'max_power_kw' => $agg->daily_max_power_kw,
                    'avg_voltage' => $agg->daily_avg_voltage,
                    'avg_current' => $agg->daily_avg_current,
                    'avg_power_factor' => $agg->daily_avg_power_factor,
                    'total_sample_count' => $agg->total_sample_count, // Simpan untuk audit bulanan
                    'data_source' => 'live',
                    'tariff_id_snapshot' => $tariffId,
                    'tariff_rate_snapshot' => $activeRate,
                ];
            }

            PowerReadingDaily::upsert(
                $upsertData,
                ['device_id', 'recorded_date'],
                [
                    'kwh_total', 'kwh_usage', 'energy_cost',
                    'avg_power_kw', 'min_power_kw', 'max_power_kw', 
                    'avg_voltage', 'avg_current', 'avg_power_factor', 'total_sample_count', 'data_source',
                    'tariff_id_snapshot', 'tariff_rate_snapshot'
                ]
            );

            $duration = (int)((microtime(true) - $startTime) * 1000);
            \App\Models\SchedulerRun::log('energy:aggregate-daily', true, $duration, 'Date: ' . $targetDate->toDateString());
            $this->info("Daily aggregation completed successfully.");
            
        } catch (\Exception $e) {
            $duration = (int)((microtime(true) - $startTime) * 1000);
            \App\Models\SchedulerRun::log('energy:aggregate-daily', false, $duration, $e->getMessage());
            $this->error("Error: " . $e->getMessage());
            throw $e;
        }
    }
}
