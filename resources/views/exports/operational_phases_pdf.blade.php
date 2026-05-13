<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Operational Phases Report</title>
    <style>
        body { font-family: sans-serif; font-size: 9px; color: #333; margin: 0; padding: 10px; }
        .header { margin-bottom: 15px; border-bottom: 2px solid #00628c; padding-bottom: 5px; }
        .header h1 { margin: 0; color: #00628c; font-size: 16px; text-transform: uppercase; }
        .info { margin-bottom: 15px; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; table-layout: fixed; }
        th { background-color: #f1f5f9; padding: 4px 2px; text-align: left; border: 1px solid #cbd5e1; font-weight: bold; text-transform: uppercase; font-size: 7px; word-wrap: break-word; }
        td { padding: 4px 2px; border: 1px solid #cbd5e1; word-wrap: break-word; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 8px; text-transform: uppercase; }
        .badge-open { background-color: #dcfce7; color: #166534; }
        .badge-closed { background-color: #f1f5f9; color: #475569; }
        .footer { margin-top: 30px; font-size: 8px; color: #64748b; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Operational Phases Report</h1>
    </div>

    <div class="info">
        <strong>Machine:</strong> {{ $device->name }}<br>
        <strong>Range:</strong> {{ $start->format('d/m/Y H:i') }} - {{ $end->format('d/m/Y H:i') }}<br>
        <strong>Generated:</strong> {{ now()->format('d/m/Y H:i:s') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Start</th>
                <th style="width: 15%;">End</th>
                <th class="text-center" style="width: 10%;">Status</th>
                <th style="width: 15%;">Phase Name</th>
                <th class="text-right" style="width: 10%;">Duration</th>
                <th class="text-right" style="width: 10%;">Avg kW</th>
                <th class="text-right" style="width: 10%;">Peak kW</th>
                <th class="text-right" style="width: 8%;">Usage</th>
                <th class="text-right" style="width: 12%;">Est. Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($phases as $p)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($p['start_time_iso'])->format('d/m/Y H:i:s') }}</td>
                    <td>{{ \Carbon\Carbon::parse($p['end_time_iso'])->format('d/m/Y H:i:s') }}</td>
                    <td class="text-center">
                        <span class="badge {{ $p['status'] === 'OPEN' ? 'badge-open' : 'badge-closed' }}">
                            {{ $p['status'] }}
                        </span>
                    </td>
                    <td style="font-weight: bold; color: #00628c;">{{ $p['phase_name'] }}</td>
                    <td class="text-right">{{ $p['duration_human'] }}</td>
                    <td class="text-right">{{ number_format($p['avg_kw'], 2) }}</td>
                    <td class="text-right">{{ number_format($p['peak_kw'], 2) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($p['usage_kwh'], 2) }}</td>
                    <td class="text-right" style="font-weight: bold; color: #0f172a;">Rp {{ number_format($p['est_cost'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f8fafc;">
                <td colspan="7" class="text-right" style="font-weight: bold; text-transform: uppercase; font-size: 8px;">Total Consumption & Cost</td>
                <td class="text-right" style="font-weight: 900; font-size: 10px; border-top: 2px solid #00628c;">
                    {{ number_format(collect($phases)->sum('usage_kwh'), 2) }}
                </td>
                <td class="text-right" style="font-weight: 900; font-size: 10px; color: #00628c; border-top: 2px solid #00628c;">
                    Rp {{ number_format(collect($phases)->sum('est_cost'), 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Energy-Tracker Industrial Historian | Page 1
    </div>
</body>
</html>
