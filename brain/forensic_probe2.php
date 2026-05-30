<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "========== PHASE 2 FORENSIC PROBE ==========\n\n";

// 1. Check cache table for withoutOverlapping mutex locks
echo "=== PROBE A: Cache table - withoutOverlapping mutex locks ===\n";
$locks = DB::select("SELECT `key`, expiration, CHAR_LENGTH(value) as val_len FROM cache WHERE `key` LIKE '%schedule%' OR `key` LIKE '%aggregate%' OR `key` LIKE '%without%'");
if (empty($locks)) {
    echo "  No schedule/aggregate mutex locks found in cache table\n";
} else {
    foreach ($locks as $r) {
        $exp = $r->expiration ? date('Y-m-d H:i:s', $r->expiration) : 'NULL';
        $expired = $r->expiration && $r->expiration < time() ? ' [EXPIRED]' : ' [ACTIVE]';
        echo "  key={$r->key} | expires={$exp}{$expired} | val_len={$r->val_len}\n";
    }
}

// 2. ALL cache entries
echo "\n=== PROBE B: All cache keys (for visibility) ===\n";
$allCache = DB::select("SELECT `key`, expiration FROM cache ORDER BY expiration DESC LIMIT 30");
foreach ($allCache as $r) {
    $exp = $r->expiration ? date('Y-m-d H:i:s', $r->expiration) : 'NULL';
    $expired = ($r->expiration && $r->expiration < time()) ? ' [EXPIRED]' : '';
    echo "  {$r->key} | expires={$exp}{$expired}\n";
}

// 3. Exact first crash timestamp
echo "\n=== PROBE C: Exact timeline from scheduler_runs ===\n";
$sched = DB::select("SELECT job_name, status, last_success_at, updated_at, message FROM scheduler_runs ORDER BY updated_at ASC");
foreach ($sched as $r) {
    echo "  [{$r->status}] {$r->job_name}\n";
    echo "    last_success_at = {$r->last_success_at}\n";
    echo "    updated_at      = {$r->updated_at}\n";
    echo "    message         = {$r->message}\n";
}

// 4. Verify DB schema - is kwh_total NOT NULL with no default?
echo "\n=== PROBE D: power_readings_daily schema - kwh_total column ===\n";
$cols = DB::select("SHOW COLUMNS FROM power_readings_daily WHERE Field IN ('kwh_total', 'kwh_usage', 'device_id', 'recorded_date')");
foreach ($cols as $c) {
    echo "  {$c->Field}: Type={$c->Type}, Null={$c->Null}, Default=" . ($c->Default ?? 'NULL') . "\n";
}

// 5. Simulate what firstOrCreate() is trying to INSERT
echo "\n=== PROBE E: Simulate the failing INSERT ===\n";
echo "  AggregateHourlyReadings line 93 calls:\n";
echo "  PowerReadingDaily::firstOrCreate([\n";
echo "    'device_id'    => \$agg->device_id,\n";
echo "    'recorded_date'=> \$targetTime->toDateString()\n";
echo "  ], [\n";
echo "    'kwh_total'    => 0,\n";
echo "    'kwh_usage'    => 0,\n";
echo "    'total_sample_count' => 0,\n";
echo "    'data_source'  => 'live'\n";
echo "  ]);\n";
echo "\n";
echo "  The 2nd arg (defaults) are ONLY used on CREATE.\n";
echo "  The 1st arg (search criteria) is used for the initial SELECT.\n";
echo "  If not found, it tries INSERT with ONLY the 1st arg fields = device_id + recorded_date.\n";
echo "  -> kwh_total is NOT NULL with NO DEFAULT -> MySQL rejects the INSERT with error 1364.\n";

// 6. Verify when kwh_total first had no default
echo "\n=== PROBE F: Check if energy_cost column has default ===\n";
$energyCostCol = DB::select("SHOW COLUMNS FROM power_readings_daily WHERE Field = 'energy_cost'");
foreach ($energyCostCol as $c) {
    echo "  energy_cost: Type={$c->Type}, Null={$c->Null}, Default=" . ($c->Default ?? 'NULL') . "\n";
}

// 7. Count how many times the job has been manually triggered vs crashed
echo "\n=== PROBE G: Verify manual execution would work for specific window ===\n";
$testWindow = '2026-05-13 00:00:00';
$testEnd    = '2026-05-13 00:59:59';
$rawInWindow = DB::select("SELECT device_id, COUNT(*) as cnt FROM power_readings_raw WHERE recorded_at BETWEEN ? AND ? GROUP BY device_id", [$testWindow, $testEnd]);
echo "  Raw rows in first missing window (2026-05-13 00:00 - 00:59):\n";
foreach ($rawInWindow as $r) {
    echo "    device_id={$r->device_id}: {$r->cnt} rows\n";
}

// 8. Determine what happens to the throw $e in AggregateHourlyReadings
echo "\n=== PROBE H: Exception propagation analysis ===\n";
echo "  AggregateHourlyReadings::handle() line 112: throw \$e;\n";
echo "  This means: exception bubbles up to the scheduler.\n";
echo "  withoutOverlapping() mutex: RELEASED on failure (Laravel releases it in finally block).\n";
echo "  SchedulerRun::log() line 110: logs failure BEFORE throw.\n";
$failedRuns = DB::select("SELECT job_name, status, message FROM scheduler_runs WHERE status = 'failed'");
echo "  Failed runs in scheduler_runs: " . count($failedRuns) . "\n";
foreach ($failedRuns as $r) {
    echo "    [{$r->status}] {$r->job_name}: {$r->message}\n";
}

echo "\n========== END OF PHASE 2 PROBE ==========\n";
