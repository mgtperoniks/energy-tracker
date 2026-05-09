<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $reading = new App\Models\PowerReadingRaw;
    $reading->device_id = 3;
    $reading->recorded_at = now();
    $reading->kwh_total = 9999.999;
    $reading->meter_kwh_raw = 0;
    $reading->is_offline = false;
    $reading->save();
    echo "Insert Success: " . $reading->id . PHP_EOL;
    $reading->delete(); // Clean up test record
} catch (\Exception $e) {
    echo "Insert Failed: " . $e->getMessage() . PHP_EOL;
}
