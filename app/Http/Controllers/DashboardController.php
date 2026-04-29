<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Machine;
use App\Models\PollerLog;
use App\Models\PowerReadingRaw;
use App\Models\PowerReadingDaily;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Dashboard summary data
        $totalDevices = Device::count();
        $onlineDevices = Device::where('is_online', true)->count();
        $offlineDevices = Device::where('is_online', false)->count();

        // 2. Current factory load (Latest by recorded_at per device menggunakan joinSub)
        $subquery = PowerReadingRaw::selectRaw('device_id, MAX(recorded_at) as max_recorded_at')
            ->groupBy('device_id');

        $latestReadings = PowerReadingRaw::joinSub($subquery, 'latest', function ($join) {
            $join->on('power_readings_raw.device_id', '=', 'latest.device_id')
                 ->on('power_readings_raw.recorded_at', '=', 'latest.max_recorded_at');
        })->get();
        
        $currentKw = $latestReadings->sum('power_kw');

        // 3. Summary cards
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todayDailies = PowerReadingDaily::where('recorded_date', $today)->get();

        if ($todayDailies->isNotEmpty()) {
            // Primary: Ambil dari tabel aggregate harian
            $todayUsage = $todayDailies->sum('kwh_usage');
        } else {
            // Fallback: raw reconstruction jika belum ada aggregate harian (valid jika usage memang 0 vs tidak ada data)
            $fallback = PowerReadingRaw::where('recorded_at', '>=', $today)
                ->selectRaw('device_id, MAX(kwh_total) - MIN(kwh_total) as energy_usage')
                ->groupBy('device_id')
                ->get();
            $todayUsage = $fallback->sum('energy_usage');
        }

        $yesterdayUsage = PowerReadingDaily::where('recorded_date', $yesterday)->sum('kwh_usage');
        $monthUsage = PowerReadingDaily::whereYear('recorded_date', $today->year)
            ->whereMonth('recorded_date', $today->month)
            ->sum('kwh_usage');

        // 3.B. Cost Calculation
        $todayCost = $todayDailies->sum('energy_cost');
        $isEstimatedCost = false;
        
        // Fallback cost realtime jika aggregate belum berjalan malam ini
        if ($todayDailies->isEmpty() && $todayUsage > 0) {
            $activeRate = \App\Models\ElectricityTariff::getRateForDate($today->toDateString());
            $todayCost = $todayUsage * $activeRate;
            $isEstimatedCost = true;
        }

        $monthCost = PowerReadingDaily::whereYear('recorded_date', $today->year)
            ->whereMonth('recorded_date', $today->month)
            ->sum('energy_cost');

        // 4. System Vitality / Anomaly Summary (Today)
        $todayLogs = PollerLog::where('event_at', '>=', $today)->get();
        
        $lowVoltageCount = $todayLogs->filter(function($log) {
            return str_contains($log->message, 'Low voltage anomaly');
        })->count();
        
        $idleLeakLogs = $todayLogs->filter(function($log) {
            return str_starts_with($log->message, 'Idle consumption anomaly');
        });
        $idleLeakCount = $idleLeakLogs->count();
        
        $idleLeakageCostToday = 0;
        foreach ($idleLeakLogs as $log) {
            if (preg_match('/Leakage Cost:\s*([\d.]+)/', $log->message, $matches)) {
                $idleLeakageCostToday += (float)$matches[1];
            }
        }
        
        $anomalySummary = [
            'low_voltage' => $lowVoltageCount,
            'idle_leak'   => $idleLeakCount
        ];

        // 5. Recent alerts
        $recentAlerts = PollerLog::whereIn('status', [
                PollerLog::STATUS_ERROR, 
                PollerLog::STATUS_WARNING, 
                PollerLog::STATUS_OFFLINE
            ])
            ->orderBy('event_at', 'desc')
            ->take(5)
            ->get();

        // Machine detail list (Cukup eager load devices)
        $machines = Machine::with(['devices'])->get();

        return view('dashboard', compact(
            'totalDevices', 'onlineDevices', 'offlineDevices',
            'currentKw',
            'todayUsage', 'yesterdayUsage', 'monthUsage',
            'todayCost', 'monthCost', 'isEstimatedCost',
            'idleLeakageCostToday', 'anomalySummary',
            'recentAlerts',
            'machines',
            'latestReadings'
        ));
    }
}
