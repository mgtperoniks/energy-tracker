@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        
        <!-- HEADER WITH DOWNLOAD BUTTONS ALIGNED TO TOP RIGHT -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Operational Report</h1>
                <p class="text-on-surface-variant text-sm mt-1">Laporan harian metrik teknis per power meter.</p>
            </div>
            <div class="flex items-center gap-2">
                @if($isFiltered && $reports->isNotEmpty())
                    <a href="{{ route('analytics.operational.export', request()->all()) }}" class="px-4 py-2 bg-secondary text-on-secondary font-medium rounded-md hover:bg-secondary/90 transition-colors flex items-center gap-2 text-sm shadow-sm">
                        <span class="material-symbols-outlined text-sm">download</span> Excel
                    </a>
                    <a href="{{ route('analytics.operational.pdf', request()->all()) }}" target="_blank" class="px-4 py-2 bg-error text-on-error font-medium rounded-md hover:bg-error/90 transition-colors flex items-center gap-2 text-sm shadow-sm">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span> PDF
                    </a>
                @else
                    <button type="button" disabled class="px-4 py-2 bg-surface-container-high text-outline font-medium rounded-md cursor-not-allowed flex items-center gap-2 text-sm opacity-50" title="Excel and PDF export tersedia setelah laporan dihasilkan">
                        <span class="material-symbols-outlined text-sm">download</span> Excel
                    </button>
                    <button type="button" disabled class="px-4 py-2 bg-surface-container-high text-outline font-medium rounded-md cursor-not-allowed flex items-center gap-2 text-sm opacity-50" title="Excel and PDF export tersedia setelah laporan dihasilkan">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span> PDF
                    </button>
                @endif
            </div>
        </div>

        @php
            $isHourlyStale = !$schedulerHealth['hourly'] || $schedulerHealth['hourly']->updated_at->diffInHours(now()) > 2;
            $isDailyStale = !$schedulerHealth['daily'] || $schedulerHealth['daily']->updated_at->diffInHours(now()) > 26;
        @endphp

        @if($isHourlyStale || $isDailyStale)
            <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-lg border border-error/20 flex items-center gap-4 shadow-sm animate-pulse">
                <span class="material-symbols-outlined text-2xl">history_toggle_off</span>
                <div>
                    <h3 class="font-black text-xs uppercase tracking-widest">Industrial Historian Pipeline Stale</h3>
                    <p class="text-[11px] font-medium opacity-90">
                        @if($isHourlyStale) 
                            • Hourly Aggregation stopped at {{ $schedulerHealth['hourly'] ? $schedulerHealth['hourly']->updated_at->format('d M H:i') : 'Never' }}
                        @endif
                        @if($isDailyStale)
                            <br>• Daily Aggregation stopped at {{ $schedulerHealth['daily'] ? $schedulerHealth['daily']->updated_at->format('d M H:i') : 'Never' }}
                        @endif
                    </p>
                </div>
            </div>
        @endif

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

        <!-- FILTER AREA -->
        <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm border border-surface-container-low mb-8">
            <form method="GET" action="{{ route('analytics.operational') }}" class="flex flex-col gap-6">
                <!-- Filters Row -->
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Date Range</label>
                        <div class="flex items-center gap-2">
                            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                            <span class="text-outline-variant font-bold">to</span>
                            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        </div>
                    </div>
                    <div class="flex-1 w-full">
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
                    <button type="submit" class="w-full md:w-auto px-6 py-2 bg-primary text-on-primary font-bold rounded-md hover:bg-primary/90 transition-colors h-[38px] flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">filter_alt</span> Apply Filter
                    </button>
                </div>

                <!-- Metrics Presets & Checkboxes Row -->
                <div class="border-t border-outline-variant/30 pt-4 flex flex-col gap-4">
                    <div>
                        <span class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Report Columns / Metrics</span>
                    </div>
                    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                        <div class="w-full lg:w-64">
                            <label class="block text-[9px] font-bold text-outline uppercase tracking-widest mb-1 opacity-70">Preset Selection</label>
                            <select id="preset-selector" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                                <option value="custom">Custom Selection</option>
                                <option value="energy_consumption">Energy Consumption</option>
                                <option value="power_quality">Power Quality</option>
                                <option value="full_operational">Full Operational</option>
                            </select>
                        </div>
                        <div class="flex-1 flex flex-wrap gap-x-6 gap-y-3 pt-4 lg:pt-0">
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-on-surface font-medium select-none">
                                <input type="checkbox" name="metrics[]" value="usage" class="metric-checkbox rounded text-primary focus:ring-primary border-outline-variant w-4 h-4" {{ in_array('usage', $selectedMetrics) ? 'checked' : '' }}>
                                <span>Usage (kWh)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-on-surface font-medium select-none">
                                <input type="checkbox" name="metrics[]" value="peak" class="metric-checkbox rounded text-primary focus:ring-primary border-outline-variant w-4 h-4" {{ in_array('peak', $selectedMetrics) ? 'checked' : '' }}>
                                <span>Peak Load (kW)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-on-surface font-medium select-none">
                                <input type="checkbox" name="metrics[]" value="volt" class="metric-checkbox rounded text-primary focus:ring-primary border-outline-variant w-4 h-4" {{ in_array('volt', $selectedMetrics) ? 'checked' : '' }}>
                                <span>Avg Volt (V)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-on-surface font-medium select-none">
                                <input type="checkbox" name="metrics[]" value="pf" class="metric-checkbox rounded text-primary focus:ring-primary border-outline-variant w-4 h-4" {{ in_array('pf', $selectedMetrics) ? 'checked' : '' }}>
                                <span>Avg PF</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-on-surface font-medium select-none">
                                <input type="checkbox" name="metrics[]" value="samples" class="metric-checkbox rounded text-primary focus:ring-primary border-outline-variant w-4 h-4" {{ in_array('samples', $selectedMetrics) ? 'checked' : '' }}>
                                <span>Samples</span>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden border border-surface-container-low">
            @if(!$isFiltered)
                <!-- Empty State Panel -->
                <div class="flex flex-col items-center justify-center p-12 text-center">
                    <div class="w-16 h-16 bg-primary/10 text-primary rounded-full flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-4xl">analytics</span>
                    </div>
                    <h3 class="text-lg font-bold text-on-surface mb-2">Belum ada laporan yang ditampilkan.</h3>
                    <p class="text-on-surface-variant text-sm max-w-md mb-6">Silakan pilih rentang tanggal dan device yang ingin dianalisis.</p>
                    
                    <div class="bg-surface-container-low p-6 rounded-lg text-left max-w-md w-full border border-outline-variant/30">
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
                        </ul>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-outline-variant/30">
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Device (Machine)</th>
                                @if(in_array('usage', $selectedMetrics))
                                    <th class="px-6 py-4 text-right">Usage (kWh)</th>
                                @endif
                                @if(in_array('peak', $selectedMetrics))
                                    <th class="px-6 py-4 text-right">Peak Load (kW)</th>
                                @endif
                                @if(in_array('volt', $selectedMetrics))
                                    <th class="px-6 py-4 text-right">Avg Voltage (V)</th>
                                @endif
                                @if(in_array('pf', $selectedMetrics))
                                    <th class="px-6 py-4 text-right">Avg PF</th>
                                @endif
                                @if(in_array('samples', $selectedMetrics))
                                    <th class="px-6 py-4 text-right">Samples</th>
                                @endif
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
                                        <div class="text-[10px] text-outline mt-0.5">
                                            @if($row->device->machine_id)
                                                <a href="{{ route('monitoring.meters', ['id' => $row->device->machine_id]) }}" class="hover:text-primary hover:underline">{{ $row->device->machine->name }}</a>
                                            @else
                                                Unassigned
                                            @endif
                                        </div>
                                    </td>
                                    @if(in_array('usage', $selectedMetrics))
                                        <td class="px-6 py-4 text-right font-black text-primary">{{ number_format($row->kwh_usage, 2) }}</td>
                                    @endif
                                    @if(in_array('peak', $selectedMetrics))
                                        <td class="px-6 py-4 text-right font-medium text-error">{{ number_format($row->max_power_kw, 2) }}</td>
                                    @endif
                                    @if(in_array('volt', $selectedMetrics))
                                        <td class="px-6 py-4 text-right font-medium text-on-surface-variant">{{ number_format($row->avg_voltage, 1) }}</td>
                                    @endif
                                    @if(in_array('pf', $selectedMetrics))
                                        <td class="px-6 py-4 text-right font-medium text-tertiary">{{ number_format($row->avg_power_factor, 2) }}</td>
                                    @endif
                                    @if(in_array('samples', $selectedMetrics))
                                        <td class="px-6 py-4 text-right font-mono text-xs text-outline">{{ number_format($row->total_sample_count) }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 2 + count($selectedMetrics) }}" class="px-6 py-10 text-center text-outline italic">No data found for this range.</td>
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
            @endif
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form');
        const presetSelector = document.getElementById('preset-selector');
        const checkboxes = document.querySelectorAll('.metric-checkbox');
        
        const presets = {
            energy_consumption: { usage: true, peak: false, volt: false, pf: false, samples: false },
            power_quality: { usage: false, peak: true, volt: true, pf: true, samples: false },
            full_operational: { usage: true, peak: true, volt: true, pf: true, samples: true }
        };

        function updatePresetDropdown() {
            let currentValues = {};
            checkboxes.forEach(cb => {
                currentValues[cb.value] = cb.checked;
            });

            let matchedPreset = 'custom';
            for (const [presetName, presetValues] of Object.entries(presets)) {
                let match = true;
                for (const [metric, checked] of Object.entries(presetValues)) {
                    if (currentValues[metric] !== checked) {
                        match = false;
                        break;
                    }
                }
                if (match) {
                    matchedPreset = presetName;
                    break;
                }
            }
            presetSelector.value = matchedPreset;
        }

        presetSelector.addEventListener('change', function () {
            const val = this.value;
            if (val !== 'custom' && presets[val]) {
                checkboxes.forEach(cb => {
                    cb.checked = presets[val][cb.value];
                });
            }
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                const checkedCount = Array.from(checkboxes).filter(c => c.checked).length;
                if (checkedCount === 0) {
                    this.checked = true;
                    alert('Paling tidak satu metrik/kolom harus dipilih.');
                    return;
                }
                updatePresetDropdown();
            });
        });

        updatePresetDropdown();

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
                        alert('Maksimal rentang laporan adalah 45 hari. Silakan pilih rentang tanggal yang lebih pendek.');
                        return;
                    }
                }

                const checkedCount = Array.from(checkboxes).filter(c => c.checked).length;
                if (checkedCount === 0) {
                    e.preventDefault();
                    alert('Paling tidak satu metrik/kolom harus dipilih.');
                    return;
                }
            });
        }
    });
</script>
@endsection
