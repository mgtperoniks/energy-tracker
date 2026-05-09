<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DatabaseBackup extends Command
{
    protected $signature = 'system:backup-db';
    protected $description = 'Backup the Energy Tracker database using mysqldump';

    public function handle()
    {
        $this->info('Starting database backup...');

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        $backupDir = storage_path('backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filename = "backup_{$database}_" . now()->format('Ymd_His') . ".sql";
        $filePath = "{$backupDir}/{$filename}";
        $compressedPath = "{$filePath}.gz";

        // 1. Execute mysqldump
        // Note: Password in command line is generally discouraged but common in simple scripts.
        // We use -p with no space if password exists.
        $passwordArg = $password ? "-p{$password}" : "";
        $command = "mysqldump -h {$host} -u {$username} {$passwordArg} {$database} > \"{$filePath}\"";

        try {
            // Using shell_exec or Process for better control
            $result = shell_exec($command);
            
            if (!File::exists($filePath) || File::size($filePath) === 0) {
                throw new \Exception("mysqldump failed or produced empty file.");
            }

            // 2. Gzip compression (using PHP's gzencode to avoid dependency on system gzip)
            $content = File::get($filePath);
            $compressedContent = gzencode($content, 9);
            File::put($compressedPath, $compressedContent);
            
            // Delete raw SQL file
            File::delete($filePath);

            $this->info("Backup completed: {$compressedPath}");

            // 3. Audit Log Entry (Success)
            AuditLog::create([
                'event_code' => 'SYSTEM_BACKUP_SUCCESS',
                'event_type' => 'SYSTEM_EVENT',
                'severity' => 'INFO',
                'title' => 'Database Backup Completed',
                'message' => "Automatic database backup saved: {$filename}.gz",
                'status' => 'resolved',
                'detected_at' => now(),
                'resolved_at' => now(),
                'source_layer' => 'system',
                'payload_json' => [
                    'filename' => $filename . '.gz',
                    'size_bytes' => File::size($compressedPath),
                    'path' => $compressedPath
                ]
            ]);

            // 4. Retention Logic (Last 14 days)
            $this->cleanupOldBackups($backupDir);

        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());

            AuditLog::create([
                'event_code' => 'SYSTEM_BACKUP_FAILED',
                'event_type' => 'SYSTEM_EVENT',
                'severity' => 'CRITICAL',
                'title' => 'Database Backup Failed',
                'message' => $e->getMessage(),
                'status' => 'open',
                'detected_at' => now(),
                'source_layer' => 'system',
                'payload_json' => ['error' => $e->getMessage()]
            ]);
        }
    }

    private function cleanupOldBackups($directory)
    {
        $files = File::files($directory);
        $retentionDays = 14;
        $now = now();

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp($file->getMTime());
            if ($lastModified->diffInDays($now) > $retentionDays) {
                File::delete($file->getRealPath());
                $this->info("Deleted old backup: " . $file->getFilename());
            }
        }
    }
}
