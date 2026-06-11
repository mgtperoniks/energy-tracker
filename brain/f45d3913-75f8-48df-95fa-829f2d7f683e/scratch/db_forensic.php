<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Count records
$total = DB::table('operational_event_tags')->count();
$active = DB::table('operational_event_tags')->whereNull('deleted_at')->count();
$deleted = DB::table('operational_event_tags')->whereNotNull('deleted_at')->count();
echo "=== TABLE: operational_event_tags ===" . PHP_EOL;
echo "Total (incl. deleted): $total" . PHP_EOL;
echo "Active: $active" . PHP_EOL;
echo "Soft-deleted: $deleted" . PHP_EOL;

// 2. Date range
$first = DB::table('operational_event_tags')->orderBy('event_time','asc')->first();
$last = DB::table('operational_event_tags')->orderBy('event_time','desc')->first();
echo "Earliest event_time: " . ($first ? $first->event_time : 'none') . PHP_EOL;
echo "Latest event_time: " . ($last ? $last->event_time : 'none') . PHP_EOL;

// 3. Per device breakdown
echo PHP_EOL . "=== PER DEVICE COUNT ===" . PHP_EOL;
$perDevice = DB::table('operational_event_tags')
    ->selectRaw('device_id, COUNT(*) as cnt, MIN(event_time) as earliest, MAX(event_time) as latest')
    ->whereNull('deleted_at')
    ->groupBy('device_id')
    ->get();
foreach ($perDevice as $row) {
    echo "Device {$row->device_id}: {$row->cnt} tags | {$row->earliest} -> {$row->latest}" . PHP_EOL;
}

// 4. Event type distribution
echo PHP_EOL . "=== EVENT TYPE DISTRIBUTION ===" . PHP_EOL;
$byType = DB::table('operational_event_tags')
    ->selectRaw('event_type, COUNT(*) as cnt')
    ->whereNull('deleted_at')
    ->groupBy('event_type')
    ->orderBy('event_type')
    ->get();
foreach ($byType as $row) {
    echo "  {$row->event_type}: {$row->cnt}" . PHP_EOL;
}

// 5. Sample of 15 May 2026 data
echo PHP_EOL . "=== SAMPLE DATA: 2026-05-15 ===" . PHP_EOL;
$may15 = DB::table('operational_event_tags')
    ->whereDate('event_time', '2026-05-15')
    ->orderBy('event_time','asc')
    ->get();
echo "Records on 2026-05-15: " . count($may15) . PHP_EOL;
foreach ($may15 as $row) {
    echo "  [{$row->event_time}] {$row->event_type} | device_id={$row->device_id} | del=" . ($row->deleted_at ?? 'NULL') . PHP_EOL;
}

// 6. Sample 5 most recent full records  
echo PHP_EOL . "=== LATEST 5 TAG RECORDS (FULL COLUMNS) ===" . PHP_EOL;
$sample = DB::table('operational_event_tags')
    ->whereNull('deleted_at')
    ->orderBy('event_time','desc')
    ->limit(5)
    ->get();
foreach ($sample as $row) {
    echo print_r((array)$row, true) . PHP_EOL;
}

// 7. tagging_audit_logs count
echo "=== TABLE: tagging_audit_logs ===" . PHP_EOL;
$auditTotal = DB::table('tagging_audit_logs')->count();
echo "Total audit records: $auditTotal" . PHP_EOL;

// 8. Check verification_status distribution
echo PHP_EOL . "=== VERIFICATION STATUS DISTRIBUTION ===" . PHP_EOL;
$byVerif = DB::table('operational_event_tags')
    ->selectRaw('verification_status, COUNT(*) as cnt')
    ->whereNull('deleted_at')
    ->groupBy('verification_status')
    ->get();
foreach ($byVerif as $row) {
    echo "  {$row->verification_status}: {$row->cnt}" . PHP_EOL;
}

// 9. Check COLUMNS of operational_event_tags
echo PHP_EOL . "=== ACTUAL COLUMNS IN TABLE ===" . PHP_EOL;
$columns = DB::select("SHOW COLUMNS FROM operational_event_tags");
foreach ($columns as $col) {
    echo "  {$col->Field} | {$col->Type} | Null={$col->Null} | Default={$col->Default}" . PHP_EOL;
}
