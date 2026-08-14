<?php

namespace App\Exports;

use App\Models\EnvironmentalReading;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EnvironmentalExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = clone $query;
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'Recorded At',
            'Sensor',
            'Temperature (°C)',
            'Humidity (%RH)'
        ];
    }

    /**
    * @param EnvironmentalReading $reading
    */
    public function map($reading): array
    {
        return [
            $reading->recorded_at ? $reading->recorded_at->format('Y-m-d H:i:s') : '-',
            $reading->device->name ?? '-',
            $reading->temperature !== null ? (float)$reading->temperature : null,
            $reading->humidity !== null ? (float)$reading->humidity : null,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
