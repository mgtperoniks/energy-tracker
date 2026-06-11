<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// ====================================================
// Q1: Distribusi semua event_type di seluruh database
// ====================================================
echo "=== Q1: DISTRIBUSI EVENT TYPE (SELURUH HISTORI) ===" . PHP_EOL;
$dist = DB::table('operational_event_tags')
    ->selectRaw('event_type, COUNT(*) as total')
    ->whereNull('deleted_at')
    ->groupBy('event_type')
    ->orderByRaw("FIELD(event_type, 'start','melting','idle','test','pour','end')")
    ->get();
foreach ($dist as $row) {
    echo "  {$row->event_type} = {$row->total}" . PHP_EOL;
}
echo "  TOTAL ACTIVE = " . DB::table('operational_event_tags')->whereNull('deleted_at')->count() . PHP_EOL;

// ====================================================
// Q2: Device 3 — 15 Mei 2026, START dan END
// ====================================================
echo PHP_EOL . "=== Q2: DEVICE 3 — 15 MEI 2026 — START & END TAGS ===" . PHP_EOL;
$d3StartEnd = DB::table('operational_event_tags')
    ->whereNull('deleted_at')
    ->where('device_id', 3)
    ->whereDate('event_time', '2026-05-15')
    ->whereIn('event_type', ['start', 'end'])
    ->orderBy('event_time', 'asc')
    ->get(['id', 'event_type', 'event_time', 'shift', 'notes']);

echo "Total START+END pada Device 3 / 15 Mei: " . $d3StartEnd->count() . PHP_EOL;
$d3Start = $d3StartEnd->where('event_type', 'start');
$d3End   = $d3StartEnd->where('event_type', 'end');
echo "  START tags: " . $d3Start->count() . PHP_EOL;
echo "  END tags  : " . $d3End->count() . PHP_EOL;
echo PHP_EOL . "Urutan kronologis:" . PHP_EOL;
foreach ($d3StartEnd as $row) {
    echo "  [{$row->event_time}] {$row->event_type} | id={$row->id} | shift={$row->shift} | notes=" . substr($row->notes ?? '(none)', 0, 40) . PHP_EOL;
}

// Juga tampilkan semua tag Device 3 / 15 Mei agar konteks lengkap
echo PHP_EOL . "Semua tag Device 3 / 15 Mei (urutan waktu):" . PHP_EOL;
$d3All = DB::table('operational_event_tags')
    ->whereNull('deleted_at')
    ->where('device_id', 3)
    ->whereDate('event_time', '2026-05-15')
    ->orderBy('event_time', 'asc')
    ->get(['event_type', 'event_time']);
foreach ($d3All as $row) {
    $marker = in_array($row->event_type, ['start','end']) ? ' <<<<' : '';
    echo "  [{$row->event_time}] {$row->event_type}{$marker}" . PHP_EOL;
}

// ====================================================
// Q3: Device 5 — 15 Mei 2026, START dan END
// ====================================================
echo PHP_EOL . "=== Q3: DEVICE 5 — 15 MEI 2026 — START & END TAGS ===" . PHP_EOL;
$d5StartEnd = DB::table('operational_event_tags')
    ->whereNull('deleted_at')
    ->where('device_id', 5)
    ->whereDate('event_time', '2026-05-15')
    ->whereIn('event_type', ['start', 'end'])
    ->orderBy('event_time', 'asc')
    ->get(['id', 'event_type', 'event_time', 'shift', 'notes']);

echo "Total START+END pada Device 5 / 15 Mei: " . $d5StartEnd->count() . PHP_EOL;
$d5Start = $d5StartEnd->where('event_type', 'start');
$d5End   = $d5StartEnd->where('event_type', 'end');
echo "  START tags: " . $d5Start->count() . PHP_EOL;
echo "  END tags  : " . $d5End->count() . PHP_EOL;
echo PHP_EOL . "Urutan kronologis:" . PHP_EOL;
foreach ($d5StartEnd as $row) {
    echo "  [{$row->event_time}] {$row->event_type} | id={$row->id} | shift={$row->shift}" . PHP_EOL;
}

echo PHP_EOL . "Semua tag Device 5 / 15 Mei (urutan waktu):" . PHP_EOL;
$d5All = DB::table('operational_event_tags')
    ->whereNull('deleted_at')
    ->where('device_id', 5)
    ->whereDate('event_time', '2026-05-15')
    ->orderBy('event_time', 'asc')
    ->get(['event_type', 'event_time']);
foreach ($d5All as $row) {
    $marker = in_array($row->event_type, ['start','end']) ? ' <<<<' : '';
    echo "  [{$row->event_time}] {$row->event_type}{$marker}" . PHP_EOL;
}

// ====================================================
// Q4: Session Coverage — START+END pairs per device per hari
// ====================================================
echo PHP_EOL . "=== Q4: SESSION COVERAGE ANALYSIS ===" . PHP_EOL;

// Ambil semua START tags, grouped by device + date
$startTags = DB::table('operational_event_tags')
    ->whereNull('deleted_at')
    ->where('event_type', 'start')
    ->selectRaw('device_id, DATE(event_time) as prod_date, COUNT(*) as cnt')
    ->groupBy('device_id', 'prod_date')
    ->orderBy('prod_date')
    ->get();

// Ambil semua END tags, grouped by device + date
$endTags = DB::table('operational_event_tags')
    ->whereNull('deleted_at')
    ->where('event_type', 'end')
    ->selectRaw('device_id, DATE(event_time) as prod_date, COUNT(*) as cnt')
    ->groupBy('device_id', 'prod_date')
    ->orderBy('prod_date')
    ->get();

