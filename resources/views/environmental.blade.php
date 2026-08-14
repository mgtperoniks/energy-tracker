@extends('layouts.app')

@section('content')
<style>
    @media print {
        body {
            background: white !important;
            color: black !important;
        }
        /* Hide layout navigation/sidebars and filter/export controls */
        .no-print, 
        nav, 
        aside, 
        header, 
        footer, 
        button,
        form,
        .print-hide {
            display: none !important;
        }
        /* Remove margin/padding overrides for main content */
        main {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .md\:ml-64 {
            margin-left: 0 !important;
        }
        /* Table borders and style adjustments for black and white */
        table {
            border: 1px solid #ccc !important;
            width: 100% !important;
            border-collapse: collapse !important;
        }
        th, td {
            border: 1px solid #ccc !important;
            padding: 8px !important;
            color: black !important;
        }
    }
</style>

<main class="md:ml-64 pt-16 p-8 min-h-screen bg-background">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8 print-hide">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-on-surface mb-1">Environmental Monitoring</h1>
                <p class="text-on-surface-variant text-sm">Real-time and historical temperature & humidity telemetry</p>
            </div>
        </div>

        {{-- Current Readings Section (Always Unfiltered) --}}
        <section class="mb-10 print-hide">
            <h2 class="text-base font-semibold uppercase tracking-widest mb-4 text-on-surface-variant">Current Readings</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($readings as $row)
                    @php
                        $isOnline = $row['latest'] && $row['latest']->recorded_at->diffInMinutes(now()) < 30;
                    @endphp
                    <div class="bg-surface-container-lowest p-6 rounded-xl shadow-[0_24px_40px_-4px_rgba(25,28,30,0.05)] border-l-4 {{ $isOnline ? 'border-primary' : 'border-outline' }} flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="font-bold text-on-surface text-base">{{ $row['device']->name }}</h3>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $isOnline ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $isOnline ? 'Online' : 'Offline' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant block mb-1">Temperature</span>
                                    @if($row['latest'] && $row['latest']->temperature !== null)
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-2xl font-extrabold tracking-tight text-on-surface font-mono">{{ number_format($row['latest']->temperature, 1) }}</span>
                                            <span class="text-on-surface-variant text-sm font-medium">°C</span>
                                        </div>
                                    @else
                                        <span class="text-on-surface-variant font-mono text-xl">—</span>
                                    @endif
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant block mb-1">Humidity</span>
                                    @if($row['latest'] && $row['latest']->humidity !== null)
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-2xl font-extrabold tracking-tight text-on-surface font-mono">{{ number_format($row['latest']->humidity, 1) }}</span>
                                            <span class="text-on-surface-variant text-sm font-medium">%RH</span>
                                        </div>
                                    @else
                                        <span class="text-on-surface-variant font-mono text-xl">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-outline-variant/30 pt-3 flex justify-between items-center text-xs text-on-surface-variant">
                            <span>Last Recorded</span>
                            <span class="font-mono">{{ $row['latest'] ? $row['latest']->recorded_at->format('Y-m-d H:i:s') : 'No data yet' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 bg-surface-container-lowest p-6 rounded-xl text-center text-on-surface-variant border border-outline-variant/30">
                        No active environmental sensors configured.
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Filter Block --}}
        <section class="bg-surface-container-lowest p-6 rounded-xl border border-outline-variant/30 mb-8 print-hide">
            <h3 class="text-sm font-bold uppercase tracking-wider text-on-surface-variant mb-4">Historical Filter</h3>
            
            <form method="GET" action="{{ route('monitoring.environmental') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                {{-- Sensor --}}
                <div>
                    <label for="sensor" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Sensor</label>
                    <select id="sensor" name="sensor" class="w-full bg-surface-container-lowest text-on-surface border border-outline-variant/50 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="all" {{ request('sensor') == 'all' ? 'selected' : '' }}>All Sensors</option>
                        @foreach($devices as $d)
                            <option value="{{ $d->id }}" {{ request('sensor') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Period --}}
                <div>
                    <label for="period" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Period</label>
                    <select id="period" name="period" class="w-full bg-surface-container-lowest text-on-surface border border-outline-variant/50 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary" onchange="toggleDateInputs()">
                        <option value="today" {{ request('period', 'today') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ request('period') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="last_24_hours" {{ request('period') == 'last_24_hours' ? 'selected' : '' }}>Last 24 Hours</option>
                        <option value="last_7_days" {{ request('period') == 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="this_month" {{ request('period') == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="custom" {{ request('period') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div id="date_from_group">
                    <label for="from" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Date From</label>
                    <input type="date" id="from" name="from" value="{{ request('from') }}" class="w-full bg-surface-container-lowest text-on-surface border border-outline-variant/50 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                </div>

                {{-- Date To --}}
                <div id="date_to_group">
                    <label for="to" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Date To</label>
                    <input type="date" id="to" name="to" value="{{ request('to') }}" class="w-full bg-surface-container-lowest text-on-surface border border-outline-variant/50 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-primary-container to-primary text-white px-4 py-2 rounded-md text-sm font-semibold hover:saturate-150 transition-all text-center">
                        Apply
                    </button>
                    <a href="{{ route('monitoring.environmental') }}" class="flex-1 bg-surface-container-high text-on-surface px-4 py-2 rounded-md text-sm font-semibold hover:bg-surface-container-highest transition-colors text-center">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        {{-- Export / Print Actions --}}
        <div class="flex flex-wrap gap-3 mb-6 print-hide">
            <a href="{{ route('monitoring.environmental.export.excel', request()->all()) }}" class="bg-surface-container-high text-on-surface px-4 py-2 rounded-md text-sm font-medium hover:bg-surface-container-highest transition-colors flex items-center gap-2">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel
            </a>
            <a href="{{ route('monitoring.environmental.export.pdf', request()->all()) }}" target="_blank" class="bg-surface-container-high text-on-surface px-4 py-2 rounded-md text-sm font-medium hover:bg-surface-container-highest transition-colors flex items-center gap-2">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export PDF
            </a>
            <button onclick="window.print()" class="bg-surface-container-high text-on-surface px-4 py-2 rounded-md text-sm font-medium hover:bg-surface-container-highest transition-colors flex items-center gap-2">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Report
            </button>
        </div>

        {{-- Readings Data Table --}}
        <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 overflow-hidden shadow-[0_24px_40px_-4px_rgba(25,28,30,0.02)]">
            <div class="p-6 border-b border-outline-variant/30 flex justify-between items-center">
                <h2 class="text-base font-semibold uppercase tracking-wider text-on-surface">Historical Readings</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-container text-on-surface-variant text-left">
                        <tr>
                            <th class="px-6 py-3.5 font-semibold">Recorded At</th>
                            <th class="px-6 py-3.5 font-semibold">Sensor</th>
                            <th class="px-6 py-3.5 font-semibold">Temperature</th>
                            <th class="px-6 py-3.5 font-semibold">Humidity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($history as $r)
                        <tr class="hover:bg-surface-container/30">
                            <td class="px-6 py-4 font-mono font-medium text-on-surface-variant">{{ $r->recorded_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-6 py-4 font-medium text-on-surface">{{ $r->device->name ?? '—' }}</td>
                            <td class="px-6 py-4 font-mono text-on-surface">{{ number_format($r->temperature, 1) }} °C</td>
                            <td class="px-6 py-4 font-mono text-on-surface">{{ number_format($r->humidity, 1) }} %RH</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-on-surface-variant">No environmental readings found matching filter.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($history->hasPages())
                <div class="px-6 py-4 border-t border-outline-variant/30 bg-surface-container-low/10 print-hide">
                    {{ $history->links() }}
                </div>
            @endif
        </section>
    </div>
</main>

<script>
    function toggleDateInputs() {
        var period = document.getElementById('period').value;
        var fromInput = document.getElementById('from');
        var toInput = document.getElementById('to');
        
        if (period === 'custom') {
            fromInput.disabled = false;
            toInput.disabled = false;
            fromInput.required = true;
            toInput.required = true;
            document.getElementById('date_from_group').style.opacity = '1';
            document.getElementById('date_to_group').style.opacity = '1';
        } else {
            fromInput.disabled = true;
            toInput.disabled = true;
            fromInput.required = false;
            toInput.required = false;
            document.getElementById('date_from_group').style.opacity = '0.5';
            document.getElementById('date_to_group').style.opacity = '0.5';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleDateInputs();
    });
</script>
@endsection
