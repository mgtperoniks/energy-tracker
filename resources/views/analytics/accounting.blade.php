@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Accounting Report</h1>
                <p class="text-on-surface-variant text-sm mt-1">Laporan estimasi biaya listrik per departemen/mesin.</p>
            </div>
            <div class="flex items-center gap-2">
                @if($isFiltered && $reports->isNotEmpty())
                    <a href="{{ route('analytics.accounting.export', request()->all()) }}" class="px-4 py-2 bg-secondary text-on-secondary font-medium rounded-md hover:bg-secondary/90 transition-colors flex items-center gap-2 text-sm">
                        <span class="material-symbols-outlined text-sm">download</span>
                        Excel
                    </a>
                    <a href="{{ route('analytics.accounting.pdf', request()->all()) }}" target="_blank" class="px-4 py-2 bg-error text-on-error font-medium rounded-md hover:bg-error/90 transition-colors flex items-center gap-2 text-sm">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                        PDF
                    </a>
                @else
                    <button type="button" disabled class="px-4 py-2 bg-surface-container-high text-outline font-medium rounded-md cursor-not-allowed flex items-center gap-2 text-sm opacity-50" title="Excel and PDF export tersedia setelah laporan dihasilkan">
                        <span class="material-symbols-outlined text-sm">download</span>
                        Excel
                    </button>
                    <button type="button" disabled class="px-4 py-2 bg-surface-container-high text-outline font-medium rounded-md cursor-not-allowed flex items-center gap-2 text-sm opacity-50" title="Excel and PDF export tersedia setelah laporan dihasilkan">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                        PDF
                    </button>
                @endif
            </div>
        </div>

        @if($errors->has('date_range'))
            <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-lg border border-error/20 flex items-center gap-4 shadow-sm animate-fade-in">
                <span class="material-symbols-outlined text-2xl text-error">error</span>
                <div>
                    <h3 class="font-black text-xs uppercase tracking-widest">Validation Error</h3>
                    <p class="text-[11px] font-medium opacity-90">
                        {{ $errors->first('date_range') }}
                    </p>
                </div>
            </div>
        @endif

        <!-- Filter Area -->
        <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm border border-surface-container-low mb-8">
            <form method="GET" action="{{ route('analytics.accounting') }}" class="flex flex-col md:flex-row gap-4 items-end">
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
                <button type="submit" class="px-6 py-2 bg-primary text-on-primary font-bold rounded-md hover:bg-primary/90 transition-colors h-[38px] flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    Calculate
                </button>
            </form>
        </div>

        @if(!$isFiltered)
            <!-- Empty State Panel -->
            <div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden border border-surface-container-low p-12 text-center">
                <div class="w-16 h-16 bg-primary/10 text-primary rounded-full flex items-center justify-center mb-4 mx-auto">
                    <span class="material-symbols-outlined text-4xl">payments</span>
                </div>
                <h3 class="text-lg font-bold text-on-surface mb-2">Belum ada laporan accounting yang ditampilkan.</h3>
                <p class="text-on-surface-variant text-sm max-w-md mb-6 mx-auto">Silakan pilih rentang tanggal dan device yang ingin dianalisis.</p>
                
                <div class="bg-surface-container-low p-6 rounded-lg text-left max-w-md w-full border border-outline-variant/30 mx-auto">
                    <h4 class="text-xs font-black uppercase tracking-wider text-outline mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">info</span>
                        Panduan:
                    </h4>
                    <ul class="text-xs text-on-surface-variant space-y-2">
                        <li class="flex items-start gap-2">
                            <span class="text-primary mt-0.5">•</span>
                            <span>Maksimal rentang laporan 45 hari</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary mt-0.5">•</span>
                            <span>Pilih All Devices untuk seluruh mesin</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary mt-0.5">•</span>
                            <span>Excel dan PDF export tersedia setelah laporan dihasilkan</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary mt-0.5">•</span>
                            <span>Laporan biaya menggunakan tarif listrik yang tersimpan pada histori data</span>
                        </li>
                    </ul>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Cost Summary -->
                <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm border-b-2 border-tertiary-container col-span-1 flex flex-col justify-center">
                    <span class="text-xs font-bold uppercase tracking-wider text-outline mb-2">Total Energy Cost (Range)</span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-outline">Rp</span>
                        <span class="text-4xl font-black tracking-tight text-on-surface">{{ number_format($totalCost, 0) }}</span>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-2 font-medium">Aggregate dari Daily Reading Frozen Data</p>
                </div>

                <!-- Top Ranking -->
                <div class="bg-surface-container-lowest rounded-lg shadow-sm border border-surface-container-low col-span-1 lg:col-span-2">
                    <div class="px-6 py-4 border-b border-surface-container-low">
                        <h2 class="text-sm font-bold uppercase tracking-tight text-on-surface">Top Devices Cost Ranking</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        @forelse($topDevices as $index => $top)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full bg-surface-container-high flex items-center justify-center text-[10px] font-black text-on-surface">#{{ $index + 1 }}</div>
                                    <div>
                                        <p class="text-sm font-bold text-on-surface">{{ $top->device->name }}</p>
                                        <p class="text-[10px] text-outline">{{ $top->device->machine->name ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-primary">Rp {{ number_format($top->total_device_cost, 0) }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-outline italic text-center">No cost data available</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden border border-surface-container-low">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-outline-variant/30">
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Device</th>
                                <th class="px-6 py-4 text-right">Usage (kWh)</th>
                                <th class="px-6 py-4 text-right">Rate (Rp)</th>
                                <th class="px-6 py-4 text-right">Biaya (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low">
                            @forelse($reports as $row)
                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs font-medium text-on-surface">{{ \Carbon\Carbon::parse($row->recorded_date)->format('d M Y') }}</span>
                                            {!! $row->data_source_badge !!}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-on-surface">{{ $row->device->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-on-surface-variant">{{ number_format($row->kwh_usage, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-[10px] text-outline">Rp {{ number_format($row->applied_rate, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-black text-tertiary">Rp {{ number_format($row->energy_cost, 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-outline italic">No cost data found.</td>
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
        @endif
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function (e) {
                const startDateVal = document.querySelector('input[name="start_date"]').value;
                const endDateVal = document.querySelector('input[name="end_date"]').value;
                
                if (startDateVal && endDateVal) {
                    const start = new Date(startDateVal);
                    const end = new Date(endDateVal);
                    
                    if (start > end) {
                        e.preventDefault();
                        alert('Tanggal mulai tidak boleh melebihi tanggal akhir.');
                        return;
                    }
                    
                    const diffTime = Math.abs(end - start);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    if (diffDays > 45) {
                        e.preventDefault();
                        alert('Rentang tanggal maksimal 45 hari. Silakan gunakan filter yang lebih spesifik.');
                    }
                }
            });
        }
    });
</script>
@endsection
