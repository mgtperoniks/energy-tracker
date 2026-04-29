<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Models\User::first();
if (!$user) { echo 'No user found'; exit; }
auth()->login($user);

$routes = [
    '/',
    '/monitoring/meters',
    '/analytics/operational',
    '/analytics/accounting',
    '/analytics/audit',
    '/admin/tariffs',
    '/admin/thresholds'
];

foreach($routes as $uri) {
    if ($uri === '/monitoring/meters') {
        $machine = \App\Models\Machine::first();
        if ($machine) $uri .= '/' . $machine->id;
    }
    $request = Illuminate\Http\Request::create($uri, 'GET');
    $response = $kernel->handle($request);
    
    echo "Route: $uri -> Status: " . $response->status() . "\n";
    if ($response->status() >= 500) {
        $content = $response->getContent();
        // Extract the error message from the Laravel exception page if possible
        if (preg_match('/<title[^>]*>(.*?)<\/title>/', $content, $matches)) {
            echo "   Error: " . trim($matches[1]) . "\n";
        }
        // Save the full content for debugging
        file_put_contents('error_' . md5($uri) . '.html', $content);
    }
}
