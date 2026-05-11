<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\OperationalEventTag;
use App\Models\PowerReadingRaw;
use App\Models\ElectricityTariff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OperationalEventTagController extends Controller
{
    public function index(Request $request, $deviceId)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        $query = OperationalEventTag::where('device_id', $deviceId)
            ->with(['tagger', 'editor'])
            ->orderBy('event_time', 'asc');

        if ($start) {
            $query->where('event_time', '>=', Carbon::parse($start)->setTimezone('Asia/Jakarta'));
        }
        if ($end) {
            $query->where('event_time', '<=', Carbon::parse($end)->setTimezone('Asia/Jakarta'));
        }

        $tags = $query->get()->map(function($tag) {
            $tag->event_time = Carbon::parse($tag->event_time)->setTimezone('Asia/Jakarta')->toIso8601String();
            return $tag;
        });

        return response()->json($tags);
    }

    private function validateSequence($deviceId, $eventTime, $eventType, $excludeId = null, $force = false)
    {
        $query = OperationalEventTag::where('device_id', $deviceId)
            ->where('event_time', '<', $eventTime)
            ->orderBy('event_time', 'desc');
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $previousTag = $query->first();

        if (!$previousTag) {
            if ($eventType !== 'start') {
                return ['status' => 'INVALID', 'message' => 'The first event must be a "start" tag.'];
            }
        } else {
            if ($previousTag->event_type === $eventType) {
                return ['status' => 'INVALID', 'message' => 'Cannot repeat the same event type consecutively.'];
            }

            if ($eventType === 'pour' && !in_array($previousTag->event_type, ['melting', 'test'])) {
                if (!$force) return ['status' => 'VALID_WITH_WARNING', 'message' => 'Pour event typically follows melting or test. This breaks the expected sequence.'];
            }

            if ($previousTag->event_type === 'end' && $eventType !== 'start') {
                if (!$force) return ['status' => 'VALID_WITH_WARNING', 'message' => 'No events allowed after an "end" tag unless starting a new operation.'];
            }
        }
        
        return ['status' => 'VALID'];
    }

    public function store(Request $request, $deviceId)
    {
        $validated = $request->validate([
            'event_type' => 'required|in:start,melting,idle,test,pour,end',
            'event_time' => 'required|date',
            'notes' => 'nullable|string',
            'shift' => 'nullable|string',
            'source_reference' => 'nullable|string',
            'force' => 'nullable|boolean',
            'verification_status' => 'nullable|in:verified,adjusted,estimated'
        ]);

        $eventTime = Carbon::parse($validated['event_time'])->setTimezone('Asia/Jakarta');

        $exists = OperationalEventTag::where('device_id', $deviceId)
            ->where('event_time', $eventTime)
            ->exists();

        if ($exists) {
            return response()->json(['status' => 'INVALID', 'message' => 'An event already exists at this exact timestamp.'], 422);
        }

        $validation = $this->validateSequence($deviceId, $eventTime, $validated['event_type'], null, $validated['force'] ?? false);
        if ($validation['status'] !== 'VALID') {
            return response()->json($validation, 422);
        }

        $tag = OperationalEventTag::create([
            'device_id' => $deviceId,
            'tagged_by' => auth()->id() ?? 1,
            'event_type' => $validated['event_type'],
            'event_time' => $eventTime,
            'notes' => $validated['notes'] ?? '',
            'shift' => $validated['shift'] ?? '1',
            'source_reference' => $validated['source_reference'] ?? null,
            'verification_status' => $validated['verification_status'] ?? 'verified',
        ]);

        $tag->event_time = Carbon::parse($tag->event_time)->setTimezone('Asia/Jakarta')->toIso8601String();
        return response()->json($tag->load(['tagger', 'editor']), 201);
    }

    public function update(Request $request, $id)
    {
        $tag = OperationalEventTag::findOrFail($id);

        $validated = $request->validate([
            'event_type' => 'required|in:start,melting,idle,test,pour,end',
            'event_time' => 'required|date',
            'notes' => 'nullable|string',
            'shift' => 'nullable|string',
            'revision_notes' => 'nullable|string',
            'force' => 'nullable|boolean',
            'verification_status' => 'nullable|in:verified,adjusted,estimated'
        ]);

        $eventTime = Carbon::parse($validated['event_time'])->setTimezone('Asia/Jakarta');

        if ($tag->event_time->ne($eventTime)) {
            $exists = OperationalEventTag::where('device_id', $tag->device_id)
                ->where('id', '!=', $id)
                ->where('event_time', $eventTime)
                ->exists();

            if ($exists) {
                return response()->json(['status' => 'INVALID', 'message' => 'An event already exists at this exact timestamp.'], 422);
            }
        }

        $validation = $this->validateSequence($tag->device_id, $eventTime, $validated['event_type'], $id, $validated['force'] ?? false);
        if ($validation['status'] !== 'VALID') {
            return response()->json($validation, 422);
        }

        $tag->update([
            'event_type' => $validated['event_type'],
            'event_time' => $eventTime,
            'notes' => $validated['notes'] ?? '',
            'shift' => $validated['shift'] ?? $tag->shift,
            'edited_by' => auth()->id() ?? 1,
            'edited_at' => now()->setTimezone('Asia/Jakarta'),
            'revision_notes' => $validated['revision_notes'],
            'verification_status' => $validated['verification_status'] ?? 'adjusted',
        ]);

        $tag->event_time = Carbon::parse($tag->event_time)->setTimezone('Asia/Jakarta')->toIso8601String();
        return response()->json($tag->load(['tagger', 'editor']));
    }

    public function destroy($id)
    {
        $tag = OperationalEventTag::findOrFail($id);
        $tag->delete();
        return response()->json(['message' => 'Tag deleted']);
    }

    public function phases(Request $request, $deviceId)
    {
        $start = $request->query('start') ? Carbon::parse($request->query('start'))->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta')->subHours(12);
        $end = $request->query('end') ? Carbon::parse($request->query('end'))->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        $query = OperationalEventTag::where('device_id', $deviceId)->orderBy('event_time', 'asc');
        
        // Include one tag before start to catch an ongoing phase
        $beforeTag = OperationalEventTag::where('device_id', $deviceId)
            ->where('event_time', '<', $start)
            ->orderBy('event_time', 'desc')
            ->first();
            
        $query->where('event_time', '>=', $beforeTag ? $beforeTag->event_time : $start);
        $query->where('event_time', '<=', $end);

        $tags = $query->get();
        $phases = [];
        $totalTags = count($tags);

        $getHumanReadablePhase = function($eventType) {
            return match($eventType) {
                'start' => 'Pre-Heating',
                'melting' => 'Melting Phase',
                'idle' => 'Idle / Holding',
                'test' => 'Testing',
                'pour' => 'Pouring',
                'end' => 'Finished',
                default => 'Unknown'
            };
        };

        for ($i = 0; $i < $totalTags; $i++) {
            $currentTag = $tags[$i];
            
            $isLastTag = ($i === $totalTags - 1);
            if ($isLastTag && $currentTag->event_type === 'end') {
                continue; // End tags don't start a phase
            }

            $nextTag = $isLastTag ? null : $tags[$i + 1];
            $phaseEnd = $nextTag ? $nextTag->event_time : $end;
            $status = $nextTag ? 'CLOSED' : 'OPEN';

            $durationMinutes = $currentTag->event_time->diffInMinutes($phaseEnd);

            $readings = PowerReadingRaw::where('device_id', $deviceId)
                ->where('recorded_at', '>=', $currentTag->event_time)
                ->where('recorded_at', '<', $phaseEnd)
                ->get();

            $avgKw = 0; $peakKw = 0; $usageKwh = 0; $estCost = 0;

            if ($readings->count() > 0) {
                $avgKw = $readings->avg('active_power_kw');
                $peakKw = $readings->max('active_power_kw');

                $tariff = ElectricityTariff::where('is_active', true)->first();
                $rate = $tariff ? $tariff->rate_per_kwh : 0;

                $firstReading = $readings->first();
                $lastReading = $readings->last();

                if ($firstReading->meter_kwh_raw && $lastReading->meter_kwh_raw) {
                    $usageKwh = max(0, $lastReading->meter_kwh_raw - $firstReading->meter_kwh_raw);
                } else {
                    $usageKwh = $avgKw * ($durationMinutes / 60);
                }

                $estCost = collect($readings)->reduce(function ($carry, $reading) use ($tariff) {
                    return $carry; 
                }, 0);
                
                $estCost = $usageKwh * $rate;
            }

            $phases[] = [
                'start_time' => $currentTag->event_time->setTimezone('Asia/Jakarta')->toIso8601String(),
                'end_time' => $phaseEnd->setTimezone('Asia/Jakarta')->toIso8601String(),
                'phase' => $currentTag->event_type,
                'phase_name' => $getHumanReadablePhase($currentTag->event_type),
                'status' => $status,
                'duration_minutes' => $durationMinutes,
                'avg_kw' => round($avgKw, 2),
                'peak_kw' => round($peakKw, 2),
                'usage_kwh' => round($usageKwh, 2),
                'est_cost' => round($estCost, 2)
            ];
        }

        return response()->json($phases);
    }
}
