<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pdo = DB::getPdo();

$start = '2026-05-13';
$end   = '2026-05-29';

echo str_pad("Date", 12) . str_pad("Raw", 10) . str_pad("Hourly", 10) . str_pad("Daily", 10) . PHP_EOL;
echo str_repeat("-", 42) . PHP_EOL;

$current = new DateTime($start);
$endDt   = new DateTime($end);

while ($current <= $endDt) {
    $d = $current->format('Y-m-d');

    $raw    = $pdo->query("SELECT COUNT(*) FROM power_readings_raw WHERE DATE(recorded_at) = '$d'")->fetchColumn();
    $hourly = $pdo->query("SELECT COUNT(*) FROM power_readings_hourly WHERE DATE(recorded_at) = '$d'")->fetchColumn();
    $daily  = $pdo->query("SELECT COUNT(*) FROM power_readings_daily WHERE recorded_date = '$d'")->fetchColumn();

    echo str_pad($d, 12) . str_pad($raw, 10) . str_pad($hourly, 10) . str_pad($daily, 10) . PHP_EOL;

    $current->modify('+1 day');
}

echo PHP_EOL;

// Check schema of scheduler_runs first
echo "=== scheduler_runs columns ===" . PHP_EOL;
$cols = $pdo->query("DESCRIBE scheduler_runs")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) { echo $c['Field'] . PHP_EOL; }

// Check last N records to understand structure
echo PHP_EOL . "=== scheduler_runs last 5 rows (raw) ===" . PHP_EOL;
$sample = $pdo->query("SELECT * FROM scheduler_runs ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($sample as $r) { echo json_encode($r) . PHP_EOL; }
