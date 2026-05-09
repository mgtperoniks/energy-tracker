<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PowerReadingDaily;
use App\Models\ElectricityTariff;

$records = PowerReadingDaily::where('energy_cost', 0)->get();
echo "Found " . $records->count() . " records to update." . PHP_EOL;

foreach ($records as $r) {
    $rate = ElectricityTariff::getRateForDate($r->recorded_date->toDateString());
    if ($rate > 0) {
        $cost = $r->kwh_usage * $rate;
        $r->update(['energy_cost' => $cost]);
        echo "Updated Date: " . $r->recorded_date->toDateString() . " Cost: " . $cost . PHP_EOL;
    }
}
echo "Recalculation complete." . PHP_EOL;
