<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PollerLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DetectLowVoltageAnomaly extends Command
{
    protected $signature = 'health:detect-low-voltage';
    protected $description = 'Detect prolonged low voltage operation anomalies';

    public function handle()
    {
        $devices = \App\Models\Device::where('type', 'power_meter')->get();
        $anomaliesCount = 0;

        foreach ($devices as $device) {
            $durationMinutes = \App\Services\SettingService::get('low_voltage_duration', $device->id, 5);
            $lowVoltThreshold = \App\Services\SettingService::get('low_voltage_threshold', $device->id, 20);
            $minOperatingKw = \App\Services\SettingService::get('min_operating_kw', $device->id, 1);

            $endWindow = Carbon::now()->startOfMinute();
            $startWindow = $endWindow->copy()->subMinutes($durationMinutes - 1);

            $anomaly = DB::table('power_readings_raw')
                ->selectRaw('AVG(voltage) as avg_v, AVG(power_kw) as avg_p, COUNT(*) as sample_count')
                ->where('device_id', $device->id)
                ->whereBetween('recorded_at', [$startWindow, $endWindow])
                ->having('avg_v', '<=', $lowVoltThreshold)
                ->having('avg_p', '>=', $minOperatingKw)
                ->having('sample_count', '>=', $durationMinutes)
                ->first();

            if ($anomaly) {
                $vRounded = round($anomaly->avg_v, 1);
                $pRounded = round($anomaly->avg_p, 2);
                $warningMessage = "Low voltage anomaly (<{$lowVoltThreshold} V) persisted for {$durationMinutes} mins. [Avg Voltage: {$vRounded} V, Avg Power: {$pRounded} kW]";
                
                $existingWarning = PollerLog::where('device_id', $device->id)
                    ->where('status', PollerLog::STATUS_WARNING)
                    ->where('message', $warningMessage)
                    ->where('event_at', '>=', $startWindow)
                    ->exists();

                if (!$existingWarning) {
                    PollerLog::create([
                        'device_id' => $device->id,
                        'status'    => PollerLog::STATUS_WARNING,
                        'message'   => $warningMessage,
                        'event_at'  => Carbon::now()
                    ]);
                    $anomaliesCount++;
                }
            }
        }

        $this->info("Completed. Generated {$anomaliesCount} anomaly warnings.");
        return Command::SUCCESS;
    }
}
