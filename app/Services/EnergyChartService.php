<?php

namespace App\Services;

use App\Models\PowerReadingRaw;
use App\Models\PowerReadingHourly;
use App\Models\PowerReadingDaily;
use Carbon\Carbon;

class EnergyChartService
{
    /**
     * Mengambil data chart menggunakan strategi Resolution Ladder.
     */
    public function getChartData(int $deviceId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->setTimezone(config('app.timezone'));
        $end = Carbon::parse($endDate)->setTimezone(config('app.timezone'));
        
        $diffInHours = $start->diffInHours($end);
        $diffInDays = $start->diffInDays($end);

        // Tier 1: RAW (0 - 48 hours)
        if ($diffInHours <= 48) {
            return $this->queryRaw($deviceId, $start, $end);
        } 
        // Tier 2: HOURLY (> 48 hours - 60 days)
        elseif ($diffInDays <= 60) {
            return $this->queryHourly($deviceId, $start, $end);
        } 
        // Tier 3: DAILY (> 60 days)
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
        });

        return $this->formatResponse('power_readings_raw', 'minute', $points);
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
        });

        return $this->formatResponse('power_readings_hourly', 'hourly', $points);
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
        });

        return $this->formatResponse('power_readings_daily', 'daily', $points);
    }

    private function formatResponse(string $source, string $resolution, $points): array
    {
        return [
            'metadata' => [
                'source' => $source,
                'resolution' => $resolution,
                'total_points' => $points->count()
            ],
            'data' => $points
        ];
    }
}
