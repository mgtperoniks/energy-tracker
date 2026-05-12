<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\PowerReadingRaw;
use App\Models\PowerReadingDaily;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Exports\TelemetryExport;

class MachineDashboardController extends Controller
{
    public function show($id = null)
    {
        // 1. Detail machine & eager load devices
        $query = Machine::with(['devices']);
        $machine = $id ? $query->findOrFail($id) : $query->firstOrFail();

        $deviceIds = $machine->devices->pluck('id');

        // 2 & 3. Related devices & Status
        $onlineDevicesCount = $machine->devices->where('is_online', true)->count();
        $offlineDevicesCount = $machine->devices->where('is_online', false)->count();

        // 4. Current latest readings (Latest by recorded_at per device untuk scope spesifik machine)
        if ($deviceIds->isEmpty()) {
            $latestReadings = collect();
        } else {
            $subquery = PowerReadingRaw::selectRaw('device_id, MAX(recorded_at) as max_recorded_at')
                ->whereIn('device_id', $deviceIds)
                ->groupBy('device_id');

            $latestReadings = PowerReadingRaw::joinSub($subquery, 'latest', function ($join) {
                $join->on('power_readings_raw.device_id', '=', 'latest.device_id')
                     ->on('power_readings_raw.recorded_at', '=', 'latest.max_recorded_at');
            })->get()->keyBy('device_id');
        }

        $currentLoadKw = $latestReadings->sum('power_kw');

        // Today's consumption (Primary vs Fallback)
        $today = Carbon::today();
        $todayCost = 0;
        $isEstimatedCost = false;
        
        if ($deviceIds->isEmpty()) {
            $todayConsumption = 0;
        } else {
            $todayDailies = PowerReadingDaily::whereIn('device_id', $deviceIds)
                ->where('recorded_date', $today)
                ->get();

            if ($todayDailies->isNotEmpty()) {
                $todayConsumption = $todayDailies->sum('kwh_usage');
                $todayCost = $todayDailies->sum('energy_cost');
            } else {
                $fallback = PowerReadingRaw::whereIn('device_id', $deviceIds)
                    ->where('recorded_at', '>=', $today)
                    ->selectRaw('device_id, MAX(kwh_total) - MIN(kwh_total) as energy_usage')
                    ->groupBy('device_id')
                    ->get();
                $todayConsumption = $fallback->sum('energy_usage');
                
                $activeRate = \App\Models\ElectricityTariff::getRateForDate($today->toDateString());
                $todayCost = $todayConsumption * $activeRate;
                $isEstimatedCost = true;
            }
        }

        // Month Cost Machine Scope
        $monthCost = 0;
        if ($deviceIds->isNotEmpty()) {
            $monthCost = PowerReadingDaily::whereIn('device_id', $deviceIds)
                ->whereYear('recorded_date', $today->year)
                ->whereMonth('recorded_date', $today->month)
                ->sum('energy_cost');
        }

        // Total Energy
        $totalEnergy = $machine->devices->sum(function ($device) use ($latestReadings) {
            $latest = $latestReadings->get($device->id);
            // kwh_total di raw table sudah ter-normalize. TIDAK menambah active_baseline_kwh.
            return $latest ? $latest->kwh_total : ($device->last_kwh_total ?? 0);
        });

        // Event Logs (WARNING, ERROR)
        $eventLogs = \App\Models\PollerLog::whereIn('device_id', $deviceIds)
            ->whereIn('status', [\App\Models\PollerLog::STATUS_WARNING, \App\Models\PollerLog::STATUS_ERROR])
            ->orderBy('event_at', 'desc')
            ->take(10)
            ->get();
            
        // Reset count direct query
        $resetCount = \App\Models\MeterReset::whereIn('device_id', $deviceIds)->count();

        // Operational Status Logic (Priority: Offline > Low Voltage > Idle > Running > Mixed Load)
        // Rule: Offline if no readings or latest reading > 10 minutes ago
        $thresholdMinutes = 10;
        $latestTimestamp = $latestReadings->max('recorded_at');
        
        $isOffline = $deviceIds->isEmpty() || 
                    !$latestTimestamp || 
                    $latestTimestamp->diffInMinutes(now()) > $thresholdMinutes;
                    
        $opStatus = 'Running'; // Default
        
        if ($isOffline) {
            $opStatus = 'Offline';
        } else {
            if ($latestReadings->count() > 1) {
                $opStatus = 'Mixed Load';
            } elseif ($latestReadings->count() == 1) {
                $latest = $latestReadings->first();
                $lowVoltThresh = \App\Services\SettingService::get('low_voltage_threshold', $latest->device_id, 20);
                $idlePowerThresh = \App\Services\SettingService::get('idle_power_threshold', $latest->device_id, 2);
                
                if ($latest->voltage > 0 && $latest->voltage <= $lowVoltThresh) {
                    $opStatus = 'Low Voltage';
                } elseif ($latest->power_kw < $idlePowerThresh) {
                    $opStatus = 'Idle';
                }
            }
        }

        $currentMeterKwh = $totalEnergy;

        return view('machine', compact(
            'machine',
            'onlineDevicesCount',
            'offlineDevicesCount',
            'latestReadings',
            'currentLoadKw',
            'todayConsumption',
            'todayCost',
            'monthCost',
            'isEstimatedCost',
            'totalEnergy',
            'currentMeterKwh',
            'opStatus',
            'eventLogs',
            'resetCount'
        ));
    }
    public function export(Request $request, $id)
    {
        \Log::info('EXPORT RANGE REQUEST', [
            'raw_start' => $request->query('start_date'),
            'raw_end'   => $request->query('end_date'),
        ]);

        $machine = Machine::with('devices')->findOrFail($id);
        $deviceIds = $machine->devices->pluck('id')->toArray();

        // Patch 8: Excel Export Hardening
        // 1. Enforce Single Device Export only
        if (count($deviceIds) > 1) {
            return back()->with('error', 'Forensic Export Limit: Multiple meters detected. Please export per individual meter to maintain historian integrity.');
        }

        $rawStart = $request->query('start_date');
        $rawEnd = $request->query('end_date');

        if (!$rawStart || !$rawEnd) {
            $endDate = now();
            $startDate = now()->subHours(12);
        } else {
            try {
                $startDate = Carbon::parse($rawStart)->setTimezone(config('app.timezone'));
                $endDate   = Carbon::parse($rawEnd)->setTimezone(config('app.timezone'));
            } catch (\Exception $e) {
                return back()->with('error', 'Invalid date format.');
            }
        }

        // 2. Deny exports larger than 24h
        if ($startDate->diffInHours($endDate) > 24) {
            return back()->with('error', 'Forensic Export Limit: Maximum export range is 24 hours (1440 rows) to prevent system instability.');
        }

        // 3. Row count validation
        $count = PowerReadingRaw::whereIn('device_id', $deviceIds)
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->count();
            
        if ($count > 1500) {
            return back()->with('error', 'Forensic Export Limit: Detected row density too high for XLSX. Please use a smaller time window.');
        }

        if ($count === 0) {
            return back()->with('warning', "No telemetry found for period {$startDate->format('d M H:i')} - {$endDate->format('d M H:i')}.");
        }

        $filename = "meter_{$machine->code}_forensic_" . $startDate->format('Ymd_His') . ".xlsx";

        try {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new TelemetryExport($deviceIds, $startDate, $endDate),
                $filename
            );
        } catch (\Exception $e) {
            \Log::error('Export Error: ' . $e->getMessage());
            return back()->with('error', 'Export Engine Failure: ' . $e->getMessage());
        }
    }
}
