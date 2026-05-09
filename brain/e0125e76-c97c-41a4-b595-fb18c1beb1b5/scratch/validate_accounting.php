<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PowerReadingDaily;
use App\Models\ElectricityTariff;

echo "--- TARIFF AUDIT ---" . PHP_EOL;
$tariffs = ElectricityTariff::all();
foreach ($tariffs as $t) {
    echo "ID: {$t->id}, Effective: {$t->effective_date->toDateString()}, Rate: {$t->rate_per_kwh} {$t->currency}, Active: " . ($t->is_active ? 'YES' : 'NO') . PHP_EOL;
}

echo "\n--- CALCULATION VERIFICATION ---" . PHP_EOL;
$record = PowerReadingDaily::latest('id')->first();
if ($record) {
    $record->hydrateLive();
    $expected = $record->kwh_usage * ElectricityTariff::getRateForDate($record->recorded_date->toDateString());
    echo "Record Date: " . $record->recorded_date->toDateString() . PHP_EOL;
    echo "Usage: " . $record->kwh_usage . " kWh" . PHP_EOL;
    echo "Current Cost in DB: " . $record->getOriginal('energy_cost') . PHP_EOL;
    echo "Hydrated Cost: " . $record->energy_cost . PHP_EOL;
    echo "Expected Cost (Calculation): " . $expected . PHP_EOL;
    echo "Match: " . (abs($record->energy_cost - $expected) < 0.01 ? 'YES' : 'NO') . PHP_EOL;
}
