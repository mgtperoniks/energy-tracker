<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ReadingController;

$request = Request::create('/api/readings', 'POST', [
    'slave_id' => 3,
    'kwh_total' => 5421.113,
    'power_kw' => 425.100,
    'voltage' => 470.20,
    'current' => 544.30,
    'power_factor' => 0.956
]);
$request->headers->set('X-Device-Token', 'i0d9CuA0HmPA5CYTlTP9FBw8z2g4JuvI4XDq17faIsZ5oB8HVFjnQxKhnloQ');

$controller = new ReadingController();
try {
    $response = $controller->store($request);
    echo "Response Status: " . $response->getStatusCode() . PHP_EOL;
    echo "Body: " . $response->getContent() . PHP_EOL;
} catch (\Exception $e) {
    echo "API Error: " . $e->getMessage() . PHP_EOL;
}
