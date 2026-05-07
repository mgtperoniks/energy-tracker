<!DOCTYPE html>
<html>
<head>
    <title>Audit Report</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #00628c; padding-bottom: 10px; }
        .company-name { font-size: 18px; font-weight: bold; color: #00628c; }
        .report-title { font-size: 14px; margin-top: 5px; text-transform: uppercase; }
        
        .kpi-container { margin-bottom: 20px; width: 100%; }
        .kpi-card { float: left; width: 13%; border: 1px solid #ddd; padding: 10px; text-align: center; margin-right: 1%; }
        .kpi-value { font-size: 14px; font-weight: bold; display: block; margin-top: 5px; }
        .kpi-label { font-size: 8px; color: #666; text-transform: uppercase; }
        .clear { clear: both; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f2f2f2; border: 1px solid #ddd; padding: 6px; text-align: left; }
        td { border: 1px solid #ddd; padding: 6px; }
        
        .severity-critical { color: red; font-weight: bold; }
        .severity-error { color: orange; font-weight: bold; }
        
        .incident-detail { margin-top: 30px; page-break-before: auto; }
        .detail-card { border: 1px solid #eee; margin-bottom: 15px; padding: 10px; }
        .detail-title { font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 5px; }
        .timeline { margin-top: 10px; color: #555; }
        .timeline-item { margin-bottom: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">ENERGY TRACKER SYSTEM</div>
        <div class="report-title">Forensic Audit & Incident Report</div>
        <div style="margin-top: 5px;">Generated at: {{ now()->format('Y-m-d H:i:s') }}</div>
    </div>

    <div class="kpi-container">
        <div class="kpi-card"><span class="kpi-label">Total</span><span class="kpi-value">{{ $summary['total'] }}</span></div>
        <div class="kpi-card"><span class="kpi-label">Open</span><span class="kpi-value">{{ $summary['open'] }}</span></div>
        <div class="kpi-card"><span class="kpi-label">Ack</span><span class="kpi-value">{{ $summary['acknowledged'] }}</span></div>
        <div class="kpi-card"><span class="kpi-label">Resolved</span><span class="kpi-value">{{ $summary['resolved'] }}</span></div>
        <div class="kpi-card"><span class="kpi-label">MTTA</span><span class="kpi-value">{{ $summary['mtta'] }}m</span></div>
        <div class="kpi-card"><span class="kpi-label">MTTR</span><span class="kpi-value">{{ $summary['mttr'] }}m</span></div>
        <div class="clear"></div>
    </div>

    <h3>Incident Summary Table ({{ strtoupper($viewMode) }} VIEW)</h3>
    <table>
        <thead>
            <tr>
                @if($viewMode == 'grouped')
                    <th>Last Seen</th>
                    <th>Device</th>
                    <th>Event Code</th>
                    <th>Severity</th>
                    <th>Count</th>
                    <th>Time Range (First - Last)</th>
                @else
                    <th>Timestamp</th>
                    <th>Device</th>
                    <th>Event</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Duration</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($logs->take(500) as $log)
            <tr>
                @if($viewMode == 'grouped')
                    <td>{{ \Carbon\Carbon::parse($log->last_seen)->format('d M H:i') }}</td>
                    <td>{{ $log->device->name ?? 'System' }}</td>
                    <td>{{ $log->event_code }}</td>
                    <td class="{{ $log->severity == 'CRITICAL' ? 'severity-critical' : '' }}">{{ $log->severity }}</td>
                    <td style="font-weight: bold; text-align: center;">{{ $log->incident_count }}x</td>
                    <td>{{ \Carbon\Carbon::parse($log->first_seen)->format('d M H:i') }} - {{ \Carbon\Carbon::parse($log->last_seen)->format('d M H:i') }}</td>
                @else
                    <td>{{ $log->detected_at->format('d M H:i') }}</td>
                    <td>{{ $log->device->name ?? 'System' }}</td>
                    <td>{{ $log->event_code }}</td>
                    <td class="{{ $log->severity == 'CRITICAL' ? 'severity-critical' : '' }}">{{ $log->severity }}</td>
                    <td>{{ strtoupper($log->status) }}</td>
                    <td>{{ $log->duration_minutes ?? '-' }} min</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($logs->count() > 500)
        <p style="color: red; text-align: center;">* Report limited to first 500 rows. Use Excel for full dataset.</p>
    @endif

    <div class="incident-detail">
        <h3>Critical Incident Drilldown</h3>
        @foreach($logs->where('severity', 'CRITICAL')->take(10) as $log)
        <div class="detail-card">
            <div class="detail-title">[{{ $log->event_code }}] {{ $log->title }} - {{ $log->device->name ?? 'System' }}</div>
            <p><strong>Message:</strong> {{ $log->message }}</p>
            <p><strong>Root Cause:</strong> {{ $log->root_cause ?? 'Pending investigation' }}</p>
            
            <div class="timeline">
                <div class="timeline-item">● Detected: {{ $log->detected_at->format('Y-m-d H:i:s') }}</div>
                @if($log->acknowledged_at)
                    <div class="timeline-item">● Acknowledged: {{ $log->acknowledged_at->format('Y-m-d H:i:s') }} (by {{ $log->acknowledger->name ?? 'User' }})</div>
                @endif
                @if($log->resolved_at)
                    <div class="timeline-item">● Resolved: {{ $log->resolved_at->format('Y-m-d H:i:s') }}</div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</body>
</html>
