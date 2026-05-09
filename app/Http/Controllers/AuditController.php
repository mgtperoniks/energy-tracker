<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\AuditExcelExport;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $viewMode = $request->query('view_mode', 'flat'); // flat or grouped
        
        $query = AuditLog::with('device.machine');

        // Apply Filters
        $query = $this->applyFilters($query, $request);

        if ($viewMode === 'grouped') {
            // Group by fingerprint and status to see recurring issues
            // Fix: Use the existing filtered query object instead of starting fresh
            $logs = $query->select(
                    'fingerprint',
                    'event_code',
                    'event_type',
                    'severity',
                    'title',
                    'status',
                    'device_id',
                    DB::raw('count(*) as incident_count'),
                    DB::raw('min(detected_at) as first_seen'),
                    DB::raw('max(detected_at) as last_seen')
                )
                ->groupBy('fingerprint', 'event_code', 'event_type', 'severity', 'title', 'status', 'device_id')
                ->orderByDesc('last_seen')
                ->paginate(50);
        } else {
            $logs = $query->orderByDesc('detected_at')->paginate(50);
        }

        $devices = Device::all();
        $summary = $this->getKpiSummary();

        return view('admin.audit_logs', compact('logs', 'devices', 'summary', 'viewMode'));
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('start_date')) {
            $query->whereDate('detected_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('detected_at', '<=', $request->end_date);
        }
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }
        if ($request->filled('event_code')) {
            $query->where('event_code', $request->event_code);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('source_layer')) {
            $query->where('source_layer', $request->source_layer);
        }

        return $query;
    }

    private function getKpiSummary()
    {
        $last7Days = Carbon::now()->subDays(7);
        
        $topDevice = AuditLog::select('device_id', DB::raw('count(*) as count'))
            ->groupBy('device_id')
            ->orderByDesc('count')
            ->with('device')
            ->first();

        return [
            'critical_open' => AuditLog::where('status', AuditLog::STATUS_OPEN)->where('severity', AuditLog::SEVERITY_CRITICAL)->count(),
            'error_open'    => AuditLog::where('status', AuditLog::STATUS_OPEN)->where('severity', AuditLog::SEVERITY_ERROR)->count(),
            'acknowledged'  => AuditLog::where('status', AuditLog::STATUS_ACKNOWLEDGED)->count(),
            'today_incidents' => AuditLog::whereDate('detected_at', Carbon::today())->count(),
            'resolved_today'  => AuditLog::whereDate('resolved_at', Carbon::today())->count(),
            'mttr_minutes'    => round(AuditLog::where('status', AuditLog::STATUS_RESOLVED)
                                    ->where('resolved_at', '>=', $last7Days)
                                    ->avg('duration_minutes') ?? 0, 1),
            'mtta_minutes'    => round(AuditLog::whereNotNull('acknowledged_at')
                                    ->where('detected_at', '>=', $last7Days)
                                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, detected_at, acknowledged_at)) as avg_mtta')
                                    ->first()->avg_mtta ?? 0, 1),
            'top_device'      => $topDevice?->device?->name ?? 'System'
        ];
    }

    public function acknowledge(AuditLog $log)
    {
        // Lifecycle Guard: Only allow open -> acknowledged
        if ($log->status !== AuditLog::STATUS_OPEN) {
            return back()->with('warning', 'Incident is already ' . strtoupper($log->status) . ' or cannot be acknowledged.');
        }

        $log->update([
            'status' => AuditLog::STATUS_ACKNOWLEDGED,
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);

        $log->logEvent('acknowledged');

        return back()->with('success', 'Incident ACKNOWLEDGED.');
    }

    public function resolve(AuditLog $log, Request $request)
    {
        // Lifecycle Guard: allowed only from open or acknowledged
        if (!in_array($log->status, [AuditLog::STATUS_OPEN, AuditLog::STATUS_ACKNOWLEDGED])) {
            return back()->with('warning', 'Incident cannot be resolved from ' . strtoupper($log->status) . ' status.');
        }

        $log->resolve($request->input('root_cause', 'Investigated and resolved'));
        return back()->with('success', 'Incident marked as RESOLVED.');
    }

    public function ignore(AuditLog $log)
    {
        // Lifecycle Guard: allowed only from open or acknowledged
        if (!in_array($log->status, [AuditLog::STATUS_OPEN, AuditLog::STATUS_ACKNOWLEDGED])) {
            return back()->with('warning', 'Incident cannot be ignored from ' . strtoupper($log->status) . ' status.');
        }

        $log->update(['status' => AuditLog::STATUS_IGNORED]);

        $log->logEvent('ignored');

        return back()->with('success', 'Incident IGNORED.');
    }

    public function reopen(AuditLog $log)
    {
        // Lifecycle Guard: allowed only from resolved or ignored
        if (!in_array($log->status, [AuditLog::STATUS_RESOLVED, AuditLog::STATUS_IGNORED])) {
            return back()->with('warning', 'Incident is already active or cannot be reopened.');
        }

        $log->update([
            'status' => AuditLog::STATUS_OPEN,
            'resolved_at' => null,
            'duration_minutes' => null,
            // Preserve original ACK history (acknowledged_at, acknowledged_by)
            'reopened_at' => now(),
            'reopened_by' => auth()->id(),
            'reopen_count' => $log->reopen_count + 1
        ]);

        $log->logEvent('reopened');

        return back()->with('success', 'Incident REOPENED.');
    }

    public function export(Request $request)
    {
        $format = $request->query('format', 'excel');
        $viewMode = $request->query('view_mode', 'flat');
        
        $query = AuditLog::with(['device', 'acknowledger']);
        $query = $this->applyFilters($query, $request);

        if ($viewMode === 'grouped') {
            $query->select(
                'fingerprint', 'event_code', 'event_type', 'severity', 'title', 'status', 'device_id',
                DB::raw('count(*) as incident_count'),
                DB::raw('min(detected_at) as first_seen'),
                DB::raw('max(detected_at) as last_seen')
            )->groupBy('fingerprint', 'event_code', 'event_type', 'severity', 'title', 'status', 'device_id')
            ->orderByDesc('last_seen');
        } else {
            $query->orderByDesc('detected_at');
        }

        $filename = 'audit_' . ($viewMode == 'grouped' ? 'summary_' : 'report_') . now()->format('Ymd_His');

        if ($format === 'pdf') {
            $data = $query->limit(501)->get();
            
            $summary = [
                'total' => $data->count(),
                'open'  => $data->where('status', 'open')->count(),
                'acknowledged' => $data->where('status', 'acknowledged')->count(),
                'resolved' => $data->where('status', 'resolved')->count(),
                'mtta' => $viewMode == 'flat' ? round($data->whereNotNull('acknowledged_at')->avg(function($log) {
                    return $log->detected_at->diffInMinutes($log->acknowledged_at);
                }) ?? 0, 1) : 'N/A',
                'mttr' => $viewMode == 'flat' ? round($data->whereNotNull('resolved_at')->avg('duration_minutes') ?? 0, 1) : 'N/A',
            ];

            $pdf = Pdf::loadView('exports.audit_pdf', [
                'logs' => $data,
                'summary' => $summary,
                'viewMode' => $viewMode
            ])->setPaper('a4', 'landscape');
            
            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(new AuditExcelExport($query, $viewMode), $filename . '.' . ($format === 'csv' ? 'csv' : 'xlsx'));
    }
}
