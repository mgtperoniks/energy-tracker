<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Operational Energy Report</title>
    <style>
        @page {
            margin: 10mm 12mm 15mm 12mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5px;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.25;
        }
        .header {
            border-bottom: 1.5px solid #00628c;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }
        .header h1 {
            color: #00628c;
            margin: 0;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 2px 0 0 0;
            color: #64748b;
            font-size: 8.5px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 1px 0;
            font-size: 8px;
        }
        .label {
            font-weight: bold;
            color: #475569;
            width: 80px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data-table th {
            background-color: #00628c;
            color: white;
            padding: 4px 6px;
            text-align: left;
            text-transform: uppercase;
            font-size: 7.5px;
            font-weight: bold;
        }
        table.data-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8px;
            vertical-align: middle;
        }
        /* Specificity handling for alignments */
        table.data-table th.text-right,
        table.data-table td.text-right {
            text-align: right;
        }
        table.data-table th.text-center,
        table.data-table td.text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            height: 20px;
            font-size: 7.5px;
            text-align: center;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
        .page-number:before {
            content: counter(page);
        }
        .summary-box {
            margin-top: 10px;
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            background-color: #f8fafc;
            border-radius: 3px;
        }
        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-title {
            font-size: 8px;
            font-weight: bold;
            color: #00628c;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 15%;
        }
        .summary-value {
            font-size: 8.5px;
            font-weight: bold;
            color: #334155;
        }
        .badge-offline {
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Operational Energy Report</h1>
        <p>Peroni Karya Sentra Energy System</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Period:</td>
            <td>{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</td>
            <td class="label" style="text-align: right">Generated At:</td>
            <td style="text-align: right">{{ now()->format('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Filter Device:</td>
            <td>{{ $deviceName }}</td>
            <td></td>
            <td></td>
        </tr>
    </table>

    @php
        $metricWidth = count($selectedMetrics) > 0 ? (60 / count($selectedMetrics)) : 60;
    @endphp

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%">Date</th>
                <th style="width: 25%">Device (Machine)</th>
                @if(in_array('usage', $selectedMetrics))
                    <th class="text-right" style="width: {{ $metricWidth }}%">Usage (kWh)</th>
                @endif
                @if(in_array('peak', $selectedMetrics))
                    <th class="text-right" style="width: {{ $metricWidth }}%">Peak Load (kW)</th>
                @endif
                @if(in_array('volt', $selectedMetrics))
                    <th class="text-right" style="width: {{ $metricWidth }}%">Avg Volt (V)</th>
                @endif
                @if(in_array('pf', $selectedMetrics))
                    <th class="text-right" style="width: {{ $metricWidth }}%">Avg PF</th>
                @endif
                @if(in_array('samples', $selectedMetrics))
                    <th class="text-right" style="width: {{ $metricWidth }}%">Samples</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @php 
                $totalKwh = 0;
                $maxPeak = 0;
            @endphp
            @foreach($reports as $row)
                @php 
                    $totalKwh += $row->kwh_usage;
                    $maxPeak = max($maxPeak, $row->max_power_kw);
                @endphp
                <tr>
                    <td class="font-bold">{{ \Carbon\Carbon::parse($row->recorded_date)->format('d/m/Y') }}</td>
                    <td style="line-height: 1.15;">
                        <span class="font-bold" style="color: #0f172a;">{{ $row->device->name }}</span><br>
                        <span style="color: #64748b; font-size: 7.5px;">{{ optional($row->device->machine)->name ?? 'Unassigned' }}</span>
                    </td>
                    @if(in_array('usage', $selectedMetrics))
                        <td class="text-right font-bold" style="color: #00628c">{{ number_format($row->kwh_usage, 2) }}</td>
                    @endif
                    @if(in_array('peak', $selectedMetrics))
                        <td class="text-right" style="color: #d32f2f">{{ number_format($row->max_power_kw, 2) }}</td>
                    @endif
                    @if(in_array('volt', $selectedMetrics))
                        <td class="text-right">{{ number_format($row->avg_voltage, 1) }}</td>
                    @endif
                    @if(in_array('pf', $selectedMetrics))
                        <td class="text-right">{{ number_format($row->avg_power_factor, 2) }}</td>
                    @endif
                    @if(in_array('samples', $selectedMetrics))
                        <td class="text-right">{{ number_format($row->total_sample_count) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(in_array('usage', $selectedMetrics) || in_array('peak', $selectedMetrics))
        <div class="summary-box">
            <table>
                <tr>
                    <td class="summary-title">SUMMARY</td>
                    <td class="summary-value">
                        @php $sumParts = []; @endphp
                        @if(in_array('usage', $selectedMetrics))
                            @php $sumParts[] = 'Total Energy: <span style="color: #00628c; font-size: 9.5px;">' . number_format($totalKwh, 2) . ' kWh</span>'; @endphp
                        @endif
                        @if(in_array('peak', $selectedMetrics))
                            @php $sumParts[] = 'Max Peak: <span style="color: #ba1a1a; font-size: 9.5px;">' . number_format($maxPeak, 2) . ' kW</span>'; @endphp
                        @endif
                        {!! implode('&nbsp;&nbsp;&nbsp;&nbsp;&bull;&nbsp;&nbsp;&nbsp;&nbsp;', $sumParts) !!}
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="footer">
        Energy Tracker Management System - Industrial Report System - Page <span class="page-number"></span>
    </div>
</body>
</html>
