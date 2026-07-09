<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductionHistorianExport implements FromArray, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $cycles;
    protected $device;
    protected $start;
    protected $end;

    public function __construct(array $cycles, $device, $start, $end)
    {
        $this->cycles = $cycles;
        $this->device = $device;
        $this->start = $start;
        $this->end = $end;
    }

    public function array(): array
    {
        return $this->cycles;
    }

    public function title(): string
    {
        return 'Production Historian';
    }

    public function headings(): array
    {
        return [
            ['PRODUCTION HISTORIAN REPORT'],
            ['Machine:', $this->device->name],
            ['Range:', $this->start->format('Y-m-d H:i') . ' to ' . $this->end->format('Y-m-d H:i')],
            [''],
            [
                'Cycle #',
                'Status',
                'Melting Start',
                'Pour Start',
                'Cycle End',
                'Duration',
                'Hasil (kg)',
                'Bahan Kembali (kg)',
                'Energy (kWh)',
                'Est. Cost (Rp)',
                'Remark'
            ]
        ];
    }

    public function map($cycle): array
    {
        return [
            $cycle['number'] !== null ? '#' . $cycle['number'] : '—',
            $cycle['status'],
            $cycle['cycle_start'] ? $cycle['cycle_start']->format('Y-m-d H:i:s') : '',
            $cycle['pouring_start'] ? $cycle['pouring_start']->format('Y-m-d H:i:s') : '—',
            $cycle['cycle_end'] ? $cycle['cycle_end']->format('Y-m-d H:i:s') : '',
            $cycle['total_duration_human'],
            $cycle['actual_output_kg'] !== null ? $cycle['actual_output_kg'] : '—',
            $cycle['return_material_kg'] !== null ? $cycle['return_material_kg'] : '—',
            $cycle['status'] !== 'INCOMPLETE' ? $cycle['kwh'] : '—',
            $cycle['status'] !== 'INCOMPLETE' ? $cycle['est_cost'] : '—',
            $cycle['remark'] !== null ? $cycle['remark'] : '—'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true, 'size' => 14]],
            5    => ['font' => ['bold' => true]],
            'A5:K5' => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']]],
        ];
    }
}
