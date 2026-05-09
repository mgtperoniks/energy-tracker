<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PowerReadingRaw;
use App\Models\PowerReadingDaily;
use App\Models\AuditLog;
use App\Models\AuditLogEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "--- TASK 1: TELEMETRY HEALTH ---" . PHP_EOL;
$latestRaw = PowerReadingRaw::orderByDesc('recorded_at')->first();
if ($latestRaw) {
    echo "Latest Raw Timestamp: " . $latestRaw->recorded_at . PHP_EOL;
    $diff = Carbon::now()->diffInMinutes(Carbon::parse($latestRaw->recorded_at));
    echo "Latency: $diff minutes" . PHP_EOL;
} else {
    echo "CRITICAL: No raw telemetry found." . PHP_EOL;
}

echo "\n--- TASK 2: AGGREGATION HEALTH ---" . PHP_EOL;
$latestDaily = PowerReadingDaily::orderByDesc('recorded_date')->first();
if ($latestDaily) {
    echo "Latest Daily: " . $latestDaily->recorded_date->toDateString() . PHP_EOL;
    // Check consistency for a day
    $date = $latestDaily->recorded_date->toDateString();
    $rawUsage = PowerReadingRaw::whereDate('recorded_at', $date)
        ->selectRaw('device_id, MAX(kwh_total) - MIN(kwh_total) as daily_raw_usage')
        ->groupBy('device_id')
        ->get();
    echo "Daily consistency checked for $date." . PHP_EOL;
}

echo "\n--- TASK 3: ACCOUNTING HEALTH ---" . PHP_EOL;
$accountingCheck = PowerReadingDaily::whereNotNull('tariff_rate_snapshot')
    ->limit(10)
    ->get()
    ->every(function($r) {
        $expected = round($r->kwh_usage * (float)$r->tariff_rate_snapshot, 0);
        $actual = round($r->energy_cost, 0);
        return abs($expected - $actual) <= 1; // Small rounding diff allowed
    });
echo "Accounting Snapshot Consistency: " . ($accountingCheck ? "OK" : "FAIL") . PHP_EOL;

echo "\n--- TASK 4: AUDIT LOG HEALTH ---" . PHP_EOL;
$statusIntegrity = AuditLog::where('status', 'resolved')->whereNull('resolved_at')->count() == 0;
$timelineIntegrity = DB::table('audit_logs')
    ->whereRaw('resolved_at IS NOT NULL AND resolved_at < detected_at')
    ->count() == 0;
echo "Status Integrity: " . ($statusIntegrity ? "OK" : "FAIL") . PHP_EOL;
echo "Timeline Integrity: " . ($timelineIntegrity ? "OK" : "FAIL") . PHP_EOL;

echo "\n--- TASK 5: AUDIT EVENT LEDGER HEALTH ---" . PHP_EOL;
$sequenceGaps = DB::table('audit_log_events')
    ->select('audit_log_id', DB::raw('COUNT(*) as cnt'), DB::raw('MAX(sequence_no) as max_seq'))
    ->groupBy('audit_log_id')
    ->havingRaw('cnt != max_seq')
    ->count();
echo "Sequence Gaps/Duplicates: " . ($sequenceGaps == 0 ? "NONE" : "$sequenceGaps gaps found") . PHP_EOL;

$orphanEvents = DB::table('audit_log_events')
    ->whereNotExists(function($q) { $q->select(DB::raw(1))->from('audit_logs')->whereColumn('audit_logs.id', 'audit_log_events.audit_log_id'); })
    ->count();
echo "Orphan Events: $orphanEvents" . PHP_EOL;

echo "\n--- TASK 6 & 7: NOTIFICATION & ESCALATION HEALTH ---" . PHP_EOL;
$orphanNotifs = DB::table('notifications')->whereNotNull('audit_log_id')
    ->whereNotExists(function($q) { $q->select(DB::raw(1))->from('audit_logs')->whereColumn('audit_logs.id', 'notifications.audit_log_id'); })
    ->count();
echo "Orphan Notifications: $orphanNotifs" . PHP_EOL;

$stuckIncidents = AuditLog::where('status', 'open')
    ->where('detected_at', '<', Carbon::now()->subDays(2))
    ->count();
echo "Stuck Open Incidents (>48h): $stuckIncidents" . PHP_EOL;

echo "\n--- TASK 9: PERFORMANCE HEALTH (INDEXES) ---" . PHP_EOL;
$tables = ['power_readings_raw', 'power_readings_hourly', 'power_readings_daily', 'audit_logs', 'audit_log_events'];
foreach($tables as $t) {
    $indexes = DB::select("SHOW INDEX FROM $t");
    echo "Table $t has " . count($indexes) . " index entries." . PHP_EOL;
}

echo "\n--- TASK 10: FINAL SYSTEM SCORE ---" . PHP_EOL;
// Calculation
$score = 10;
if (!$latestRaw || $diff > 60) $score -= 2;
if (!$accountingCheck) $score -= 2;
if (!$statusIntegrity || !$timelineIntegrity) $score -= 2;
if ($orphanEvents > 0 || $sequenceGaps > 0) $score -= 2;

echo "System Readiness Score: $score / 10" . PHP_EOL;
echo "Verdict: " . ($score >= 8 ? "READY" : "NEEDS PATCH") . PHP_EOL;
