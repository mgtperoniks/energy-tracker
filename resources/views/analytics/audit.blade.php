@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">System Audit Report</h1>
            <p class="text-on-surface-variant text-sm mt-1">Lacak dan identifikasi anomali mesin, error koneksi, dan reset perangkat keras.</p>
        </div>

        <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm border border-surface-container-low mb-8">
            <form method="GET" action="{{ route('analytics.audit') }}" class="flex flex-col md:flex-row gap-4 items-end flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Date Range</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="start_date" value="{{ $startDate }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        <span class="text-outline-variant font-bold">to</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    </div>
                </div>
                
                <div class="w-full md:w-48">
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Device</label>
                    <select name="device_id" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        <option value="">All Devices</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" {{ $deviceId == $device->id ? 'selected' : '' }}>
                                {{ $device->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full md:w-32">
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Severity</label>
                    <select name="severity" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        <option value="">ALL</option>
                        <option value="WARNING" {{ $severity == 'WARNING' ? 'selected' : '' }}>WARNING</option>
                        <option value="ERROR" {{ $severity == 'ERROR' ? 'selected' : '' }}>ERROR</option>
                    </select>
                </div>

                <div class="w-full md:w-40">
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Event Type</label>
                    <select name="event_type" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        <option value="">All Events</option>
                        <option value="poller" {{ $eventType == 'poller' ? 'selected' : '' }}>Poller Events</option>
                        <option value="anomaly" {{ $eventType == 'anomaly' ? 'selected' : '' }}>Anomalies</option>
                        <option value="reset" {{ $eventType == 'reset' ? 'selected' : '' }}>Hardware Resets</option>
                    </select>
                </div>

                <button type="submit" class="px-6 py-2 bg-primary text-on-primary font-bold rounded-md hover:bg-primary/90 transition-colors h-[38px] flex items-center justify-center gap-2 w-full md:w-auto">
                    <span class="material-symbols-outlined text-sm">search</span>
                    Audit
                </button>
            </form>
        </div>

        <div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden border border-surface-container-low">
            <div class="px-6 py-4 border-b border-surface-container-low flex justify-between items-center bg-surface-container-low/30">
                <h2 class="text-sm font-bold tracking-tight text-on-surface uppercase flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">terminal</span>
                    Audit Trail Log (Max 100/Type)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-outline-variant/30">
                            <th class="px-6 py-4 w-48">Timestamp</th>
                            <th class="px-6 py-4 w-32">Type</th>
                            <th class="px-6 py-4 w-24">Severity</th>
                            <th class="px-6 py-4 w-48">Device</th>
                            <th class="px-6 py-4">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @forelse($auditTrail as $row)
                            <tr class="hover:bg-surface-container-low/50 transition-colors group">
                                <td class="px-6 py-4 font-mono text-xs font-bold text-outline">{{ \Carbon\Carbon::parse($row->timestamp)->format('d M y H:i:s') }}</td>
                                <td class="px-6 py-4">
                                    <span class="bg-surface-container-high px-2 py-1 rounded text-[10px] font-bold text-on-surface uppercase">{{ $row->type }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $badgeClass = match($row->severity) {
                                            'CRITICAL' => 'bg-error text-white',
                                            'ERROR' => 'bg-error-container text-error',
                                            'WARNING' => 'bg-tertiary-container text-tertiary',
                                            default => 'bg-outline-variant text-outline'
                                        };
                                    @endphp
                                    <span class="{{ $badgeClass }} px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight">{{ $row->severity }}</span>
                                </td>
                                <td class="px-6 py-4 font-bold text-on-surface">
                                    @if($row->device && $row->device->machine_id)
                                        <a href="{{ route('monitoring.meters', ['id' => $row->device->machine_id]) }}" class="text-primary hover:underline">{{ $row->device->name }}</a>
                                    @else
                                        {{ $row->device->name ?? 'Unknown' }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs font-medium text-on-surface-variant leading-relaxed">{{ $row->message }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2">shield_with_heart</span>
                                    <p class="text-sm font-bold text-outline uppercase tracking-widest">Sistem Bersih</p>
                                    <p class="text-xs text-outline italic">Tidak ada catatan audit yang cocok.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
@endsection
