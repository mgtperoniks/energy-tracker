@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Power Meters (Devices)</h1>
            <p class="text-on-surface-variant text-sm mt-1">Daftar perangkat keras power meter yang terdaftar di sistem.</p>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden border border-surface-container-low">
            <div class="p-6 border-b border-surface-container-low bg-surface-container-low/30">
                <h3 class="text-sm font-bold tracking-tight text-on-surface uppercase">Registered Devices</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-surface-container-low">
                            <th class="px-6 py-4">Slave ID</th>
                            <th class="px-6 py-4">Device Name</th>
                            <th class="px-6 py-4">Machine Link</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @foreach($devices as $device)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold">{{ $device->slave_id }}</td>
                            <td class="px-6 py-4 font-bold">{{ $device->name }}</td>
                            <td class="px-6 py-4">
                                {{ $device->machine ? $device->machine->code . ' - ' . $device->machine->name : 'Unlinked' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($device->is_online)
                                    <span class="text-secondary font-bold text-[10px] uppercase">Online</span>
                                @else
                                    <span class="text-outline font-bold text-[10px] uppercase">Offline</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-8 p-4 bg-primary/5 rounded-xl border border-primary/10 flex items-center gap-4">
            <span class="material-symbols-outlined text-primary text-3xl">info</span>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Untuk konfigurasi detail koneksi Modbus, Slave ID, dan API Token, silakan gunakan menu 
                <a href="{{ route('admin.device-config') }}" class="text-primary font-bold hover:underline italic">Administration > Device Config</a>.
            </p>
        </div>
    </div>
</main>
@endsection
