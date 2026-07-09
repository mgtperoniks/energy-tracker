@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        
        <!-- HEADER WITH DISABLED DOWNLOAD BUTTONS ALIGNED TO TOP RIGHT -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Production Historian</h1>
                <p class="text-on-surface-variant text-sm mt-1">Analisis performa manufaktur berbasis cycle produksi (Melting &rarr; Pouring).</p>
            </div>
            <div class="flex items-center gap-2">
                @if($isFiltered && count($cycles) > 0)
                    <a href="{{ route('analytics.historian.export', request()->query()) }}" class="px-4 py-2 bg-primary text-on-primary hover:bg-primary/90 font-medium rounded-md flex items-center gap-2 text-sm transition-all shadow-sm">
                        <span class="material-symbols-outlined text-sm">download</span> Excel
                    </a>
                    <a href="{{ route('analytics.historian.pdf', request()->query()) }}" target="_blank" class="px-4 py-2 bg-primary text-on-primary hover:bg-primary/90 font-medium rounded-md flex items-center gap-2 text-sm transition-all shadow-sm">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span> PDF
                    </a>
                @else
                    <button type="button" disabled class="px-4 py-2 bg-surface-container-high text-outline font-medium rounded-md cursor-not-allowed flex items-center gap-2 text-sm opacity-50" title="Excel export akan tersedia setelah filter diterapkan">
                        <span class="material-symbols-outlined text-sm">download</span> Excel
                    </button>
                    <button type="button" disabled class="px-4 py-2 bg-surface-container-high text-outline font-medium rounded-md cursor-not-allowed flex items-center gap-2 text-sm opacity-50" title="PDF export akan tersedia setelah filter diterapkan">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span> PDF
                    </button>
                @endif
            </div>
        </div>

        <!-- VALIDATION ERRORS -->
        @if($errors->any())
            <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-lg border border-error/20 flex items-center gap-4 shadow-sm animate-fade-in">
                <span class="material-symbols-outlined text-2xl text-error">error</span>
                <div>
                    <h3 class="font-black text-xs uppercase tracking-widest">Validation Error</h3>
                    <ul class="text-[11px] font-medium opacity-90 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- FILTER AREA -->
        <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm border border-surface-container-low mb-8">
            <form method="GET" action="{{ route('analytics.historian') }}" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Filter by Device *</label>
                    <select name="device_id" required class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        <option value="">Pilih Device...</option>
                        @foreach($devices as $dev)
                            <option value="{{ $dev->id }}" {{ $deviceId == $dev->id ? 'selected' : '' }}>
                                {{ $dev->name }} {{ $dev->machine ? '('.$dev->machine->name.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 w-full">
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Start Datetime</label>
                    <input type="datetime-local" name="start_datetime" value="{{ $startDatetime }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="flex-1 w-full">
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">End Datetime</label>
                    <input type="datetime-local" name="end_datetime" value="{{ $endDatetime }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <button type="submit" class="px-6 py-2 bg-primary text-on-primary font-bold rounded-md hover:bg-primary/90 transition-colors h-[38px] flex items-center justify-center gap-2 w-full md:w-auto">
                    <span class="material-symbols-outlined text-sm">filter_alt</span> Apply Filter
                </button>
            </form>
        </div>

        @if(!$isFiltered)
            <!-- Empty State Panel -->
            <div class="bg-surface-container-lowest rounded-lg shadow-sm border border-surface-container-low overflow-hidden">
                <div class="flex flex-col items-center justify-center p-12 text-center">
                    <div class="w-16 h-16 bg-primary/10 text-primary rounded-full flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-4xl">history</span>
                    </div>
                    <h3 class="text-lg font-bold text-on-surface mb-2">Belum ada data Production Historian yang ditampilkan.</h3>
                    <p class="text-on-surface-variant text-sm max-w-md mb-6">Silakan pilih device dan rentang waktu di panel filter untuk merekonstruksi cycle produksi.</p>
                    
                    <div class="bg-surface-container-low p-6 rounded-lg text-left max-w-md w-full border border-outline-variant/30">
                        <h4 class="text-xs font-black uppercase tracking-wider text-outline mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">info</span>
                            Panduan:
                        </h4>
                        <ul class="text-xs text-on-surface-variant space-y-2">
                            <li class="flex items-start gap-2">
                                <span class="text-primary mt-0.5">•</span>
                                <span>Pilihan Device bersifat wajib (wajib memilih satu device).</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary mt-0.5">•</span>
                                <span>Default rentang waktu adalah 7 hari terakhir.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary mt-0.5">•</span>
                                <span>Maksimal rentang waktu pencarian adalah 45 hari.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        @else
            <!-- WARNING BANNER IF INCOMPLETE CYCLES FOUND -->
            @if(isset($kpi['incomplete_cycles']) && $kpi['incomplete_cycles'] > 0)
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 text-red-900 dark:text-red-200 rounded-lg border border-red-200 dark:border-red-900/40 flex items-center gap-4 shadow-sm animate-pulse">
                    <span class="material-symbols-outlined text-2xl text-red-600 dark:text-red-400">warning</span>
                    <div>
                        <h3 class="font-black text-xs uppercase tracking-widest text-red-800 dark:text-red-300">Incomplete Tagging Warning</h3>
                        <p class="text-[11px] font-medium opacity-90 mt-0.5">
                            Terdeteksi <strong>{{ $kpi['incomplete_cycles'] }} cycle incomplete</strong> (Melting berurutan tanpa Pouring). Hal ini mengindikasikan kemungkinan operator lupa mencatat tag 'pour'. Silakan periksa tabel detail di bawah.
                        </p>
                    </div>
                </div>
            @endif

            <!-- KPI SUMMARY CARDS -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <!-- 1. Closed Cycles -->
                <div class="bg-surface-container-lowest p-4 rounded-lg border border-surface-container-low shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-outline block">Closed Cycles</span>
                        <span class="text-2xl font-extrabold text-on-surface mt-1 block">{{ $kpi['closed_cycles'] }}</span>
                    </div>
                    <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mt-2 block">Normal state</span>
                </div>

                <!-- 2. Open Cycles -->
                <div class="bg-surface-container-lowest p-4 rounded-lg border border-surface-container-low shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-outline block">Open Cycles</span>
                        <span class="text-2xl font-extrabold text-on-surface mt-1 block">{{ $kpi['open_cycles'] }}</span>
                    </div>
                    <span class="text-[9px] font-bold uppercase tracking-wider mt-2 block {{ $kpi['open_cycles'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                        {{ $kpi['open_cycles'] > 0 ? '⚠️ Active Shift' : 'No active cycle' }}
                    </span>
                </div>

                <!-- 3. Incomplete Cycles -->
                <div class="bg-surface-container-lowest p-4 rounded-lg border border-surface-container-low shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-outline block">Incomplete Cycles</span>
                        <span class="text-2xl font-extrabold text-on-surface mt-1 block">{{ $kpi['incomplete_cycles'] }}</span>
                    </div>
                    <span class="text-[9px] font-bold uppercase tracking-wider mt-2 block {{ $kpi['incomplete_cycles'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-400' }}">
                        {{ $kpi['incomplete_cycles'] > 0 ? '❌ Tagging Gap' : 'Integrity OK' }}
                    </span>
                </div>

                <!-- 4. Tag Integrity -->
                <div class="bg-surface-container-lowest p-4 rounded-lg border border-surface-container-low shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-outline block">Tag Integrity</span>
                        <span class="text-2xl font-extrabold text-on-surface mt-1 block">
                            {{ $kpi['tag_integrity_pct'] !== null ? $kpi['tag_integrity_pct'] . '%' : '—' }}
                        </span>
                    </div>
                    @if($kpi['tag_integrity_pct'] !== null)
                        <span class="text-[9px] font-bold uppercase tracking-wider mt-2 block {{ $kpi['tag_integrity_pct'] >= 95 ? 'text-secondary' : ($kpi['tag_integrity_pct'] >= 85 ? 'text-amber-500' : 'text-red-500') }}">
                            {{ $kpi['tag_integrity_pct'] >= 95 ? 'Excellent' : ($kpi['tag_integrity_pct'] >= 85 ? 'Needs Review' : 'Poor') }}
                        </span>
                    @else
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-2 block">No closed/incomplete</span>
                    @endif
                </div>

                <!-- 5. Average Duration -->
                <div class="bg-surface-container-lowest p-4 rounded-lg border border-surface-container-low shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-outline block">Avg Duration</span>
                        <span class="text-2xl font-extrabold text-on-surface mt-1 block">{{ $kpi['avg_duration_human'] }}</span>
                    </div>
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-2 block">CLOSED cycles only</span>
                </div>

                <!-- 6. Total Energy -->
                <div class="bg-surface-container-lowest p-4 rounded-lg border border-surface-container-low shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-outline block">Total Energy</span>
                        <span class="text-2xl font-extrabold text-primary mt-1 block">{{ number_format($kpi['total_kwh'], 1) }} <span class="text-xs font-normal text-outline">kWh</span></span>
                    </div>
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-2 block">Excl. Incomplete</span>
                </div>

                <!-- 7. Total Cost -->
                <div class="bg-surface-container-lowest p-4 rounded-lg border border-surface-container-low shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-outline block">Total Cost</span>
                        <span class="text-2xl font-extrabold text-on-surface mt-1 block text-slate-800 dark:text-slate-200">
                            Rp {{ number_format($kpi['total_cost'] / 1000, 1) }}k
                        </span>
                    </div>
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-2 block">Estimated tariff</span>
                </div>

                <!-- 8. Average kWh/Cycle -->
                <div class="bg-surface-container-lowest p-4 rounded-lg border border-surface-container-low shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-outline block">Avg kWh/Cycle</span>
                        <span class="text-2xl font-extrabold text-on-surface mt-1 block">
                            {{ $kpi['avg_kwh_per_cycle'] !== null ? number_format($kpi['avg_kwh_per_cycle'], 1) . ' kWh' : '—' }}
                        </span>
                    </div>
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-2 block">CLOSED cycles only</span>
                </div>

                <!-- 9. Fastest Cycle -->
                <div class="bg-surface-container-lowest p-4 rounded-lg border border-surface-container-low shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-outline block">Fastest Cycle</span>
                        <span class="text-2xl font-extrabold text-secondary mt-1 block">
                            {{ $kpi['fastest_cycle'] ? $kpi['fastest_cycle']['minutes'] . 'm' : '—' }}
                        </span>
                    </div>
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-2 block truncate">
                        {{ $kpi['fastest_cycle'] ? 'Cycle #' . $kpi['fastest_cycle']['cycle_number'] : 'No closed data' }}
                    </span>
                </div>

                <!-- 10. Slowest Cycle -->
                <div class="bg-surface-container-lowest p-4 rounded-lg border border-surface-container-low shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-outline block">Slowest Cycle</span>
                        <span class="text-2xl font-extrabold text-error mt-1 block">
                            {{ $kpi['slowest_cycle'] ? $kpi['slowest_cycle']['minutes'] . 'm' : '—' }}
                        </span>
                    </div>
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-2 block truncate">
                        {{ $kpi['slowest_cycle'] ? 'Cycle #' . $kpi['slowest_cycle']['cycle_number'] : 'No closed data' }}
                    </span>
                </div>
            </div>

            <!-- DETAIL TABLE -->
            <div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden border border-surface-container-low">
                <div class="p-4 bg-surface-container-low border-b border-outline-variant/30 flex justify-between items-center">
                    <h2 class="text-xs font-black uppercase tracking-widest text-on-surface-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">list</span>
                        Detail Cycle Reconstructed ({{ count($cycles) }} cycles)
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-outline-variant/30">
                                <th class="px-6 py-4">Cycle</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Melting Start</th>
                                <th class="px-6 py-4">Pour Start</th>
                                <th class="px-6 py-4">Cycle End</th>
                                <th class="px-6 py-4 text-right">Duration</th>
                                <th class="px-6 py-4 text-right">Hasil (kg)</th>
                                <th class="px-6 py-4 text-right">Bahan Kembali (kg)</th>
                                <th class="px-6 py-4 text-right">Energy</th>
                                <th class="px-6 py-4 text-right">Estimated Cost</th>
                                <th class="px-6 py-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low">
                            @forelse($cycles as $cycle)
                                @php
                                    $bgClass = '';
                                    if ($cycle['status'] === 'INCOMPLETE') {
                                        $bgClass = 'bg-red-50/50 dark:bg-red-950/10 italic text-outline';
                                    } elseif ($cycle['status'] === 'OUTLIER') {
                                        $bgClass = 'bg-purple-50/50 dark:bg-purple-950/10';
                                    } elseif ($cycle['status'] === 'OPEN') {
                                        $bgClass = 'bg-amber-50/50 dark:bg-amber-950/10';
                                    }
                                @endphp
                                <tr class="hover:bg-surface-container-low/40 transition-colors {{ $bgClass }}">
                                    <td class="px-6 py-4 font-mono text-xs font-bold">
                                        {{ $cycle['number'] !== null ? '#' . $cycle['number'] : '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($cycle['status'] === 'CLOSED')
                                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-400 border border-slate-200 dark:border-slate-800">
                                                CLOSED
                                            </span>
                                        @elseif($cycle['status'] === 'OPEN')
                                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800 animate-pulse" title="Cycle belum selesai. Waktu akhir didasarkan pada batas pencarian.">
                                                OPEN
                                            </span>
                                        @elseif($cycle['status'] === 'INCOMPLETE')
                                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800" title="Operator lupa tag 'pour' di antara dua melting.">
                                                INCOMPLETE
                                            </span>
                                        @elseif($cycle['status'] === 'OUTLIER')
                                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 border border-purple-200 dark:border-purple-800" title="Durasi melebihi threshold statistis (mean + 2 std dev = {{ $cycle['outlier_threshold'] }} min).">
                                                OUTLIER
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-on-surface">
                                        {{ $cycle['cycle_start']->format('d M H:i') }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-on-surface-variant">
                                        {{ $cycle['pouring_start'] ? $cycle['pouring_start']->format('d M H:i') : '—' }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-on-surface-variant">
                                        {{ $cycle['cycle_end']->format('d M H:i') }}
                                        @if($cycle['status'] === 'OPEN')
                                            <span class="text-[9px] text-amber-600 block mt-0.5 font-sans">(Limit Pencarian)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-on-surface font-mono text-xs">
                                        {{ $cycle['total_duration_human'] }}
                                    </td>
                                    <!-- Hasil (kg) with color coding -->
                                    <td class="px-6 py-4 text-right font-mono text-xs">
                                        @if($cycle['actual_output_kg'] !== null)
                                            @php
                                                $outputVal = $cycle['actual_output_kg'];
                                                $outputColor = $outputVal >= 430 ? 'text-green-600 dark:text-green-400' : ($outputVal >= 400 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400');
                                            @endphp
                                            <span class="{{ $outputColor }} font-bold">{{ number_format($outputVal, 2) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <!-- Bahan Kembali (kg) with color coding -->
                                    <td class="px-6 py-4 text-right font-mono text-xs">
                                        @if($cycle['return_material_kg'] !== null)
                                            @php
                                                $returnVal = $cycle['return_material_kg'];
                                                $returnColor = $returnVal <= 20 ? 'text-green-600 dark:text-green-400' : ($returnVal <= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400');
                                            @endphp
                                            <span class="{{ $returnColor }} font-bold">{{ number_format($returnVal, 2) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-primary font-mono text-xs">
                                        {{ $cycle['status'] !== 'INCOMPLETE' ? number_format($cycle['kwh'], 1) : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-800 dark:text-slate-200 font-mono text-xs">
                                        {{ $cycle['status'] !== 'INCOMPLETE' ? 'Rp ' . number_format($cycle['est_cost'], 0, ',', '.') : '—' }}
                                    </td>
                                    <!-- Action Button -->
                                    <td class="px-6 py-4 text-center">
                                        @if($canManageProductionResults)
                                            @if(in_array($cycle['status'], ['CLOSED', 'OUTLIER']))
                                                @if($cycle['actual_output_kg'] === null && $cycle['return_material_kg'] === null)
                                                    <button type="button" 
                                                            onclick="openProductionModal({{ json_encode([
                                                                'melting_tag_id' => $cycle['melting_tag_id'],
                                                                'cycle_num' => $cycle['number'],
                                                                'melting' => $cycle['cycle_start']->format('d M Y H:i'),
                                                                'pour' => $cycle['pouring_start'] ? $cycle['pouring_start']->format('d M Y H:i') : '—',
                                                                'duration' => $cycle['total_duration_human'],
                                                                'energy' => $cycle['status'] !== 'INCOMPLETE' ? number_format($cycle['kwh'], 1) . ' kWh' : '—',
                                                                'actual_output_kg' => '',
                                                                'return_material_kg' => '',
                                                                'remark' => ''
                                                            ]) }})" 
                                                            class="px-3 py-1.5 bg-orange-600 text-white text-[11px] font-bold rounded shadow hover:bg-orange-700 transition-all flex items-center gap-1 mx-auto">
                                                        <span class="material-symbols-outlined text-[14px]">add_circle</span> Input Produksi
                                                    </button>
                                                @else
                                                    <button type="button" 
                                                            onclick="openProductionModal({{ json_encode([
                                                                'melting_tag_id' => $cycle['melting_tag_id'],
                                                                'cycle_num' => $cycle['number'],
                                                                'melting' => $cycle['cycle_start']->format('d M Y H:i'),
                                                                'pour' => $cycle['pouring_start'] ? $cycle['pouring_start']->format('d M Y H:i') : '—',
                                                                'duration' => $cycle['total_duration_human'],
                                                                'energy' => $cycle['status'] !== 'INCOMPLETE' ? number_format($cycle['kwh'], 1) . ' kWh' : '—',
                                                                'actual_output_kg' => $cycle['actual_output_kg'],
                                                                'return_material_kg' => $cycle['return_material_kg'],
                                                                'remark' => $cycle['remark']
                                                            ]) }})" 
                                                            class="px-3 py-1.5 bg-blue-600 text-white text-[11px] font-bold rounded shadow hover:bg-blue-700 transition-all flex items-center gap-1 mx-auto">
                                                        <span class="material-symbols-outlined text-[14px]">edit</span> Edit Produksi
                                                    </button>
                                                @endif
                                            @else
                                                <span class="text-xs text-outline italic">—</span>
                                            @endif
                                        @else
                                            <span class="text-xs text-outline italic">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-10 text-center text-outline italic">Tidak ada cycle yang terdeteksi dalam rentang waktu terpilih.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($cycles) > 0)
                            <tfoot class="bg-surface-container-low font-bold border-t border-outline-variant/30">
                                <tr class="text-on-surface">
                                    <td colspan="5" class="px-6 py-4 text-xs font-black uppercase tracking-widest text-outline">Total (CLOSED + OPEN + OUTLIER)</td>
                                    <td class="px-6 py-4 text-right font-mono text-xs">
                                        {{ $kpi['total_melting_human'] }} (Melt)<br>
                                        {{ $kpi['total_pouring_human'] }} (Pour)
                                    </td>
                                    <!-- Hasil Total -->
                                    <td class="px-6 py-4 text-right font-mono text-xs font-bold">
                                        @php
                                            $totalHasil = collect($cycles)->sum('actual_output_kg');
                                        @endphp
                                        {{ $totalHasil > 0 ? number_format($totalHasil, 2) . ' kg' : '—' }}
                                    </td>
                                    <!-- Bahan Kembali Total -->
                                    <td class="px-6 py-4 text-right font-mono text-xs font-bold">
                                        @php
                                            $totalReturn = collect($cycles)->sum('return_material_kg');
                                        @endphp
                                        {{ $totalReturn > 0 ? number_format($totalReturn, 2) . ' kg' : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-primary font-mono text-xs">{{ number_format($kpi['total_kwh'], 1) }} kWh</td>
                                    <td class="px-6 py-4 text-right text-slate-800 dark:text-slate-200 font-mono text-xs">Rp {{ number_format($kpi['total_cost'], 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @endif
    </div>
</main>

<!-- INPUT PRODUCTION RESULT MODAL -->
<div id="productionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 transition-all duration-300">
    <div class="bg-surface-container-lowest rounded-xl shadow-2xl border border-outline-variant max-w-md w-full overflow-hidden transform scale-95 transition-transform duration-300 ease-out" id="modalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-primary text-on-primary flex justify-between items-center">
            <h3 class="text-sm font-black uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined">analytics</span>
                <span id="modalTitle">Input Data Produksi</span>
            </h3>
            <button type="button" onclick="closeProductionModal()" class="text-on-primary hover:opacity-80 transition-opacity">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <form action="{{ route('analytics.historian.store_production') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="melting_tag_id" id="form_melting_tag_id">

            <!-- Cycle Summary Panel -->
            <div class="bg-surface-container-low p-4 rounded-lg border border-outline-variant/30 space-y-2 text-xs">
                <div class="flex justify-between border-b border-outline-variant/20 pb-1.5 font-bold">
                    <span class="text-outline">Cycle Number</span>
                    <span id="summary_cycle_num" class="text-on-surface"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-outline">Melting Start</span>
                    <span id="summary_melting" class="text-on-surface font-mono"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-outline">Pour Start</span>
                    <span id="summary_pour" class="text-on-surface font-mono"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-outline">Duration</span>
                    <span id="summary_duration" class="text-on-surface font-mono"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-outline">Energy Consumption</span>
                    <span id="summary_energy" class="text-primary font-mono font-bold"></span>
                </div>
            </div>

            <!-- Form Inputs -->
            <div class="space-y-4">
                <div>
                    <label for="actual_output_kg" class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Hasil (kg) *</label>
                    <input type="number" step="0.01" name="actual_output_kg" id="form_actual_output_kg" min="0" max="600"
                           class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                           placeholder="Masukkan hasil tuang (0 - 600)">
                    <p class="text-[9px] text-outline mt-1">Validasi: Min 0 kg, Max 600 kg. Kosongkan jika belum ditimbang.</p>
                </div>

                <div>
                    <label for="return_material_kg" class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Bahan Kembali (kg) *</label>
                    <input type="number" step="0.01" name="return_material_kg" id="form_return_material_kg" min="0" max="200"
                           class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                           placeholder="Masukkan bahan kembali (0 - 200)">
                    <p class="text-[9px] text-outline mt-1">Validasi: Min 0 kg, Max 200 kg. Kosongkan jika tidak ada return.</p>
                </div>

                <div>
                    <label for="remark" class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Remark (Optional)</label>
                    <textarea name="remark" id="form_remark" rows="2"
                              class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all resize-none"
                              placeholder="Catatan tambahan..."></textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-2 pt-2 border-t border-outline-variant/30">
                <button type="button" onclick="closeProductionModal()"
                        class="px-4 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-bold rounded-md text-xs transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-primary hover:bg-primary/95 text-on-primary font-bold rounded-md text-xs transition-colors flex items-center gap-1 shadow-sm">
                    <span class="material-symbols-outlined text-[14px]">save</span> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openProductionModal(data) {
        document.getElementById('form_melting_tag_id').value = data.melting_tag_id;
        document.getElementById('summary_cycle_num').innerText = '#' + data.cycle_num;
        document.getElementById('summary_melting').innerText = data.melting;
        document.getElementById('summary_pour').innerText = data.pour;
        document.getElementById('summary_duration').innerText = data.duration;
        document.getElementById('summary_energy').innerText = data.energy;

        document.getElementById('form_actual_output_kg').value = data.actual_output_kg !== null && data.actual_output_kg !== '' ? data.actual_output_kg : '';
        document.getElementById('form_return_material_kg').value = data.return_material_kg !== null && data.return_material_kg !== '' ? data.return_material_kg : '';
        document.getElementById('form_remark').value = data.remark !== null ? data.remark : '';

        // Set Title
        const isEdit = data.actual_output_kg !== '' || data.return_material_kg !== '';
        document.getElementById('modalTitle').innerText = isEdit ? 'Edit Data Produksi' : 'Input Data Produksi';

        // Show modal
        const modal = document.getElementById('productionModal');
        const container = document.getElementById('modalContainer');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
        }, 10);
    }

    function closeProductionModal() {
        const modal = document.getElementById('productionModal');
        const container = document.getElementById('modalContainer');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 150);
    }
</script>
@endsection
