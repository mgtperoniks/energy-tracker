<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AccountingReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
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
            'Machine Name',
            'Usage (kWh)',
            'Rate (Rp/kWh)',
            'Total Cost (Rp)',
            'Data Source'
        ];
    }

    public function map($row): array
    {
        $row->hydrateLive();

        $rate = $row->applied_rate;

        return [
            $row->recorded_date ? $row->recorded_date->format('Y-m-d') : '-',
            optional($row->device)->name ?? '-',
            optional(optional($row->device)->machine)->name ?? '-',
            round($row->kwh_usage, 2),
            round($rate, 2),
            round($row->energy_cost, 0),
            strtoupper($row->data_source ?? 'live')
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
                $totalCost = $summaryQuery->sum('energy_cost');

                $delegate = $event->sheet->getDelegate();
                $delegate->mergeCells("A{$footerRow}:C{$footerRow}");
                $delegate->setCellValue("A{$footerRow}", 'GRAND TOTAL');
                $delegate->setCellValue("D{$footerRow}", round($totalKwh, 2));
                $delegate->setCellValue("F{$footerRow}", round($totalCost, 0));

                $delegate->getStyle("A{$footerRow}:F{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E9ECEF']
                    ]
                ]);

                // Format currency for cost column
                $delegate->getStyle("F2:F{$footerRow}")
                    ->getNumberFormat()
                    ->setFormatCode('"Rp "#,##0');

                // Style for Data Source column (G)
                $delegate->getStyle("G2:G{$footerRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