// Build lookup maps
$startMap = [];
foreach ($startTags as $row) {
    $startMap[$row->device_id][$row->prod_date] = $row->cnt;
}
$endMap = [];
foreach ($endTags as $row) {
    $endMap[$row->device_id][$row->prod_date] = $row->cnt;
}

// Get all unique device+date combinations that have either start or end
$allKeys = [];
foreach ($startTags as $row) {
    $allKeys[$row->device_id . '_' . $row->prod_date] = ['device_id' => $row->device_id, 'date' => $row->prod_date];
}
foreach ($endTags as $row) {
    $allKeys[$row->device_id . '_' . $row->prod_date] = ['device_id' => $row->device_id, 'date' => $row->prod_date];
}

$bothCount   = 0;
$startOnly   = 0;
$endOnly     = 0;
$totalDays   = count($allKeys);

echo "Per device-date session analysis:" . PHP_EOL;
foreach ($allKeys as $key => $item) {
    $d = $item['device_id'];
    $dt = $item['date'];
    $hasStart = isset($startMap[$d][$dt]);
    $hasEnd   = isset($endMap[$d][$dt]);
    $startCnt = $startMap[$d][$dt] ?? 0;
    $endCnt   = $endMap[$d][$dt] ?? 0;

    if ($hasStart && $hasEnd) {
        $bothCount++;
        $label = "START+END";
    } elseif ($hasStart) {
        $startOnly++;
        $label = "START only";
    } else {
        $endOnly++;
        $label = "END only  ";
    }
    echo "  Device {$d} | {$dt} | {$label} | start_tags={$startCnt} end_tags={$endCnt}" . PHP_EOL;
}

echo PHP_EOL . "--- COVERAGE SUMMARY ---" . PHP_EOL;
echo "Total device-date sessions dengan START atau END: {$totalDays}" . PHP_EOL;
if ($totalDays > 0) {
    echo "  START + END lengkap : {$bothCount} (" . round($bothCount / $totalDays * 100, 1) . "%)" . PHP_EOL;
    echo "  START only          : {$startOnly} (" . round($startOnly / $totalDays * 100, 1) . "%)" . PHP_EOL;
    echo "  END only            : {$endOnly} (" . round($endOnly / $totalDays * 100, 1) . "%)" . PHP_EOL;
}

// Berapa hari produksi yang TIDAK memiliki START sama sekali?
echo PHP_EOL . "--- HARI PRODUKSI TANPA START TAG ---" . PHP_EOL;
$datesWithMelting = DB::table('operational_event_tags')
    ->whereNull('deleted_at')
    ->where('event_type', 'melting')
    ->selectRaw('device_id, DATE(event_time) as prod_date')
    ->groupBy('device_id', 'prod_date')
    ->orderBy('prod_date')
    ->get();

$noStartCount = 0;
foreach ($datesWithMelting as $row) {
    $hasStart = isset($startMap[$row->device_id][$row->prod_date]);
    if (!$hasStart) {
        $noStartCount++;
        echo "  Device {$row->device_id} | {$row->prod_date} | HAS MELTING but NO START tag" . PHP_EOL;
    }
}
echo "Total device-date dengan melting tapi tanpa START: {$noStartCount}" . PHP_EOL;
echo "Total device-date dengan melting: " . count($datesWithMelting) . PHP_EOL;
$noStartPct = count($datesWithMelting) > 0 ? round($noStartCount / count($datesWithMelting) * 100, 1) : 0;
echo "Coverage gap (hari produksi tanpa START): {$noStartPct}%" . PHP_EOL;

// ====================================================
// Q5: Rata-rata gap antara END dan START berikutnya (session gap)
// ====================================================
echo PHP_EOL . "=== Q5: GAP ANTARA END DAN START BERIKUTNYA (per device) ===" . PHP_EOL;
foreach ([3, 5] as $devId) {
    $ends = DB::table('operational_event_tags')
        ->whereNull('deleted_at')
        ->where('device_id', $devId)
        ->where('event_type', 'end')
        ->orderBy('event_time', 'asc')
        ->pluck('event_time');

    $starts = DB::table('operational_event_tags')
        ->whereNull('deleted_at')
        ->where('device_id', $devId)
        ->where('event_type', 'start')
        ->orderBy('event_time', 'asc')
        ->pluck('event_time');

    echo "Device {$devId}: " . count($ends) . " END tags, " . count($starts) . " START tags" . PHP_EOL;

    // Pair each END with the next START
    $gaps = [];
    foreach ($ends as $endTime) {
        foreach ($starts as $startTime) {
            if ($startTime > $endTime) {
                $gap = (strtotime($startTime) - strtotime($endTime)) / 60;
                $gaps[] = round($gap, 1);
                break;
            }
        }
    }
    if (!empty($gaps)) {
        echo "  Paired END→START gaps: " . count($gaps) . PHP_EOL;
        echo "  Min gap: " . min($gaps) . " menit" . PHP_EOL;
        echo "  Max gap: " . max($gaps) . " menit" . PHP_EOL;
        echo "  Avg gap: " . round(array_sum($gaps) / count($gaps), 1) . " menit" . PHP_EOL;
        echo "  Gap detail: " . implode(', ', array_slice($gaps, 0, 10)) . (count($gaps) > 10 ? '...' : '') . PHP_EOL;
    } else {
        echo "  Tidak cukup data untuk pairing END→START" . PHP_EOL;
    }
}
