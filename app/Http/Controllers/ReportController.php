<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\DailyEnergySummary;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

            $query = DailyEnergySummary::with('machine')
                ->whereBetween('date', [$startDate, $endDate]);

            if ($machineId) {
                $query->where('machine_id', $machineId);
            }

            $reports = $query->orderBy('date', 'desc')->paginate(50);
        } else {
            // Set default dates for the form inputs only
            $startDate = now()->subDays(30)->toDateString();
            $endDate = now()->toDateString();
        }

        return view('reports', compact('reports', 'machines', 'machineId', 'startDate', 'endDate', 'isGenerated'));
    }
}
