<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MachineDashboardController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    // 1. OVERVIEW
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 2. MONITORING
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/meters/{id?}', [\App\Http\Controllers\MachineDashboardController::class, 'show'])->name('meters');
        Route::get('/meters/{id}/export', [\App\Http\Controllers\MachineDashboardController::class, 'export'])->name('meters.export');
        Route::get('/environmental', [\App\Http\Controllers\EnvironmentalController::class, 'index'])->name('environmental');
        Route::get('/system-health', [\App\Http\Controllers\SystemHealthController::class, 'index'])->name('health');
    });

    // 3. ANALYTICS
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/operational', [\App\Http\Controllers\ReportController::class, 'operational'])->name('operational');
        Route::get('/operational/export', [\App\Http\Controllers\ReportController::class, 'exportOperational'])->name('operational.export');
        Route::get('/accounting', [\App\Http\Controllers\ReportController::class, 'accounting'])->name('accounting');
        Route::get('/audit', [\App\Http\Controllers\ReportController::class, 'audit'])->name('audit');
    });

    // 4. ASSETS
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/departments', [\App\Http\Controllers\AssetController::class, 'departments'])->name('departments');
        Route::get('/machines', [\App\Http\Controllers\AssetController::class, 'machines'])->name('machines');
        Route::get('/devices', [\App\Http\Controllers\AssetController::class, 'devices'])->name('devices');
        Route::get('/sensors', [\App\Http\Controllers\AssetController::class, 'sensors'])->name('sensors');
    });

    // 5. ADMINISTRATION
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/tariffs', [\App\Http\Controllers\AdminController::class, 'tariffs'])->name('tariffs');
        Route::post('/tariffs', [\App\Http\Controllers\AdminController::class, 'storeTariff'])->name('tariffs.store');
        Route::get('/thresholds', [\App\Http\Controllers\AdminController::class, 'thresholds'])->name('thresholds');
        Route::post('/thresholds', [\App\Http\Controllers\AdminController::class, 'updateThresholds'])->name('thresholds.update');
        Route::get('/device-config', [\App\Http\Controllers\AdminController::class, 'deviceConfig'])->name('device-config');
        Route::post('/device-config', [\App\Http\Controllers\AdminController::class, 'updateDeviceConfig'])->name('device-config.update');
        Route::get('/poller-logs', [\App\Http\Controllers\AdminController::class, 'pollerLogs'])->name('poller-logs');
        Route::get('/reset-history', [\App\Http\Controllers\AdminController::class, 'resetHistory'])->name('reset-history');
        Route::get('/deployment-health', [\App\Http\Controllers\AdminController::class, 'deploymentHealth'])->name('deployment-health');
    });

    // API Routes
    Route::get('/api/machines/{id}/readings', [\App\Http\Controllers\Api\MachineController::class, 'readings'])->name('api.machines.readings');
    Route::get('/api/charts/device', [\App\Http\Controllers\Api\ChartController::class, 'getDeviceChart'])->name('api.charts.device');
    Route::get('/api/charts/facility', [\App\Http\Controllers\Api\ChartController::class, 'getFacilityChart'])->name('api.charts.facility');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Backward Compatibility Redirects
Route::get('/machines/{id}', function ($id) {
    return redirect()->route('monitoring.meters', ['id' => $id]);
});
