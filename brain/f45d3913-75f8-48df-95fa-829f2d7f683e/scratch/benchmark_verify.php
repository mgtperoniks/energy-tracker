<?php
/**
 * Benchmark Verification Script — CycleReconstructionService
 * Target: Device 3 = 14 CLOSED, Device 5 = 15 CLOSED (15 Mei 2026)
 */
require __DIR__ . '/../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\CycleReconstructionService;
use Carbon\Carbon;

$service = new CycleReconstructionService();

$benchmarks = [
    ['device' => 3, 'expected_closed' => 14, 'label' => 'Device 3 — 15 Mei 2026'],
    ['device' => 5, 'expected_closed' => 15, 'label' => 'Device 5 — 15 Mei 2026'],
];

$allPassed = true;

foreach ($benchmarks as $bm) {
    $start = Carbon::create(2026, 5, 15, 0, 0, 0, 'Asia/Jakarta');
    $end   = Carbon::create(2026, 5, 15, 23, 59, 59, 'Asia/Jakarta');

    echo "═══════════════════════════════════════════════════" . PHP_EOL;
    echo "BENCHMARK: {$bm['label']}" . PHP_EOL;
    echo "Window : {$start} → {$end}" . PHP_EOL;
    echo "───────────────────────────────────────────────────" . PHP_EOL;

    $cycles = $service->reconstruct($bm['device'], $start, $end);
    $kpi    = $service->buildKpiSummary($cycles);

    // Status breakdown
    $byStatus = [];
    foreach ($cycles as $c) {
        $byStatus[$c['status']] = ($byStatus[$c['status']] ?? 0) + 1;
    }
    ksort($byStatus);

    echo "Total cycles reconstructed : " . count($cycles) . PHP_EOL;
    foreach ($byStatus as $status => $cnt) {
        echo "  {$status}: {$cnt}" . PHP_EOL;
    }

    // KPI summary
    echo PHP_EOL . "KPI SUMMARY:" . PHP_EOL;
    echo "  Closed cycles   : {$kpi['closed_cycles']}" . PHP_EOL;
    echo "  Open cycles     : {$kpi['open_cycles']}" . PHP_EOL;
    echo "  Incomplete      : {$kpi['incomplete_cycles']}" . PHP_EOL;
    echo "  Outlier cycles  : {$kpi['outlier_cycles']}" . PHP_EOL;
    echo "  Tag Integrity % : " . ($kpi['tag_integrity_pct'] ?? 'N/A') . "%" . PHP_EOL;
    echo "  Total kWh       : {$kpi['total_kwh']}" . PHP_EOL;
    echo "  Total Cost (Rp) : " . number_format($kpi['total_cost'], 0, ',', '.') . PHP_EOL;
    echo "  Avg kWh/cycle   : {$kpi['avg_kwh_per_cycle']}" . PHP_EOL;
    echo "  Avg duration    : {$kpi['avg_duration_human']}" . PHP_EOL;

    if ($kpi['fastest_cycle']) {
        echo "  Fastest cycle   : {$kpi['fastest_cycle']['minutes']} min (Cycle #{$kpi['fastest_cycle']['cycle_number']})" . PHP_EOL;
    }
    if ($kpi['slowest_cycle']) {
        echo "  Slowest cycle   : {$kpi['slowest_cycle']['minutes']} min (Cycle #{$kpi['slowest_cycle']['cycle_number']})" . PHP_EOL;
    }
    if ($kpi['outlier_threshold_minutes']) {
        echo "  Outlier threshold: {$kpi['outlier_threshold_minutes']} min" . PHP_EOL;
    }

    // Cycle detail table
    echo PHP_EOL . "CYCLE DETAIL:" . PHP_EOL;
    echo sprintf("  %-4s %-20s %-20s %-10s %-10s %-10s %-10s %-12s %-10s\n",
        '#', 'Melting Start', 'Pouring Start', 'Cycle End', 'Total', 'Melt', 'Pour', 'kWh', 'Status');
    echo "  " . str_repeat('-', 120) . PHP_EOL;

    foreach ($cycles as $c) {
        $num       = $c['number'] ?? '—';
        $mStart    = $c['cycle_start']->format('m-d H:i:s');
        $pStart    = $c['pouring_start'] ? $c['pouring_start']->format('m-d H:i:s') : '—';
        $cEnd      = $c['cycle_end']->format('m-d H:i:s');
        $total     = $c['total_duration_human'];
        $melt      = $c['melting_duration_human'];
        $pour      = $c['pouring_duration_human'];
        $kwh       = $c['kwh'];
        $status    = $c['status'];
        echo sprintf("  %-4s %-20s %-20s %-10s %-10s %-10s %-10s %-12s %-10s\n",
            $num, $mStart, $pStart, $cEnd, $total, $melt, $pour, $kwh, $status);
    }

    // BENCHMARK VERDICT
    $actualClosed = $kpi['closed_cycles'];
    $expectedClosed = $bm['expected_closed'];
    $passed = ($actualClosed === $expectedClosed);

    echo PHP_EOL;
    if ($passed) {
        echo "✅ BENCHMARK PASSED: CLOSED = {$actualClosed} (expected {$expectedClosed})" . PHP_EOL;
    } else {
        echo "❌ BENCHMARK FAILED: CLOSED = {$actualClosed} (expected {$expectedClosed})" . PHP_EOL;
        $allPassed = false;

        // Debug: show beforeTag info by checking what resolveWindowStart would return
        echo PHP_EOL . "DEBUG — Investigating discrepancy..." . PHP_EOL;
        $beforeTag = \Illuminate\Support\Facades\DB::table('operational_event_tags')
            ->where('device_id', $bm['device'])
            ->where('event_time', '<', '2026-05-15 00:00:00')
            ->whereIn('event_type', ['melting', 'pour'])
            ->whereNull('deleted_at')
            ->orderBy('event_time', 'desc')
            ->first();
        if ($beforeTag) {
            echo "  Before-tag: [{$beforeTag->event_time}] {$beforeTag->event_type} (id={$beforeTag->id})" . PHP_EOL;
            if ($beforeTag->event_type === 'melting') {
                echo "  → beforeTag is MELTING → windowStart shifted back to {$beforeTag->event_time}" . PHP_EOL;
                echo "  → This adds 1 extra CLOSED cycle (cross-midnight from May 14)" . PHP_EOL;
            }
        } else {
            echo "  Before-tag: NULL (no tag before window start)" . PHP_EOL;
        }
    }
    echo PHP_EOL;
}

echo "═══════════════════════════════════════════════════" . PHP_EOL;
if ($allPassed) {
    echo "✅ ALL BENCHMARKS PASSED — Phase 1 complete. Ready for Phase 2." . PHP_EOL;
} else {
    echo "❌ BENCHMARK FAILED — STOP. Do not proceed to Phase 2." . PHP_EOL;
    echo "   Review debug output above before continuing." . PHP_EOL;
}
echo "═══════════════════════════════════════════════════" . PHP_EOL;
