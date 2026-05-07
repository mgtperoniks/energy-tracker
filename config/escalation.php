<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Escalation Thresholds (Minutes)
    |--------------------------------------------------------------------------
    | Default minutes an incident remains 'open' before escalation triggers.
    */
    'thresholds' => [
        'WARNING'  => 30,
        'ERROR'    => 15,
        'CRITICAL' => 10,
    ],

    'channels' => [
        'email'    => \App\Services\Channels\EmailChannel::class,
        'telegram' => \App\Services\Channels\TelegramChannel::class,
        'dashboard'=> \App\Services\Channels\DashboardChannel::class,
    ]
];
