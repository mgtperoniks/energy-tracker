<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\PollerLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SystemHealthController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // 1. Schedulers Health 
        $schedulers = [
            'aggregate_hourly' => $this->checkSchedulerHealth('cron_aggregate_hourly', 65), // 60 mins + 5 mins buffer
            'aggregate_daily'  => $this->checkSchedulerHealth('cron_aggregate_daily', 1450), // 24 hours + 10 mins buffer
            'prune_raw'        => $this->checkSchedulerHealth('cron_prune_raw', 1450),
            'prune_hourly'     => $this->checkSchedulerHealth('cron_prune_hourly', 1450),
        ];

        // 2. Failed Jobs
        $failedJobsCount = DB::table('failed_jobs')->count();

        // 3. Early Warning: Silent Devices (> 5 mins && <= 15 mins)
        $silentThreshold = $now->copy()->subMinutes(5);
        $staleThreshold = $now->copy()->subMinutes(15);
        
        $silentDevices = Device::whereNotNull('last_seen_at')
            ->where('last_seen_at', '<', $silentThreshold)
            ->where('last_seen_at', '>=', $staleThreshold)
            ->get();

        // 4. Stale Devices (> 15 mins)
        $staleDevices = Device::whereNotNull('last_seen_at')
            ->where('last_seen_at', '<', $staleThreshold)
            ->get();

        // 5. Offline Devices (Murni dimensi flag logic poller)
        $offlineDevices = Device::where('is_online', false)->get();

        // 6. Packet Loss Rate (24 Jam terakhir)
        $last24Hours = $now->copy()->subDay();
        $packetErrors = PollerLog::where('event_at', '>=', $last24Hours)
            ->whereIn('status', [PollerLog::STATUS_ERROR, PollerLog::STATUS_WARNING])
            ->count();
            
        // Denominator hanya menggunakan device yang active/enabled (status = true)
        $activeDevicesCount = Device::where('status', true)->count(); 
        
        // Asumsi polling 1 menit = 1440 polling per hari per device
        $expectedPolls = $activeDevicesCount * 1440; 
        $packetLossRate = 0.00;
        
        if ($expectedPolls > 0) {
            $packetLossRate = ($packetErrors / $expectedPolls) * 100;
        }

        return view('system-health', compact(
            'schedulers',
            'failedJobsCount',
            'silentDevices',
            'staleDevices',
            'offlineDevices',
            'packetErrors',
            'packetLossRate',
            'expectedPolls'
        ));
    }

    /**
     * Memeriksa apakah scheduler berjalan sesuai jadwal
     */
    private function checkSchedulerHealth(string $cacheKey, int $maxMinutesExpected): array
    {
        $lastRun = Cache::get($cacheKey);
        
        if (!$lastRun) {
            return [
                'timestamp' => null,
                'status'    => 'stale',
                'message'   => 'Never run or missing cache marker'
            ];
        }

        $lastRunDate = Carbon::parse($lastRun);
        $minutesSinceLastRun = $lastRunDate->diffInMinutes(Carbon::now());
        
        $isHealthy = $minutesSinceLastRun <= $maxMinutesExpected;

        return [
            'timestamp' => $lastRunDate,
            'status'    => $isHealthy ? 'healthy' : 'stale',
            'message'   => $isHealthy ? 'Running normally' : 'Delayed'
        ];
    }
}
