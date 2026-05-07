<?php

use App\Models\PowerReadingDaily;
use App\Models\PollerLog;
use Carbon\Carbon;

$deviceId = 3;
$rate = 0;

// May 6
PowerReadingDaily::updateOrCreate(
    ['device_id' => $deviceId, 'recorded_date' => '2026-05-06'],
    [
        'kwh_total' => 6353.396,
        'kwh_usage' => 1721.465,
        'energy_cost' => 1721.465 * $rate,
        'avg_power_kw' => 198.63,
        'min_power_kw' => 0,
        'max_power_kw' => 400,
        'total_sample_count' => 0,
    ]
);

// May 7
PowerReadingDaily::updateOrCreate(
    ['device_id' => $deviceId, 'recorded_date' => '2026-05-07'],
    [
        'kwh_total' => 8798.938,
        'kwh_usage' => 2445.542,
        'energy_cost' => 2445.542 * $rate,
        'avg_power_kw' => 198.82,
        'min_power_kw' => 0,
        'max_power_kw' => 400,
        'total_sample_count' => 0,
    ]
);

// Audit Log
PollerLog::create([
    'device_id' => $deviceId,
    'status' => 'warning',
    'message' => 'Telemetry ingestion outage recovered via cumulative meter continuity.',
    'event_at' => Carbon::now(),
]);

echo "Recovery complete\n";
