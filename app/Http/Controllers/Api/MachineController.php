<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\DailyEnergySummary;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function index()
    {
        $machines = Machine::with('location')->get();
        return response()->json([
            'status' => 'success',
            'data' => $machines
        ]);
    }

    public function energyData($id, Request $request)
    {
        $machine = Machine::findOrFail($id);
        
        // Example query for machine consumption
        $dailySummaries = DailyEnergySummary::where('machine_id', $machine->id)
                            ->orderBy('date', 'desc')
                            ->take(30)
                            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'machine' => $machine,
                'energy_trends' => $dailySummaries
            ]
        ]);
    }

    public function readings($id, Request $request)
    {
        $machine = Machine::findOrFail($id);
        $limit = (int) $request->get('limit', 15);
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = \App\Models\PowerReadingRaw::whereIn('device_id', function($query) use ($machine) {
                        $query->select('id')->from('devices')->where('machine_id', $machine->id);
                    });

        if ($startDate && $endDate) {
            $query->whereBetween('recorded_at', [$startDate, $endDate]);
        }

        $readings = $query->orderBy('recorded_at', 'desc')
                    ->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => $readings
        ]);
    }
}


