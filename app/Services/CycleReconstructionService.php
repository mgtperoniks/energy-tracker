<?php

namespace App\Services;

use App\Models\OperationalEventTag;
use App\Models\ElectricityTariff;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CycleReconstructionService
 *
 * Reconstructs production cycles from operational_event_tags event log.
 * A cycle = one Melting phase + one Pouring phase.
 * Boundary: melting[i].event_time → melting[i+1].event_time
 *
 * Query strategy: 4 constant queries per request (no N+1).
 *   Q1: beforeTag  — boundary continuity
 *   Q2: relevant tags (melting+pour) in window
 *   Q3: bulk raw readings (single query, in-memory kWh lookup)
 *   Q4: tariff rate
 */
class CycleReconstructionService
{
    // ─────────────────── PUBLIC API ───────────────────

    /**
     * Reconstruct all production cycles within a time window for a device.
     *
     * @param  int    $deviceId
     * @param  Carbon $start    User-selected window start
     * @param  Carbon $end      User-selected window end
     * @return array  Array of CycleDTO (see implementation_plan.md §3a)
     */
    public function reconstruct(int $deviceId, Carbon $start, Carbon $end): array
    {
        // Q1: Resolve window start via beforeTag strategy
        [$windowStart, $beforeTagInfo] = $this->resolveWindowStart($deviceId, $start);

        // Q2: Fetch relevant tags in window (melting + pour only)
        $tags = $this->fetchRelevantTags($deviceId, $windowStart, $end);

        if ($tags->isEmpty()) {
            return [];
        }

        // Q3: Bulk raw readings — one query for entire window
        $rawReadings = $this->fetchBulkRawReadings($deviceId, $windowStart, $end);

        // Q4: Tariff rate — single value for all cycles in this request
        $tariffRate = ElectricityTariff::getRateForDate($start->toDateString());

        // Separate into indexed collections for pairing
        $meltings = $tags->where('event_type', 'melting')->values();
        $pours    = $tags->where('event_type', 'pour')->values();

        if ($meltings->isEmpty()) {
            return [];
        }

        // Q5: Bulk query for production results (Actual Output & Return Material)
        // DILARANG melakukan query per-row. Seluruh data production_result diambil menggunakan SATU bulk query.
        $meltingIds = $meltings->pluck('id')->toArray();
        $productionResults = \App\Models\ProductionCycleResult::whereIn('melting_tag_id', $meltingIds)
            ->get()
            ->keyBy('melting_tag_id');

        $cycles      = [];
        $cycleNumber = 0;

        for ($i = 0; $i < $meltings->count(); $i++) {
            $meltingTag  = $meltings[$i];
            $nextMelting = $meltings->get($i + 1);

            $cycleStart = Carbon::parse($meltingTag->event_time)->setTimezone('Asia/Jakarta');

            // Find any end tag strictly after cycleStart and before nextMelting (or end of window)
            $endTag = $tags->first(function ($t) use ($cycleStart, $nextMelting, $end) {
                if ($t->event_type !== 'end') return false;
                $tTime = Carbon::parse($t->event_time);
                if ($nextMelting) {
                    return $tTime->gt($cycleStart) && $tTime->lt(Carbon::parse($nextMelting->event_time));
                }
                return $tTime->gt($cycleStart) && $tTime->lte($end);
            });

            // Determine cycle boundary end
            if ($endTag) {
                $cycleEnd      = Carbon::parse($endTag->event_time)->setTimezone('Asia/Jakarta');
                $isEnded       = true;
            } elseif ($nextMelting) {
                $cycleEnd      = Carbon::parse($nextMelting->event_time)->setTimezone('Asia/Jakarta');
                $isEnded       = true;
            } else {
                $cycleEnd      = $end->copy()->setTimezone('Asia/Jakarta');
                $isEnded       = false;
            }

            // Find first pour strictly between cycleStart and cycleEnd
            $pour = $pours->first(function ($p) use ($cycleStart, $cycleEnd) {
                $pt = Carbon::parse($p->event_time);
                return $pt->gt($cycleStart) && $pt->lt($cycleEnd);
            });

            $totalMinutes = max(0, (int) $cycleStart->diffInMinutes($cycleEnd));

            if ($pour === null) {
                // No pour between boundary
                // Check if downtime occurred during this cycle
                $hasDowntime = $tags->contains(function ($t) use ($cycleStart, $cycleEnd) {
                    return $t->event_type === 'downtime' &&
                        Carbon::parse($t->event_time)->gt($cycleStart) &&
                        Carbon::parse($t->event_time)->lt($cycleEnd);
                });

                if ($isEnded) {
                    $status = $hasDowntime ? 'ABORTED' : 'INCOMPLETE';
                } else {
                    $status = 'OPEN';
                }

                $pouringStart   = null;
                $meltingMinutes = $totalMinutes;
                $pouringMinutes = 0;
                $kwh            = 0.0;
                $estCost        = 0.0;
                $cycleNum       = null;   // INCOMPLETE/ABORTED has no sequence number
                $pourTagId      = null;
            } else {
                $status         = 'CLOSED';
                $pouringStart   = Carbon::parse($pour->event_time)->setTimezone('Asia/Jakarta');
                $meltingMinutes = max(0, (int) $cycleStart->diffInMinutes($pouringStart));
                $pouringMinutes = max(0, (int) $pouringStart->diffInMinutes($cycleEnd));
                $kwh            = $this->calculateKwh($rawReadings, $cycleStart, $cycleEnd);
                $estCost        = round($kwh * $tariffRate, 2);
                $cycleNumber++;
                $cycleNum  = $cycleNumber;
                $pourTagId = $pour->id;
            }

            // Fetch production result for this cycle from bulk collection
            $prod = $productionResults->get($meltingTag->id);
            $actualOutputKg = $prod ? $prod->actual_output_kg : null;
            $returnMaterialKg = $prod ? $prod->return_material_kg : null;
            $remark = $prod ? $prod->remark : null;

            $cycles[] = [
                // Identity
                'number'                 => $cycleNum,
                'melting_tag_id'         => $meltingTag->id,
                'pour_tag_id'            => $pourTagId,

                // Timestamps
                'cycle_start'            => $cycleStart,
                'pouring_start'          => $pouringStart,
                'cycle_end'              => $cycleEnd,

                // Durations (integer minutes)
                'melting_minutes'        => $meltingMinutes,
                'pouring_minutes'        => $pouringMinutes,
                'total_minutes'          => $totalMinutes,

                // Energy & Cost
                'kwh'                    => round($kwh, 3),
                'est_cost'               => $estCost,

                // Production Results
                'actual_output_kg'       => $actualOutputKg,
                'return_material_kg'     => $returnMaterialKg,
                'remark'                 => $remark,

                // Status (may be mutated to OUTLIER by detectOutliers)
                'status'                 => $status,
                'outlier_threshold'      => null,

                // Pre-formatted strings (ready for Blade)
                'total_duration_human'   => $this->formatDuration($totalMinutes),
                'melting_duration_human' => $this->formatDuration($meltingMinutes),
                'pouring_duration_human' => $pour ? $this->formatDuration($pouringMinutes) : '—',
                'is_cross_day'           => $cycleStart->toDateString() !== $cycleEnd->toDateString(),
                // Internal audit flag: cycle boundary crosses calendar midnight.
                // Physical process boundary takes precedence over calendar boundary.
                // Not displayed in UI — available for forensic audit only.
                'is_cross_midnight'      => $cycleStart->toDateString() !== $cycleEnd->toDateString(),
            ];
        }

        // Post-process: mark statistical outliers among CLOSED cycles
        $this->detectOutliers($cycles);

        return $cycles;
    }

