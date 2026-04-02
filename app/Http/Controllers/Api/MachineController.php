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
}
