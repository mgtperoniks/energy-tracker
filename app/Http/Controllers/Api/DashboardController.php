<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Device;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary()
    {
        $totalMachines = Machine::count();
        $totalDevices = Device::count();

        // Calculate total today's consumption
        // Based on the daily_energy_summaries
        $todayUsage = \App\Models\DailyEnergySummary::whereDate('date', today())->sum('kwh_usage');
        $thisMonthUsage = \App\Models\DailyEnergySummary::whereYear('date', today()->year)
                            ->whereMonth('date', today()->month)
                            ->sum('kwh_usage');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_machines' => $totalMachines,
                'total_devices' => $totalDevices,
                'today_kwh' => $todayUsage,
                'this_month_kwh' => $thisMonthUsage
            ]
        ]);
    }
}
