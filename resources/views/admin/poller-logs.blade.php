@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Poller Logs Explorer</h1>
            <p class="text-on-surface-variant text-sm mt-1">Eksplorasi log sistem, error poller, dan histori perubahan konfigurasi.</p>
        </div>

        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-surface-container-low mb-8">
            <form method="GET" action="{{ route('admin.poller-logs') }}" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-64">
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Filter Device</label>
                    <select name="device_id" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        <option value="">All Devices</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" {{ $deviceId == $device->id ? 'selected' : '' }}>
                                {{ $device->name }} {{ $device->machine ? '('.$device->machine->name.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full md:w-48">
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Status / Category</label>
                    <select name="status" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        <option value="">All Status</option>
                        <option value="offline" {{ $status == 'offline' ? 'selected' : '' }}>OFFLINE</option>
                        <option value="warning" {{ $status == 'warning' ? 'selected' : '' }}>WARNING</option>
                        <option value="error" {{ $status == 'error' ? 'selected' : '' }}>ERROR</option>
                        <option value="SYSTEM_CONFIG" {{ $status == 'SYSTEM_CONFIG' ? 'selected' : '' }}>SYSTEM_CONFIG</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-2 bg-primary text-on-primary font-bold rounded-md hover:bg-primary/90 transition-colors h-[38px] flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">search</span>
                        Filter
                    </button>
                    <a href="{{ route('admin.poller-logs') }}" class="px-6 py-2 bg-surface-container-high text-on-surface font-bold rounded-md hover:bg-surface-container-highest transition-colors h-[38px] flex items-center justify-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden border border-surface-container-low">
            <div class="px-6 py-4 border-b border-surface-container-low bg-surface-container-low/30">
                <h3 class="text-sm font-bold tracking-tight text-on-surface uppercase flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">list_alt</span>
                    System Event Logs
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-surface-container-low">
                            <th class="px-6 py-4 w-48">Timestamp</th>
                            <th class="px-6 py-4 w-32 text-center">Status</th>
                            <th class="px-6 py-4">Device / Source</th>
                            <th class="px-6 py-4">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @forelse($logs as $log)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-outline whitespace-nowrap">
                                {{ $log->event_at->format('d M Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $badgeClass = match($log->status) {
                                        'error' => 'bg-error-container text-error',
                                        'warning' => 'bg-tertiary-container text-tertiary',
                                        'offline' => 'bg-surface-container-highest text-outline',
                                        'SYSTEM_CONFIG' => 'bg-primary-container text-primary-dark',
                                        default => 'bg-surface-container-high text-on-surface-variant'
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider {{ $badgeClass }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($log->device)
                                    <div class="font-bold text-xs text-on-surface">{{ $log->device->name }}</div>
                                    <div class="text-[10px] text-outline italic">{{ $log->device->machine->name ?? 'No Machine' }}</div>
                                @else
                                    <span class="text-[10px] text-outline font-bold uppercase">System</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-on-surface-variant leading-relaxed">
                                {{ $log->message }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-outline italic text-sm">
                                No logs found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-surface-container-low bg-surface-container-low/10">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</main>
@endsection
