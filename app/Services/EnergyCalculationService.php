<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Machine;
use App\Models\MeterReset;
use App\Models\PowerReading;
use App\Models\DailyEnergySummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnergyCalculationService
{
    /**
     * Minimum kWh drop to consider a meter reset (not just noise).
     * If kwh_total drops by more than this value, we treat it as a reset.
     * Set conservatively: if the meter drops 100 kWh it cannot be normal consumption.
     */
    const RESET_DETECTION_THRESHOLD_KWH = 100.0;

    /**
     * Recalculate daily summaries for ALL devices for a given date.
     */
    public function calculateDailyUsageForAll(Carbon $date): void
    {
        $devices = Device::where('type', 'power_meter')
                         ->whereNotNull('machine_id')
                         ->get();

        foreach ($devices as $device) {
            $this->calculateDailyUsage($device, $date);
        }
    }

    /**
     * Calculate and persist the daily kWh usage for a specific device.
     *
     * Strategy:
     *   - Sum consecutive deltas (kwh_total[n] - kwh_total[n-1]).
     *   - If a delta is NEGATIVE and the drop > RESET_DETECTION_THRESHOLD_KWH,
     *     it is treated as a meter reset: log it and update the machine baseline.
     *   - If the drop is small (< threshold), assume data noise and skip.
     *   - Fallback: if total is still 0 with ≥2 readings, estimate from avg kW × hours.
     *
     * @return float kWh usage for the day
     */
    public function calculateDailyUsage(Device $device, Carbon $date): float
    {
        $readings = PowerReading::where('device_id', $device->id)
                        ->whereDate('recorded_at', $date)
                        ->orderBy('recorded_at', 'asc')
                        ->get();

        if ($readings->isEmpty()) {
            return 0.0;
        }

        $totalUsage      = 0.0;
        $previousReading = null;

        foreach ($readings as $reading) {
            if ($previousReading !== null) {
                $diff = $reading->kwh_total - $previousReading->kwh_total;

                if ($diff >= 0) {
                    // Normal: meter is counting up
                    $totalUsage += $diff;
                } else {
                    // Negative delta detected
                    $drop = abs($diff);

                    if ($drop >= self::RESET_DETECTION_THRESHOLD_KWH) {
                        // ---- METER RESET DETECTED ----
                        // The kwh_total before the reset is the last known value.
                        $this->handleMeterReset($device, $previousReading->kwh_total, $reading->recorded_at);

                        // After a reset, the new meter reading IS the consumption
                        // since the reset happened (meter counts from 0 again).
                        $totalUsage += $reading->kwh_total;

                        Log::warning(
                            "EnergyCalc: Meter reset detected for device #{$device->id} " .
                            "({$device->machine->name ?? 'N/A'}) at {$reading->recorded_at}. " .
                            "Drop: {$drop} kWh. Baseline updated."
                        );
                    } else {
                        // Small noise / data glitch — skip this interval silently
                        Log::info(
                            "EnergyCalc: Small negative delta ({$diff} kWh) ignored for " .
                            "device #{$device->id} at {$reading->recorded_at}."
                        );
                    }
                }
            }

            $previousReading = $reading;
        }

        // FALLBACK: if totalUsage is 0 but we have multiple readings
        // (e.g., the meter counter was not moving or all values are identical),
        // estimate from average active power × elapsed time.
        if ($totalUsage <= 0 && $readings->count() >= 2) {
            $avgKw        = $readings->avg('power_kw');
            $firstTime    = $readings->first()->recorded_at;
            $lastTime     = $readings->last()->recorded_at;
            $hoursElapsed = $firstTime->diffInSeconds($lastTime) / 3600;
            $totalUsage   = round($avgKw * $hoursElapsed, 3);

            Log::info(
                "EnergyCalc: Fallback estimation used for device #{$device->id} on {$date->toDateString()}. " .
                "Estimated {$totalUsage} kWh from avg {$avgKw} kW × {$hoursElapsed} h."
            );
        }

        // Persist daily summary
        if ($totalUsage > 0) {
            DailyEnergySummary::updateOrCreate(
                [
                    'machine_id' => $device->machine_id,
                    'date'       => $date->toDateString(),
                ],
                [
                    'kwh_usage' => round($totalUsage, 3),
                ]
            );
        }

        return round($totalUsage, 3);
    }

    /**
     * Handle a meter reset event:
     *   1. Log it in the meter_resets table (audit trail).
     *   2. Add the pre-reset kWh value to the machine's baseline.
     *
     * This ensures that lifetime_kwh = baseline + current_meter_kwh
     * remains continuous across resets.
     *
     * @param Device $device
     * @param float  $kwhAtReset  The last known kwh_total before the reset
     * @param Carbon $resetAt     Approximate timestamp of the reset
     */
    public function handleMeterReset(Device $device, float $kwhAtReset, Carbon $resetAt): void
    {
        if (!$device->machine_id) {
            return;
        }

        $machine = Machine::find($device->machine_id);
        if (!$machine) {
            return;
        }

        // 1. Log the reset event
        MeterReset::create([
            'machine_id'   => $machine->id,
            'kwh_at_reset' => $kwhAtReset,
            'notes'        => "Auto-detected meter reset. Pre-reset reading: {$kwhAtReset} kWh.",
            'reset_at'     => $resetAt,
        ]);

        // 2. Add the pre-reset reading to the baseline so history is preserved
        $machine->kwh_baseline = ($machine->kwh_baseline ?? 0) + $kwhAtReset;
        $machine->save();
    }

    /**
     * Manually log a meter reset performed by an operator.
     * Call this when you intentionally reset the meter (e.g., 1 May 2026).
     *
     * Usage:
     *   $service = new EnergyCalculationService();
     *   $service->logManualReset($machine, $lastKwhReading, 'Annual reset - 1 May 2026', auth()->id());
     *
     * @param Machine   $machine
     * @param float     $lastKwhTotal  The meter reading just before the manual reset
     * @param string    $notes
     * @param int|null  $performedBy   User ID who did the reset
     */
    public function logManualReset(Machine $machine, float $lastKwhTotal, string $notes = '', ?int $performedBy = null): void
    {
        // 1. Log the reset
        MeterReset::create([
            'machine_id'   => $machine->id,
            'kwh_at_reset' => $lastKwhTotal,
            'notes'        => $notes ?: "Manual reset. Pre-reset reading: {$lastKwhTotal} kWh.",
            'performed_by' => $performedBy,
            'reset_at'     => now(),
        ]);

        // 2. Update machine baseline
        $machine->kwh_baseline = ($machine->kwh_baseline ?? 0) + $lastKwhTotal;
        $machine->save();

        Log::info(
            "EnergyCalc: Manual reset logged for machine #{$machine->id} ({$machine->name}). " .
            "Added {$lastKwhTotal} kWh to baseline. New baseline: {$machine->kwh_baseline} kWh."
        );
    }
}
