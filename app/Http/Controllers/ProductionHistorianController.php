<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\CycleReconstructionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductionHistorianController extends Controller
{
    private CycleReconstructionService $cycleService;

    public function __construct(CycleReconstructionService $cycleService)
    {
        $this->cycleService = $cycleService;
    }

    /**
     * Display the Production Historian report page.
     *
     * Responsibilities of this controller (thin):
     *   1. Validate filter input
     *   2. Resolve defaults for unprovided dates
     *   3. Call CycleReconstructionService
     *   4. Pass clean payload to view
     *
     * This controller does NOT: calculate cycles, compute KPIs,
     * calculate costs, detect outliers, or touch raw data.
     */
    public function index(Request $request)
    {
        $devices  = Device::with('machine')->orderBy('name')->get();
        $deviceId = $request->query('device_id');

        // Default datetime range: last 7 days
        $defaultEnd   = now('Asia/Jakarta')->endOfDay()->format('Y-m-d\TH:i');
        $defaultStart = now('Asia/Jakarta')->subDays(7)->startOfDay()->format('Y-m-d\TH:i');

        $startDatetime = $request->query('start_datetime', $defaultStart);
        $endDatetime   = $request->query('end_datetime',   $defaultEnd);

        // Not yet filtered: show empty state
        $isFiltered = $request->has('device_id') && $deviceId !== null && $deviceId !== '';

        if (!$isFiltered) {
            return view('analytics.historian', compact(
                'devices',
                'deviceId',
                'startDatetime',
                'endDatetime',
                'isFiltered',
            ) + ['device' => null, 'cycles' => [], 'kpi' => []]);
        }

        // ── Validation ──────────────────────────────────────────────

        // V1: Device must exist
        $device = Device::find($deviceId);
        if (!$device) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['device_id' => 'Device tidak ditemukan.']);
        }

        // V2: Parse datetimes
        try {
            $start = Carbon::parse($startDatetime)->setTimezone('Asia/Jakarta');
            $end   = Carbon::parse($endDatetime)->setTimezone('Asia/Jakarta');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['date_range' => 'Format tanggal tidak valid.']);
        }

        // V3: Start must be before end
        if ($start->gte($end)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['date_range' => 'Tanggal mulai harus lebih awal dari tanggal akhir.']);
        }

        // V4: Maximum range 45 days (consistent with Operational & Accounting reports)
        if ($start->diffInDays($end) > 45) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['date_range' => 'Maksimal rentang Production Historian adalah 45 hari.']);
        }

        // ── Service calls (all business logic lives here) ────────────

        $cycles = $this->cycleService->reconstruct($device->id, $start, $end);
        $kpi    = $this->cycleService->buildKpiSummary($cycles);

        // ── Return clean view payload ────────────────────────────────

        return view('analytics.historian', compact(
            'device',
            'devices',
            'cycles',
            'kpi',
            'isFiltered',
            'deviceId',
            'startDatetime',
            'endDatetime',
        ));
    }
}
