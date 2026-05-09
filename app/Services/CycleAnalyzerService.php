<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ElectricityTariff;

class CycleAnalyzerService
{
    public function analyze($deviceId, $start, $end)
    {
        // 1. Load data
        $readings = DB::table('power_readings_raw')
            ->where('device_id', $deviceId)
            ->whereBetween('recorded_at', [$start, $end])
            ->select('power_kw', 'recorded_at')
            ->orderBy('recorded_at', 'asc')
            ->get();

        if ($readings->isEmpty()) {
            return $this->emptyResponse();
        }

        // 2. Calculate threshold with percentile stability
        $thresholdData = $this->calculateStableThreshold($readings);
        $threshold = $thresholdData['threshold'];
        $thresholdSource = $thresholdData['source'];
        
        $activeRate = ElectricityTariff::getRateForDate(Carbon::parse($start)->toDateString());
        $standbyBaseline = config('telemetry.standby_baseline_kw', 10);

        $cycles = [];
        $currentCycle = null;
        $idlePoints = [];
        $cycleCount = 0;
        $lastCycleEndTime = null;

        // Sustain state variables
        $currentState = 'IDLE';
        $consecutiveCount = 0;
        $SUSTAIN_REQUIRED = 3;

        // 3. Detect transitions with Sustain Logic
        foreach ($readings as $index => $reading) {
            $isAboveThreshold = $reading->power_kw > $threshold;
            $timestamp = Carbon::parse($reading->recorded_at);

            if ($currentState === 'IDLE') {
                if ($isAboveThreshold) {
                    $consecutiveCount++;
                } else {
                    $consecutiveCount = 0;
                    $idlePoints[] = $reading->power_kw;
                }

                if ($consecutiveCount >= $SUSTAIN_REQUIRED) {
                    $currentState = 'RUN';
                    $consecutiveCount = 0;
                    $cycleCount++;
                    
                    $startIndex = max(0, $index - ($SUSTAIN_REQUIRED - 1));
                    $actualStartTime = Carbon::parse($readings[$startIndex]->recorded_at);

                    // Finalize idle gap for previous cycle
                    $idleLeakage = $this->calculateIdleLeakage($idlePoints, $lastCycleEndTime, $actualStartTime, $activeRate, $standbyBaseline);

                    $currentCycle = [
                        'cycle_number' => $cycleCount,
                        'start_time' => $actualStartTime->toIso8601String(),
                        'end_time' => null,
                        'points' => [],
                        'idle_gap_minutes' => $lastCycleEndTime ? $lastCycleEndTime->diffInMinutes($actualStartTime) : 0,
                        'idle_avg_kw' => $idleLeakage['avg_kw'],
                        'standby_baseline_kw' => $standbyBaseline,
                        'waste_kw' => $idleLeakage['waste_kw'],
                        'idle_energy_loss_kwh' => $idleLeakage['energy_loss_kwh'],
                        'idle_cost_loss' => $idleLeakage['cost_loss']
                    ];
                    
                    for($i = $startIndex; $i <= $index; $i++) {
                        $currentCycle['points'][] = $readings[$i]->power_kw;
                    }
                    $idlePoints = [];
                }
            } else { // RUN
                if (!$isAboveThreshold) {
                    $consecutiveCount++;
                } else {
                    $consecutiveCount = 0;
                }

                if ($consecutiveCount >= $SUSTAIN_REQUIRED) {
                    $currentState = 'IDLE';
                    $consecutiveCount = 0;
                    
                    $endIndex = max(0, $index - ($SUSTAIN_REQUIRED - 1));
                    $actualEndTime = Carbon::parse($readings[$endIndex]->recorded_at);
                    
                    $currentCycle['end_time'] = $actualEndTime->toIso8601String();
                    $currentCycle['duration_minutes'] = Carbon::parse($currentCycle['start_time'])->diffInMinutes($actualEndTime);
                    $currentCycle['peak_kw'] = !empty($currentCycle['points']) ? max($currentCycle['points']) : 0;
                    $currentCycle['avg_kw'] = !empty($currentCycle['points']) ? array_sum($currentCycle['points']) / count($currentCycle['points']) : 0;
                    $currentCycle['energy_estimate_kwh'] = $currentCycle['avg_kw'] * ($currentCycle['duration_minutes'] / 60);
                    
                    unset($currentCycle['points']);
                    $cycles[] = $currentCycle;
                    
                    $lastCycleEndTime = $actualEndTime;
                    $currentCycle = null;
                    
                    for($i = $endIndex + 1; $i <= $index; $i++) {
                        $idlePoints[] = $readings[$i]->power_kw;
                    }
                } else {
                    if ($currentCycle) $currentCycle['points'][] = $reading->power_kw;
                }
            }
        }

        // Finalize open cycle
        if ($currentCycle && $currentState === 'RUN') {
            $lastReading = $readings->last();
            $currentCycle['end_time'] = Carbon::parse($lastReading->recorded_at)->toIso8601String();
            $currentCycle['duration_minutes'] = Carbon::parse($currentCycle['start_time'])->diffInMinutes(Carbon::parse($currentCycle['end_time']));
            $currentCycle['peak_kw'] = !empty($currentCycle['points']) ? max($currentCycle['points']) : 0;
            $currentCycle['avg_kw'] = !empty($currentCycle['points']) ? array_sum($currentCycle['points']) / count($currentCycle['points']) : 0;
            $currentCycle['energy_estimate_kwh'] = $currentCycle['avg_kw'] * ($currentCycle['duration_minutes'] / 60);
            unset($currentCycle['points']);
            $cycles[] = $currentCycle;
        }

        return $this->formatResponse($cycles, $threshold, $thresholdSource);
    }

