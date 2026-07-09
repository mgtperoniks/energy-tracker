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
    /**
     * Helper to verify if the user belongs to the QC whitelist.
     */
    private function isQcUser($user)
    {
        if (!$user) {
            return false;
        }
        $allowed = [
            'adminqcflange@peroniks.com',
            'adminqcfitting@peroniks.com',
        ];
        return in_array($user->email, $allowed);
    }

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

        $canManageProductionResults = $this->isQcUser(auth()->user());

        if (!$isFiltered) {
            return view('analytics.historian', compact(
                'devices',
                'deviceId',
                'startDatetime',
                'endDatetime',
                'isFiltered',
                'canManageProductionResults',
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
            'canManageProductionResults',
        ));
    }

    /**
     * Store or update production results for a cycle.
     */
    public function storeProductionResult(Request $request)
    {
        if (!$this->isQcUser(auth()->user())) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'melting_tag_id'     => 'required|exists:operational_event_tags,id',
            'actual_output_kg'   => 'nullable|numeric|min:0|max:600',
            'return_material_kg' => 'nullable|numeric|min:0|max:200',
            'remark'             => 'nullable|string|max:1000',
        ]);

        $userId = auth()->id() ?? 1;

        $result = \App\Models\ProductionCycleResult::where('melting_tag_id', $validated['melting_tag_id'])->first();

        if ($result) {
            $result->update([
                'actual_output_kg'   => $validated['actual_output_kg'],
                'return_material_kg' => $validated['return_material_kg'],
                'remark'             => $validated['remark'],
                'updated_by'         => $userId,
            ]);
        } else {
            \App\Models\ProductionCycleResult::create([
                'melting_tag_id'     => $validated['melting_tag_id'],
                'actual_output_kg'   => $validated['actual_output_kg'],
                'return_material_kg' => $validated['return_material_kg'],
                'remark'             => $validated['remark'],
                'created_by'         => $userId,
                'updated_by'         => $userId,
            ]);
        }

        return redirect()->back()->with('success', 'Data produksi berhasil disimpan.');
    }

    /**
     * Export production historian report to Excel.
     */
    public function exportExcel(Request $request)
    {
        $deviceId = $request->query('device_id');
        $startDatetime = $request->query('start_datetime');
        $endDatetime   = $request->query('end_datetime');

        if (!$deviceId) {
            return redirect()->back()->withErrors(['device_id' => 'Device harus dipilih untuk ekspor.']);
        }

        $device = Device::find($deviceId);
        if (!$device) {
            return redirect()->back()->withErrors(['device_id' => 'Device tidak ditemukan.']);
        }

        try {
            $start = Carbon::parse($startDatetime)->setTimezone('Asia/Jakarta');
            $end   = Carbon::parse($endDatetime)->setTimezone('Asia/Jakarta');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['date_range' => 'Format tanggal tidak valid.']);
        }

        if ($start->gte($end)) {
            return redirect()->back()->withErrors(['date_range' => 'Tanggal mulai harus lebih awal dari tanggal akhir.']);
        }

        if ($start->diffInDays($end) > 45) {
            return redirect()->back()->withErrors(['date_range' => 'Maksimal rentang ekspor adalah 45 hari.']);
        }

        $cycles = $this->cycleService->reconstruct($device->id, $start, $end);
        $filename = 'production_historian_report_' . $device->name . '_' . now()->format('Ymd_Hi') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProductionHistorianExport($cycles, $device, $start, $end),
            $filename
        );
    }

    /**
     * Export production historian report to PDF.
     */
    public function exportPdf(Request $request)
    {
        $deviceId = $request->query('device_id');
        $startDatetime = $request->query('start_datetime');
        $endDatetime   = $request->query('end_datetime');

        if (!$deviceId) {
            return redirect()->back()->withErrors(['device_id' => 'Device harus dipilih untuk ekspor.']);
        }

        $device = Device::find($deviceId);
        if (!$device) {
            return redirect()->back()->withErrors(['device_id' => 'Device tidak ditemukan.']);
        }

        try {
            $start = Carbon::parse($startDatetime)->setTimezone('Asia/Jakarta');
            $end   = Carbon::parse($endDatetime)->setTimezone('Asia/Jakarta');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['date_range' => 'Format tanggal tidak valid.']);
        }

        if ($start->gte($end)) {
            return redirect()->back()->withErrors(['date_range' => 'Tanggal mulai harus lebih awal dari tanggal akhir.']);
        }

        if ($start->diffInDays($end) > 45) {
            return redirect()->back()->withErrors(['date_range' => 'Maksimal rentang ekspor adalah 45 hari.']);
        }

        $cycles = $this->cycleService->reconstruct($device->id, $start, $end);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.historian_pdf', compact('cycles', 'device', 'start', 'end'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('production_historian_report_' . $device->name . '_' . now()->format('Ymd_Hi') . '.pdf');
    }
}
