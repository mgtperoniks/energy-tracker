<?php

namespace App\Services;

use App\Models\Device;
use App\Models\PowerReading;
use App\Models\DailyEnergySummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EnergyCalculationService
{
    /**
     * Calculates and stores the daily summary for all machines.
     * This logic handles missing gaps as "no reading" or ignores them, 
     * and correctly sums up power drops if meter resets.
     */
    public function calculateDailyUsageForAll(Carbon $date)
    {
        $devices = Device::where('type', 'power_meter')->whereNotNull('machine_id')->get();

        foreach ($devices as $device) {
            $this->calculateDailyUsage($device, $date);
        }
    }

    public function calculateDailyUsage(Device $device, Carbon $date)
    {
        // Get all readings for this exact day ordered chronologically
        $readings = PowerReading::where('device_id', $device->id)
                        ->whereDate('recorded_at', $date)
                        ->orderBy('recorded_at', 'asc')
                        ->get();

        if ($readings->isEmpty()) {
            return 0;
        }

        $totalUsage = 0;
        $previousReading = null;

        foreach ($readings as $reading) {
            if ($previousReading !== null) {
                $diff = $reading->kwh_total - $previousReading->kwh_total;
                
                // If diff is >= 0, it's normal consumption
                if ($diff >= 0) {
                    $totalUsage += $diff;
                } else {
                    // Meter reset or replaced. 
                    // Only add the absolute value if it's reasonably small (e.g., < 50 kWh)
                    // otherwise it's likely a data mismatch after cleanup or a major error.
                    if ($reading->kwh_total < 50) {
                        $totalUsage += $reading->kwh_total;
                    }
                    // If it's large, we ignore the jump to keep data integrity.
                }
            }
            $previousReading = $reading;
        }

        // Store into daily summaries
        if ($totalUsage > 0) {
            DailyEnergySummary::updateOrCreate(
                [
                    'machine_id' => $device->machine_id,
                    'date' => $date->toDateString(),
                ],
                [
                    'kwh_usage' => $totalUsage
                ]
            );
        }

        return $totalUsage;
    }
}
