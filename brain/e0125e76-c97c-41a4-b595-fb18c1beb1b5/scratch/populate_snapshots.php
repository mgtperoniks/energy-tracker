<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PowerReadingDaily;
use App\Models\ElectricityTariff;

$records = PowerReadingDaily::all();
echo "Populating snapshots for " . $records->count() . " records." . PHP_EOL;

foreach ($records as $r) {
    $tariff = ElectricityTariff::where('effective_date', '<=', $r->recorded_date->toDateString())
        ->orderBy('effective_date', 'desc')
        ->first();
        
    if ($tariff) {
        $r->update([
            'tariff_id_snapshot' => $tariff->id,
            'tariff_rate_snapshot' => $tariff->rate_per_kwh
        ]);
        echo "Populated snapshot for Date: " . $r->recorded_date->toDateString() . " Rate: " . $tariff->rate_per_kwh . PHP_EOL;
    }
}
echo "Snapshot population complete." . PHP_EOL;
