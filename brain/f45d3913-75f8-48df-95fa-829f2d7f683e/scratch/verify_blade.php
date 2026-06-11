<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\CycleReconstructionService;
use App\Models\Device;
use Carbon\Carbon;

try {
    $service = new CycleReconstructionService();
    $devices = Device::with('machine')->orderBy('name')->get();
    $device  = Device::find(3);

    $start = Carbon::create(2026, 5, 15, 0, 0, 0, 'Asia/Jakarta');
    $end   = Carbon::create(2026, 5, 15, 23, 59, 59, 'Asia/Jakarta');

    $cycles = $service->reconstruct($device->id, $start, $end);
    $kpi    = $service->buildKpiSummary($cycles);

    $payload = [
        'device' => $device,
        'devices' => $devices,
        'cycles' => $cycles,
        'kpi' => $kpi,
        'isFiltered' => true,
        'deviceId' => $device->id,
        'startDatetime' => $start->format('Y-m-d\TH:i'),
        'endDatetime' => $end->format('Y-m-d\TH:i'),
        'errors' => new \Illuminate\Support\ViewErrorBag(),
    ];

    // Attempt to compile and render the view
    $html = view('analytics.historian', $payload)->render();
    echo "✅ Blade rendered successfully!" . PHP_EOL;
    echo "   HTML length: " . strlen($html) . " characters" . PHP_EOL;

    // Verify some expected strings inside HTML
    $expectedStrings = [
        'Production Historian',
        'Total Cycles',
        'Tag Integrity',
        'CLOSED',
        'OUTLIER',
        'INCOMPLETE',
        'Rp ',
        'kWh',
    ];

    foreach ($expectedStrings as $str) {
        if (str_contains($html, $str)) {
            echo "   [Match] Found string: '{$str}'" . PHP_EOL;
        } else {
            echo "   [Fail] String NOT found: '{$str}'" . PHP_EOL;
        }
    }

} catch (\Throwable $e) {
    echo "❌ Render failed: " . $e->getMessage() . PHP_EOL;
    echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