    private function calculateStableThreshold($readings)
    {
        $powers = $readings->pluck('power_kw')->sort()->values();
        $count = $powers->count();

        if ($count < 10) {
            return [
                'threshold' => $powers->max() * 0.20,
                'source' => 'fallback_max'
            ];
        }

        // P80 Percentile
        $p80Index = ceil($count * 0.8) - 1;
        $p80Value = $powers[$p80Index];

        return [
            'threshold' => $p80Value * 0.20,
            'source' => 'percentile_p80'
        ];
    }

    private function calculateIdleLeakage($points, $start, $end, $rate, $baseline)
    {
        if (empty($points) || !$start || !$end) {
            return ['avg_kw' => 0, 'waste_kw' => 0, 'energy_loss_kwh' => 0, 'cost_loss' => 0];
        }

        $avgKw = array_sum($points) / count($points);
        $wasteKw = max(0, $avgKw - $baseline);
        $durationHours = $start->diffInMinutes($end) / 60;
        $energyLoss = $wasteKw * $durationHours;
        
        return [
            'avg_kw' => round($avgKw, 2),
            'waste_kw' => round($wasteKw, 2),
            'energy_loss_kwh' => round($energyLoss, 2),
            'cost_loss' => round($energyLoss * $rate, 0)
        ];
    }

    private function formatResponse($cycles, $threshold, $thresholdSource)
    {
        if (empty($cycles)) {
            return $this->emptyResponse($threshold, $thresholdSource);
        }

        $durations = collect($cycles)->pluck('duration_minutes');
        $mean = $durations->avg();
        $stdDev = $this->stdDev($durations->toArray(), $mean);
        
        $idleGaps = collect($cycles)->pluck('idle_gap_minutes')->filter();
        $avgGap = $idleGaps->avg();

        foreach ($cycles as &$cycle) {
            // Statistical Anomaly (Z-Score)
            $zScore = $stdDev > 0 ? ($cycle['duration_minutes'] - $mean) / $stdDev : 0;
            $cycle['z_score'] = round($zScore, 2);
            $cycle['abnormal'] = $zScore > 2;
            $cycle['anomaly_reason'] = $cycle['abnormal'] ? 'cycle_duration_statistical_outlier' : null;

            // Operator Suspicion Score
            $suspicion = $this->calculateSuspicion($cycle, $mean, $avgGap);
            $cycle['suspicion_score'] = $suspicion['score'];
            $cycle['suspicion_level'] = $suspicion['level'];
        }

        $summary = [
            'total_cycles' => count($cycles),
            'avg_duration' => round($mean, 1),
            'longest_cycle' => round($durations->max(), 1),
            'shortest_cycle' => round($durations->min(), 1),
            'avg_idle_gap' => round($avgGap, 1),
            'total_idle_loss_kwh' => round(collect($cycles)->sum('idle_energy_loss_kwh'), 2),
            'total_idle_cost_loss' => round(collect($cycles)->sum('idle_cost_loss'), 0)
        ];

        return [
            'threshold' => round($threshold, 2),
            'threshold_source' => $thresholdSource,
            'summary' => $summary,
            'cycles' => $cycles
        ];
    }

    private function calculateSuspicion($cycle, $avgDuration, $avgGap)
    {
        $score = 0;
        
        // 1. Duration deviation (max 40 pts)
        if ($cycle['duration_minutes'] > $avgDuration) {
            $score += min(40, (($cycle['duration_minutes'] / $avgDuration) - 1) * 100);
        }

        // 2. Idle Gap deviation (max 30 pts)
        if ($cycle['idle_gap_minutes'] > $avgGap) {
            $score += min(30, (($cycle['idle_gap_minutes'] / $avgGap) - 1) * 50);
        }

        // 3. Waste severity (max 30 pts)
        if ($cycle['waste_kw'] > 5) {
            $score += min(30, ($cycle['waste_kw'] / 2));
        }

        $score = round($score);
        
        $level = 'normal';
        if ($score > 80) $level = 'critical';
        elseif ($score > 60) $level = 'suspicious';
        elseif ($score > 30) $level = 'watch';

        return ['score' => $score, 'level' => $level];
    }

    private function stdDev($data, $mean)
    {
        if (count($data) < 2) return 0;
        $sumSq = 0;
        foreach ($data as $val) {
            $sumSq += pow($val - $mean, 2);
        }
        return sqrt($sumSq / (count($data) - 1));
    }

    private function emptyResponse($threshold = 0, $source = 'none')
    {
        return [
            'threshold' => round($threshold, 2),
            'threshold_source' => $source,
            'summary' => [
                'total_cycles' => 0,
                'avg_duration' => 0,
                'longest_cycle' => 0,
                'shortest_cycle' => 0,
                'avg_idle_gap' => 0
            ],
            'cycles' => []
        ];
    }
}
