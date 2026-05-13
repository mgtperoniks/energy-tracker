<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OperationalPhasesExport implements FromArray, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $phases;
    protected $device;
    protected $start;
    protected $end;

    public function __construct(array $phases, $device, $start, $end)
    {
        $this->phases = $phases;
        $this->device = $device;
        $this->start = $start;
        $this->end = $end;
    }

    public function array(): array
    {
        $data = $this->phases;
        
        // Calculate totals
        $totalUsage = array_sum(array_column($this->phases, 'usage_kwh'));
        $totalCost = array_sum(array_column($this->phases, 'est_cost'));
        
        // Append total row
        $data[] = [
            'start_time' => null,
            'end_time' => null,
            'status' => 'TOTAL',
            'phase_name' => 'AGGREGATE TOTAL',
            'duration_human' => '-',
            'avg_kw' => '-',
            'peak_kw' => '-',
            'usage_kwh' => $totalUsage,
            'est_cost' => $totalCost
        ];

        return $data;
    }

    public function title(): string
    {
        return 'Operational Phases';
    }

    public function headings(): array
    {
        return [
            ['OPERATIONAL PHASES REPORT'],
            ['Machine:', $this->device->name],
            ['Range:', $this->start->format('Y-m-d H:i') . ' to ' . $this->end->format('Y-m-d H:i')],
            [''],
            [
                'Start',
                'End',
                'Status',
                'Phase Name',
                'Duration',
                'Avg kW',
                'Peak kW',
                'Usage kWh',
                'Est. Cost (Rp)'
            ]
        ];
    }

    public function map($phase): array
    {
        return [
            $phase['start_time'] ? $phase['start_time']->format('Y-m-d H:i:s') : '',
            $phase['end_time'] ? $phase['end_time']->format('Y-m-d H:i:s') : '',
            $phase['status'],
            $phase['phase_name'],
            $phase['duration_human'],
            $phase['avg_kw'],
            $phase['peak_kw'],
            $phase['usage_kwh'],
            $phase['est_cost']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->phases) + 5;
        return [
            1    => ['font' => ['bold' => true, 'size' => 14]],
            5    => ['font' => ['bold' => true]],
            'A5:I5' => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']]],
            $lastRow => ['font' => ['bold' => true]],
        ];
    }
}
