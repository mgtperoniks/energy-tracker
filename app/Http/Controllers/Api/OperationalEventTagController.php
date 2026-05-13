<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\OperationalEventTag;
use App\Models\PowerReadingRaw;
use App\Models\ElectricityTariff;
use App\Models\TaggingAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OperationalEventTagController extends Controller
{
    private function canModifyTags()
    {
        $allowed = [
            'adminqcflange@peroniks.com',
            'adminqcfitting@peroniks.com'
        ];

        return auth()->check() && in_array(auth()->user()->email, $allowed);
    }

    public function index(Request $request, $deviceId)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        // Patch 6: Limit timeline render to latest 100 entries to prevent DOM bloat
        $query = OperationalEventTag::query()->with('tagger')
            ->where('device_id', $deviceId);

        if ($start && $end) {
            $query->whereBetween('event_time', [Carbon::parse($start)->setTimezone('Asia/Jakarta'), Carbon::parse($end)->setTimezone('Asia/Jakarta')]);
        }

        return response()->json($query->orderBy('event_time', 'desc')->limit(100)->get()->map(function($tag) {
            $tag->event_time_iso = Carbon::parse($tag->event_time)->setTimezone('Asia/Jakarta')->toIso8601String();
            return $tag;
        }));
    }

    private function validateSequence($deviceId, $eventTime, $eventType, $excludeId = null, $force = false)
    {
        $eventTimeStr = $eventTime instanceof Carbon ? $eventTime->format('Y-m-d H:i:s') : Carbon::parse($eventTime)->format('Y-m-d H:i:s');

        $query = OperationalEventTag::where('device_id', $deviceId)
            ->where('event_time', '<', $eventTimeStr)
            ->orderBy('event_time', 'desc');
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $previousTag = $query->first();

        $nextQuery = OperationalEventTag::where('device_id', $deviceId)
            ->where('event_time', '>', $eventTimeStr)
            ->orderBy('event_time', 'asc');
            
        if ($excludeId) {
            $nextQuery->where('id', '!=', $excludeId);
        }
        
        $nextTag = $nextQuery->first();

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

        if ($nextTag) {
            if ($nextTag->event_type === $eventType) {
                return ['status' => 'INVALID', 'message' => 'Cannot repeat the same event type consecutively with the next tag.'];
            }

            if ($nextTag->event_type === 'pour' && !in_array($eventType, ['melting', 'test'])) {
                if (!$force) return ['status' => 'VALID_WITH_WARNING', 'message' => 'The next event is pour, which typically follows melting or test. This breaks the expected sequence.'];
            }

            if ($eventType === 'end' && $nextTag->event_type !== 'start') {
                if (!$force) return ['status' => 'VALID_WITH_WARNING', 'message' => 'No events allowed after an "end" tag unless starting a new operation. The next tag is not start.'];
            }
        }
        
        return ['status' => 'VALID'];
    }

    public function store(Request $request, $deviceId)
    {
        if (!$this->canModifyTags()) {
            return response()->json(['error' => 'READONLY MODE: Unauthorized to create tags.'], 403);
        }

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
        $eventTimeStr = $eventTime->format('Y-m-d H:i:s');

        $exists = OperationalEventTag::where('device_id', $deviceId)
            ->where('event_time', $eventTimeStr)
            ->exists();

        if ($exists) {
            return response()->json(['status' => 'INVALID', 'message' => 'An event already exists at this exact timestamp.'], 422);
        }

        $validation = $this->validateSequence($deviceId, $eventTimeStr, $validated['event_type'], null, $validated['force'] ?? false);
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

        $this->logTagAudit('CREATE', $tag, null, $tag->toArray());

        $tag->event_time_iso = Carbon::parse($tag->event_time)->setTimezone('Asia/Jakarta')->toIso8601String();
        return response()->json($tag->load(['tagger', 'editor']), 201);
    }

    public function update(Request $request, $id)
    {
        $tag = OperationalEventTag::findOrFail($id);

        if (!$this->canModifyTags()) {
            return response()->json(['error' => 'READONLY MODE: Unauthorized to edit tags.'], 403);
        }

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
        $eventTimeStr = $eventTime->format('Y-m-d H:i:s');

        if ($tag->event_time->ne($eventTime)) {
            $exists = OperationalEventTag::where('device_id', $tag->device_id)
                ->where('id', '!=', $id)
                ->where('event_time', $eventTimeStr)
                ->exists();

            if ($exists) {
                return response()->json(['status' => 'INVALID', 'message' => 'An event already exists at this exact timestamp.'], 422);
            }
        }

        $validation = $this->validateSequence($tag->device_id, $eventTimeStr, $validated['event_type'], $id, $validated['force'] ?? false);
        if ($validation['status'] !== 'VALID') {
            return response()->json($validation, 422);
        }

        $oldValues = $tag->toArray();
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

        $this->logTagAudit('EDIT', $tag, $oldValues, $tag->toArray(), $validated['revision_notes']);

        $tag->event_time_iso = Carbon::parse($tag->event_time)->setTimezone('Asia/Jakarta')->toIso8601String();
        return response()->json($tag->load(['tagger', 'editor']));
    }

    public function destroy(Request $request, $id)
    {
        if (!$this->canModifyTags()) {
            return response()->json(['error' => 'READONLY MODE: Unauthorized to perform forensic deletions.'], 403);
        }

        // Patch 4: Strict Delete Governance (Reason Required)
        $validated = $request->validate([
            'reason' => 'required|string|min:10'
        ]);

        $tag = OperationalEventTag::findOrFail($id);
        $reason = $validated['reason'];

        $oldValues = $tag->toArray();
        $tag->update([
            'deleted_by' => auth()->id() ?? 1,
            'delete_reason' => $reason
        ]);
        
        $tag->delete();

        $this->logTagAudit('DELETE', $tag, $oldValues, null, $reason);

        return response()->json(['message' => 'Tag soft-deleted for audit safety.']);
    }

    public function phases(Request $request, $deviceId)
    {
        $start = $request->query('start') ? Carbon::parse($request->query('start'))->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta')->subHours(12);
        $end = $request->query('end') ? Carbon::parse($request->query('end'))->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        $phases = $this->getReconstructedPhases($deviceId, $start, $end);

        return response()->json(array_slice($phases, 0, 15));
    }

    private function getReconstructedPhases($deviceId, $start, $end)
    {
        $beforeTag = OperationalEventTag::where('device_id', $deviceId)
            ->where('event_time', '<', $start)
            ->orderBy('event_time', 'desc')
            ->first();

        $tags = OperationalEventTag::where('device_id', $deviceId)
            ->where('event_time', '>=', $beforeTag ? $beforeTag->event_time : $start)
            ->where('event_time', '<=', $end)
            ->orderBy('event_time', 'asc')
            ->get();
            
        $phases = [];
        $sortedTags = $tags->all();
        $totalTags = count($sortedTags);

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

        $tariff = ElectricityTariff::where('is_active', true)->first();
        $rate = $tariff ? $tariff->rate_per_kwh : 0;

        for ($i = 0; $i < $totalTags; $i++) {
            $currentTag = $sortedTags[$i];
            if ($currentTag->event_type === 'end') continue;

            $nextTag = ($i < $totalTags - 1) ? $sortedTags[$i + 1] : null;
            $phaseEnd = $nextTag ? $nextTag->event_time : $end;
            
            if ($phaseEnd->isFuture()) $phaseEnd = Carbon::now('Asia/Jakarta');
            
            $status = $nextTag ? 'CLOSED' : 'OPEN';
            $durationMinutes = max(0, $currentTag->event_time->diffInMinutes($phaseEnd));

            $metrics = DB::table('power_readings_raw')
                ->where('device_id', $deviceId)
                ->whereBetween('recorded_at', [$currentTag->event_time, $phaseEnd])
                ->selectRaw('AVG(power_kw) as avg_kw, MAX(power_kw) as peak_kw')
                ->first();

            $avgKw = max(0, $metrics->avg_kw ?? 0);
            $peakKw = max(0, $metrics->peak_kw ?? 0);
            $usageKwh = 0;

            $firstRead = DB::table('power_readings_raw')
                ->where('device_id', $deviceId)
                ->where('recorded_at', '>=', $currentTag->event_time)
                ->orderBy('recorded_at', 'asc')
                ->first();
            $lastRead = DB::table('power_readings_raw')
                ->where('device_id', $deviceId)
                ->where('recorded_at', '<=', $phaseEnd)
                ->orderBy('recorded_at', 'desc')
                ->first();
            
            if ($firstRead && $lastRead) {
                $usageKwh = max(0, $lastRead->kwh_total - $firstRead->kwh_total);
            } else {
                $usageKwh = max(0, $avgKw * ($durationMinutes / 60));
            }
            
            $estCost = max(0, $usageKwh * $rate);

            $phases[] = [
                'start_time' => $currentTag->event_time->setTimezone('Asia/Jakarta'),
                'end_time' => $phaseEnd->setTimezone('Asia/Jakarta'),
                'start_time_iso' => $currentTag->event_time->setTimezone('Asia/Jakarta')->toIso8601String(),
                'end_time_iso' => $phaseEnd->setTimezone('Asia/Jakarta')->toIso8601String(),
                'phase' => $currentTag->event_type,
                'phase_name' => $getHumanReadablePhase($currentTag->event_type),
                'status' => $status,
                'duration_minutes' => $durationMinutes,
                'duration_human' => $this->formatIndustrialDuration($durationMinutes),
                'avg_kw' => round($avgKw, 2),
                'peak_kw' => round($peakKw, 2),
                'usage_kwh' => round($usageKwh, 2),
                'est_cost' => round($estCost, 2)
            ];
        }

        return array_reverse($phases);
    }

    public function exportPhases(Request $request, $deviceId)
    {
        $start = $request->query('start') ? Carbon::parse($request->query('start')) : now()->subHours(12);
        $end = $request->query('end') ? Carbon::parse($request->query('end')) : now();
        $device = Device::findOrFail($deviceId);

        $phases = $this->getReconstructedPhases($deviceId, $start, $end);
        $filename = 'operational_phases_' . $device->name . '_' . now()->format('Ymd_Hi') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\OperationalPhasesExport($phases, $device, $start, $end),
            $filename
        );
    }

    public function exportPhasesPdf(Request $request, $deviceId)
    {
        $start = $request->query('start') ? Carbon::parse($request->query('start')) : now()->subHours(12);
        $end = $request->query('end') ? Carbon::parse($request->query('end')) : now();
        $device = Device::findOrFail($deviceId);

        $phases = $this->getReconstructedPhases($deviceId, $start, $end);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.operational_phases_pdf', [
            'phases' => $phases,
            'device' => $device,
            'start' => $start,
            'end' => $end
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('operational_report_' . $device->name . '_' . now()->format('Ymd_Hi') . '.pdf');
    }

    public function export(Request $request, $deviceId)
    {
        $start = $request->query('start', now()->subDays(7)->toDateTimeString());
        $end = $request->query('end', now()->toDateTimeString());

        $filename = 'operational_tags_audit_' . now()->format('Ymd_Hi') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\TagAuditExport($deviceId, $start, $end),
            $filename
        );
    }

    private function formatIndustrialDuration($minutes)
    {
        $minutes = (int)$minutes;
        if ($minutes < 60) return "{$minutes}m";
        $hours = floor($minutes / 60);
        $rem = $minutes % 60;
        return "{$hours}h {$rem}m";
    }

    private function logTagAudit($action, $tag, $old = null, $new = null, $reason = null)
    {
        try {
            $user = auth()->user();
            TaggingAuditLog::create([
                'user_id' => $user->id,
                'tag_id' => $tag->id,
                'action' => $action,
                'tag_type' => $tag->event_type,
                'old_values' => $old,
                'new_values' => $new,
                'reason' => "By: {$user->email} | Reason: " . ($reason ?? 'N/A'),
                'event_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error("Tagging Audit Failure: " . $e->getMessage());
        }
    }
}