    /**
     * Build KPI summary from cycles array.
     * Inklusi per KPI:
     *   - total_cycles, counters: all statuses
     *   - total kWh/cost/duration: CLOSED + OUTLIER + OPEN (not INCOMPLETE)
     *   - avg, fastest, slowest: CLOSED + OUTLIER only
     *   - tag_integrity_pct = CLOSED / (CLOSED + INCOMPLETE) × 100
     *
     * @param  array $cycles  Output of reconstruct()
     * @return array KpiSummaryDTO
     */
    public function buildKpiSummary(array $cycles): array
    {
        $closed     = array_values(array_filter($cycles, fn($c) => $c['status'] === 'CLOSED'));
        $outliers   = array_values(array_filter($cycles, fn($c) => $c['status'] === 'OUTLIER'));
        $open       = array_values(array_filter($cycles, fn($c) => $c['status'] === 'OPEN'));
        $incomplete = array_values(array_filter($cycles, fn($c) => $c['status'] === 'INCOMPLETE' || $c['status'] === 'ABORTED'));

        // OUTLIER is a flagged CLOSED — combine for "completed" aggregates
        $completed    = array_merge($closed, $outliers);
        $closedCount  = count($completed);
        $openCount    = count($open);
        $incCount     = count($incomplete);
        $outlierCount = count($outliers);
        $totalCount   = count($cycles);

        // Measurable cycles: CLOSED + OUTLIER + OPEN (exclude INCOMPLETE)
        $measurable = array_merge($completed, $open);

        $totalMeltingMinutes = (int) array_sum(array_column($measurable, 'melting_minutes'));
        $totalPouringMinutes = (int) array_sum(array_column($measurable, 'pouring_minutes'));
        $totalKwh            = round(array_sum(array_column($measurable, 'kwh')), 3);
        $totalCost           = round(array_sum(array_column($measurable, 'est_cost')), 2);

        // Per-cycle metrics from CLOSED + OUTLIER only
        $avgKwh            = null;
        $avgDuration       = null;
        $fastestCycle      = null;
        $slowestCycle      = null;
        $outlierThreshold  = null;

        if ($closedCount > 0) {
            $avgKwh    = round(array_sum(array_column($completed, 'kwh')) / $closedCount, 3);
            $durations = array_column($completed, 'total_minutes');
            $avgDuration = round(array_sum($durations) / $closedCount, 1);

            $minDur = min($durations);
            $maxDur = max($durations);

            foreach ($completed as $c) {
                if ($fastestCycle === null && $c['total_minutes'] === $minDur) {
                    $fastestCycle = [
                        'minutes'      => $minDur,
                        'cycle_number' => $c['number'],
                        'cycle_start'  => $c['cycle_start'],
                    ];
                }
                if ($slowestCycle === null && $c['total_minutes'] === $maxDur) {
                    $slowestCycle = [
                        'minutes'      => $maxDur,
                        'cycle_number' => $c['number'],
                        'cycle_start'  => $c['cycle_start'],
                    ];
                }
            }

            // Retrieve outlier threshold from first flagged cycle
            foreach ($cycles as $c) {
                if ($c['status'] === 'OUTLIER' && $c['outlier_threshold'] !== null) {
                    $outlierThreshold = $c['outlier_threshold'];
                    break;
                }
            }
        }

        // Tag Integrity %: Closed / (Closed + Incomplete) × 100
        $integrityDenom = $closedCount + $incCount;
        $tagIntegrityPct = $integrityDenom > 0
            ? round($closedCount / $integrityDenom * 100, 1)
            : null;

        return [
            // Counters
            'total_cycles'              => $totalCount,
            'closed_cycles'             => $closedCount,
            'open_cycles'               => $openCount,
            'incomplete_cycles'         => $incCount,
            'outlier_cycles'            => $outlierCount,
            'tag_integrity_pct'         => $tagIntegrityPct,

            // Aggregates
            'total_melting_minutes'     => $totalMeltingMinutes,
            'total_pouring_minutes'     => $totalPouringMinutes,
            'total_kwh'                 => $totalKwh,
            'total_cost'                => $totalCost,

            // Per-cycle (CLOSED only)
            'avg_kwh_per_cycle'         => $avgKwh,
            'avg_duration_minutes'      => $avgDuration,
            'fastest_cycle'             => $fastestCycle,
            'slowest_cycle'             => $slowestCycle,
            'outlier_threshold_minutes' => $outlierThreshold,

            // Pre-formatted
            'total_melting_human'       => $this->formatDuration($totalMeltingMinutes),
            'total_pouring_human'       => $this->formatDuration($totalPouringMinutes),
            'avg_duration_human'        => $avgDuration
                ? $this->formatDuration((int) round($avgDuration))
                : '—',
        ];
    }

