<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionChecklist extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'check_date' => 'date',
        'items_json' => 'array',
    ];

    public static function getDefaultItems()
    {
        return [
            ['item' => 'Telemetry', 'expected' => 'No gaps in raw readings', 'actual' => '', 'notes' => '', 'action' => ''],
            ['item' => 'Operational Report', 'expected' => 'Yesterday totals match raw', 'actual' => '', 'notes' => '', 'action' => ''],
            ['item' => 'Accounting Report', 'expected' => 'Tariff snapshot applied', 'actual' => '', 'notes' => '', 'action' => ''],
            ['item' => 'Audit Trail', 'expected' => '0 unacknowledged critical', 'actual' => '', 'notes' => '', 'action' => ''],
            ['item' => 'Health Snapshot', 'expected' => 'Score > 8.0 generated 07:00', 'actual' => '', 'notes' => '', 'action' => ''],
        ];
    }
}
