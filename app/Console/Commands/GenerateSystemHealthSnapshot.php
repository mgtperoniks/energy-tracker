<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SystemHealthSnapshot;
use App\Models\PowerReadingDaily;
use App\Models\AuditLog;
use App\Models\ElectricityTariff;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateSystemHealthSnapshot extends Command
{
    protected $signature = 'energy:generate-health-snapshot {--date= : Custom date Y-m-d}';
    protected $description = 'Generate daily operational summary for system health';

    public function handle()
    {
        $targetStr = $this->option('date') ?: now()->subDay()->toDateString();
        $targetDate = Carbon::parse($targetStr);
        $isToday = $targetDate->isToday();
        
        $this->info("Generating health snapshot for: " . $targetDate->toDateString());

        // 1. Telemetry Health
        $pollInterval = config('telemetry.poll_interval_minutes', 10);
        $expectedPerDevice = 1440 / $pollInterval;
        $freshnessThreshold = $pollInterval * 2;
        
        $devices = Device::all();
        $deviceCount = $devices->count();
        
        // Optimize: Use single aggregate query for all devices
        $telemetryStats = DB::table('power_readings_raw')
            ->whereDate('recorded_at', $targetDate->toDateString())
            ->selectRaw('device_id, count(*) as cnt, max(recorded_at) as last_seen')
            ->groupBy('device_id')
            ->get()
            ->keyBy('device_id');

        $deviceMetrics = [];
        $totalCoverage = 0;
        $healthyCount = 0;
        $staleCount = 0;
        $offlineCount = 0;

        foreach ($devices as $device) {
            $stats = $telemetryStats->get($device->id);
            $actualCount = $stats ? ($stats->cnt ?? 0) : 0;
            $lastSeen = ($stats && $stats->last_seen) ? Carbon::parse($stats->last_seen) : null;
            
            // Coverage calculation (capped at 100%)
            $coverage = $expectedPerDevice > 0 ? min(1, $actualCount / $expectedPerDevice) : 0;
            $totalCoverage += $coverage;

            // Health status logic
            $status = 'offline';
            if ($isToday) {
                // Live Freshness Mode
                if ($lastSeen) {
                    $diff = $lastSeen->diffInMinutes(now());
                    if ($diff <= $freshnessThreshold) {
                        $status = 'healthy';
                        $healthyCount++;
                    } elseif ($diff <= 1440) {
                        $status = 'stale';
                        $staleCount++;
                    } else {
                        $offlineCount++;
                    }
                } else {
                    $offlineCount++;
                }
            } else {
                // Historical Coverage Mode
                $covPercent = $coverage * 100;
                if ($covPercent >= 90) {
                    $status = 'healthy';
                    $healthyCount++;
                } elseif ($covPercent >= 50) {
                    $status = 'stale';
                    $staleCount++;
                } else {
                    $status = 'offline';
                    $offlineCount++;
                }
            }

            $deviceMetrics[] = [
                'device_id' => $device->id,
                'name' => $device->name,
                'actual_count' => $actualCount,
                'coverage_percent' => round($coverage * 100, 1),
                'last_seen' => $stats->last_seen ?? null,
                'status' => $status
            ];
        }
        
        $telemetryScore = $deviceCount > 0 ? ($totalCoverage / $deviceCount) * 10 : 0;
        
        // 2. Incident Health
        $currentOpenCritical = AuditLog::where('status', AuditLog::STATUS_OPEN)
            ->where('severity', AuditLog::SEVERITY_CRITICAL)
            ->count();
            
        $dailyNewIncidents = AuditLog::whereDate('detected_at', $targetDate->toDateString())->count();
        $incidentScore = max(0, 10 - ($currentOpenCritical * 1.0) - ($dailyNewIncidents * 0.1));

        // 3. Accounting Health
        $dailyRecords = PowerReadingDaily::where('recorded_date', $targetDate->toDateString())->get();
        $totalKwh = $dailyRecords->sum('kwh_usage');
        $totalCost = $dailyRecords->sum('energy_cost');
        
        $tariffActive = ElectricityTariff::where('effective_date', '<=', $targetDate->toDateString())
            ->where('is_active', true)
            ->exists();
            
        if (!$tariffActive) {
            $accountingScore = 0;
        } else {
            $accountingScore = $deviceCount > 0 ? min(10, ($dailyRecords->count() / $deviceCount) * 10) : 0;
        }

        // 4. Failed Escalations
        $failedEscalations = DB::table('incident_escalations')
            ->whereDate('created_at', $targetDate->toDateString())
            ->where('delivery_status', 'failed')
            ->count();

        $overallScore = ($telemetryScore + $incidentScore + $accountingScore) / 3;

        // --- TREND DELTA ANALYSIS ---
        $prevSnapshot = SystemHealthSnapshot::where('snapshot_date', '<', $targetDate->toDateString())
            ->orderByDesc('snapshot_date')
            ->first();

        $deltas = [
            'telemetry' => $prevSnapshot ? round($telemetryScore - $prevSnapshot->telemetry_score, 2) : 0,
            'incident'  => $prevSnapshot ? round($incidentScore - $prevSnapshot->incident_score, 2) : 0,
            'accounting'=> $prevSnapshot ? round($accountingScore - $prevSnapshot->accounting_score, 2) : 0,
            'overall'   => $prevSnapshot ? round($overallScore - $prevSnapshot->overall_score, 2) : 0,
        ];

        $trend = 'stable';
        if ($deltas['overall'] > 3) $trend = 'improving';
        elseif ($deltas['overall'] < -3) $trend = 'degrading';

        $payload = [
            'telemetry' => [
                'poll_interval_minutes' => $pollInterval,
                'evaluation_mode' => $isToday ? 'live_freshness' : 'historical_coverage',
                'expected_per_device' => $expectedPerDevice,
                'devices_total' => $deviceCount,
                'healthy_devices' => $healthyCount,
                'stale_devices' => $staleCount,
                'offline_devices' => $offlineCount,
                'device_metrics' => $deviceMetrics,
                'delta' => $deltas['telemetry'],
            ],
            'incidents' => [
                'current_open_critical' => $currentOpenCritical,
                'daily_new_incidents' => $dailyNewIncidents,
                'failed_escalations' => $failedEscalations,
                'delta' => $deltas['incident'],
            ],
            'accounting' => [
                'total_kwh' => round($totalKwh, 2),
                'total_cost' => round($totalCost, 0),
                'tariff_active' => $tariffActive,
                'records_count' => $dailyRecords->count(),
                'delta' => $deltas['accounting'],
            ],
            'trend' => [
                'label' => $trend,
                'overall_delta' => $deltas['overall'],
            ],
            'generated_at' => now()->toDateTimeString(),
        ];

        SystemHealthSnapshot::updateOrCreate(
            ['snapshot_date' => $targetDate->toDateString()],
            [
                'telemetry_score' => $telemetryScore,
                'incident_score' => $incidentScore,
                'accounting_score' => $accountingScore,
                'overall_score' => $overallScore,
                'payload_json' => $payload,
            ]
        );

        $this->info("Snapshot saved with overall score: " . number_format($overallScore, 2));
    }
}
