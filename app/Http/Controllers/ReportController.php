<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\PowerReadingDaily;
use App\Models\PowerReadingRaw;
use App\Models\ElectricityTariff;
use App\Models\PollerLog;
use App\Models\MeterReset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\OperationalReportExport;
use App\Exports\AccountingReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{

    public function operational(Request $request)
    {
        $deviceId = $request->query('device_id');
        $startDate = $request->query('start_date', now('Asia/Jakarta')->subDays(7)->toDateString());
        $endDate = $request->query('end_date', now('Asia/Jakarta')->toDateString());
        
        $devices = Device::with('machine')->get();
        
        $isFiltered = $request->has('start_date');
        $reports = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);

        if ($isFiltered) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            if ($start->greaterThan($end)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['date_range' => 'Tanggal mulai tidak boleh melebihi tanggal akhir.']);
            }

            if ($start->diffInDays($end) > 45) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['date_range' => 'Maksimal rentang laporan adalah 45 hari.']);
            }

            $query = $this->buildOperationalQuery($startDate, $endDate, $deviceId);
            $reports = $query->paginate(50)->withQueryString();

            // Live Hydration for rows that exist in daily table
            $reports->getCollection()->transform(function($report) {
                return $report->hydrateLive();
            });

            // Phase 2 Fallback: if today's stub is missing (scheduler was down),
            // synthesize in-memory rows from raw telemetry — read-only, no DB writes.
            if ((int) $request->query('page', 1) === 1) {
                $synthetic = $this->synthesizeMissingTodayRows($startDate, $endDate, $deviceId);
                if ($synthetic->isNotEmpty()) {
                    $reports->setCollection($synthetic->merge($reports->getCollection()));
                }
            }
        }

        $schedulerHealth = [
            'hourly' => \App\Models\SchedulerRun::where('job_name', 'energy:aggregate-hourly')->first(),
            'daily'  => \App\Models\SchedulerRun::where('job_name', 'energy:aggregate-daily')->first(),
        ];

        return view('analytics.operational', compact('reports', 'devices', 'deviceId', 'startDate', 'endDate', 'schedulerHealth', 'isFiltered'));
    }

    public function exportOperational(Request $request)
    {
        $deviceId = $request->query('device_id');
        $startDate = $request->query('start_date', now('Asia/Jakarta')->subDays(7)->toDateString());
        $endDate = $request->query('end_date', now('Asia/Jakarta')->toDateString());

        $filename = 'energy_operational_report_' . now('Asia/Jakarta')->format('Ymd_Hi') . '.xlsx';

        $query = $this->buildOperationalQuery($startDate, $endDate, $deviceId);

        // Fetch and Hydrate Collection explicitly
        $reports = $query->get()->each(function($row) {
            $row->hydrateLive();
        });

        return Excel::download(
            new OperationalReportExport($reports), 
            $filename
        );
    }

    public function exportOperationalPdf(Request $request)
    {
        $deviceId = $request->query('device_id');
        $startDate = $request->query('start_date', now('Asia/Jakarta')->subDays(7)->toDateString());
        $endDate = $request->query('end_date', now('Asia/Jakarta')->toDateString());

        $query = $this->buildOperationalQuery($startDate, $endDate, $deviceId);
        
        // Safety limit for PDF to prevent memory 500 error
        $reports = $query->limit(1000)->get();

        // Apply live hydration for current day records
        foreach ($reports as $report) {
            $report->hydrateLive();
        }

        $deviceName = 'All Devices';
        if ($deviceId) {
            $device = \App\Models\Device::find($deviceId);
            $deviceName = $device ? $device->name : 'All Devices';
        }

        $pdf = Pdf::loadView('exports.operational_pdf', compact('reports', 'startDate', 'endDate', 'deviceName'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream('energy_operational_report_' . now()->format('Ymd_Hi') . '.pdf');
    }

    private function buildOperationalQuery($startDate, $endDate, $deviceId = null)
    {
        $query = PowerReadingDaily::with('device.machine')
            ->whereBetween('recorded_date', [$startDate, $endDate]);

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        return $query->orderBy('recorded_date', 'desc');
    }


    public function accounting(Request $request)
    {
        $deviceId = $request->query('device_id');
        $startDate = $request->query('start_date', now('Asia/Jakarta')->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now('Asia/Jakarta')->toDateString());
        
        $devices = Device::with('machine')->get();
        $isFiltered = $request->has('start_date');

        $reports = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
        $totalCost = 0;
        $topDevices = collect();

        if ($isFiltered) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            if ($start->greaterThan($end)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['date_range' => 'Tanggal mulai tidak boleh melebihi tanggal akhir.']);
            }

            if ($start->diffInDays($end) > 45) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['date_range' => 'Rentang tanggal maksimal 45 hari. Silakan gunakan filter yang lebih spesifik.']);
            }

            $baseQuery = PowerReadingDaily::with('device.machine')
                ->whereBetween('recorded_date', [$startDate, $endDate]);

            if ($deviceId) {
                $baseQuery->where('device_id', $deviceId);
            }

            // Clone query to avoid builder reuse
            $reportsQuery = clone $baseQuery;
            $costQuery = clone $baseQuery;
            $rankingQuery = clone $baseQuery;

            $reports = $reportsQuery->orderBy('recorded_date', 'desc')->paginate(50)->withQueryString();

            // Live Hydration for rows that exist in daily table
            $reports->getCollection()->transform(function($report) {
                return $report->hydrateLive();
            });

            // Phase 2 Fallback: inject synthetic today rows if stub is missing
            if ((int) $request->query('page', 1) === 1) {
                $synthetic = $this->synthesizeMissingTodayRows($startDate, $endDate, $deviceId);
                if ($synthetic->isNotEmpty()) {
                    $reports->setCollection($synthetic->merge($reports->getCollection()));
                }
            }

            $totalCost = $costQuery->get()->sum(function($item) {
                return $item->hydrateLive()->energy_cost;
            });
            
            // Top device cost ranking
            $topDevices = $rankingQuery->select('device_id', DB::raw('SUM(energy_cost) as total_device_cost'))
                ->groupBy('device_id')
                ->orderBy('total_device_cost', 'desc')
                ->limit(5)
                ->with('device.machine')
                ->get();
        }

        return view('analytics.accounting', compact('reports', 'devices', 'deviceId', 'startDate', 'endDate', 'totalCost', 'topDevices', 'isFiltered'));
    }

    public function exportAccounting(Request $request)
    {
        $deviceId = $request->query('device_id');
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $query = PowerReadingDaily::with('device.machine')
            ->whereBetween('recorded_date', [$startDate, $endDate]);

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $filename = 'energy_accounting_report_' . now()->format('Ymd_Hi') . '.xlsx';

        return Excel::download(
            new AccountingReportExport($query->orderBy('recorded_date', 'desc')), 
            $filename
        );
    }

    public function exportAccountingPdf(Request $request)
    {
        $deviceId = $request->query('device_id');
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $query = PowerReadingDaily::with('device.machine')
            ->whereBetween('recorded_date', [$startDate, $endDate]);

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        // Safety limit for PDF to prevent memory 500 error
        $reports = $query->orderBy('recorded_date', 'desc')->limit(1000)->get();
        
        // Live Hydration for accuracy
        foreach ($reports as $report) {
            $report->hydrateLive();
        }

        $totalCost = $reports->sum('energy_cost');

        $deviceName = 'All Devices';
        if ($deviceId) {
            $device = \App\Models\Device::find($deviceId);
            $deviceName = $device ? $device->name : 'All Devices';
        }

        $pdf = Pdf::loadView('exports.accounting_pdf', compact('reports', 'startDate', 'endDate', 'deviceName', 'totalCost'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream('energy_accounting_report_' . now()->format('Ymd_Hi') . '.pdf');
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

    /**
     * PHASE 2 — Read-only live fallback.
     *
     * When the scheduler is down and today's daily stub row has not been created,
     * this method synthesizes in-memory PowerReadingDaily instances directly from
     * power_readings_raw for each device that is missing a today-row.
     *
     * CONTRACT:
     *   - NEVER writes to the database.
     *   - Only fires when today is within the requested date range.
     *   - Only synthesizes rows for devices that truly have no daily row today.
     *   - Uses a single grouped SQL query (no N+1).
     *   - Returns an empty collection if there is nothing to synthesize.
     *
     * @param  string   $startDate  Y-m-d
     * @param  string   $endDate    Y-m-d
     * @param  int|null $deviceId
     * @return \Illuminate\Support\Collection<PowerReadingDaily>
     */
    private function synthesizeMissingTodayRows(string $startDate, string $endDate, ?int $deviceId): \Illuminate\Support\Collection
    {
        $today = now('Asia/Jakarta')->toDateString();

        // Guard: only run when today falls inside the requested range
        if ($today < $startDate || $today > $endDate) {
            return collect();
        }

        // Determine which device IDs already have a daily row for today
        $existingQuery = PowerReadingDaily::where('recorded_date', $today);
        if ($deviceId) {
            $existingQuery->where('device_id', $deviceId);
        }
        $coveredDeviceIds = $existingQuery->pluck('device_id')->toArray();

        // Find active power-meter devices that are NOT yet covered
        $devicesQuery = Device::with('machine')
            ->where('type', 'power_meter')
            ->where('status', true);
        if ($deviceId) {
            $devicesQuery->where('id', $deviceId);
        }
        if (!empty($coveredDeviceIds)) {
            $devicesQuery->whereNotIn('id', $coveredDeviceIds);
        }
        $missingDevices = $devicesQuery->get();

        if ($missingDevices->isEmpty()) {
            return collect();
        }

        // Single grouped query — aggregate today's raw readings for all missing devices
        $missingIds = $missingDevices->pluck('id')->toArray();
        $liveRows = PowerReadingRaw::whereIn('device_id', $missingIds)
            ->whereDate('recorded_at', $today)
            ->selectRaw('
                device_id,
                MAX(kwh_total)                                AS live_kwh_total,
                GREATEST(MAX(kwh_total) - MIN(kwh_total), 0) AS live_kwh_usage,
                MAX(power_kw)                                 AS live_max_power,
                AVG(power_kw)                                 AS live_avg_power,
                AVG(voltage)                                  AS live_avg_voltage,
                AVG(current)                                  AS live_avg_current,
                AVG(power_factor)                             AS live_avg_pf,
                COUNT(*)                                      AS live_samples
            ')
            ->groupBy('device_id')
            ->get()
            ->keyBy('device_id');

        $activeRate = ElectricityTariff::getRateForDate($today);

        $synthetic = collect();
        foreach ($missingDevices as $device) {
            $live = $liveRows->get($device->id);

            // Skip devices with no raw data today
            if (!$live || (int) $live->live_samples === 0) {
                continue;
            }

            $kwhUsage = (float) $live->live_kwh_usage;

            // Build an in-memory model — exists = false, never saved
            $row = new PowerReadingDaily();
            $row->device_id          = $device->id;
            $row->recorded_date      = Carbon::parse($today);
            $row->kwh_total          = (float) $live->live_kwh_total;
            $row->kwh_usage          = $kwhUsage;
            $row->avg_power_kw       = round((float) $live->live_avg_power, 3);
            $row->max_power_kw       = round((float) $live->live_max_power, 3);
            $row->avg_voltage        = round((float) $live->live_avg_voltage, 2);
            $row->avg_current        = round((float) $live->live_avg_current, 2);
            $row->avg_power_factor   = round((float) $live->live_avg_pf, 3);
            $row->total_sample_count = (int) $live->live_samples;
            $row->energy_cost        = round($kwhUsage * $activeRate, 2);
            $row->data_source        = 'live';
            $row->tariff_rate_snapshot = $activeRate;

            // Attach the eager-loaded device so Blade templates work without extra queries
            $row->setRelation('device', $device);

            $synthetic->push($row);
        }

        return $synthetic->sortByDesc(fn ($r) => $r->device_id)->values();
    }
}
