<?php
// Forensic Probe Script - read only, no writes
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========== FORENSIC PROBE REPORT ==========\n";
echo "Server time (PHP now()): " . now() . "\n";
echo "Server time (Asia/Jakarta): " . now('Asia/Jakarta') . "\n";
echo "PHP default timezone: " . date_default_timezone_get() . "\n";
echo "\n";

// 1. RAW data availability
echo "=== STEP 1: power_readings_raw - rows per day (May 10+) ===\n";
$rows = DB::select('SELECT DATE(recorded_at) as day, COUNT(*) as cnt, MIN(recorded_at) as first_at, MAX(recorded_at) as last_at FROM power_readings_raw WHERE recorded_at >= "2026-05-10 00:00:00" GROUP BY DATE(recorded_at) ORDER BY day');
if (empty($rows)) {
    echo "  NO RAW DATA found after 2026-05-10\n";
} else {
    foreach($rows as $r) {
        echo "  {$r->day}: {$r->cnt} rows | {$r->first_at} → {$r->last_at}\n";
    }
}

// Most recent raw reading
echo "\n--- Most recent raw reading ---\n";
$latest = DB::select('SELECT device_id, recorded_at, kwh_total, power_kw FROM power_readings_raw ORDER BY recorded_at DESC LIMIT 5');
foreach($latest as $r) {
    echo "  device_id={$r->device_id} at={$r->recorded_at} kwh={$r->kwh_total} kw={$r->power_kw}\n";
}

// 2. HOURLY data availability
echo "\n=== STEP 2: power_readings_hourly - rows per day (May 10+) ===\n";
$rows = DB::select('SELECT DATE(recorded_at) as day, COUNT(*) as cnt, MIN(recorded_at) as first_at, MAX(recorded_at) as last_at FROM power_readings_hourly WHERE recorded_at >= "2026-05-10 00:00:00" GROUP BY DATE(recorded_at) ORDER BY day');
if (empty($rows)) {
    echo "  NO HOURLY DATA found after 2026-05-10\n";
} else {
    foreach($rows as $r) {
        echo "  {$r->day}: {$r->cnt} rows | {$r->first_at} → {$r->last_at}\n";
    }
}
$latestHourly = DB::select('SELECT device_id, recorded_at, kwh_usage FROM power_readings_hourly ORDER BY recorded_at DESC LIMIT 3');
echo "--- Most recent hourly rows ---\n";
foreach($latestHourly as $r) {
    echo "  device_id={$r->device_id} at={$r->recorded_at} usage={$r->kwh_usage}\n";
}

// 3. DAILY data availability
echo "\n=== STEP 3: power_readings_daily - ALL rows (May 10+) ===\n";
$rows = DB::select('SELECT device_id, recorded_date, kwh_usage, data_source, total_sample_count FROM power_readings_daily WHERE recorded_date >= "2026-05-10" ORDER BY recorded_date DESC');
if (empty($rows)) {
    echo "  NO DAILY DATA found after 2026-05-10\n";
} else {
    foreach($rows as $r) {
        echo "  device_id={$r->device_id} date={$r->recorded_date} usage={$r->kwh_usage} source={$r->data_source} samples={$r->total_sample_count}\n";
    }
}

// 4. SCHEDULER_RUNS state
echo "\n=== STEP 4: scheduler_runs status ===\n";
$sched = DB::select('SELECT job_name, status, last_success_at, last_duration_ms, message, updated_at FROM scheduler_runs ORDER BY updated_at DESC');
foreach($sched as $r) {
    echo "  [{$r->status}] {$r->job_name} | last_success={$r->last_success_at} | msg={$r->message}\n";
}

// 5. Date logic inspection
echo "\n=== STEP 5: Date Logic Inspection ===\n";
$todayJakarta   = now('Asia/Jakarta')->toDateString();
$todayUTC       = now('UTC')->toDateString();
$subDayJakarta  = now('Asia/Jakarta')->subDay()->toDateString();
$subDayUTC      = now('UTC')->subDay()->toDateString();
echo "  now('Asia/Jakarta')->toDateString()   = {$todayJakarta}\n";
echo "  now('UTC')->toDateString()            = {$todayUTC}\n";
echo "  now('Asia/Jakarta')->subDay()         = {$subDayJakarta}\n";
echo "  now('UTC')->subDay()                  = {$subDayUTC}\n";
echo "  subHour() for hourly job target       = " . now('Asia/Jakarta')->subHour()->startOfHour()->toDateTimeString() . "\n";

// 6. AggregateHourly target window RIGHT NOW
$targetStr = now('Asia/Jakarta')->subHour()->startOfHour()->toDateTimeString();
$targetTime = \Carbon\Carbon::parse($targetStr);
$start = $targetTime->copy()->startOfHour()->toDateTimeString();
$end   = $targetTime->copy()->endOfHour()->toDateTimeString();
echo "  Hourly job would target window: {$start} → {$end}\n";

// 7. AggregateDailyReadings target date RIGHT NOW
$dailyTarget = now('Asia/Jakarta')->subDay()->toDateString();
echo "  Daily job would target date: {$dailyTarget}\n";

// 8. Check if hydrateLive() would fire for today
echo "\n=== STEP 6: hydrateLive() condition check ===\n";
$today = now('Asia/Jakarta')->toDateString();
echo "  today (Jakarta) = {$today}\n";
$todayRows = DB::select('SELECT device_id, recorded_date, data_source FROM power_readings_daily WHERE recorded_date = ?', [$today]);
if (empty($todayRows)) {
    echo "  WARNING: NO stub row for today ({$today}) in power_readings_daily!\n";
    echo "  hydrateLive() CANNOT FIRE because there is no daily row for today\n";
} else {
    echo "  Today rows found:\n";
    foreach($todayRows as $r) {
        echo "    device_id={$r->device_id} date={$r->recorded_date} source={$r->data_source}\n";
    }
}

// 9. Check data_source ENUM constraint
echo "\n=== STEP 7: data_source ENUM constraint ===\n";
$enumInfo = DB::select("SHOW COLUMNS FROM power_readings_daily LIKE 'data_source'");
foreach($enumInfo as $col) {
    echo "  Column: {$col->Field}, Type: {$col->Type}, Default: {$col->Default}\n";
}

// 10. AggregateDailyReadings: what does it produce for a missing day?
echo "\n=== STEP 8: Check hourly data for 2026-05-13 (first missing day) ===\n";
$hourlyCheck = DB::select('SELECT COUNT(*) as cnt FROM power_readings_hourly WHERE recorded_at >= "2026-05-13 00:00:00" AND recorded_at < "2026-05-14 00:00:00"');
echo "  Hourly rows for 2026-05-13: {$hourlyCheck[0]->cnt}\n";

$rawCheck = DB::select('SELECT COUNT(*) as cnt FROM power_readings_raw WHERE recorded_at >= "2026-05-13 00:00:00" AND recorded_at < "2026-05-14 00:00:00"');
echo "  Raw rows for 2026-05-13: {$rawCheck[0]->cnt}\n";

echo "\n========== END OF FORENSIC PROBE ==========\n";
