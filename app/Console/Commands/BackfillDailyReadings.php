<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PowerReadingRaw;
use App\Models\PowerReadingDaily;
use App\Models\ElectricityTariff;
use App\Models\SchedulerRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BackfillDailyReadings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'energy:backfill-daily {--start-date= : Tanggal mulai backfill (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill missing daily power readings from last available date until today using raw data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting backfill for daily power readings...");
        $startTime = microtime(true);

        // 1. Detect last existing recorded_date in power_readings_daily
        $lastRecord = PowerReadingDaily::orderBy('recorded_date', 'desc')->first();
        
        $startDateStr = $this->option('start-date');
        if ($startDateStr) {
            $startDate = Carbon::parse($startDateStr);
        } elseif ($lastRecord) {
            $startDate = Carbon::parse($lastRecord->recorded_date)->addDay();
        } else {
            // Fallback: search for first raw record
            $firstRaw = PowerReadingRaw::orderBy('recorded_at', 'asc')->first();
            if ($firstRaw) {
                $startDate = Carbon::parse($firstRaw->recorded_at)->startOfDay();
            } else {
                $this->error("No data found in raw or daily tables to determine start date.");
                return 1;
            }
        }

        $today = Carbon::today();
        
        if ($startDate->greaterThanOrEqualTo($today)) {
            $this->info("Daily data is already up to date (Last date: " . $startDate->subDay()->toDateString() . ").");
            return 0;
        }

        $this->info("Backfilling from " . $startDate->toDateString() . " to " . $today->copy()->subDay()->toDateString());

        // 2. Loop from next day until today
        $currentDate = $startDate->copy();
        $processedCount = 0;

        while ($currentDate->lessThan($today)) {
            $dateStr = $currentDate->toDateString();
            $start = $currentDate->copy()->startOfDay()->toDateTimeString();
            $end = $currentDate->copy()->endOfDay()->toDateTimeString();

            $this->info("Processing date: {$dateStr}...");

            // 3. For each date: run aggregation logic using power_readings_raw
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
                    COUNT(*) as total_sample_count
                ')
                ->groupBy('device_id')
                ->get();

            if ($aggregates->isEmpty()) {
                $this->warn("No raw data found for {$dateStr}. Skipping.");
                $currentDate->addDay();
                continue;
            }

            $activeTariff = ElectricityTariff::where('effective_date', '<=', $dateStr)
                ->orderBy('effective_date', 'desc')
                ->first();
            
            $activeRate = $activeTariff ? (float) $activeTariff->rate_per_kwh : 0.0;
            $tariffId = $activeTariff ? $activeTariff->id : null;
            
            $upsertData = [];

            foreach ($aggregates as $agg) {
                $energyCost = $agg->kwh_usage * $activeRate;

                $upsertData[] = [
                    'device_id' => $agg->device_id,
                    'recorded_date' => $dateStr,
                    'kwh_total' => $agg->last_kwh_total,
                    'kwh_usage' => $agg->kwh_usage,
                    'energy_cost' => $energyCost,
                    'avg_power_kw' => $agg->avg_power_kw,
                    'min_power_kw' => $agg->min_power_kw,
                    'max_power_kw' => $agg->max_power_kw,
                    'avg_voltage' => $agg->avg_voltage,
                    'avg_current' => $agg->avg_current,
                    'avg_power_factor' => $agg->avg_power_factor,
                    'total_sample_count' => $agg->total_sample_count,
                    'data_source' => 'manual_backfill',
                    'tariff_id_snapshot' => $tariffId,
                    'tariff_rate_snapshot' => $activeRate,
                ];
            }

            // 4. Insert only if not exists (avoid duplicates) - using upsert as it handles this efficiently
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

            // 5. Log each processed date
            $this->info("Successfully processed {$dateStr}.");
            $processedCount++;
            $currentDate->addDay();
        }

        $duration = (int)((microtime(true) - $startTime) * 1000);
        SchedulerRun::log('energy:backfill-daily', true, $duration, "Processed {$processedCount} days starting from " . $startDate->toDateString());

        $this->info("Backfill completed. {$processedCount} days processed.");
        return 0;
    }
}
