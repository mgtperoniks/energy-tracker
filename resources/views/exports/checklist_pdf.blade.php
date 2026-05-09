<!DOCTYPE html>
<html>
<head>
    <title>Production Observation Checklist</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2d3e50; padding-bottom: 10px; }
        .company-name { font-size: 20px; font-weight: bold; color: #2d3e50; }
        .report-title { font-size: 14px; margin-top: 5px; text-transform: uppercase; letter-spacing: 1px; }
        
        .meta-info { margin-bottom: 20px; }
        .meta-item { margin-bottom: 5px; }
        .meta-label { font-weight: bold; width: 100px; display: inline-block; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; text-align: left; font-weight: bold; }
        td { border: 1px solid #dee2e6; padding: 10px; vertical-align: top; }
        
        .footer { margin-top: 50px; text-align: right; font-style: italic; font-size: 9px; }
        .signature-box { margin-top: 60px; }
        .signature-line { border-top: 1px solid #333; width: 200px; margin-top: 40px; display: inline-block; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">ENERGY TRACKER SYSTEM</div>
        <div class="report-title">Production Observation Checklist</div>
    </div>

    <div class="meta-info">
        <div class="meta-item"><span class="meta-label">Audit Date:</span> {{ $checklist->check_date->format('l, d F Y') }}</div>
        <div class="meta-item"><span class="meta-label">Inspector:</span> {{ $checklist->inspector_name }}</div>
        <div class="meta-item"><span class="meta-label">Status:</span> {{ strtoupper($checklist->status) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="15%">Check Item</th>
                <th width="20%">Expected Result</th>
                <th width="20%">Actual Result</th>
                <th width="25%">Notes</th>
                <th width="20%">Action Needed</th>
            </tr>
        </thead>
        <tbody>
            @foreach($checklist->items_json as $item)
            <tr>
                <td style="font-weight: bold;">{{ $item['item'] }}</td>
                <td style="color: #666;">{{ $item['expected'] }}</td>
                <td>{{ $item['actual'] ?: '-' }}</td>
                <td>{{ $item['notes'] ?: '-' }}</td>
                <td>{{ $item['action'] ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-box">
        <div style="float: left;">
            <div class="signature-line">Inspector Signature</div>
        </div>
        <div style="float: right;">
            <div class="signature-line">Site Manager Approval</div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="footer">
        Generated via Energy Tracker Forensic Engine at {{ now()->format('Y-m-d H:i:s') }}
    </div>
</body>
</html>
