<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('energy:aggregate-hourly')
    ->hourly()
    ->withoutOverlapping()
    ->onSuccess(function () {
        Illuminate\Support\Facades\Cache::forever('cron_aggregate_hourly', now());
        logger()->info('Aggregation success (hourly)');
    });
Schedule::command('energy:aggregate-daily')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->onSuccess(function () {
        Illuminate\Support\Facades\Cache::forever('cron_aggregate_daily', now());
        logger()->info('Aggregation success (daily)');
    });

// --- DATA RETENTION LIFECYCLE ---

// Prune data raw (> 365 hari)
Schedule::command('prune:raw-readings')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/prune_raw.log'))
    ->onSuccess(fn () => Illuminate\Support\Facades\Cache::forever('cron_prune_raw', now()));

// Prune data hourly (> 1825 hari)
Schedule::command('prune:hourly-readings')
    ->dailyAt('02:20')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/prune_hourly.log'))
    ->onSuccess(fn () => Illuminate\Support\Facades\Cache::forever('cron_prune_hourly', now()));

// --- SYSTEM HEALTH & ANOMALY DETECTORS ---

// Deteksi Operational Anomaly (Low Voltage Operation)
Schedule::command('health:detect-low-voltage')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onSuccess(fn () => \Illuminate\Support\Facades\Cache::forever('cron_detect_low_voltage', now()));

// Deteksi Konsumsi Daya Berlebih saat Pabrik Libur/Tutup (Luar Jam Operasional)
Schedule::command('health:detect-idle-consumption')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onSuccess(fn () => \Illuminate\Support\Facades\Cache::forever('cron_detect_idle_consumption', now()));

// --- ADMINISTRATION JOBS ---

// Sinkronisasi status tarif listrik (UI Helper)
Schedule::call(function () {
    app(\App\Services\TariffService::class)->syncActiveTariffHelper();
})->dailyAt('00:01')->name('sync-tariffs');

// --- BACKUP JOBS ---

// Backup harian DB
Schedule::command('system:backup-db')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->onSuccess(fn () => \Illuminate\Support\Facades\Cache::forever('cron_backup_db', now()));
