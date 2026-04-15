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
        $offset = (int) $request->get('offset', 0);
        $limit = (int) $request->get('limit', 10);

        $readings = \App\Models\PowerReading::whereIn('device_id', function($query) use ($machine) {
                        $query->select('id')->from('devices')->where('machine_id', $machine->id);
                    })
                    ->orderBy('recorded_at', 'desc')
                    ->skip($offset)
                    ->take($limit)
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $readings
        ]);
    }
}