    /**
     * Format integer minutes into industrial human-readable string.
     * 0 → "0m" | 45 → "45m" | 65 → "1h 5m" | 120 → "2h"
     */
    public function formatDuration(int $minutes): string
    {
        if ($minutes <= 0) return '0m';
        if ($minutes < 60) return "{$minutes}m";
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }

    // ─────────────────── PRIVATE HELPERS ───────────────────

    /**
     * Resolve effective window start via beforeTag strategy.
     *
     * Decision:
     *   beforeTag = null    → windowStart = $start
     *   beforeTag = melting → windowStart = beforeTag.event_time (shift back;
     *                         cycle started before user window)
     *   beforeTag = pour    → windowStart = $start (previous cycle already closed)
     *
     * @return array [Carbon $windowStart, array|null $info]
     */
    private function resolveWindowStart(int $deviceId, Carbon $start): array
    {
        $beforeTag = OperationalEventTag::where('device_id', $deviceId)
            ->where('event_time', '<', $start->toDateTimeString())
            ->whereIn('event_type', ['melting', 'pour'])
            ->whereNull('deleted_at')
            ->orderBy('event_time', 'desc')
            ->first(['id', 'event_type', 'event_time']);

        if ($beforeTag === null) {
            return [$start->copy(), null];
        }

        $info = [
            'type' => $beforeTag->event_type,
            'time' => $beforeTag->event_time,
            'id'   => $beforeTag->id,
        ];

        if ($beforeTag->event_type === 'melting') {
            $windowStart = Carbon::parse($beforeTag->event_time)->setTimezone('Asia/Jakarta');
            return [$windowStart, $info];
        }

        // beforeTag is pour → previous cycle is closed, start from $start
        return [$start->copy(), $info];
    }

