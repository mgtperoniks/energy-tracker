<?php

namespace App\Services;

use App\Models\PowerReadingRaw;
use App\Models\PowerReadingHourly;
use App\Models\PowerReadingDaily;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class EnergyChartService
{
    /**
     * Mengambil data chart menggunakan strategi Resolution Ladder.
     */
    public function getChartData(int $deviceId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->setTimezone(config('app.timezone'));
        $end = Carbon::parse($endDate)->setTimezone(config('app.timezone'));
        
        // Cap max range to 180 days for performance safety
        if ($start->diffInDays($end) > 180) {
            $start = $end->copy()->subDays(180);
        }

        $diffInDays = $start->diffInDays($end);

        // Smart Source Selection
        // 1. RAW: <= 7 Days
        if ($diffInDays <= 7) {
            return $this->queryRaw($deviceId, $start, $end);
        } 
        // 2. HOURLY: <= 30 Days
        elseif ($diffInDays <= 30) {
            return $this->queryHourly($deviceId, $start, $end);
        } 
        // 3. DAILY: > 30 Days (max 180d)
        else {
            return $this->queryDaily($deviceId, $start, $end);
        }
    }

    private function queryRaw(int $deviceId, Carbon $start, Carbon $end): array
    {
        $data = PowerReadingRaw::where('device_id', $deviceId)
            ->whereBetween('recorded_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->select('recorded_at', 'kwh_total', 'power_kw', 'voltage', 'current', 'power_factor')
            ->orderBy('recorded_at', 'asc')
            ->limit(3000)
            ->get();

        $points = $data->map(function ($item) {
            return [
                'timestamp' => $item->recorded_at->toIso8601String(),
                'kwh_total' => $item->kwh_total !== null ? (float) $item->kwh_total : null,
                'kwh_usage' => null, // Raw tidak punya usage
                'power_kw'  => $item->power_kw !== null ? (float) $item->power_kw : null,
                'voltage'   => $item->voltage !== null ? (float) $item->voltage : null,
                'current'   => $item->current !== null ? (float) $item->current : null,
                'power_factor' => $item->power_factor !== null ? (float) $item->power_factor : null,
            ];
        })->toArray();

        return $this->formatResponse('power_readings_raw', 'minute', $this->normalizeTimeline($points, $start, $end, 'minute'));
    }

    private function queryHourly(int $deviceId, Carbon $start, Carbon $end): array
    {
        $data = PowerReadingHourly::where('device_id', $deviceId)
            ->whereBetween('recorded_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->select('recorded_at', 'kwh_total', 'kwh_usage', 'avg_power_kw', 'avg_voltage', 'avg_current', 'avg_power_factor')
            ->orderBy('recorded_at', 'asc')
            ->limit(2000)
            ->get();

        $points = $data->map(function ($item) {
            return [
                'timestamp' => $item->recorded_at->toIso8601String(),
                'kwh_total' => $item->kwh_total !== null ? (float) $item->kwh_total : null,
                'kwh_usage' => $item->kwh_usage !== null ? (float) $item->kwh_usage : null,
                'power_kw'  => $item->avg_power_kw !== null ? (float) $item->avg_power_kw : null,
                'voltage'   => $item->avg_voltage !== null ? (float) $item->avg_voltage : null,
                'current'   => $item->avg_current !== null ? (float) $item->avg_current : null,
                'power_factor' => $item->avg_power_factor !== null ? (float) $item->avg_power_factor : null,
            ];
        })->toArray();

        return $this->formatResponse('power_readings_hourly', 'hourly', $this->normalizeTimeline($points, $start, $end, 'hourly'));
    }

    private function queryDaily(int $deviceId, Carbon $start, Carbon $end): array
    {
        $data = PowerReadingDaily::where('device_id', $deviceId)
            ->whereBetween('recorded_date', [$start->toDateString(), $end->toDateString()])
            ->select('recorded_date', 'kwh_total', 'kwh_usage', 'avg_power_kw', 'avg_voltage', 'avg_current', 'avg_power_factor')
            ->orderBy('recorded_date', 'asc')
            ->limit(4000)
            ->get();

        $points = $data->map(function ($item) {
            return [
                'timestamp' => Carbon::parse($item->recorded_date)->startOfDay()->toIso8601String(),
                'kwh_total' => $item->kwh_total !== null ? (float) $item->kwh_total : null,
                'kwh_usage' => $item->kwh_usage !== null ? (float) $item->kwh_usage : null,
                'power_kw'  => $item->avg_power_kw !== null ? (float) $item->avg_power_kw : null,
                'voltage'   => $item->avg_voltage !== null ? (float) $item->avg_voltage : null,
                'current'   => $item->avg_current !== null ? (float) $item->avg_current : null,
                'power_factor' => $item->avg_power_factor !== null ? (float) $item->avg_power_factor : null,
            ];
        })->toArray();

        return $this->formatResponse('power_readings_daily', 'daily', $this->normalizeTimeline($points, $start, $end, 'daily'));
    }

    private function formatResponse(string $source, string $resolution, array $points): array
    {
        return [
            'metadata' => [
                'source' => $source,
                'resolution' => $resolution,
                'total_points' => count($points)
            ],
            'data' => $points
        ];
    }

    /**
     * Melakukan normalisasi timeline agar rentang waktu dari start sampai end
     * terisi penuh, baik dengan data aktual maupun null.
     */
    private function normalizeTimeline(array $points, Carbon $start, Carbon $end, string $resolution): array
    {
        $interval = match($resolution) {
            'minute' => '1 minute',
            'hourly' => '1 hour',
            'daily'  => '1 day',
            default  => '1 minute',
        };

        $period = CarbonPeriod::create($start, $interval, $end);
        
        // Buat lookup map berdasarkan key normalisasi
        $map = [];
        foreach ($points as $point) {
            $key = $this->getNormalizationKey($point['timestamp'], $resolution);
            $map[$key] = $point;
        }

        $normalized = [];
        foreach ($period as $date) {
            $key = $this->getNormalizationKey($date, $resolution);
            
            if (isset($map[$key])) {
                // Gunakan timestamp asli dari DB untuk presisi
                $normalized[] = $map[$key];
            } else {
                // Buat baris null untuk slot yang kosong
                $normalized[] = [
                    'timestamp' => $date->toIso8601String(),
                    'kwh_total' => null,
                    'kwh_usage' => null,
                    'power_kw'  => null,
                    'voltage'   => null,
                    'current'   => null,
                    'power_factor' => null,
                ];
            }
        }

        return $normalized;
    }

    private function getNormalizationKey($timestamp, string $resolution): string
    {
        $date = $timestamp instanceof Carbon ? $timestamp : Carbon::parse($timestamp);
        
        return match($resolution) {
            'minute' => $date->format('Y-m-d H:i'),
            'hourly' => $date->format('Y-m-d H:00'),
            'daily'  => $date->format('Y-m-d'),
            default  => $date->format('Y-m-d H:i'),
        };
    }
}
