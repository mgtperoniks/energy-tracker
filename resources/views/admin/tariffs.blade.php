@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Tariff Management</h1>
            <p class="text-on-surface-variant text-sm mt-1">Kelola dan jadwalkan tarif listrik untuk akurasi perhitungan akuntansi.</p>
        </div>

        @if(session('success'))
            <div class="bg-primary-container/30 border-l-4 border-primary text-primary-dark px-4 py-3 rounded mb-8 text-sm font-bold">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-error-container/30 border-l-4 border-error text-error px-4 py-3 rounded mb-8 text-sm font-bold">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Active Tariff Card -->
            <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm border-b-2 border-primary-container lg:col-span-1">
                <div class="flex justify-between items-start mb-6">
                    <span class="text-xs font-bold uppercase tracking-wider text-outline">Current Active Tariff</span>
                    @if($activeTariff)
                        <span class="bg-secondary-container text-on-secondary-container px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest">Active</span>
                    @else
                        <span class="bg-error/20 text-error px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest">No Active</span>
                    @endif
                </div>
                
                @if($activeTariff)
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-2xl font-bold text-outline">Rp</span>
                        <span class="text-4xl font-black tracking-tight text-primary">{{ number_format($activeTariff->rate_per_kwh, 2) }}</span>
                        <span class="text-sm font-bold text-outline">/ kWh</span>
                    </div>
                    <div class="mt-6 pt-4 border-t border-surface-container-low text-xs text-on-surface-variant font-medium">
                        Effective Since: <span class="font-bold text-on-surface">{{ \Carbon\Carbon::parse($activeTariff->effective_date)->format('d M Y') }}</span>
                        @if($activeTariff->notes)
                            <p class="mt-2 text-outline italic">"{{ $activeTariff->notes }}"</p>
                        @endif
                    </div>
                @else
                    <div class="text-center py-6 opacity-50">
                        <span class="material-symbols-outlined text-4xl mb-2">payments</span>
                        <p class="text-sm font-bold uppercase tracking-widest">Belum ada Tarif</p>
                    </div>
                @endif
            </div>

            <!-- Create New Tariff Form -->
            <div class="bg-surface-container-lowest rounded-lg shadow-sm border border-surface-container-low lg:col-span-2 overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-surface-container-low bg-surface-container-low/30">
                    <h2 class="text-sm font-bold tracking-tight text-on-surface uppercase flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">add_circle</span>
                        Schedule New Tariff
                    </h2>
                </div>
                <div class="p-6 flex-1">
                    <form action="{{ route('admin.tariffs.store') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Rate per kWh (Rp)</label>
                                <input type="number" step="0.01" name="rate_per_kwh" required min="1" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-3 font-mono font-bold text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" placeholder="Misal: 1444.70">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Effective Date</label>
                                <input type="date" name="effective_date" required class="w-full bg-surface border border-outline-variant rounded-md text-sm p-3 font-mono font-bold text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                            </div>
                        </div>
                        <div class="mb-6">
                            <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Notes (Optional)</label>
                            <input type="text" name="notes" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-3 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" placeholder="Catatan internal, cth: Kenaikan TDL Kuartal 3">
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-3 bg-primary text-white font-bold rounded-md hover:bg-primary/90 transition-colors uppercase tracking-wider text-xs">
                                Schedule Tariff
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Upcoming Tariffs -->
            <div class="bg-surface-container-lowest rounded-lg shadow-sm border border-surface-container-low overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-surface-container-low flex justify-between items-center bg-tertiary-container/10">
                    <h2 class="text-sm font-bold tracking-tight text-tertiary uppercase flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">schedule</span>
                        Upcoming Tariffs
                    </h2>
                </div>
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-outline-variant/30">
                            <tr>
                                <th class="px-6 py-3">Effective Date</th>
                                <th class="px-6 py-3 text-right">Rate / kWh</th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low">
                            @forelse($upcomingTariffs as $tariff)
                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                    <td class="px-6 py-4 font-mono text-xs font-bold">{{ \Carbon\Carbon::parse($tariff->effective_date)->format('d M Y') }}</td>
                                    <td class="px-6 py-4 text-right font-bold text-tertiary">Rp {{ number_format($tariff->rate_per_kwh, 2) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-tertiary-container text-on-tertiary-container px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest">Scheduled</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-outline italic text-xs">No upcoming tariffs scheduled.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Historical Tariffs -->
            <div class="bg-surface-container-lowest rounded-lg shadow-sm border border-surface-container-low overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-surface-container-low flex justify-between items-center bg-surface-container-low/30">
                    <h2 class="text-sm font-bold tracking-tight text-on-surface uppercase flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">history</span>
                        Historical Tariffs (Read-Only)
                    </h2>
                </div>
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-outline-variant/30">
                            <tr>
                                <th class="px-6 py-3">Effective Date</th>
                                <th class="px-6 py-3 text-right">Rate / kWh</th>
                                <th class="px-6 py-3">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low">
                            @forelse($historicalTariffs as $tariff)
                                <tr class="hover:bg-surface-container-low/50 transition-colors opacity-70 hover:opacity-100">
                                    <td class="px-6 py-4 font-mono text-xs font-medium">{{ \Carbon\Carbon::parse($tariff->effective_date)->format('d M Y') }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-on-surface-variant">Rp {{ number_format($tariff->rate_per_kwh, 2) }}</td>
                                    <td class="px-6 py-4 text-xs text-outline">{{ $tariff->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-outline italic text-xs">No historical records.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
