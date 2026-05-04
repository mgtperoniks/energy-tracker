<?php

namespace App\Exports;

use App\Models\PowerReadingDaily;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OperationalReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Device Name',
            'Machine',
            'kWh Usage',
            'kWh Total (Raw)',
            'Peak Load (kW)',
            'Avg Voltage (V)',
            'Avg Current (A)',
            'Avg Power Factor',
            'Sample Count'
        ];
    }

    public function map($row): array
    {
        return [
            $row->recorded_date ? $row->recorded_date->format('Y-m-d') : '-',
            optional($row->device)->name ?? '-',
            optional(optional($row->device)->machine)->name ?? '-',
            round($row->kwh_usage, 2),
            round($row->kwh_total, 2),
            round($row->max_power_kw, 2),
            round($row->avg_voltage, 1),
            round($row->avg_current, 1),
            round($row->avg_power_factor, 2),
            $row->total_sample_count
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();
                $footerRow = $lastRow + 1;

                $summaryQuery = clone $this->query;
                $totalKwh = $summaryQuery->sum('kwh_usage');
                $maxPeak = $summaryQuery->max('max_power_kw');
                $avgVolt = $summaryQuery->avg('avg_voltage');
                $totalSamples = $summaryQuery->sum('total_sample_count');

                $delegate = $event->sheet->getDelegate();
                $delegate->mergeCells("A{$footerRow}:C{$footerRow}");
                $delegate->setCellValue("A{$footerRow}", 'SUMMARY / TOTAL');
                $delegate->setCellValue("D{$footerRow}", round($totalKwh, 2));
                $delegate->setCellValue("F{$footerRow}", round($maxPeak, 2));
                $delegate->setCellValue("G{$footerRow}", round($avgVolt, 1));
                $delegate->setCellValue("J{$footerRow}", $totalSamples);

                $delegate->getStyle("A{$footerRow}:J{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F0F0']
                    ]
                ]);
            },
        ];
    }
}
