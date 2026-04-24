<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\PowerReading;
use App\Services\EnergyCalculationService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReadingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slave_id' => 'required|integer',
            'kwh_total' => 'nullable|numeric', // Changed to nullable
            'power_kw' => 'nullable|numeric',
            'voltage' => 'nullable|numeric',
            'current' => 'nullable|numeric',
            'power_factor' => 'nullable|numeric',
            'is_offline' => 'nullable|boolean'
        ]);

        $device = Device::where('slave_id', $validated['slave_id'])
                        ->where('type', 'power_meter')
                        ->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => "Device with Slave ID {$validated['slave_id']} not found."
            ], 404);
        }

        // If offline and kwh_total is missing, use the last known kwh_total
        $kwh_total = $validated['kwh_total'];
        if ($kwh_total === null) {
            $lastReading = PowerReading::where('device_id', $device->id)
                                      ->orderBy('recorded_at', 'desc')
                                      ->first();
            $kwh_total = $lastReading ? $lastReading->kwh_total : 0;
        }

        // Create the reading
        $reading = PowerReading::create([
            'device_id' => $device->id,
            'kwh_total' => $kwh_total,
            'power_kw' => $validated['power_kw'] ?? 0,
            'voltage' => $validated['voltage'] ?? 0,
            'current' => $validated['current'] ?? 0,
            'power_factor' => $validated['power_factor'] ?? 1.0,
            'recorded_at' => now(),
        ]);

        // Trigger immediate recalculation for today for this specific machine
        // This ensures the dashboard stays live
        if ($device->machine_id) {
            try {
                $calcService = new EnergyCalculationService();
                $calcService->calculateDailyUsage($device, Carbon::today());
            } catch (\Exception $e) {
                Log::error("Energy Calculation Error: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Reading recorded successfully',
            'data' => $reading
        ]);
    }
}
