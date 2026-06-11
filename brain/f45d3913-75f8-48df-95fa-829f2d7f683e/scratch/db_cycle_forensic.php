<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Simulate cycle counting for 15 May 2026, per device
// A cycle = melting → pour pair
echo "=== CYCLE RECONSTRUCTION: 2026-05-15 ===" . PHP_EOL . PHP_EOL;

foreach ([3, 5] as $deviceId) {
    echo "--- Device $deviceId ---" . PHP_EOL;
    
    $tags = DB::table('operational_event_tags')
        ->whereNull('deleted_at')
        ->where('device_id', $deviceId)
        ->whereDate('event_time', '2026-05-15')
        ->orderBy('event_time', 'asc')
        ->get();
    
    $meltingCount = $tags->where('event_type', 'melting')->count();
    $pourCount = $tags->where('event_type', 'pour')->count();
    echo "Melting events: $meltingCount" . PHP_EOL;
    echo "Pour events: $pourCount" . PHP_EOL;
    
    // Build cycles: pair each melting with next pour
    $cycles = [];
    $sortedTags = $tags->values()->all();
    $totalTags = count($sortedTags);
    
    for ($i = 0; $i < $totalTags; $i++) {
        if ($sortedTags[$i]->event_type === 'melting') {
            $meltStart = $sortedTags[$i]->event_time;
            // Find next pour
            for ($j = $i + 1; $j < $totalTags; $j++) {
                if ($sortedTags[$j]->event_type === 'pour') {
                    $pourStart = $sortedTags[$j]->event_time;
                    $durationMin = (strtotime($pourStart) - strtotime($meltStart)) / 60;
                    
                    // Get kWh for this melting window
                    $firstRead = DB::table('power_readings_raw')
                        ->where('device_id', $deviceId)
                        ->where('recorded_at', '>=', $meltStart)
                        ->orderBy('recorded_at', 'asc')
                        ->first();
                    $lastRead = DB::table('power_readings_raw')
                        ->where('device_id', $deviceId)
                        ->where('recorded_at', '<=', $pourStart)
                        ->orderBy('recorded_at', 'desc')
                        ->first();
                    
                    $kwh = 0;
                    if ($firstRead && $lastRead) {
                        $kwh = max(0, $lastRead->kwh_total - $firstRead->kwh_total);
                    }
                    
                    $cycles[] = [
                        'melting_start' => $meltStart,
                        'pour_start'    => $pourStart,
                        'duration_min'  => round($durationMin, 1),
                        'kwh'           => round($kwh, 3),
                    ];
                    break;
                }
            }
        }
    }
    
    echo "Reconstructed melting cycles: " . count($cycles) . PHP_EOL;
    if (!empty($cycles)) {
        $totalKwh = array_sum(array_column($cycles, 'kwh'));
        $durations = array_column($cycles, 'duration_min');
        $fastest = min($durations);
        $slowest = max($durations);
        $totalDur = array_sum($durations);
        
        echo "Total kWh (melting only): " . round($totalKwh, 3) . PHP_EOL;
        echo "Total duration (min): $totalDur" . PHP_EOL;
        echo "Fastest cycle: $fastest min" . PHP_EOL;
        echo "Slowest cycle: $slowest min" . PHP_EOL;
        echo PHP_EOL;
        
        echo "Cycle detail:" . PHP_EOL;
        foreach ($cycles as $idx => $c) {
            echo sprintf("  #%d | %s -> %s | %s min | %.3f kWh\n",
                $idx + 1, $c['melting_start'], $c['pour_start'], $c['duration_min'], $c['kwh']);
        }
    }
    echo PHP_EOL;
}

// Also check power_readings_raw date range for 15 May
echo "=== POWER_READINGS_RAW: Coverage on 2026-05-15 ===" . PHP_EOL;
foreach ([3, 5] as $deviceId) {
    $rawCount = DB::table('power_readings_raw')
        ->where('device_id', $deviceId)
        ->whereDate('recorded_at', '2026-05-15')
        ->count();
    $firstRaw = DB::table('power_readings_raw')
        ->where('device_id', $deviceId)
        ->whereDate('recorded_at', '2026-05-15')
        ->orderBy('recorded_at','asc')->first();
    $lastRaw = DB::table('power_readings_raw')
        ->where('device_id', $deviceId)
        ->whereDate('recorded_at', '2026-05-15')
        ->orderBy('recorded_at','desc')->first();
    
    echo "Device $deviceId: $rawCount rows | " 
        . ($firstRaw ? $firstRaw->recorded_at : 'none') 
        . " -> " 
        . ($lastRaw ? $lastRaw->recorded_at : 'none')
        . PHP_EOL;
    if ($firstRaw) {
        echo "  First kwh_total={$firstRaw->kwh_total}, Last kwh_total=" . ($lastRaw ? $lastRaw->kwh_total : 'n/a') . PHP_EOL;
    }
}

// 15 May daily aggregate
echo PHP_EOL . "=== POWER_READINGS_DAILY: 2026-05-15 ===" . PHP_EOL;
$dailies = DB::table('power_readings_daily')
    ->whereIn('device_id', [3, 5])
    ->where('recorded_date', '2026-05-15')
    ->get();
foreach ($dailies as $d) {
    echo "Device {$d->device_id}: kwh_usage={$d->kwh_usage}, energy_cost={$d->energy_cost}, data_source={$d->data_source}" . PHP_EOL;
}
if (count($dailies) === 0) echo "No daily aggregates found for 2026-05-15" . PHP_EOL;
