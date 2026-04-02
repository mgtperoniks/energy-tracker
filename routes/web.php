<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        $todayKwh = \App\Models\DailyEnergySummary::where('date', today()->toDateString())->sum('kwh_usage');
        $monthKwh = \App\Models\DailyEnergySummary::whereYear('date', today()->year)
                        ->whereMonth('date', today()->month)
                        ->sum('kwh_usage');
        
        // Sum of the most recent power_kw for each device
        $latestReadings = \App\Models\PowerReading::whereIn('id', function($query) {
            $query->selectRaw('max(id)')->from('power_readings')->groupBy('device_id');
        })->get();
        
        $currentKw = $latestReadings->sum('power_kw');

        // Detailed machine list for the dashboard table
        $machines = \App\Models\Machine::with(['latestReading', 'todaySummary'])->get();

        return view('dashboard', compact('todayKwh', 'monthKwh', 'currentKw', 'machines'));
    })->name('dashboard');

    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports');

    Route::get('/machines/{id?}', function ($id = null) {
        $machine = null;
        if ($id) {
            $machine = \App\Models\Machine::with('devices')->find($id);
        } else {
            $machine = \App\Models\Machine::with('devices')->first();
        }
        return view('machine', compact('machine'));
    })->name('machines');

    Route::get('/environmental', function () {
        return view('environmental');
    })->name('environmental');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
