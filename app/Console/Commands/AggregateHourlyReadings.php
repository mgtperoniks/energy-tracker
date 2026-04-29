<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PowerReadingRaw;
use App\Models\PowerReadingHourly;
use App\Models\PollerLog;
use Carbon\Carbon;

class AggregateHourlyReadings extends Command
{
    protected $signature = 'energy:aggregate-hourly {--time= : Waktu acuan (Y-m-d H:00:00)}';
    protected $description = 'Agregasi power_readings_raw per jam menggunakan SQL Native';

    public function handle()
    {
        $startTime = microtime(true);
        try {
            $targetStr = $this->option('time') ?: now()->subHour()->startOfHour()->toDateTimeString();
            $targetTime = Carbon::parse($targetStr);
            
            $start = $targetTime->copy()->startOfHour()->toDateTimeString();
            $end = $targetTime->copy()->endOfHour()->toDateTimeString();
            
            $this->info("Aggregating hourly data for window: {$start} to {$end}");

            $aggregates = PowerReadingRaw::whereBetween('recorded_at', [$start, $end])
                ->selectRaw('
                    device_id,
                    MAX(kwh_total) as last_kwh_total,
                    GREATEST(MAX(kwh_total) - MIN(kwh_total), 0) as kwh_usage,
                    AVG(power_kw) as avg_power_kw,
                    MIN(power_kw) as min_power_kw,
                    MAX(power_kw) as max_power_kw,
                    AVG(voltage) as avg_voltage,
                    AVG(current) as avg_current,
                    AVG(power_factor) as avg_power_factor,
                    COUNT(meter_kwh_raw) as sample_count
                ')
                ->groupBy('device_id')
                ->get();

            if ($aggregates->isEmpty()) {
                $this->info("No data found for this window.");
                \App\Models\SchedulerRun::log('energy:aggregate-hourly', true, (int)((microtime(true) - $startTime) * 1000), 'No data found for window ' . $start);
                return;
            }

            $upsertData = [];

            foreach ($aggregates as $agg) {
                $upsertData[] = [
                    'device_id' => $agg->device_id,
                    'recorded_at' => $start,
                    'kwh_total' => $agg->last_kwh_total,
                    'kwh_usage' => $agg->kwh_usage,
                    'avg_power_kw' => $agg->avg_power_kw,
                    'min_power_kw' => $agg->min_power_kw,
                    'max_power_kw' => $agg->max_power_kw,
                    'avg_voltage' => $agg->avg_voltage,
                    'avg_current' => $agg->avg_current,
                    'avg_power_factor' => $agg->avg_power_factor,
                    'sample_count' => $agg->sample_count, // Simpan untuk Daily Completeness
                ];

                // --- PACKET LOSS AUDIT DENGAN DEDUP ---
                // Hanya log jika ada payload yang masuk, tapi tidak utuh (< 50)
                if ($agg->sample_count > 0 && $agg->sample_count < 50) {
                    PollerLog::firstOrCreate([
                        'device_id' => $agg->device_id,
                        'status' => PollerLog::STATUS_WARNING,
                        'event_at' => $start // Jadikan waktu awal window sebagai penanda unique event
                    ], [
                        'message' => "Packet loss detected in hourly aggregation (Window: {$start}). Only {$agg->sample_count}/60 valid samples received.",
                    ]);
                }
            }

            PowerReadingHourly::upsert(
                $upsertData,
                ['device_id', 'recorded_at'], 
                [
                    'kwh_total', 'kwh_usage', 
                    'avg_power_kw', 'min_power_kw', 'max_power_kw', 
                    'avg_voltage', 'avg_current', 'avg_power_factor', 'sample_count'
                ]
            );

            $duration = (int)((microtime(true) - $startTime) * 1000);
            \App\Models\SchedulerRun::log('energy:aggregate-hourly', true, $duration, 'Window: ' . $start);
            $this->info("Hourly aggregation completed successfully.");
            
        } catch (\Exception $e) {
            $duration = (int)((microtime(true) - $startTime) * 1000);
            \App\Models\SchedulerRun::log('energy:aggregate-hourly', false, $duration, $e->getMessage());
            $this->error("Error: " . $e->getMessage());
            throw $e;
        }
    }
}
