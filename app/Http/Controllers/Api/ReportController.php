<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyEnergySummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function daily(Request $request)
    {
        $date = $request->query('date', today()->toDateString());
        
        $report = DailyEnergySummary::with('machine.location')
                    ->whereDate('date', $date)
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $report
        ]);
    }

    public function monthly(Request $request)
    {
        $year = $request->query('year', today()->year);
        $month = $request->query('month', today()->month);

        $report = DailyEnergySummary::with('machine.location')
                    ->select('machine_id', DB::raw('SUM(kwh_usage) as total_kwh'))
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->groupBy('machine_id')
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $report
        ]);
    }
}
