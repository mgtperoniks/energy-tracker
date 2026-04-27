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

        // Chart Data: Aggregate Power (kW) per hour for last 24 hours
        $chartReadings = \App\Models\PowerReading::where('recorded_at', '>=', now()->subHours(24))
            ->selectRaw('DATE_FORMAT(recorded_at, "%H:00") as hour, AVG(power_kw) as avg_kw')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $chartLabels = $chartReadings->pluck('hour');
        $chartValues = $chartReadings->pluck('avg_kw');

        return view('dashboard', compact('todayKwh', 'monthKwh', 'currentKw', 'machines', 'chartLabels', 'chartValues'));
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
        $historyVoltage = [];
        $todayConsumption = 0;
        if ($machine) {
            $deviceIds = $machine->devices->pluck('id');
            $history = \App\Models\PowerReading::whereIn('device_id', $deviceIds)
                        ->where('recorded_at', '>=', now()->subHours(24))
                        ->orderBy('recorded_at', 'asc')
                        ->get(['recorded_at', 'power_kw', 'kwh_total', 'voltage']);
            
            foreach ($history as $point) {
                $historyLabels[] = $point->recorded_at->format('H:i');
                $historyValues[] = $point->power_kw;
                $historyVoltage[] = $point->voltage;
            }

            // If the last reading is more than 15 minutes ago, 
            // force the chart to drop to 0 at the current time
            if ($history->isNotEmpty() && $history->last()->recorded_at->diffInMinutes(now()) > 15) {
                $historyLabels[] = now()->format('H:i');
                $historyValues[] = 0;
                $historyVoltage[] = 0;
            }

            // Use the pre-calculated today's consumption
            $todayConsumption = $machine->todaySummary ? $machine->todaySummary->kwh_usage : 0;
            
            // Total Cumulative Energy from the latest reading
            $totalEnergy = $machine->latestReading ? $machine->latestReading->kwh_total : 0;
        }

        return view('machine', compact('machine', 'historyLabels', 'historyValues', 'historyVoltage', 'todayConsumption', 'totalEnergy'));
    })->name('machines');


    Route::get('/environmental', function () {
        return view('environmental');
    })->name('environmental');

    Route::get('/api/machines/{id}/readings', [\App\Http\Controllers\Api\MachineController::class, 'readings'])->name('api.machines.readings');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

