<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentalReading;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnvironmentalController extends Controller
{
    public function index(Request $request)
    {
        // Get all active environmental devices with their latest reading (unfiltered)
        $devices = Device::where('type', 'temperature_sensor')
            ->where('status', 1)
            ->get();

        $readings = [];
        foreach ($devices as $device) {
            $latest = EnvironmentalReading::where('device_id', $device->id)
                ->orderByDesc('recorded_at')
                ->first();
            $readings[] = [
                'device' => $device,
                'latest' => $latest,
            ];
        }

        // Apply filters to query
        $query = $this->buildFilteredQuery($request);

        // Paginate history for the web table (50 records per page)
        $history = $query->paginate(50)->withQueryString();

        return view('environmental', compact('readings', 'history', 'devices'));
    }

    public function exportExcel(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        if ($query->count() === 0) {
            return back()->with('warning', 'No records found for the selected filter.');
        }

        $filename = 'environmental_report_' . now()->format('Ymd_His') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\EnvironmentalExport($query),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        // Safety limit for PDF memory
        $reports = $query->limit(2000)->get();

        if ($reports->isEmpty()) {
            return back()->with('warning', 'No records found for the selected filter.');
        }

        $sensorId = $request->query('sensor');
        $sensorName = 'All Sensors';
        if ($sensorId && $sensorId !== 'all') {
            $device = Device::find($sensorId);
            $sensorName = $device ? $device->name : 'All Sensors';
        }

        $period = $request->query('period', 'today');
        $tz = config('app.timezone', 'Asia/Jakarta');

        $startDate = Carbon::today($tz);
        $endDate = Carbon::now($tz);
        $fromStr = $request->query('from');
        $toStr = $request->query('to');

        switch ($period) {
            case 'today':
                $startDate = Carbon::today($tz);
                $endDate = Carbon::now($tz);
                break;
            case 'yesterday':
                $startDate = Carbon::yesterday($tz)->startOfDay();
                $endDate = Carbon::yesterday($tz)->endOfDay();
                break;
            case 'last_24_hours':
                $startDate = Carbon::now($tz)->subHours(24);
                $endDate = Carbon::now($tz);
                break;
            case 'last_7_days':
                $startDate = Carbon::now($tz)->subDays(7);
                $endDate = Carbon::now($tz);
                break;
            case 'this_month':
                $startDate = Carbon::now($tz)->startOfMonth();
                $endDate = Carbon::now($tz);
                break;
            case 'custom':
                if ($fromStr && $toStr) {
                    try {
                        $startDate = Carbon::parse($fromStr, $tz)->startOfDay();
                        $endDate = Carbon::parse($toStr, $tz)->endOfDay();
                    } catch (\Exception $e) {}
                }
                break;
        }

        $dateRange = $startDate->format('d M Y H:i') . ' - ' . $endDate->format('d M Y H:i');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.environmental_pdf', compact('reports', 'dateRange', 'sensorName', 'period'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('environmental_report_' . now()->format('Ymd_His') . '.pdf');
    }

    protected function buildFilteredQuery(Request $request)
    {
        $sensorId = $request->query('sensor');
        $period   = $request->query('period', 'today');
        $fromStr  = $request->query('from');
        $toStr    = $request->query('to');

        $tz = config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($tz);

        $startDate = $now->copy()->startOfDay();
        $endDate   = $now->copy();

        switch ($period) {
            case 'today':
                $startDate = Carbon::today($tz);
                $endDate   = Carbon::now($tz);
                break;
            case 'yesterday':
                $startDate = Carbon::yesterday($tz)->startOfDay();
                $endDate   = Carbon::yesterday($tz)->endOfDay();
                break;
            case 'last_24_hours':
                $startDate = Carbon::now($tz)->subHours(24);
                $endDate = Carbon::now($tz);
                break;
            case 'last_7_days':
                $startDate = Carbon::now($tz)->subDays(7);
                $endDate = Carbon::now($tz);
                break;
            case 'this_month':
                $startDate = Carbon::now($tz)->startOfMonth();
                $endDate = Carbon::now($tz);
                break;
            case 'custom':
                if ($fromStr && $toStr) {
                    try {
                        $startDate = Carbon::parse($fromStr, $tz)->startOfDay();
                        $endDate   = Carbon::parse($toStr, $tz)->endOfDay();
                    } catch (\Exception $e) {}
                }
                break;
        }

        $query = EnvironmentalReading::with('device')
            ->whereBetween('recorded_at', [$startDate, $endDate]);

        if ($sensorId && $sensorId !== 'all') {
            $query->where('device_id', $sensorId);
        }

        return $query->orderBy('recorded_at', 'desc');
    }
}
