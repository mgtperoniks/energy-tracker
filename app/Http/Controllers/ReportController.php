<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\PowerReadingDaily;
use App\Models\PollerLog;
use App\Models\MeterReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function operational(Request $request)
    {
        $deviceId = $request->query('device_id');
        $startDate = $request->query('start_date', now()->subDays(7)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        
        $devices = Device::with('machine')->get();
        
        $query = PowerReadingDaily::with('device.machine')
            ->whereBetween('recorded_date', [$startDate, $endDate]);

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $reports = $query->orderBy('recorded_date', 'desc')->paginate(50);

        return view('analytics.operational', compact('reports', 'devices', 'deviceId', 'startDate', 'endDate'));
    }

    public function accounting(Request $request)
    {
        $deviceId = $request->query('device_id');
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        
        $devices = Device::with('machine')->get();

        $baseQuery = PowerReadingDaily::with('device.machine')
            ->whereBetween('recorded_date', [$startDate, $endDate]);

        if ($deviceId) {
            $baseQuery->where('device_id', $deviceId);
        }

        // Clone query to avoid builder reuse
        $reportsQuery = clone $baseQuery;
        $costQuery = clone $baseQuery;
        $rankingQuery = clone $baseQuery;

        $reports = $reportsQuery->orderBy('recorded_date', 'desc')->paginate(50);
        $totalCost = $costQuery->sum('energy_cost');
        
        // Top device cost ranking
        $topDevices = $rankingQuery->select('device_id', DB::raw('SUM(energy_cost) as total_device_cost'))
            ->groupBy('device_id')
            ->orderBy('total_device_cost', 'desc')
            ->limit(5)
            ->with('device.machine')
            ->get();

        return view('analytics.accounting', compact('reports', 'devices', 'deviceId', 'startDate', 'endDate', 'totalCost', 'topDevices'));
    }

    public function audit(Request $request)
    {
        $deviceId = $request->query('device_id');
        $startDate = $request->query('start_date', now()->subDays(7)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $severity = $request->query('severity'); 
        $eventType = $request->query('event_type'); // poller, reset, anomaly

        $devices = Device::with('machine')->get();
        
        // Poller Logs Query
        $logsQuery = PollerLog::with('device.machine')
            ->whereBetween('event_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
        if ($deviceId) $logsQuery->where('device_id', $deviceId);
        if ($severity) $logsQuery->where('status', $severity);
        
        if ($eventType === 'anomaly') {
            $logsQuery->where(function($q) {
                $q->where('message', 'like', '%anomaly%')
                  ->orWhere('message', 'like', '%Leakage%');
            });
        }
        
        // Meter Resets Query
        $resetsQuery = MeterReset::with('device.machine')
            ->whereBetween('reset_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
        if ($deviceId) $resetsQuery->where('device_id', $deviceId);

        $limit = 100;

        $logs = collect();
        if ($eventType !== 'reset') {
            $logs = $logsQuery->orderBy('event_at', 'desc')->limit($limit)->get();
        }

        $resets = collect();
        // Ignore resets if user explicitly filtering by severity (WARNING/ERROR usually implies poller logs)
        if ($eventType === 'reset' || (!$eventType && !$severity)) {
            $resets = $resetsQuery->orderBy('reset_at', 'desc')->limit($limit)->get();
        }

        // Merge and sort in-memory up to max 200 items
        $auditTrail = $logs->map(function($item) {
            $type = str_contains(strtolower($item->message), 'anomaly') ? 'Anomaly' : 'Poller Event';
            return (object) [
                'type' => $type,
                'timestamp' => $item->event_at,
                'device' => $item->device,
                'severity' => $item->status,
                'message' => $item->message,
            ];
        })->concat($resets->map(function($item) {
            return (object) [
                'type' => 'Meter Reset',
                'timestamp' => $item->reset_at,
                'device' => $item->device,
                'severity' => 'CRITICAL',
                'message' => "Hardware counter reset. Last kWh: {$item->kwh_at_reset}. Notes: {$item->notes}"
            ];
        }))->sortByDesc('timestamp')->values();

        return view('analytics.audit', compact('auditTrail', 'devices', 'deviceId', 'startDate', 'endDate', 'severity', 'eventType'));
    }
}
