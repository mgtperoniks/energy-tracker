<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\MeterResetController;
use App\Http\Controllers\Api\ReportController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('dashboard')->group(function () {
    Route::get('/summary', [DashboardController::class, 'summary']);
});

Route::prefix('machines')->group(function () {
    Route::get('/', [MachineController::class, 'index']);
    Route::get('/{id}/energy', [MachineController::class, 'energyData']);

    // Meter reset endpoints
    // POST   /api/machines/{id}/reset  → log a manual reset (call BEFORE physically resetting)
    // GET    /api/machines/{id}/resets → view reset history
    Route::post('/{id}/reset',  [MeterResetController::class, 'store']);
    Route::get('/{id}/resets',  [MeterResetController::class, 'index']);
});

Route::prefix('reports')->group(function () {
    Route::get('/daily', [ReportController::class, 'daily']);
    Route::get('/monthly', [ReportController::class, 'monthly']);
});

Route::post('/readings', [App\Http\Controllers\Api\ReadingController::class, 'store'])
    ->middleware(['device.auth', 'throttle:ingestion']);

