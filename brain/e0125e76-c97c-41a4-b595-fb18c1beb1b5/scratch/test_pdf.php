<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PowerReadingDaily;

try {
    echo "Testing Operational PDF..." . PHP_EOL;
    $reports = PowerReadingDaily::with('device.machine')->limit(10)->get();
    foreach ($reports as $r) $r->hydrateLive();
    $startDate = '2026-05-01';
    $endDate = '2026-05-09';
    $deviceName = 'Test Device';
    
    $pdf = Pdf::loadView('exports.operational_pdf', compact('reports', 'startDate', 'endDate', 'deviceName'));
    $pdf->output();
    echo "Operational PDF OK" . PHP_EOL;

} catch (\Throwable $e) {
    echo "Operational PDF FAILED: " . $e->getMessage() . PHP_EOL;
    echo "Line: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}

try {
    echo "\nTesting Accounting PDF..." . PHP_EOL;
    $reports = PowerReadingDaily::with('device.machine')->limit(10)->get();
    foreach ($reports as $r) $r->hydrateLive();
    $totalCost = $reports->sum('energy_cost');
    $startDate = '2026-05-01';
    $endDate = '2026-05-09';
    $deviceName = 'Test Device';
    
    $pdf = Pdf::loadView('exports.accounting_pdf', compact('reports', 'startDate', 'endDate', 'deviceName', 'totalCost'));
    $pdf->output();
    echo "Accounting PDF OK" . PHP_EOL;

} catch (\Throwable $e) {
    echo "Accounting PDF FAILED: " . $e->getMessage() . PHP_EOL;
    echo "Line: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}
