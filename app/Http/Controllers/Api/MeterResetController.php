<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Device;
use App\Models\MeterReset;
use App\Models\PowerReadingRaw;
use Illuminate\Http\Request;

class MeterResetController extends Controller
{
    public function store(Request $request, $id)
    {
        $machine = Machine::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Transitional hybrid compatibility: Ambil device utama dari mesin ini
        $device = Device::where('machine_id', $machine->id)->first();

        if (!$device) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No device found for this machine. Cannot determine pre-reset kWh.',
            ], 422);
        }

        $latestReading = PowerReadingRaw::where('device_id', $device->id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$latestReading) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No readings found for this device.',
            ], 422);
        }

        // Ambil nilai mentah sebelum di-reset
        $lastRaw = $latestReading->meter_kwh_raw;

        MeterReset::create([
            'device_id'    => $device->id,
            'kwh_at_reset' => $lastRaw,
            'notes'        => $validated['notes'] ?? "Manual reset via API. Pre-reset meter raw: {$lastRaw} kWh.",
            'performed_by' => auth()->id(),
            'reset_at'     => now(),
        ]);

        // Tambahkan ke baseline milik Device
        $device->active_baseline_kwh = ($device->active_baseline_kwh ?? 0) + $lastRaw;
        $device->save();

        return response()->json([
            'status'         => 'success',
            'message'        => "Reset logged successfully for device: {$device->name}.",
            'data' => [
                'machine_id'       => $machine->id,
                'device_id'        => $device->id,
                'kwh_at_reset'     => $lastRaw,
                'new_baseline'     => $device->active_baseline_kwh,
                'reset_at'         => now()->toDateTimeString(),
            ],
        ]);
    }

    public function index($id)
    {
        $machine = Machine::findOrFail($id);
        
        // Transitional hybrid compatibility: Ambil device dari mesin ini
        $deviceIds = Device::where('machine_id', $machine->id)->pluck('id');

        $resets = MeterReset::whereIn('device_id', $deviceIds)
            ->with('performedBy:id,name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'machine'      => [
                    'id'            => $machine->id,
                    'name'          => $machine->name,
                ],
                'resets' => $resets,
            ],
        ]);
    }
}
