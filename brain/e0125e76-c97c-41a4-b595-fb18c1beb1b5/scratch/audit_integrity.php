<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "--- TASK 1: INTEGRITY ---" . PHP_EOL;
$missingData = AuditLog::whereNull('severity')
    ->orWhereNull('event_code')
    ->orWhereNull('status')
    ->orWhereNull('detected_at')
    ->get();
echo "Missing mandatory fields: " . $missingData->count() . PHP_EOL;

$brokenDevice = AuditLog::whereNotNull('device_id')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
              ->from('devices')
              ->whereColumn('devices.id', 'audit_logs.device_id');
    })->get();
echo "Broken device relations: " . $brokenDevice->count() . PHP_EOL;

echo "\n--- TASK 2: STATUS LIFECYCLE ---" . PHP_EOL;
$resolvedMismatch = AuditLog::where('status', 'resolved')->whereNull('resolved_at')->get();
echo "Resolved but missing resolved_at: " . $resolvedMismatch->count() . PHP_EOL;
foreach($resolvedMismatch as $r) echo " - ID: {$r->id} Code: {$r->event_code}\n";

$ackMismatch = AuditLog::where('status', 'acknowledged')->whereNull('acknowledged_at')->get();
echo "Acknowledged but missing acknowledged_at: " . $ackMismatch->count() . PHP_EOL;

echo "\n--- TASK 3: TIMELINE ORDER ---" . PHP_EOL;
// Use Raw SQL for comparison to be safe
$timelineViolations = DB::table('audit_logs')
    ->whereRaw('acknowledged_at IS NOT NULL AND acknowledged_at < detected_at')
    ->orWhereRaw('resolved_at IS NOT NULL AND resolved_at < acknowledged_at')
    ->orWhereRaw('resolved_at IS NOT NULL AND resolved_at < detected_at')
    ->get();
echo "Timeline violations: " . $timelineViolations->count() . PHP_EOL;

echo "\n--- TASK 4: KPI CROSS-CHECK ---" . PHP_EOL;
$last7Days = Carbon::now()->subDays(7);
$stats = [
    'open' => AuditLog::where('status', 'open')->count(),
    'ack'  => AuditLog::where('status', 'acknowledged')->count(),
    'res'  => AuditLog::where('status', 'resolved')->count(),
    'ign'  => AuditLog::where('status', 'ignored')->count(),
    'mttr' => AuditLog::where('status', 'resolved')->where('resolved_at', '>=', $last7Days)->avg('duration_minutes') ?? 0,
];
print_r($stats);

echo "\n--- TASK 7 & 8: LINKAGE ---" . PHP_EOL;
$orphanNotifications = DB::table('notifications')
    ->whereNotNull('audit_log_id')
    ->whereNotExists(function($q) { $q->select(DB::raw(1))->from('audit_logs')->whereColumn('audit_logs.id', 'notifications.audit_log_id'); })
    ->count();
echo "Orphan Notifications: $orphanNotifications" . PHP_EOL;

$orphanEscalations = DB::table('incident_escalations')
    ->whereNotNull('audit_log_id')
    ->whereNotExists(function($q) { $q->select(DB::raw(1))->from('audit_logs')->whereColumn('audit_logs.id', 'incident_escalations.audit_log_id'); })
    ->count();
echo "Orphan Escalations: $orphanEscalations" . PHP_EOL;
