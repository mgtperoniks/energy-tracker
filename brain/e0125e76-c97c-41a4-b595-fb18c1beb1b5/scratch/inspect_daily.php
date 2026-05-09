<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PowerReadingDaily;
use Illuminate\Support\Facades\DB;

$dates = ['2026-05-06', '2026-05-07', '2026-05-08', '2026-05-09'];

echo str_pad("Date", 12) . str_pad("Device", 8) . str_pad("Usage", 10) . str_pad("Peak", 10) . str_pad("Volt", 8) . str_pad("PF", 8) . str_pad("Samples", 8) . PHP_EOL;
echo str_repeat("-", 70) . PHP_EOL;

$records = PowerReadingDaily::whereIn('recorded_date', $dates)
    ->orderBy('recorded_date')
    ->orderBy('device_id')
    ->get();

foreach ($records as $r) {
    echo str_pad($r->recorded_date->toDateString(), 12) . 
         str_pad($r->device_id, 8) . 
         str_pad($r->kwh_usage, 10) . 
         str_pad($r->max_power_kw, 10) . 
         str_pad($r->avg_voltage, 8) . 
         str_pad($r->avg_power_factor, 8) . 
         str_pad($r->total_sample_count, 8) . 
         PHP_EOL;
}
