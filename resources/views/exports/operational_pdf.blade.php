<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Operational Energy Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #00628c;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #00628c;
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 2px 0;
        }
        .label {
            font-weight: bold;
            color: #555;
            width: 100px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data-table th {
            background-color: #00628c;
            color: white;
            padding: 6px 4px;
            text-align: left;
            text-transform: uppercase;
            font-size: 8px;
        }
        table.data-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #eee;
            font-size: 9px;
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
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
            font-size: 8px;
            text-align: center;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
        .summary-box {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .badge-offline {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Operational Energy Report</h1>
        <p>Industrial Site A - Heavy Industrial Wing</p>
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

    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Device (Machine)</th>
                <th class="text-right">Usage (kWh)</th>
                <th class="text-right">Peak Load (kW)</th>
                <th class="text-right">Avg Volt (V)</th>
                <th class="text-right">Avg PF</th>
                <th class="text-right">Samples</th>
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
                    <td>
                        {{ $row->device->name }}<br>
                        <small style="color: #888">{{ optional($row->device->machine)->name ?? 'Unassigned' }}</small>
                    </td>
                    <td class="text-right font-bold" style="color: #00628c">{{ number_format($row->kwh_usage, 2) }}</td>
                    <td class="text-right" style="color: #d32f2f">{{ number_format($row->max_power_kw, 2) }}</td>
                    <td class="text-right">{{ number_format($row->avg_voltage, 1) }}</td>
                    <td class="text-right">{{ number_format($row->avg_power_factor, 2) }}</td>
                    <td class="text-right">{{ number_format($row->total_sample_count) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <table style="width: 100%">
            <tr>
                <td>
                    <span style="font-size: 12px; font-weight: bold; color: #00628c">SUMMARY TOTALS</span>
                </td>
                <td class="text-right">
                    <span class="label">Total Energy:</span> 
                    <span style="font-size: 14px; font-weight: bold; color: #00628c">{{ number_format($totalKwh, 2) }} kWh</span>
                </td>
                <td class="text-right">
                    <span class="label">Max Peak:</span> 
                    <span style="font-size: 14px; font-weight: bold; color: #d32f2f">{{ number_format($maxPeak, 2) }} kW</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Energy Tracker Management System - Industrial Report System - Page 1 of 1
    </div>
</body>
</html>
