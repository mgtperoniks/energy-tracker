<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PowerReadingRaw;
use App\Models\PowerReadingHourly;
use App\Models\PowerReadingDaily;
use App\Models\SchedulerRun;
use Carbon\Carbon;

class DiagnoseEnergy extends Command
{
    protected $signature   = 'energy:diagnose';
    protected $description = 'Read-only diagnostics for the Energy Tracker data pipeline';

    // Staleness thresholds (in minutes)
    const RAW_STALE_MINUTES    = 20;   // raw arrives every ~10 min
    const HOURLY_STALE_MINUTES = 90;   // hourly job runs every 60 min
    const DAILY_STALE_HOURS    = 26;   // daily job runs at 00:05 each day

    public function handle(): int
    {
        $now = now('Asia/Jakarta');

        $this->line('');
        $this->line('  <fg=cyan;options=bold>╔══════════════════════════════════════════════╗</>');
        $this->line('  <fg=cyan;options=bold>║   ENERGY TRACKER — PIPELINE DIAGNOSTICS      ║</>');
        $this->line('  <fg=cyan;options=bold>╚══════════════════════════════════════════════╝</>');
        $this->line('');
        $this->line("  Server Time : <fg=yellow>{$now->format('Y-m-d H:i:s')} WIB</>");
        $this->line("  Database    : <fg=yellow>" . config('database.connections.mysql.database') . " @ " . config('database.connections.mysql.host') . "</>");
        $this->line('');

        // ── Layer 1: RAW telemetry ──────────────────────────────────────────
        $lastRaw = PowerReadingRaw::orderByDesc('recorded_at')->first(['device_id', 'recorded_at']);

        if (!$lastRaw) {
            $rawStatus    = 'CRITICAL';
            $rawAge       = '∞';
            $rawTimestamp = 'NO DATA';
        } else {
            $rawAgeMin    = (int) Carbon::parse($lastRaw->recorded_at)->diffInMinutes($now);
            $rawAge       = $this->humanAge($rawAgeMin);
            $rawTimestamp = $lastRaw->recorded_at . " (device #{$lastRaw->device_id})";
            $rawStatus    = $rawAgeMin > self::RAW_STALE_MINUTES ? 'STALE' : 'OK';
        }

        // ── Layer 2: HOURLY aggregation ─────────────────────────────────────
        $lastHourly = PowerReadingHourly::orderByDesc('recorded_at')->first(['device_id', 'recorded_at']);
        $hourlyRun  = SchedulerRun::where('job_name', 'energy:aggregate-hourly')->first();

        if (!$lastHourly) {
            $hourlyStatus    = 'CRITICAL';
            $hourlyAge       = '∞';
            $hourlyTimestamp = 'NO DATA';
        } else {
            $hourlyAgeMin    = (int) Carbon::parse($lastHourly->recorded_at)->diffInMinutes($now);
            $hourlyAge       = $this->humanAge($hourlyAgeMin);
            $hourlyTimestamp = $lastHourly->recorded_at;
            $hourlyStatus    = $hourlyAgeMin > self::HOURLY_STALE_MINUTES ? 'STALE' : 'OK';
        }

        // ── Layer 3: DAILY aggregation ──────────────────────────────────────
        $lastDaily = PowerReadingDaily::orderByDesc('recorded_date')->first(['device_id', 'recorded_date', 'data_source']);
        $dailyRun  = SchedulerRun::where('job_name', 'energy:aggregate-daily')->first();

        if (!$lastDaily) {
            $dailyStatus    = 'CRITICAL';
            $dailyAge       = '∞';
            $dailyTimestamp = 'NO DATA';
        } else {
            $dailyAgeHours   = (int) Carbon::parse($lastDaily->recorded_date)->diffInHours($now);
            $dailyAge        = $this->humanAge($dailyAgeHours * 60);
            $dailyTimestamp  = $lastDaily->recorded_date . " (source: {$lastDaily->data_source})";
            $dailyStatus     = $dailyAgeHours > self::DAILY_STALE_HOURS ? 'STALE' : 'OK';
        }

        // ── Scheduler job health ────────────────────────────────────────────
        $schedHourlyAge  = $hourlyRun
            ? (int) Carbon::parse($hourlyRun->updated_at)->diffInMinutes($now)
            : null;
        $schedDailyAge   = $dailyRun
            ? (int) Carbon::parse($dailyRun->updated_at)->diffInMinutes($now)
            : null;

        // ── Missing data gap analysis ───────────────────────────────────────
        $latestDailyDate   = $lastDaily?->recorded_date
            ? Carbon::parse($lastDaily->recorded_date)
            : null;
        $dailyGapDays      = $latestDailyDate
            ? max(0, (int) $latestDailyDate->diffInDays($now->copy()->subDay()) - 1)
            : 999;

        $latestHourlyDate  = $lastHourly?->recorded_at
            ? Carbon::parse($lastHourly->recorded_at)
            : null;
        $hourlyGapHours    = $latestHourlyDate
            ? max(0, (int) $latestHourlyDate->diffInHours($now) - 1)
            : 999;

        // ── Overall status ──────────────────────────────────────────────────
        $overallStatus = 'OK';
        if ($rawStatus !== 'OK' || $hourlyStatus !== 'OK' || $dailyStatus !== 'OK') {
            $overallStatus = ($dailyGapDays > 2 || $hourlyGapHours > 48) ? 'CRITICAL' : 'WARNING';
        }

        // ── Render table ────────────────────────────────────────────────────
        $this->line('  <options=bold>DATA PIPELINE LAYERS</>');
        $this->line('  ' . str_repeat('─', 72));

        $this->printRow('RAW TELEMETRY',     $rawStatus,    $rawTimestamp,    $rawAge);
        $this->printRow('HOURLY AGGREGATION', $hourlyStatus, $hourlyTimestamp, $hourlyAge);
        $this->printRow('DAILY AGGREGATION',  $dailyStatus,  $dailyTimestamp,  $dailyAge);

        $this->line('');
        $this->line('  <options=bold>SCHEDULER JOB HEALTH</>');
        $this->line('  ' . str_repeat('─', 72));

        $this->printSchedulerRow(
            'energy:aggregate-hourly',
            $hourlyRun,
            $schedHourlyAge,
            self::HOURLY_STALE_MINUTES
        );
        $this->printSchedulerRow(
            'energy:aggregate-daily',
            $dailyRun,
            $schedDailyAge,
            self::DAILY_STALE_HOURS * 60
        );

        $this->line('');
        $this->line('  <options=bold>GAP ANALYSIS</>');
        $this->line('  ' . str_repeat('─', 72));
        $this->line(sprintf(
            '  %-28s %s',
            'Hourly gaps (approx):',
            $hourlyGapHours > 0
                ? "<fg=red>{$hourlyGapHours} hours missing</>"
                : '<fg=green>None detected</>'
        ));
        $this->line(sprintf(
            '  %-28s %s',
            'Daily gaps:',
            $dailyGapDays > 0
                ? "<fg=red>{$dailyGapDays} days missing</>"
                : '<fg=green>None detected</>'
        ));

        // ── Overall verdict ─────────────────────────────────────────────────
        $this->line('');
        $this->line('  ' . str_repeat('═', 72));
        $statusColour = match($overallStatus) {
            'OK'       => 'green',
            'WARNING'  => 'yellow',
            'CRITICAL' => 'red',
            default    => 'white',
        };
        $this->line("  OVERALL STATUS: <fg={$statusColour};options=bold>{$overallStatus}</>");
        $this->line('  ' . str_repeat('═', 72));

        // ── Recovery hints ──────────────────────────────────────────────────
        if ($overallStatus !== 'OK') {
            $this->line('');
            $this->line('  <fg=yellow;options=bold>RECOVERY HINTS:</>');

            if ($hourlyGapHours > 0 || $dailyGapDays > 0) {
                $this->line('  • Run hourly backfill loop from the last good window to now.');
                $this->line('  • Then run: php artisan energy:backfill-daily');
            }
            if ($schedHourlyAge === null || $schedHourlyAge > self::HOURLY_STALE_MINUTES) {
                $this->line('  • Verify Windows Task Scheduler: php artisan schedule:run (every 1 min).');
            }
        }

        $this->line('');

        return self::SUCCESS;
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function printRow(string $label, string $status, string $timestamp, string $age): void
    {
        $icon   = $this->statusIcon($status);
        $colour = $this->statusColour($status);
        $this->line(sprintf(
            '  %-22s %s  <fg=%s;options=bold>%-8s</>  Last: %s  (age: %s)',
            $label, $icon, $colour, $status, $timestamp, $age
        ));
    }

    private function printSchedulerRow(string $jobName, ?SchedulerRun $run, ?int $ageMinutes, int $thresholdMinutes): void
    {
        if (!$run) {
            $status    = 'NEVER RAN';
            $colour    = 'red';
            $icon      = '🔴';
            $detail    = 'No entry in scheduler_runs';
        } else {
            $stale     = $ageMinutes !== null && $ageMinutes > $thresholdMinutes;
            $status    = $stale ? 'STALE' : 'OK';
            $colour    = $this->statusColour($status);
            $icon      = $this->statusIcon($status);
            $age       = $ageMinutes !== null ? $this->humanAge($ageMinutes) : '?';
            $detail    = "last_success={$run->last_success_at}  age={$age}  msg={$run->message}";
        }
        $this->line(sprintf(
            '  %-30s %s  <fg=%s;options=bold>%-10s</>  %s',
            $jobName, $icon, $colour, $status, $detail ?? ''
        ));
    }

    private function statusIcon(string $status): string
    {
        return match($status) {
            'OK'      => '✅',
            'STALE'   => '⚠️ ',
            default   => '🔴',
        };
    }

    private function statusColour(string $status): string
    {
        return match($status) {
            'OK'    => 'green',
            'STALE' => 'yellow',
            default => 'red',
        };
    }

    private function humanAge(int $totalMinutes): string
    {
        if ($totalMinutes < 1)    return 'just now';
        if ($totalMinutes < 60)   return "{$totalMinutes} min";
        $hours = intdiv($totalMinutes, 60);
        $mins  = $totalMinutes % 60;
        if ($hours < 24)          return "{$hours}h {$mins}m";
        $days  = intdiv($hours, 24);
        $hrs   = $hours % 24;
        return "{$days} days {$hrs}h";
    }
}
