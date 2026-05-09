<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class AuditExcelExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $query;
    protected $viewMode;

    public function __construct($query, $viewMode = 'flat')
    {
        $this->query = $query;
        $this->viewMode = $viewMode;
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        if ($this->viewMode === 'grouped') {
            return [
                'Fingerprint', 'Device', 'Code', 'Type', 'Severity', 'Status',
                'Title', 'Incident Count', 'First Seen', 'Last Seen'
            ];
        }

        return [
            'Incident ID', 'Device', 'Code', 'Type', 'Severity', 'Status',
            'Title', 'Message', 'Detected At', 'Acknowledged At', 'Acknowledged By',
            'Resolved At', 'Duration (min)', 'MTTA (min)', 'Layer', 'Root Cause',
            'Payload: Prev KWh', 'Payload: Incoming KWh', 'Payload: Drop Ratio', 'Payload: Other'
        ];
    }

    public function map($log): array
    {
        if ($this->viewMode === 'grouped') {
            return [
                $log->fingerprint,
                $log->device?->name ?? 'System',
                $log->event_code,
                $log->event_type,
                $log->severity,
                strtoupper($log->status),
                $log->title,
                $log->incident_count,
                Carbon::parse($log->first_seen)->format('Y-m-d H:i:s'),
                Carbon::parse($log->last_seen)->format('Y-m-d H:i:s'),
            ];
        }

        $mtta = $log->acknowledged_at ? $log->detected_at->diffInMinutes($log->acknowledged_at) : null;
        $payload = $log->payload_json ?? [];

        return [
            $log->id,
            $log->device?->name ?? 'System',
            $log->event_code,
            $log->event_type,
            $log->severity,
            strtoupper($log->status),
            $log->title,
            $log->message,
            $log->detected_at->format('Y-m-d H:i:s'),
            $log->acknowledged_at ? $log->acknowledged_at->format('Y-m-d H:i:s') : '-',
            $log->acknowledger?->name ?? '-',
            $log->resolved_at ? $log->resolved_at->format('Y-m-d H:i:s') : '-',
            $log->duration_minutes ?? '-',
            $mtta ?? '-',
            $log->source_layer,
            $log->root_cause ?? '-',
            $payload['previous_raw'] ?? $payload['prev_kwh'] ?? '-',
            $payload['new_raw'] ?? $payload['curr_kwh'] ?? '-',
            $payload['drop_ratio'] ?? '-',
            json_encode(array_diff_key($payload, array_flip(['previous_raw', 'prev_kwh', 'new_raw', 'curr_kwh', 'drop_ratio'])))
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $maxCol = $this->viewMode === 'grouped' ? 'J' : 'T';
        $sheet->getStyle("A1:{$maxCol}1")->getFont()->setBold(true);
        $sheet->freezePane('A2');

        // Severity Color Mapping
        $severityCol = $this->viewMode === 'grouped' ? 'E' : 'E'; // Both are E
        $rows = $sheet->getHighestRow();
        for ($i = 2; $i <= $rows; $i++) {
            $severity = $sheet->getCell($severityCol . $i)->getValue();
            $cellRange = $severityCol . $i;

            if ($severity == 'CRITICAL') {
                $sheet->getStyle($cellRange)->getFont()->getColor()->setARGB('FFFF0000');
            } elseif ($severity == 'ERROR') {
                $sheet->getStyle($cellRange)->getFont()->getColor()->setARGB('FFFF6600');
            } elseif ($severity == 'WARNING') {
                $sheet->getStyle($cellRange)->getFont()->getColor()->setARGB('FFCC9900');
            }
        }
    }
}
