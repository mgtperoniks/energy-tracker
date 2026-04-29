<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PollerLog;
use App\Models\Device;
use App\Models\ElectricityTariff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DetectIdleConsumptionAnomaly extends Command
{
    protected $signature = 'health:detect-idle-consumption';
    protected $description = 'Detect unexpected power consumption during non-operational hours';

    public function handle()
    {
        $monitoredDevices = Device::where('monitor_idle_consumption', true)->get();
        
        if ($monitoredDevices->isEmpty()) {
            $this->info('No devices configured for idle monitoring. Exiting.');
            return Command::SUCCESS;
        }

        $now = Carbon::now();
        $nonOpDays = config('energy.non_operational_days', [0]);
        $isNonOperationalDay = in_array($now->dayOfWeek, $nonOpDays);
        
        $opStart = Carbon::createFromTimeString(config('energy.operational_start', '08:00'));
        $opEnd = Carbon::createFromTimeString(config('energy.operational_end', '17:00'));
        $isOutsideOperationalHours = $now->lt($opStart) || $now->gte($opEnd);

        if (!$isNonOperationalDay && !$isOutsideOperationalHours) {
            $this->info('Currently within operational hours. Skipping idle consumption scan.');
            return Command::SUCCESS;
        }

        $anomaliesCount = 0;

        foreach ($monitoredDevices as $device) {
            $durationMinutes = \App\Services\SettingService::get('idle_duration', $device->id, 10);
            $idlePowerThreshold = \App\Services\SettingService::get('idle_power_threshold', $device->id, 2);
            
            $endWindow = $now->copy()->startOfMinute();
            $startWindow = $endWindow->copy()->subMinutes($durationMinutes - 1);
            
            $activeTariff = ElectricityTariff::getRateForDate($startWindow->toDateString());

            $anomaly = DB::table('power_readings_raw')
                ->selectRaw('AVG(power_kw) as avg_p, COUNT(*) as sample_count')
                ->where('device_id', $device->id)
                ->whereBetween('recorded_at', [$startWindow, $endWindow])
                ->having('avg_p', '>=', $idlePowerThreshold)
                ->having('sample_count', '>=', $durationMinutes)
                ->first();

            if ($anomaly) {
                $pRounded = round($anomaly->avg_p, 2);
                
                $kwhLeakage = ($durationMinutes / 60) * $anomaly->avg_p;
                $costLeakage = round($kwhLeakage * $activeTariff, 2);
                
                $warningMessage = "Idle consumption anomaly detected. [Avg Power: {$pRounded} kW, Leakage Cost: {$costLeakage}]";
                
                $existingWarning = PollerLog::where('device_id', $device->id)
                    ->where('status', PollerLog::STATUS_WARNING)
                    ->where('message', 'LIKE', 'Idle consumption anomaly%')
                    ->where('event_at', '>=', $startWindow)
                    ->exists();

                if (!$existingWarning) {
                    PollerLog::create([
                        'device_id' => $device->id,
                        'status'    => PollerLog::STATUS_WARNING,
                        'message'   => $warningMessage,
                        'event_at'  => $now
                    ]);
                    $anomaliesCount++;
                }
            }
        }

        $this->info("Completed. Generated {$anomaliesCount} idle consumption warnings.");
        return Command::SUCCESS;
    }
}
