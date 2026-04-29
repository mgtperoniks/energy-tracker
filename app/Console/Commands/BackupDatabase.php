<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\SchedulerRun;

class BackupDatabase extends Command
{
    protected $signature = 'system:backup-db';
    protected $description = 'Database backup using mysqldump and prune old backups';

    public function handle()
    {
        $startTime = microtime(true);
        $filename = "backup-" . Carbon::now()->format('Y-m-d-H-i-s') . ".sql";
        $directory = storage_path('app/backups');
        
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');

        // Note: In some environments, you might need --column-statistics=0 or other flags
        $command = "mysqldump -h {$dbHost} -u {$dbUser} " . ($dbPass ? "-p{$dbPass} " : "") . "{$dbName} > {$path}";
        
        $this->info("Starting backup: {$filename}");
        
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $duration = (int)((microtime(true) - $startTime) * 1000);
            SchedulerRun::log('system:backup-db', false, $duration, 'mysqldump failed with exit code ' . $returnVar);
            $this->error("Backup failed!");
            return 1;
        }

        // Verify file integrity
        if (!file_exists($path) || filesize($path) === 0) {
            $duration = (int)((microtime(true) - $startTime) * 1000);
            SchedulerRun::log('system:backup-db', false, $duration, 'Backup file missing or empty');
            $this->error("Backup file invalid!");
            return 1;
        }

        $this->info("Backup completed: " . round(filesize($path) / 1024 / 1024, 2) . " MB");

        // Prune old backups (> 30 days)
        $files = glob($directory . DIRECTORY_SEPARATOR . "*.sql");
        $prunedCount = 0;
        foreach ($files as $file) {
            if (filemtime($file) < Carbon::now()->subDays(30)->getTimestamp()) {
                unlink($file);
                $prunedCount++;
            }
        }

        $duration = (int)((microtime(true) - $startTime) * 1000);
        SchedulerRun::log('system:backup-db', true, $duration, "Success. Pruned {$prunedCount} old backups.");
        
        return 0;
    }
}
