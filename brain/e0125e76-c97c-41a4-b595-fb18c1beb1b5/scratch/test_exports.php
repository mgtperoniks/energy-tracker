<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

try {
    $controller = new ReportController();
    $request = new Request([
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-09'
    ]);

    echo "Testing Operational Export..." . PHP_EOL;
    $controller->exportOperational($request);
    echo "Operational Export OK" . PHP_EOL;

} catch (\Exception $e) {
    echo "Operational Export FAILED: " . $e->getMessage() . PHP_EOL;
    echo "Line: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

try {
    $controller = new ReportController();
    $request = new Request([
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-09'
    ]);

    echo "\nTesting Accounting Export..." . PHP_EOL;
    $controller->exportAccounting($request);
    echo "Accounting Export OK" . PHP_EOL;

} catch (\Exception $e) {
    echo "Accounting Export FAILED: " . $e->getMessage() . PHP_EOL;
    echo "Line: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
