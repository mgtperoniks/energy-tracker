<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\PowerReading;
use App\Services\EnergyCalculationService;
use Illuminate\Http\Request;

class MeterResetController extends Controller
{
    /**
     * POST /api/machines/{id}/reset
     *
     * Call this BEFORE physically resetting the power meter.
     * It will:
     *   1. Capture the last known kWh reading from the DB.
     *   2. Add it to the machine's kwh_baseline.
     *   3. Log the reset event in meter_resets table.
     *
     * Request body (optional):
     *   {
     *     "notes": "Annual reset - 1 May 2026"
     *   }
     */
    public function store(Request $request, $id)
    {
        $machine = Machine::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Get the latest known kwh_total from power_readings
        $latestReading = PowerReading::whereIn('device_id', function ($q) use ($machine) {
                $q->select('id')->from('devices')->where('machine_id', $machine->id);
            })
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$latestReading) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No readings found for this machine. Cannot determine pre-reset kWh.',
            ], 422);
        }

        $lastKwh = $latestReading->kwh_total;

        $service = new EnergyCalculationService();
        $service->logManualReset(
            machine:       $machine,
            lastKwhTotal:  $lastKwh,
            notes:         $validated['notes'] ?? "Manual reset via API. Pre-reset: {$lastKwh} kWh.",
            performedBy:   auth()->id(),
        );

        return response()->json([
            'status'         => 'success',
            'message'        => "Reset logged successfully for machine: {$machine->name}.",
            'data' => [
                'machine_id'       => $machine->id,
                'machine_name'     => $machine->name,
                'kwh_at_reset'     => $lastKwh,
                'new_baseline'     => $machine->fresh()->kwh_baseline,
                'reset_at'         => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * GET /api/machines/{id}/resets
     *
     * Returns the reset history for a machine.
     */
    public function index($id)
    {
        $machine = Machine::findOrFail($id);

        $resets = $machine->meterResets()
            ->with('performedBy:id,name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'machine'      => [
                    'id'            => $machine->id,
                    'name'          => $machine->name,
                    'kwh_baseline'  => $machine->kwh_baseline,
                    'lifetime_kwh'  => $machine->lifetime_kwh,
                ],
                'resets' => $resets,
            ],
        ]);
    }
}
