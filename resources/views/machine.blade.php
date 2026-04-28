@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-block w-2 h-2 rounded-full bg-secondary"></span>
                    <span class="text-label-sm uppercase tracking-widest text-outline font-bold text-[10px]">Operational Status: Normal</span>
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">{{ $machine->name }}</h1>
                <p class="text-on-surface-variant text-sm mt-1">Meter Code: {{ $machine->code }} | Device Connection: Modbus TCP/RS485</p>
            </div>
            <div class="flex gap-2">
                <button class="px-4 py-2 bg-surface-container-high text-on-surface font-medium rounded-md hover:bg-surface-container-highest transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm" data-icon="download">download</span>
                    Export Data
                </button>
                <button class="px-4 py-2 bg-gradient-to-r from-primary-container to-primary text-white font-medium rounded-md hover:brightness-110 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm" data-icon="tune">tune</span>
                    Adjust Parameters
                </button>
            </div>
        </div>

        <!-- Metrics Bento Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <!-- Big Aggregate Metric -->
            <div class="bg-surface-container-lowest p-5 rounded-lg shadow-sm border-b-2 border-primary-container">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-outline">Consumption (Today)</span>
                    <span class="material-symbols-outlined text-primary" data-icon="electric_bolt">electric_bolt</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black tracking-tight text-on-surface">{{ number_format($todayConsumption, 1) }}</span>
                    <span class="text-xl font-bold text-outline">kWh</span>
                </div>
                <div class="mt-4 flex items-center gap-2 text-secondary text-sm font-bold">
                    <span class="material-symbols-outlined text-sm" data-icon="trending_up">trending_up</span>
                    @if($todayConsumption > 0)
                        Estimated from avg power × time
                    @else
                        Waiting for data accumulation
                    @endif
                </div>
            </div>

            <!-- Total Energy Card -->
            <div class="bg-surface-container-lowest p-5 rounded-lg shadow-sm border-b-2 border-secondary-container">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-outline">Total Energy (Lifetime)</span>
                    <span class="material-symbols-outlined text-secondary" data-icon="history">history</span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black tracking-tight text-on-surface">{{ number_format($totalEnergy, 2) }}</span>
                    <span class="text-sm font-bold text-outline">kWh</span>
                </div>
                <div class="mt-3 space-y-1">
                    <div class="flex items-center gap-1 text-[10px] font-bold text-outline">
                        <span class="material-symbols-outlined text-xs">database</span>
                        Baseline: {{ number_format($machine->kwh_baseline, 2) }} kWh
                    </div>
                    <div class="flex items-center gap-1 text-[10px] font-medium text-secondary">
                        <span class="material-symbols-outlined text-xs">electric_meter</span>
                        Meter now: {{ number_format($currentMeterKwh, 2) }} kWh
                    </div>
                </div>
            </div>

            <!-- Secondary Metrics -->
            <div class="bg-surface-container-lowest p-5 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase tracking-wider text-outline block mb-2">Voltage</span>
                <div class="text-3xl font-bold tracking-tight text-on-surface">{{ number_format($machine->latestReading?->voltage ?? 0, 1) }} <span class="text-sm font-medium text-outline">V</span></div>
                <div class="w-full bg-surface-container-low h-1 mt-4 rounded-full overflow-hidden">
                    <div class="bg-primary h-full" style="width: {{ min(($machine->latestReading?->voltage ?? 0) / 400 * 100, 100) }}%"></div>
                </div>
            </div>
            
            <div class="bg-surface-container-lowest p-5 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase tracking-wider text-outline block mb-2">Current</span>
                <div class="text-3xl font-bold tracking-tight text-on-surface">{{ number_format($machine->latestReading?->current ?? 0, 1) }} <span class="text-sm font-medium text-outline">A</span></div>
                <div class="w-full bg-surface-container-low h-1 mt-4 rounded-full overflow-hidden">
                    <div class="bg-tertiary h-full" style="width: {{ min(($machine->latestReading?->current ?? 0) / 100 * 100, 100) }}%"></div>
                </div>
            </div>
            
            <div class="bg-surface-container-lowest p-5 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase tracking-wider text-outline block mb-2">Power Factor</span>
                <div class="text-3xl font-bold tracking-tight text-on-surface">{{ number_format($machine->latestReading?->power_factor ?? 0, 2) }}</div>
                <div class="mt-4 flex items-center gap-1 @if(($machine->latestReading?->power_factor ?? 0) > 0.85) text-secondary @else text-rose-500 @endif text-[10px] font-bold">
                    <span class="material-symbols-outlined text-xs">@if(($machine->latestReading?->power_factor ?? 0) > 0.85) check_circle @else warning @endif</span>
                    {{ ($machine->latestReading?->power_factor ?? 0) > 0.85 ? 'OPTIMAL RANGE' : 'LOW PF DETECTED' }}
                </div>
            </div>
        </div>

        <!-- Power History Chart Section -->
        <div class="bg-surface-container-lowest p-8 rounded-lg shadow-sm mb-8 relative overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-on-surface">Power History</h2>
                    <p class="text-xs text-on-surface-variant">Active Load (kW) & Voltage (V) over last 12 hours</p>
                </div>
                <div class="flex gap-6">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-sm" style="background:#00628c"></span>
                        <span class="text-xs font-bold text-on-surface">Active Power (kW)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-sm" style="background:#f97316"></span>
                        <span class="text-xs font-bold text-on-surface">Voltage (V)</span>
                    </div>
                </div>
            </div>

            <!-- Real Chart Canvas -->
            <div class="h-[300px] w-full">
                <canvas id="powerChart"></canvas>
            </div>
        </div>

        <script src="{{ asset('assets/js/chart.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('powerChart').getContext('2d');
                
                // Fetch data from PHP
                const labels   = {!! json_encode($historyLabels) !!};
                const values   = {!! json_encode($historyValues) !!};
                const voltages = {!! json_encode($historyVoltage) !!};

                const powerChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Active Power (kW)',
                                data: values,
                                yAxisID: 'y',
                                fill: true,
                                backgroundColor: 'rgba(0, 98, 140, 0.08)',
                                borderColor: '#00628c',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 5,
                                tension: 0.3,
                                segment: {
                                    borderColor: ctx => {
                                        if (ctx.p1.parsed.y > 40) return '#fb7185';
                                        return undefined;
                                    }
                                }
                            },
                            {
                                label: 'Voltage (V)',
                                data: voltages,
                                yAxisID: 'y1',
                                fill: false,
                                borderColor: '#f97316',
                                backgroundColor: 'rgba(249, 115, 22, 0.08)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 5,
                                tension: 0.3,
                                borderDash: [4, 3],
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleFont: { size: 10, weight: 'bold' },
                                bodyFont: { size: 12 },
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(item) {
                                        if (item.datasetIndex === 0) {
                                            return ' Power: ' + item.parsed.y.toFixed(2) + ' kW';
                                        }
                                        return ' Voltage: ' + item.parsed.y.toFixed(1) + ' V';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                position: 'left',
                                beginAtZero: false,
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.1)',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: { size: 10, family: 'JetBrains Mono', weight: 'bold' },
                                    color: '#00628c',
                                    callback: function(value) { return value.toFixed(1) + ' kW'; }
                                }
                            },
                            y1: {
                                type: 'linear',
                                position: 'right',
                                beginAtZero: false,
                                grid: {
                                    drawOnChartArea: false,
                                },
                                ticks: {
                                    font: { size: 10, family: 'JetBrains Mono', weight: 'bold' },
                                    color: '#f97316',
                                    callback: function(value) { return value.toFixed(0) + ' V'; }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: { size: 10, family: 'JetBrains Mono', weight: 'bold' },
                                    color: '#94a3b8',
                                    maxRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: 12
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                        }
                    }
                });
            });
        </script>


        <!-- Recent Readings Log -->
        <div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden">
            <div class="px-8 py-4 border-b border-surface-container-low flex justify-between items-center">
                <h2 class="text-sm font-bold tracking-tight text-on-surface uppercase">Recent Readings Log</h2>
                <div class="text-[10px] text-outline font-medium">REAL-TIME UPDATE EVERY 5 SECONDS</div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left inline-table">
                    <thead>
                        <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest">
                            <th class="px-8 py-3">Timestamp</th>
                            <th class="px-4 py-3 text-right">Power (kW)</th>
                            <th class="px-4 py-3 text-right">Voltage (V)</th>
                            <th class="px-4 py-3 text-right">Current (A)</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-8 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @forelse($machine->recentReadings as $row)
                        <tr class="border-b border-surface-container-low hover:bg-surface-container-low transition-colors">
                            <td class="px-8 py-4 font-mono text-xs">{{ $row->recorded_at }}</td>
                            <td class="px-4 py-4 text-right font-bold text-primary">{{ number_format($row->power_kw, 2) }}</td>
                            <td class="px-4 py-4 text-right text-on-surface-variant">{{ number_format($row->voltage, 1) }}</td>
                            <td class="px-4 py-4 text-right text-on-surface-variant">{{ number_format($row->current, 1) }}</td>
                            <td class="px-4 py-4 text-center">
                                @if($row->power_kw > 40)
                                    <span class="bg-tertiary-container/20 text-tertiary px-2 py-1 rounded-full text-[10px] font-bold uppercase">Spike</span>
                                @elseif($row->power_kw <= 0)
                                    <span class="bg-outline/20 text-outline px-2 py-1 rounded-full text-[10px] font-bold uppercase">Mati</span>
                                @else
                                    <span class="bg-secondary-container text-on-secondary-container px-2 py-1 rounded-full text-[10px] font-bold uppercase">Optimal</span>
                                @endif
                            </td>
                            <td class="px-8 py-4 text-right">
                                <button class="text-primary hover:underline text-xs font-bold detail-btn" 
                                    data-timestamp="{{ $row->recorded_at }}"
                                    data-power="{{ number_format($row->power_kw, 2) }}"
                                    data-voltage="{{ number_format($row->voltage, 1) }}"
                                    data-current="{{ number_format($row->current, 1) }}"
                                    data-pf="{{ number_format($row->power_factor ?? 1.0, 2) }}"
                                    data-kwh="{{ number_format($row->kwh_total, 2) }}">
                                    Details
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-8 py-10 text-center text-outline italic">No recent readings available for this machine.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 bg-surface-container-low flex justify-center">
                <button id="load-more-btn" data-machine-id="{{ $machine->id }}" data-offset="10" class="text-[10px] font-black text-on-surface-variant uppercase tracking-widest hover:text-primary transition-colors">Load Full History</button>
            </div>
        </div>
    </div>

    {{-- ====================================================
         METER RESET HISTORY PANEL
         Shows all logged reset events with a button to log a new one.
    ====================================================== --}}
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden">
            <div class="px-8 py-4 border-b border-surface-container-low flex justify-between items-center">
                <div>
                    <h2 class="text-sm font-bold tracking-tight text-on-surface uppercase">Meter Reset History</h2>
                    <p class="text-[10px] text-outline mt-0.5">Riwayat reset counter energy meter fisik</p>
                </div>
                {{-- Button to log a manual reset (disabled until 1 May or as needed) --}}
                <button id="btn-log-reset"
                    data-machine-id="{{ $machine->id }}"
                    data-machine-name="{{ $machine->name }}"
                    data-current-kwh="{{ number_format($currentMeterKwh, 2) }}"
                    class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-md transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">restart_alt</span>
                    Log Reset Meter
                </button>
            </div>

            @if($machine->meterResets->isEmpty())
                <div class="px-8 py-6 text-center text-outline italic text-sm">
                    Belum ada reset meter tercatat. Total Energy = Baseline + Meter saat ini.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest">
                                <th class="px-8 py-3">Reset At</th>
                                <th class="px-4 py-3 text-right">kWh at Reset</th>
                                <th class="px-4 py-3">Notes</th>
                                <th class="px-4 py-3">By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($machine->meterResets as $reset)
                            <tr class="border-b border-surface-container-low text-sm">
                                <td class="px-8 py-3 font-mono text-xs">{{ $reset->reset_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-right font-bold text-secondary">{{ number_format($reset->kwh_at_reset, 2) }} kWh</td>
                                <td class="px-4 py-3 text-on-surface-variant text-xs">{{ $reset->notes ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $reset->performedBy?->name ?? 'System' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="px-8 py-3 bg-surface-container-low">
                <p class="text-[10px] text-outline">
                    <strong>Total Lifetime kWh</strong> =
                    Baseline <strong>{{ number_format($machine->kwh_baseline, 2) }} kWh</strong>
                    + Meter sekarang <strong>{{ number_format($currentMeterKwh, 2) }} kWh</strong>
                    = <strong class="text-primary">{{ number_format($totalEnergy, 2) }} kWh</strong>
                </p>
            </div>
        </div>
    </div>
</main>

<!-- Details Modal -->
<div id="details-modal" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-all animate-in fade-in duration-300 pointer-events-none">
    <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all scale-95 opacity-0 duration-300 pointer-events-auto" id="modal-content">
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Reading Details</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-end border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Timestamp</span>
                    <div id="modal-timestamp" class="text-sm font-mono font-bold text-slate-700 dark:text-slate-300"></div>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Status</span>
                    <div id="modal-status" class="mt-1"></div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Active Power</span>
                    <div class="flex items-baseline gap-1">
                        <span id="modal-power" class="text-2xl font-black text-primary tracking-tight"></span>
                        <span class="text-xs font-bold text-slate-400">kW</span>
                    </div>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Total Energy</span>
                    <div class="flex items-baseline gap-1">
                        <span id="modal-kwh" class="text-2xl font-black text-on-surface tracking-tight"></span>
                        <span class="text-xs font-bold text-slate-400">kWh</span>
                    </div>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Voltage</span>
                    <div class="flex items-baseline gap-1 text-slate-700">
                        <span id="modal-voltage" class="text-xl font-bold tracking-tight"></span>
                        <span class="text-xs font-bold text-slate-400">V</span>
                    </div>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Current</span>
                    <div class="flex items-baseline gap-1 text-slate-700">
                        <span id="modal-current" class="text-xl font-bold tracking-tight"></span>
                        <span class="text-xs font-bold text-slate-400">A</span>
                    </div>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Power Factor</span>
                    <div id="modal-pf" class="text-xl font-bold text-slate-700"></div>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800">
            <button onclick="closeModal()" class="w-full py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold rounded-lg text-xs uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Close</button>
        </div>
    </div>
</div>

<script>
    function openModal(data) {
        document.getElementById('modal-timestamp').innerText = data.timestamp;
        document.getElementById('modal-power').innerText = data.power;
        document.getElementById('modal-voltage').innerText = data.voltage;
        document.getElementById('modal-current').innerText = data.current;
        document.getElementById('modal-pf').innerText = data.pf;
        document.getElementById('modal-kwh').innerText = data.kwh;
        
        const statusEl = document.getElementById('modal-status');
        if (parseFloat(data.power) > 40) {
            statusEl.innerHTML = '<span class="bg-rose-100 text-rose-600 px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight">Spike</span>';
        } else if (parseFloat(data.power) <= 0) {
            statusEl.innerHTML = '<span class="bg-slate-100 text-slate-500 px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight">Mati</span>';
        } else {
            statusEl.innerHTML = '<span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight">Optimal</span>';
        }

        const modal = document.getElementById('details-modal');
        const content = document.getElementById('modal-content');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.classList.remove('pointer-events-none');
            content.classList.remove('scale-95', 'opacity-0');
        }, 10);
    }

    function closeModal() {
        const modal = document.getElementById('details-modal');
        const content = document.getElementById('modal-content');
        content.classList.add('scale-95', 'opacity-0');
        modal.classList.add('pointer-events-none');
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Event delegation for Details buttons
        document.body.addEventListener('click', function(e) {
            if (e.target.classList.contains('detail-btn')) {
                const data = e.target.dataset;
                openModal(data);
            }
        });

        // Load More function
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', async function() {
                const machineId = this.dataset.machineId;
                const offset = parseInt(this.dataset.offset);
                const originalText = this.innerText;
                this.innerText = 'LOADING...';
                this.classList.add('opacity-50', 'cursor-not-allowed');
                this.disabled = true;

                try {
                    const response = await fetch(`/api/machines/${machineId}/readings?offset=${offset}&limit=10`);
                    const result = await response.json();

                    if (result.status === 'success' && result.data.length > 0) {
                        const tbody = document.querySelector('tbody');

                        /**
                         * Format ISO 8601 timestamp from API to the same style
                         * as Blade's recorded_at (e.g. "2026-04-28 09:21:52").
                         * The server stores UTC; Laravel displays in app timezone (Asia/Jakarta = UTC+7).
                         * We replicate that by adding 7 hours offset here.
                         */
                        function formatTimestamp(isoString) {
                            // Parse the ISO string as UTC
                            const d = new Date(isoString);
                            // Add WIB offset (UTC+7 = 7 * 60 minutes)
                            const wib = new Date(d.getTime() + 7 * 60 * 60 * 1000);
                            const yyyy = wib.getUTCFullYear();
                            const mm   = String(wib.getUTCMonth() + 1).padStart(2, '0');
                            const dd   = String(wib.getUTCDate()).padStart(2, '0');
                            const hh   = String(wib.getUTCHours()).padStart(2, '0');
                            const min  = String(wib.getUTCMinutes()).padStart(2, '0');
                            const ss   = String(wib.getUTCSeconds()).padStart(2, '0');
                            return `${yyyy}-${mm}-${dd} ${hh}:${min}:${ss}`;
                        }

                        result.data.forEach(row => {
                            const tr = document.createElement('tr');
                            tr.className = 'border-b border-surface-container-low hover:bg-surface-container-low transition-colors';

                            const power     = parseFloat(row.power_kw);
                            const timestamp = formatTimestamp(row.recorded_at);  // ← formatted
                            const statusHtml = power > 40
                                ? '<span class="bg-tertiary-container/20 text-tertiary px-2 py-1 rounded-full text-[10px] font-bold uppercase">Spike</span>'
                                : (power <= 0
                                    ? '<span class="bg-outline/20 text-outline px-2 py-1 rounded-full text-[10px] font-bold uppercase">Mati</span>'
                                    : '<span class="bg-secondary-container text-on-secondary-container px-2 py-1 rounded-full text-[10px] font-bold uppercase">Optimal</span>');

                            tr.innerHTML = `
                                <td class="px-8 py-4 font-mono text-xs">${timestamp}</td>
                                <td class="px-4 py-4 text-right font-bold text-primary">${power.toFixed(2)}</td>
                                <td class="px-4 py-4 text-right text-on-surface-variant">${parseFloat(row.voltage).toFixed(1)}</td>
                                <td class="px-4 py-4 text-right text-on-surface-variant">${parseFloat(row.current).toFixed(1)}</td>
                                <td class="px-4 py-4 text-center">${statusHtml}</td>
                                <td class="px-8 py-4 text-right">
                                    <button class="text-primary hover:underline text-xs font-bold detail-btn"
                                        data-timestamp="${timestamp}"
                                        data-power="${power.toFixed(2)}"
                                        data-voltage="${parseFloat(row.voltage).toFixed(1)}"
                                        data-current="${parseFloat(row.current).toFixed(1)}"
                                        data-pf="${parseFloat(row.power_factor || 1.0).toFixed(2)}"
                                        data-kwh="${parseFloat(row.kwh_total).toFixed(2)}">
                                        Details
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });

                        this.dataset.offset = offset + result.data.length;
                        this.innerText = originalText;
                        this.classList.remove('opacity-50', 'cursor-not-allowed');
                        this.disabled = false;
                        
                        if (result.data.length < 10) {
                            this.remove(); // No more data
                        }
                    } else {
                        this.remove();
                    }
                } catch (error) {
                    console.error('Error loading more readings:', error);
                    this.innerText = originalText;
                    this.classList.remove('opacity-50', 'cursor-not-allowed');
                    this.disabled = false;
                }
            });
        }
    });

    // Close modal on background click
    document.getElementById('details-modal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // --- LOG RESET METER BUTTON ---
    const btnLogReset = document.getElementById('btn-log-reset');
    if (btnLogReset) {
        btnLogReset.addEventListener('click', async function () {
            const machineId   = this.dataset.machineId;
            const machineName = this.dataset.machineName;
            const currentKwh  = this.dataset.currentKwh;

            const confirmed = confirm(
                `⚠️ LOG RESET METER\n\n` +
                `Mesin     : ${machineName}\n` +
                `kWh sekarang: ${currentKwh} kWh\n\n` +
                `Nilai ${currentKwh} kWh akan ditambahkan ke baseline.\n` +
                `Lakukan ini SEBELUM reset fisik di meter.\n\n` +
                `Lanjutkan?`
            );

            if (!confirmed) return;

            const notes = prompt(
                'Masukkan catatan reset (opsional):',
                'Reset tahunan - ' + new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
            );

            this.disabled = true;
            this.innerText = 'SAVING...';

            try {
                const response = await fetch(`/api/machines/${machineId}/reset`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ notes: notes || '' })
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    alert(
                        `✅ Reset berhasil dicatat!\n\n` +
                        `kWh at reset : ${result.data.kwh_at_reset} kWh\n` +
                        `Baseline baru: ${result.data.new_baseline} kWh\n\n` +
                        `Sekarang Anda bisa reset counter fisik di power meter.\n` +
                        `Halaman akan di-refresh.`
                    );
                    window.location.reload();
                } else {
                    alert('❌ Gagal mencatat reset: ' + (result.message || 'Unknown error'));
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined text-sm">restart_alt</span> Log Reset Meter';
                }
            } catch (err) {
                alert('❌ Error: ' + err.message);
                this.disabled = false;
                this.innerHTML = '<span class="material-symbols-outlined text-sm">restart_alt</span> Log Reset Meter';
            }
        });
    }
</script>
@endsection