    /**
     * Fetch tags filtered to melting + pour within the resolved window.
     * Excludes soft-deleted records.
     */
    private function fetchRelevantTags(int $deviceId, Carbon $windowStart, Carbon $end): Collection
    {
        return OperationalEventTag::where('device_id', $deviceId)
            ->where('event_time', '>=', $windowStart->toDateTimeString())
            ->where('event_time', '<=', $end->toDateTimeString())
            ->whereIn('event_type', ['start', 'melting', 'idle', 'test', 'pour', 'end', 'downtime'])
            ->whereNull('deleted_at')
            ->orderBy('event_time', 'asc')
            ->get(['id', 'event_type', 'event_time']);
    }

    /**
     * Fetch all raw readings in window (single bulk query).
     * kWh per cycle is computed in-memory from this collection.
     */
    private function fetchBulkRawReadings(int $deviceId, Carbon $windowStart, Carbon $end): Collection
    {
        return DB::table('power_readings_raw')
            ->where('device_id', $deviceId)
            ->where('recorded_at', '>=', $windowStart->toDateTimeString())
            ->where('recorded_at', '<=', $end->toDateTimeString())
            ->orderBy('recorded_at', 'asc')
            ->get(['recorded_at', 'kwh_total']);
    }

    /**
     * Calculate kWh consumed in a cycle window using pre-loaded bulk collection.
     * Zero additional DB queries.
     */
    private function calculateKwh(Collection $rawReadings, Carbon $from, Carbon $to): float
    {
        $fromStr = $from->toDateTimeString();
        $toStr   = $to->toDateTimeString();

        $inRange = $rawReadings->filter(
            fn($r) => $r->recorded_at >= $fromStr && $r->recorded_at <= $toStr
        );

        if ($inRange->isEmpty()) {
            return 0.0;
        }

        $firstKwh = (float) $inRange->first()->kwh_total;
        $lastKwh  = (float) $inRange->last()->kwh_total;

        return max(0.0, $lastKwh - $firstKwh);
    }

    /**
     * Post-processing: flag CLOSED cycles as OUTLIER using mean + 2×stddev.
     * Only activates when CLOSED cycle count >= 3.
     * Mutates $cycles array in-place via reference.
     */
    private function detectOutliers(array &$cycles): void
    {
        $closedIndices = [];
        $durations     = [];

        foreach ($cycles as $idx => $cycle) {
            if ($cycle['status'] === 'CLOSED') {
                $closedIndices[] = $idx;
                $durations[]     = $cycle['total_minutes'];
            }
        }

        if (count($durations) < 3) {
            return;
        }

        $count    = count($durations);
        $mean     = array_sum($durations) / $count;
        $variance = array_sum(array_map(fn($d) => ($d - $mean) ** 2, $durations)) / $count;
        $std      = sqrt($variance);
        $threshold = round($mean + (2 * $std), 1);

        foreach ($closedIndices as $idx) {
            if ($cycles[$idx]['total_minutes'] > $threshold) {
                $cycles[$idx]['status']            = 'OUTLIER';
                $cycles[$idx]['outlier_threshold'] = $threshold;
            }
        }
    }
}
