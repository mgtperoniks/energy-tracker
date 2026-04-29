<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;

class SmokeTestCommand extends Command
{
    protected $signature = 'smoke:test';
    protected $description = 'Smoke test all main routes';

    public function handle()
    {
        $user = \App\Models\User::first();
        if (!$user) {
            $this->error('No user found to authenticate.');
            return;
        }
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
                if ($machine) {
                    $uri .= '/' . $machine->id;
                } else {
                    $this->warn('No machine found to test /monitoring/meters/{id}');
                    continue;
                }
            }

            $request = Request::create($uri, 'GET');
            $response = app()->handle($request);
            
            $status = $response->status();
            $this->info("Route: $uri -> Status: " . $status);
            
            if ($status >= 500) {
                $this->error("ERROR on $uri");
                $content = $response->getContent();
                if (preg_match('/<title[^>]*>(.*?)<\/title>/s', $content, $matches)) {
                    $this->error("   Exception: " . trim($matches[1]));
                }
            }
        }
    }
}
