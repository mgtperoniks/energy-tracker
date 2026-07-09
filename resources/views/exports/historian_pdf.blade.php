<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Production Historian Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header h1 {
            color: #1e3a8a;
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }

        .header p {
            margin: 3px 0 0 0;
            color: #666;
            font-size: 9px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 15px;
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
            margin-top: 5px;
        }

        table.data-table th {
            background-color: #f0f4f8;
            color: #1e3a8a;
            padding: 8px 6px;
            text-align: left;
            text-transform: uppercase;
            font-size: 9px;
            border-bottom: 2px solid #1e3a8a;
        }

        table.data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #eee;
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

        .status-badge {
            font-size: 8px;
            padding: 2px 4px;
            border-radius: 2px;
            font-weight: bold;
            display: inline-block;
        }

        .status-closed { background-color: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; }
        .status-open { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .status-incomplete { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .status-outlier { background-color: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }

        /* Color Indicators */
        .color-green { color: #16a34a; font-weight: bold; }
        .color-yellow { color: #ca8a04; font-weight: bold; }
        .color-red { color: #dc2626; font-weight: bold; }

        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 20px;
            font-size: 8px;
            text-align: center;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Production Historian Report</h1>
        <p>Industrial Telemetry & Manufacturing Efficiency System</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Device Machine:</td>
            <td>{{ $device->name }} {{ $device->machine ? '('.$device->machine->name.')' : '' }}</td>
            <td class="label" style="text-align: right">Generated At:</td>
            <td style="text-align: right">{{ now()->setTimezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Date Range:</td>
            <td>{{ $start->format('d M Y H:i') }} - {{ $end->format('d M Y H:i') }}</td>
            <td class="label" style="text-align: right">Timezone:</td>
            <td style="text-align: right">WIB (Asia/Jakarta)</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Cycle</th>
                <th>Status</th>
                <th>Melting Start</th>
                <th>Pour Start</th>
                <th>Cycle End</th>
                <th class="text-right">Duration</th>
                <th class="text-right">Hasil (kg)</th>
                <th class="text-right">Bahan Kembali (kg)</th>
                <th class="text-right">Energy (kWh)</th>
                <th class="text-right">Est. Cost</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cycles as $cycle)
                <tr>
                    <td class="font-bold">{{ $cycle['number'] !== null ? '#' . $cycle['number'] : '—' }}</td>
                    <td class="text-center">
                        @if($cycle['status'] === 'CLOSED')
                            <span class="status-badge status-closed">CLOSED</span>
                        @elseif($cycle['status'] === 'OPEN')
                            <span class="status-badge status-open">OPEN</span>
                        @elseif($cycle['status'] === 'INCOMPLETE')
                            <span class="status-badge status-incomplete">INCOMPLETE</span>
                        @elseif($cycle['status'] === 'OUTLIER')
                            <span class="status-badge status-outlier">OUTLIER</span>
                        @endif
                    </td>
                    <td>{{ $cycle['cycle_start']->format('d M H:i') }}</td>
                    <td>{{ $cycle['pouring_start'] ? $cycle['pouring_start']->format('d M H:i') : '—' }}</td>
                    <td>{{ $cycle['cycle_end']->format('d M H:i') }}</td>
                    <td class="text-right">{{ $cycle['total_duration_human'] }}</td>
                    
                    <!-- Color Coding for Hasil (kg) -->
                    <td class="text-right font-mono">
                        @if($cycle['actual_output_kg'] !== null)
                            @php
                                $outputVal = $cycle['actual_output_kg'];
                                $outputClass = $outputVal >= 430 ? 'color-green' : ($outputVal >= 400 ? 'color-yellow' : 'color-red');
                            @endphp
                            <span class="{{ $outputClass }}">{{ number_format($outputVal, 2) }}</span>
                        @else
                            —
                        @endif
                    </td>

                    <!-- Color Coding for Bahan Kembali (kg) -->
                    <td class="text-right font-mono">
                        @if($cycle['return_material_kg'] !== null)
                            @php
                                $returnVal = $cycle['return_material_kg'];
                                $returnClass = $returnVal <= 20 ? 'color-green' : ($returnVal <= 50 ? 'color-yellow' : 'color-red');
                            @endphp
                            <span class="{{ $returnClass }}">{{ number_format($returnVal, 2) }}</span>
                        @else
                            —
                        @endif
                    </td>

                    <td class="text-right font-bold font-mono" style="color: #1e3a8a;">
                        {{ $cycle['status'] !== 'INCOMPLETE' ? number_format($cycle['kwh'], 1) : '—' }}
                    </td>
                    <td class="text-right font-bold font-mono">
                        {{ $cycle['status'] !== 'INCOMPLETE' ? 'Rp ' . number_format($cycle['est_cost'], 0, ',', '.') : '—' }}
                    </td>
                    <td style="font-size: 8px; max-width: 150px; word-wrap: break-word;">{{ $cycle['remark'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated by Energy-Tracker Production Historian Module • Confidential Report
    </div>
</body>

</html>
