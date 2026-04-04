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
            $machine = \App\Models\Machine::with(['devices', 'latestReading', 'todaySummary', 'recentReadings'])->find($id);
        } else {
            $machine = \App\Models\Machine::with(['devices', 'latestReading', 'todaySummary', 'recentReadings'])->first();
        }

        // Fetch Power History (Kw) for the past 24 hours for chart
        $historyLabels = [];
        $historyValues = [];
        $todayConsumption = 0;
        if ($machine) {
            $deviceIds = $machine->devices->pluck('id');
            $history = \App\Models\PowerReading::whereIn('device_id', $deviceIds)
                        ->where('recorded_at', '>=', now()->subHours(24))
                        ->orderBy('recorded_at', 'asc')
                        ->get(['recorded_at', 'power_kw', 'kwh_total']);
            
            foreach ($history as $point) {
                $historyLabels[] = $point->recorded_at->format('H:i');
                $historyValues[] = $point->power_kw;
            }

            // Calculate today's consumption from kwh_total difference
            $todayReadings = \App\Models\PowerReading::whereIn('device_id', $deviceIds)
                        ->whereDate('recorded_at', today())
                        ->orderBy('recorded_at', 'asc')
                        ->get(['kwh_total', 'power_kw', 'recorded_at']);

            if ($todayReadings->count() >= 2) {
                $kwhDiff = $todayReadings->last()->kwh_total - $todayReadings->first()->kwh_total;
                if ($kwhDiff > 0) {
                    $todayConsumption = round($kwhDiff, 2);
                } else {
                    // Estimate from average power × hours elapsed
                    $avgKw = $todayReadings->avg('power_kw');
                    $hoursElapsed = $todayReadings->first()->recorded_at->diffInMinutes($todayReadings->last()->recorded_at) / 60;
                    $todayConsumption = round($avgKw * $hoursElapsed, 2);
                }
            }
        }

        return view('machine', compact('machine', 'historyLabels', 'historyValues', 'todayConsumption'));
    })->name('machines');

    Route::get('/environmental', function () {
        return view('environmental');
    })->name('environmental');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
