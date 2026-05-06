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
        $now = now();
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 1. Get ALL devices with their latest raw reading
        $subquery = PowerReadingRaw::selectRaw('device_id, MAX(recorded_at) as max_recorded_at')
            ->groupBy('device_id');

        $latestReadings = PowerReadingRaw::joinSub($subquery, 'latest', function ($join) {
            $join->on('power_readings_raw.device_id', '=', 'latest.device_id')
                 ->on('power_readings_raw.recorded_at', '=', 'latest.max_recorded_at');
        })->get()->keyBy('device_id');

        $devices = Device::all();
        
        $onlineDevicesCount = 0;
        $offlineDevicesCount = 0;
        $totalKw = 0;
        $latestSync = null;

        foreach ($devices as $device) {
            $reading = $latestReadings->get($device->id);
            
            if ($reading) {
                $diffMinutes = $reading->recorded_at->diffInMinutes($now);
                
                // Definition: Offline if > 15 minutes
                if ($diffMinutes > 15) {
                    $offlineDevicesCount++;
                } else {
                    $onlineDevicesCount++;
                    $totalKw += (float)$reading->power_kw;
                }

                if ($latestSync === null || $reading->recorded_at > $latestSync) {
                    $latestSync = $reading->recorded_at;
                }
            } else {
                $offlineDevicesCount++;
            }
        }

        $currentKw = $totalKw;

        // 2. System Status (based on latest sync across all devices)
        $systemStatus = 'OFFLINE';
        if ($latestSync) {
            $syncDiff = $latestSync->diffInMinutes($now);
            if ($syncDiff <= 3) $systemStatus = 'LIVE';
            elseif ($syncDiff <= 15) $systemStatus = 'STALE';
        }

        // 3. Summary cards (Today's Energy & Cost)
        $todayDailies = PowerReadingDaily::where('recorded_date', $today)->get();
        $activeRate = \App\Models\ElectricityTariff::getRateForDate($today->toDateString());

        if ($todayDailies->isNotEmpty()) {
            $todayUsage = $todayDailies->sum('kwh_usage');
            $todayCost = $todayDailies->sum('energy_cost');
            $isEstimatedCost = false;
        } else {
            // Fallback: raw reconstruction
            $fallback = PowerReadingRaw::where('recorded_at', '>=', $today)
                ->selectRaw('device_id, MAX(kwh_total) - MIN(kwh_total) as energy_usage')
                ->groupBy('device_id')
                ->get();
            $todayUsage = $fallback->sum('energy_usage');
            $todayCost = $todayUsage * $activeRate;
            $isEstimatedCost = true;
        }

        // Monthly Totals
        $monthUsage = PowerReadingDaily::whereYear('recorded_date', $today->year)
            ->whereMonth('recorded_date', $today->month)
            ->sum('kwh_usage');
        $monthCost = PowerReadingDaily::whereYear('recorded_date', $today->year)
            ->whereMonth('recorded_date', $today->month)
            ->sum('energy_cost');

        if ($todayDailies->isEmpty()) {
            $monthUsage += $todayUsage;
            $monthCost += $todayCost;
        }

        // 4. Anomaly Summary
        $todayLogs = PollerLog::where('event_at', '>=', $today)->get();
        $lowVoltageCount = $todayLogs->filter(fn($l) => str_contains($l->message, 'Low voltage anomaly'))->count();
        $idleLeakLogs = $todayLogs->filter(fn($l) => str_starts_with($l->message, 'Idle consumption anomaly'));
        
        $idleLeakageCostToday = 0;
        foreach ($idleLeakLogs as $log) {
            if (preg_match('/Leakage Cost:\s*([\d.]+)/', $log->message, $matches)) {
                $idleLeakageCostToday += (float)$matches[1];
            }
        }

        $anomalySummary = [
            'low_voltage' => $lowVoltageCount,
            'idle_leak'   => $idleLeakLogs->count()
        ];

        // 5. Machines list for Node Ledger
        $machines = Machine::with(['devices', 'todaySummary'])->get();

        // 6. Diagnostic Logs
        \Log::info('DASHBOARD REFRESH', [
            'dashboard_total_load' => $currentKw,
            'dashboard_online_devices' => $onlineDevicesCount,
            'dashboard_offline_devices' => $offlineDevicesCount,
            'dashboard_last_sync' => $latestSync ? $latestSync->toDateTimeString() : 'NEVER',
            'system_status' => $systemStatus
        ]);

        return view('dashboard', compact(
            'onlineDevicesCount', 'offlineDevicesCount',
            'currentKw', 'systemStatus',
            'todayUsage', 'monthUsage',
            'todayCost', 'monthCost', 'isEstimatedCost',
            'idleLeakageCostToday', 'anomalySummary',
            'machines',
            'latestReadings'
        ));
    }
}
