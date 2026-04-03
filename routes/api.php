<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MachineController;
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
});

Route::prefix('reports')->group(function () {
    Route::get('/daily', [ReportController::class, 'daily']);
    Route::get('/monthly', [ReportController::class, 'monthly']);
});

Route::post('/readings', [App\Http\Controllers\Api\ReadingController::class, 'store']);

