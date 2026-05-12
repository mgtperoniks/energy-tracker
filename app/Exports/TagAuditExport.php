<?php

namespace App\Exports;

use App\Models\OperationalEventTag;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TagAuditExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $deviceId;
    protected $start;
    protected $end;

    public function __construct($deviceId, $start, $end)
    {
        $this->deviceId = $deviceId;
        $this->start = $start;
        $this->end = $end;
    }

    public function collection()
    {
        return OperationalEventTag::withTrashed()
            ->with(['tagger', 'editor', 'deleter'])
            ->where('device_id', $this->deviceId)
            ->whereBetween('event_time', [$this->start, $this->end])
            ->orderBy('event_time', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Event Time (WIB)',
            'Event Type',
            'Shift',
            'Tagger',
            'Created At',
            'Edited By',
            'Edited At',
            'Revision Notes',
            'Is Deleted',
            'Deleted By',
            'Deleted At',
            'Delete Reason'
        ];
    }

    /**
    * @var OperationalEventTag $tag
    */
    public function map($tag): array
    {
        return [
            $tag->event_time->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            strtoupper($tag->event_type),
            $tag->shift,
            ($tag->tagger->name ?? 'System') . ($tag->tagger ? " ({$tag->tagger->email})" : ''),
            $tag->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ($tag->editor->name ?? '-') . ($tag->editor ? " ({$tag->editor->email})" : ''),
            $tag->edited_at ? $tag->edited_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '-',
            $tag->revision_notes ?? '-',
            $tag->trashed() ? 'YES' : 'NO',
            ($tag->deleter->name ?? '-') . ($tag->deleter ? " ({$tag->deleter->email})" : ''),
            $tag->deleted_at ? $tag->deleted_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '-',
            $tag->delete_reason ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
