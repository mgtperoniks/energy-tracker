@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Operational Report</h1>
            <p class="text-on-surface-variant text-sm mt-1">Laporan harian metrik teknis per power meter.</p>
        </div>

        <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm border border-surface-container-low mb-8">
            <form method="GET" action="{{ route('analytics.operational') }}" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Date Range</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="start_date" value="{{ $startDate }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        <span class="text-outline-variant font-bold">to</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    </div>
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Filter by Device</label>
                    <select name="device_id" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        <option value="">All Devices</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" {{ $deviceId == $device->id ? 'selected' : '' }}>
                                {{ $device->name }} {{ $device->machine ? '('.$device->machine->name.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-2 bg-primary text-on-primary font-bold rounded-md hover:bg-primary/90 transition-colors h-[38px] flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">filter_alt</span>
                        Apply Filter
                    </button>
                    <a href="{{ route('analytics.operational.export', request()->all()) }}" class="px-6 py-2 bg-surface-container-high text-on-surface font-bold rounded-md hover:bg-surface-container-highest transition-colors h-[38px] flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">download</span>
                        Excel
                    </a>
                    <a href="{{ route('analytics.operational.pdf', request()->all()) }}" target="_blank" class="px-6 py-2 bg-error-container text-on-error-container font-bold rounded-md hover:bg-error-container/80 transition-colors h-[38px] flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                        PDF
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden border border-surface-container-low">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-outline-variant/30">
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Device (Machine)</th>
                            <th class="px-6 py-4 text-right">Usage (kWh)</th>
                            <th class="px-6 py-4 text-right">Peak Load (kW)</th>
                            <th class="px-6 py-4 text-right">Avg Voltage (V)</th>
                            <th class="px-6 py-4 text-right">Avg PF</th>
                            <th class="px-6 py-4 text-right">Samples</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @forelse($reports as $row)
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs font-medium text-on-surface">{{ \Carbon\Carbon::parse($row->recorded_date)->format('d M Y') }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-on-surface">{{ $row->device->name }}</div>
                                    <div class="text-[10px] text-outline mt-0.5">
                                        @if($row->device->machine_id)
                                            <a href="{{ route('monitoring.meters', ['id' => $row->device->machine_id]) }}" class="hover:text-primary hover:underline">{{ $row->device->machine->name }}</a>
                                        @else
                                            Unassigned
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-black text-primary">{{ number_format($row->kwh_usage, 2) }}</td>
                                <td class="px-6 py-4 text-right font-medium text-error">{{ number_format($row->max_power_kw, 2) }}</td>
                                <td class="px-6 py-4 text-right font-medium text-on-surface-variant">{{ number_format($row->avg_voltage, 1) }}</td>
                                <td class="px-6 py-4 text-right font-medium text-tertiary">{{ number_format($row->avg_power_factor, 2) }}</td>
                                <td class="px-6 py-4 text-right font-mono text-xs text-outline">{{ number_format($row->total_sample_count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-outline italic">No data found for this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reports->hasPages())
                <div class="px-6 py-4 border-t border-surface-container-low">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </div>
</main>
@endsection
