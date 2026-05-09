<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Exports\AccountingReportExport;
use App\Models\PowerReadingDaily;

$today = now()->toDateString();
$record = PowerReadingDaily::where('recorded_date', $today)->first();

if ($record) {
    echo "Found record for today: " . $record->id . PHP_EOL;
    echo "Initial energy_cost: " . $record->energy_cost . PHP_EOL;
    
    $export = new AccountingReportExport(PowerReadingDaily::query());
    $mapped = $export->map($record);
    
    echo "After mapping (hydration):" . PHP_EOL;
    echo "Usage: " . $mapped[3] . PHP_EOL;
    echo "Rate: " . $mapped[4] . PHP_EOL;
    echo "Total Cost: " . $mapped[5] . PHP_EOL;
} else {
    echo "No record for today to test hydration." . PHP_EOL;
}
