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
    protected $deviceIds;
    protected $startDate;
    protected $endDate;

    public function __construct($deviceIds, $startDate, $endDate)
    {
        $this->deviceIds = is_array($deviceIds) ? $deviceIds : [$deviceIds];
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return PowerReadingRaw::with(['device.machine'])
            ->whereIn('device_id', $this->deviceIds)
            ->whereBetween('recorded_at', [$this->startDate, $this->endDate])
            ->orderBy('recorded_at', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Timestamp',
            'Machine Code',
            'Power (kW)',
            'Voltage (V)',
            'Current (A)',
            'Power Factor',
            'Total kWh',
            'Status'
        ];
    }

    /**
    * @var PowerReadingRaw $reading
    */
    public function map($reading): array
    {
        return [
            $reading->recorded_at ? $reading->recorded_at->format('Y-m-d H:i:s') : '-',
            $reading->device->machine->code ?? '-',
            $reading->power_kw !== null ? round((float)$reading->power_kw, 2) : null,
            $reading->voltage !== null ? round((float)$reading->voltage, 1) : null,
            $reading->current !== null ? round((float)$reading->current, 1) : null,
            $reading->power_factor !== null ? round((float)$reading->power_factor, 2) : null,
            $reading->kwh_total !== null ? round((float)$reading->kwh_total, 2) : null,
            $reading->operational_status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
