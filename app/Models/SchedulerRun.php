<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulerRun extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'last_success_at' => 'datetime',
    ];

    /**
     * Record a heartbeat for a specific job.
     */
    public static function log(string $jobName, bool $success, int $durationMs, ?string $message = null)
    {
        return self::updateOrCreate(
            ['job_name' => $jobName],
            [
                'last_success_at' => $success ? now() : null,
                'last_duration_ms' => $durationMs,
                'status' => $success ? 'success' : 'failed',
                'message' => $message,
                'updated_at' => now(),
            ]
        );
    }
}
