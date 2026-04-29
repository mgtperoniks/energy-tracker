<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PruneRawReadings extends Command
{
    protected $signature = 'prune:raw-readings {--dry-run : Count rows to be deleted without executing deletion}';
    protected $description = 'Prune power_readings_raw older than 365 days using deterministic ID batching';

    public function handle()
    {
        $cutoffDate = Carbon::now()->subDays(365);
        $batchSize = 5000;
        
        if ($this->option('dry-run')) {
            $this->info("Calculating raw readings older than {$cutoffDate->toDateString()}...");
            $count = DB::table('power_readings_raw')->where('recorded_at', '<', $cutoffDate)->count();
            $this->warn("DRY RUN: $count rows would be deleted.");
            return Command::SUCCESS;
        }

        $totalDeleted = 0;
        $this->info("Starting prune for raw readings older than {$cutoffDate->toDateString()}");

        do {
            $ids = DB::table('power_readings_raw')
                ->where('recorded_at', '<', $cutoffDate)
                ->orderBy('id')
                ->limit($batchSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted = DB::table('power_readings_raw')->whereIn('id', $ids)->delete();
            $totalDeleted += $deleted;
            
            $this->info("Deleted $deleted rows. Total so far: $totalDeleted");
            usleep(100000); 

        } while ($deleted > 0);

        $message = "Pruned raw readings older than {$cutoffDate->toDateString()}. Deleted: $totalDeleted rows.";
        $this->info($message);
        Log::info("DataRetention: " . $message);

        return Command::SUCCESS;
    }
}
