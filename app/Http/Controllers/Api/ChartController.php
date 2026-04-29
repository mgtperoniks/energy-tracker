<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\EnergyChartService;

class ChartController extends Controller
{
    public function getDeviceChart(Request $request, EnergyChartService $chartService)
    {
        $validated = $request->validate([
            'device_id' => 'required|integer|exists:devices,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $result = $chartService->getChartData(
            $validated['device_id'], 
            $validated['start_date'], 
            $validated['end_date']
        );

        return response()->json($result);
    }
}
