<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class RepairHistory extends Command
{
    protected $signature = 'energy:repair-history {start_date} {end_date}';
    protected $description = 'Loop through a date range and force aggregate hourly then daily';

    public function handle()
    {
        $start = Carbon::parse($this->argument('start_date'));
        $end = Carbon::parse($this->argument('end_date'));

        $this->info("Repairing history from {$start->toDateString()} to {$end->toDateString()}...");

        $current = $start->copy();
        while ($current <= $end) {
            $this->info("Processing Date: " . $current->toDateString());
            
            // For each hour in this day
            for ($h = 0; $h < 24; $h++) {
                $targetTime = $current->copy()->setHour($h)->startOfHour()->toDateTimeString();
                $this->comment("  Aggregating hour: {$targetTime}");
                Artisan::call('energy:aggregate-hourly', ['--time' => $targetTime]);
            }

            // Aggregate daily for this day
            $this->comment("  Aggregating daily for: " . $current->toDateString());
            Artisan::call('energy:aggregate-daily', ['--date' => $current->toDateString()]);

            $current->addDay();
        }

        $this->info("History repair complete!");
    }
}
