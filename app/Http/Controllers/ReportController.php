<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\DailyEnergySummary;
use App\Models\PowerReading;
use App\Models\Device;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $machineId = $request->query('machine_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $isGenerated = $request->has('machine_id') || $request->has('start_date');

        $machines = Machine::orderBy('code')->get();
        $reports = collect();

        if ($isGenerated) {
            $startDate = $startDate ?? now()->subDays(30)->toDateString();
            $endDate = $endDate ?? now()->toDateString();

            // First try daily_energy_summaries
            $query = DailyEnergySummary::with('machine')
                ->whereBetween('date', [$startDate, $endDate]);

            if ($machineId) {
                $query->where('machine_id', $machineId);
            }

            $reports = $query->orderBy('date', 'desc')->paginate(50);

            // If no summary data found, generate from power_readings
            if ($reports->isEmpty()) {
                $deviceQuery = Device::where('type', 'power_meter');
                if ($machineId) {
                    $deviceQuery->where('machine_id', $machineId);
                }
                $devices = $deviceQuery->whereNotNull('machine_id')->get();

                foreach ($devices as $device) {
                    // Get readings grouped by date
                    $dailyReadings = PowerReading::where('device_id', $device->id)
                        ->whereDate('recorded_at', '>=', $startDate)
                        ->whereDate('recorded_at', '<=', $endDate)
                        ->orderBy('recorded_at', 'asc')
                        ->get()
                        ->groupBy(function ($reading) {
                            return $reading->recorded_at->toDateString();
                        });

                    foreach ($dailyReadings as $date => $readings) {
                        if ($readings->count() < 2) continue;

                        $kwhDiff = $readings->last()->kwh_total - $readings->first()->kwh_total;
                        if ($kwhDiff <= 0) {
                            // Estimate from average power × hours
                            $avgKw = $readings->avg('power_kw');
                            $hours = $readings->first()->recorded_at->diffInMinutes($readings->last()->recorded_at) / 60;
                            $kwhDiff = round($avgKw * $hours, 2);
                        }

                        if ($kwhDiff > 0) {
                            DailyEnergySummary::updateOrCreate(
                                [
                                    'machine_id' => $device->machine_id,
                                    'date' => $date,
                                ],
                                [
                                    'kwh_usage' => round($kwhDiff, 2),
                                ]
                            );
                        }
                    }
                }

                // Re-query after generation
                $query = DailyEnergySummary::with('machine')
                    ->whereBetween('date', [$startDate, $endDate]);
                if ($machineId) {
                    $query->where('machine_id', $machineId);
                }
                $reports = $query->orderBy('date', 'desc')->paginate(50);
            }
        } else {
            $startDate = now()->subDays(30)->toDateString();
            $endDate = now()->toDateString();
        }

        return view('reports', compact('reports', 'machines', 'machineId', 'startDate', 'endDate', 'isGenerated'));
    }
}
