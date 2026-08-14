<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Environmental Monitoring Report</title>
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
            width: 120px;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Environmental Monitoring Report</h1>
        <p>Peroni Karya Sentra Energy System</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Date Range:</td>
            <td>{{ $dateRange }}</td>
            <td class="label" style="text-align: right">Generated At:</td>
            <td style="text-align: right">{{ now()->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Filter Sensor:</td>
            <td>{{ $sensorName }}</td>
            <td class="label" style="text-align: right">Period Preset:</td>
            <td style="text-align: right">{{ ucfirst(str_replace('_', ' ', $period)) }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Recorded At</th>
                <th>Sensor</th>
                <th>Temperature (°C)</th>
                <th>Humidity (%RH)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reports as $row)
                <tr>
                    <td class="font-bold">{{ $row->recorded_at ? $row->recorded_at->format('Y-m-d H:i:s') : '-' }}</td>
                    <td>{{ $row->device->name ?? '-' }}</td>
                    <td>{{ $row->temperature !== null ? number_format($row->temperature, 1) . ' °C' : '—' }}</td>
                    <td>{{ $row->humidity !== null ? number_format($row->humidity, 1) . ' %RH' : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Peroni Karya Sentra Environmental Logging
    </div>
</body>
</html>
