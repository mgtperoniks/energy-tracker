<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OperationalReportExport;
use App\Exports\AccountingReportExport;
use App\Models\PowerReadingDaily;

function buildOperationalQuery($startDate, $endDate, $deviceId = null)
{
    $query = PowerReadingDaily::with('device.machine')
        ->whereBetween('recorded_date', [$startDate, $endDate]);

    if ($deviceId) {
        $query->where('device_id', $deviceId);
    }

    return $query->orderBy('recorded_date', 'desc');
}

try {
    echo "Processing Operational Export (Store)..." . PHP_EOL;
    $query = buildOperationalQuery('2026-05-01', '2026-05-09');
    Excel::store(new OperationalReportExport($query), 'test_op.xlsx', 'local');
    echo "Operational Export OK" . PHP_EOL;

} catch (\Throwable $e) {
    echo "Operational Export FAILED: " . $e->getMessage() . PHP_EOL;
    echo "Line: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    // echo $e->getTraceAsString() . PHP_EOL;
}

try {
    echo "\nProcessing Accounting Export (Store)..." . PHP_EOL;
    $query = PowerReadingDaily::with('device.machine')
        ->whereBetween('recorded_date', ['2026-05-01', '2026-05-09']);
    Excel::store(new AccountingReportExport($query), 'test_acc.xlsx', 'local');
    echo "Accounting Export OK" . PHP_EOL;

} catch (\Throwable $e) {
    echo "Accounting Export FAILED: " . $e->getMessage() . PHP_EOL;
    echo "Line: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    // echo $e->getTraceAsString() . PHP_EOL;
}
