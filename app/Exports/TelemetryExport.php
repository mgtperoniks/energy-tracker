<?php

namespace App\Exports;

use App\Models\PowerReadingRaw;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TelemetryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $deviceId;
    protected $startDate;
    protected $endDate;

    public function __construct($deviceId, $startDate, $endDate)
    {
        $this->deviceId = $deviceId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return PowerReadingRaw::where('device_id', $this->deviceId)
            ->whereBetween('recorded_at', [$this->startDate, $this->endDate])
            ->orderBy('recorded_at', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Timestamp',
            'Power (kW)',
            'Voltage (V)',
            'Current (A)',
            'Power Factor',
            'Total Energy (kWh)'
        ];
    }

    /**
    * @var PowerReadingRaw $reading
    */
    public function map($reading): array
    {
        return [
            $reading->recorded_at->format('Y-m-d H:i:s'),
            number_format((float)$reading->power_kw, 2, '.', ''),
            number_format((float)$reading->voltage, 1, '.', ''),
            number_format((float)$reading->current, 1, '.', ''),
            number_format((float)$reading->power_factor, 2, '.', ''),
            number_format((float)$reading->kwh_total, 2, '.', ''),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
