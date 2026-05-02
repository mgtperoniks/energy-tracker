@extends('layouts.app')

@section('content')
<!-- Flatpickr for 24h Datetime Picker (Localized for Offline) -->
<link rel="stylesheet" href="{{ asset('assets/vendor/flatpickr/flatpickr.min.css') }}">
<script src="{{ asset('assets/vendor/flatpickr/flatpickr.min.js') }}"></script>

<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-4 md:p-6 max-w-7xl mx-auto">
        <!-- Alerts -->
        @if(session('success'))
            <div class="bg-secondary-container text-on-secondary-container p-3 rounded text-xs font-bold mb-4 flex items-center gap-2 border border-secondary/20">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-error-container text-error p-3 rounded text-xs font-bold mb-4 flex items-center gap-2 border border-error/20">
                <span class="material-symbols-outlined text-sm">error</span>
                {{ session('error') }}
            </div>
        @endif

        <!-- Header Section - Compact -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 mb-4">
            <div>
                <div class="flex items-center gap-2">
                    @php
                        $statusColor = match($opStatus) {
                            'Offline' => 'bg-outline',
                            'Low Voltage' => 'bg-error',
                            'Idle' => 'bg-tertiary',
                            'Mixed Load' => 'bg-primary',
                            default => 'bg-secondary'
                        };
                    @endphp
                    <span class="inline-block w-1.5 h-1.5 rounded-full {{ $statusColor }} @if($opStatus == 'Running') animate-pulse @endif"></span>
                    <span class="text-[9px] uppercase tracking-widest text-outline font-bold">{{ $opStatus }}</span>
                </div>
                <h1 class="text-xl font-black tracking-tight text-on-surface">{{ $machine->name }}</h1>
                <p class="text-on-surface-variant text-[10px] mt-0.5">Code: {{ $machine->code }} | Modbus TCP/RS485</p>
            </div>
            <div class="flex gap-2">
                <button id="btn-export-excel" data-machine-id="{{ $machine->id }}" class="px-3 py-1.5 bg-surface-container-high text-on-surface text-[10px] font-bold rounded hover:bg-surface-container-highest transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">download</span>
                    Export
                </button>
                <button class="px-3 py-1.5 bg-primary text-white text-[10px] font-bold rounded hover:brightness-110 transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">tune</span>
                    Config
                </button>
            </div>
        </div>

        <!-- KPI Cards - Single Row Compact -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-4">
            <!-- Cost -->
            <div class="bg-surface-container-lowest p-3 rounded border-b-2 border-tertiary-container shadow-sm">
                <span class="text-[8px] font-black uppercase tracking-wider text-outline block mb-1">Cost Today</span>
                <div class="flex items-baseline gap-1">
                    <span class="text-[10px] font-bold text-outline">Rp</span>
                    <span class="text-lg font-black tracking-tighter text-on-surface">{{ number_format($todayCost, 0) }}</span>
                </div>
            </div>
            <!-- Consumption -->
            <div class="bg-surface-container-lowest p-3 rounded border-b-2 border-primary-container shadow-sm">
                <span class="text-[8px] font-black uppercase tracking-wider text-outline block mb-1">Today Usage</span>
                <div class="flex items-baseline gap-1">
                    <span class="text-lg font-black tracking-tighter text-on-surface">{{ number_format($todayConsumption, 1) }}</span>
                    <span class="text-[10px] font-bold text-outline">kWh</span>
                </div>
            </div>
            <!-- Lifetime -->
            <div class="bg-surface-container-lowest p-3 rounded border-b-2 border-secondary-container shadow-sm">
                <span class="text-[8px] font-black uppercase tracking-wider text-outline block mb-1">Lifetime</span>
                <div class="flex items-baseline gap-1">
                    <span class="text-lg font-black tracking-tighter text-on-surface">{{ number_format($totalEnergy, 1) }}</span>
                    <span class="text-[10px] font-bold text-outline">kWh</span>
                </div>
            </div>
            <!-- Voltage -->
            <div class="bg-surface-container-lowest p-3 rounded border-b border-surface-container shadow-sm">
                <span class="text-[8px] font-black uppercase tracking-wider text-outline block mb-1">Voltage</span>
                <div class="text-lg font-black tracking-tighter text-on-surface">{{ number_format($machine->latestReading?->voltage ?? 0, 1) }} <span class="text-[10px] font-bold text-outline">V</span></div>
            </div>
            <!-- Current -->
            <div class="bg-surface-container-lowest p-3 rounded border-b border-surface-container shadow-sm">
                <span class="text-[8px] font-black uppercase tracking-wider text-outline block mb-1">Current</span>
                <div class="text-lg font-black tracking-tighter text-on-surface">{{ number_format($machine->latestReading?->current ?? 0, 1) }} <span class="text-[10px] font-bold text-outline">A</span></div>
            </div>
            <!-- PF -->
            <div class="bg-surface-container-lowest p-3 rounded border-b border-surface-container shadow-sm">
                <span class="text-[8px] font-black uppercase tracking-wider text-outline block mb-1">Power Factor</span>
                <div class="text-lg font-black tracking-tighter text-on-surface">{{ number_format($machine->latestReading?->power_factor ?? 0, 2) }}</div>
            </div>
        </div>

        <!-- Chart Section - Compact Height -->
        <div class="bg-surface-container-lowest p-4 rounded border border-surface-container shadow-sm mb-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-on-surface">Power History</h3>
                <div class="flex gap-2">
                    <select id="chart-metric-toggle" class="bg-surface-container-low text-[9px] p-1 rounded font-bold text-on-surface-variant outline-none border-none">
                        <option value="power">Active Power</option>
                        <option value="voltage">Voltage</option>
                        <option value="current">Current</option>
                        <option value="pf">PF</option>
                    </select>
                    <div class="flex bg-surface-container-low p-0.5 rounded" id="chart-range-selector">
                        <button class="px-2 py-0.5 text-[9px] font-bold rounded text-on-surface-variant transition-colors" data-range="1">1H</button>
                        <button class="px-2 py-0.5 text-[9px] font-bold rounded text-on-surface-variant transition-colors" data-range="4">4H</button>
                        <button class="px-2 py-0.5 text-[9px] font-bold rounded bg-white text-primary shadow-sm transition-colors" data-range="12">12H</button>
                        <button class="px-2 py-0.5 text-[9px] font-bold rounded text-on-surface-variant transition-colors" data-range="24">24H</button>
                        <button class="px-2 py-0.5 text-[9px] font-bold rounded text-on-surface-variant transition-colors" data-range="168">7D</button>
                    </div>

                    <!-- Custom Range / Forensic UI -->
                    <div class="flex items-center gap-1.5 border-l border-surface-container pl-2 ml-1" id="forensic-filter">
                        <div class="flex flex-col">
                            <span class="text-[8px] font-black uppercase text-outline">Start</span>
                            <input type="text" id="forensic-start" class="bg-surface-container-low text-[10px] px-2 py-1 rounded font-bold text-on-surface-variant outline-none border-none w-28" placeholder="YYYY-MM-DD HH:mm">
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[8px] font-black uppercase text-outline">End</span>
                            <input type="text" id="forensic-end" class="bg-surface-container-low text-[10px] px-2 py-1 rounded font-bold text-on-surface-variant outline-none border-none w-28" placeholder="YYYY-MM-DD HH:mm">
                        </div>
                        <button id="btn-forensic-generate" class="bg-primary text-white text-[10px] font-black px-3 py-1.5 rounded self-end hover:brightness-110 transition-all uppercase tracking-tighter">
                            Gen
                        </button>
                    </div>
                </div>
            </div>
            <div class="h-[330px] w-full">
                <canvas id="powerChart"></canvas>
            </div>
        </div>

        <!-- Event Log Section - Full Width Compact -->
        <div class="bg-surface-container-lowest rounded border border-surface-container shadow-sm mb-4">
            <div class="px-4 py-2 border-b border-surface-container-low bg-surface-container-low/30 flex justify-between items-center">
                <h2 class="text-[10px] font-black text-on-surface uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">warning</span>
                    Anomaly & Event Log
                </h2>
                <span class="text-[9px] font-bold text-outline">Showing last 10 entries</span>
            </div>
            <div class="p-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-2">
                @forelse($eventLogs as $log)
                    <div class="border-l-2 @if($log->status == 'WARNING') border-tertiary @else border-error @endif pl-2 py-1 bg-surface-container-low/30 rounded-r">
                        <p class="text-[8px] font-mono font-bold text-outline">{{ $log->event_at->format('H:i:s') }}</p>
                        <p class="text-[9px] font-bold text-on-surface leading-tight truncate" title="{{ $log->message }}">{{ $log->message }}</p>
                    </div>
                @empty
                    <div class="col-span-full py-2 text-center text-[10px] text-outline italic">No anomalous events recorded.</div>
                @endforelse
            </div>
        </div>

        <!-- Raw Telemetry - Full Width Priority -->
        <div class="bg-surface-container-lowest rounded border border-surface-container shadow-sm mb-4">
            <div class="px-4 py-3 border-b border-surface-container-low flex justify-between items-center bg-surface-container-low/50">
                <h2 class="text-[10px] font-black tracking-widest text-on-surface uppercase flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">stream</span>
                    Telemetry Data Ledger (Live)
                </h2>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse"></span>
                        <span class="text-[9px] font-bold text-outline">SYNCING</span>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface-container-low text-[9px] font-black text-on-surface-variant uppercase tracking-widest">
                            <th class="px-4 py-2">Timestamp</th>
                            <th class="px-4 py-2 text-right">Power (kW)</th>
                            <th class="px-4 py-2 text-right">Volt (V)</th>
                            <th class="px-4 py-2 text-right">Curr (A)</th>
                            <th class="px-4 py-2 text-right">PF</th>
                            <th class="px-4 py-2 text-right">Total (kWh)</th>
                            <th class="px-4 py-2 text-center">Status</th>
                            <th class="px-4 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-[11px]">
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-outline italic">Loading telemetry stream...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-4 py-2 bg-surface-container-low flex justify-between items-center border-t border-surface-container-low">
                <div class="flex items-center gap-2">
                    <button id="prev-page" class="p-1 hover:bg-primary/10 rounded-full transition-colors disabled:opacity-20">
                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                    </button>
                    <span class="text-[9px] font-black text-outline uppercase tracking-widest">
                        Page <span id="current-page-display">1</span> / <span id="last-page-display">1</span>
                    </span>
                    <button id="next-page" class="p-1 hover:bg-primary/10 rounded-full transition-colors disabled:opacity-20">
                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                    </button>
                </div>
                <div class="text-[9px] text-outline font-black uppercase tracking-widest">
                    Total: <span id="total-readings-display">0</span> Records
                </div>
            </div>
        </div>

        <!-- Meter Reset History - Collapsible Below -->
        <details class="bg-surface-container-lowest rounded border border-surface-container shadow-sm group">
            <summary class="px-4 py-3 cursor-pointer list-none flex justify-between items-center hover:bg-surface-container-low/30 transition-colors">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">history</span>
                    <h2 class="text-[10px] font-black tracking-widest text-on-surface uppercase">Meter Reset History</h2>
                </div>
                <span class="material-symbols-outlined text-sm transform transition-transform group-open:rotate-180">expand_more</span>
            </summary>
            <div class="border-t border-surface-container-low">
                <div class="px-4 py-3 flex justify-between items-center">
                    <p class="text-[10px] text-outline">Log baseline adjustments for physical meter resets.</p>
                    <button id="btn-log-reset"
                        data-machine-id="{{ $machine->id }}"
                        data-machine-name="{{ $machine->name }}"
                        data-current-kwh="{{ number_format($currentMeterKwh, 2) }}"
                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-[9px] font-black rounded uppercase tracking-widest transition-colors flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">restart_alt</span>
                        Log New Reset
                    </button>
                </div>
                @if($machine->meterResets->isEmpty())
                    <div class="px-4 py-4 text-center text-outline italic text-[10px]">No reset history found.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-[10px]">
                            <thead>
                                <tr class="bg-surface-container-low font-black text-on-surface-variant uppercase tracking-tighter">
                                    <th class="px-4 py-2">Date</th>
                                    <th class="px-4 py-2 text-right">kWh at Reset</th>
                                    <th class="px-4 py-2">Notes</th>
                                    <th class="px-4 py-2">By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container-low">
                                @foreach($machine->meterResets as $reset)
                                <tr>
                                    <td class="px-4 py-2 font-mono">{{ $reset->reset_at->format('d M y H:i') }}</td>
                                    <td class="px-4 py-2 text-right font-bold text-secondary">{{ number_format($reset->kwh_at_reset, 2) }}</td>
                                    <td class="px-4 py-2 text-on-surface-variant truncate max-w-[150px]">{{ $reset->notes ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $reset->performedBy?->name ?? 'System' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="px-4 py-2 bg-surface-container-low/50">
                    <p class="text-[9px] text-outline font-bold">
                        Calculated Baseline: {{ number_format($machine->kwh_baseline, 2) }} kWh | Current Raw: {{ number_format($currentMeterKwh, 2) }} kWh
                    </p>
                </div>
            </div>
        </details>
    </div>
</main>

<!-- Details Modal -->
<div id="details-modal" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm transition-all animate-in fade-in duration-300 pointer-events-none">
    <div class="bg-white w-full max-w-sm rounded shadow-2xl border border-surface-container overflow-hidden transform transition-all scale-95 opacity-0 duration-300 pointer-events-auto" id="modal-content">
        <div class="px-4 py-3 bg-surface-container-low/50 border-b border-surface-container flex justify-between items-center">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-outline">Telemetry Details</h3>
            <button onclick="closeModal()" class="text-outline hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
        <div class="p-4 space-y-4">
            <div class="flex justify-between items-end border-b border-surface-container-low pb-3">
                <div>
                    <span class="text-[8px] font-black text-outline uppercase tracking-widest">Timestamp</span>
                    <div id="modal-timestamp" class="text-xs font-mono font-black text-on-surface"></div>
                </div>
                <div id="modal-status" class="text-right"></div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Active Power</span>
                    <div class="flex items-baseline gap-1">
                        <span id="modal-power" class="text-xl font-black text-primary tracking-tighter"></span>
                        <span class="text-[9px] font-bold text-outline">kW</span>
                    </div>
                </div>
                <div>
                    <span class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Total Energy</span>
                    <div class="flex items-baseline gap-1">
                        <span id="modal-kwh" class="text-xl font-black text-on-surface tracking-tighter"></span>
                        <span class="text-[9px] font-bold text-outline">kWh</span>
                    </div>
                </div>
                <div>
                    <span class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Voltage</span>
                    <div class="text-lg font-bold tracking-tighter text-on-surface"><span id="modal-voltage"></span> <span class="text-[9px] font-bold text-outline">V</span></div>
                </div>
                <div>
                    <span class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Current</span>
                    <div class="text-lg font-bold tracking-tighter text-on-surface"><span id="modal-current"></span> <span class="text-[9px] font-bold text-outline">A</span></div>
                </div>
                <div class="col-span-2">
                    <span class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Power Factor</span>
                    <div id="modal-pf" class="text-lg font-black text-on-surface"></div>
                </div>
            </div>
        </div>
        <div class="px-4 py-3 bg-surface-container-low/50 border-t border-surface-container">
            <button onclick="closeModal()" class="w-full py-2 bg-primary text-white font-black rounded text-[9px] uppercase tracking-widest hover:brightness-110 transition-all shadow-sm">OK</button>
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/chart.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('powerChart').getContext('2d');
        let chartInstance = null;
        let currentHours = 12;
        let currentMetric = 'power';
        
        const deviceId = {{ $machine->devices->first()?->id ?? 'null' }};
        if (!deviceId) {
            renderEmptyChart();
            return;
        }

        // UI bindings
        const metricSelect = document.getElementById('chart-metric-toggle');
        const rangeButtons = document.querySelectorAll('#chart-range-selector button');
        const forensicBtn = document.getElementById('btn-forensic-generate');
        const forensicStart = document.getElementById('forensic-start');
        const forensicEnd = document.getElementById('forensic-end');

        // Initialize Flatpickr for 24h format
        const fpConfig = {
            enableTime: true,
            time_24hr: true,
            dateFormat: "Y-m-d H:i",
            disableMobile: "true",
            allowInput: true
        };
        const startPicker = flatpickr(forensicStart, fpConfig);
        const endPicker = flatpickr(forensicEnd, fpConfig);

        metricSelect.addEventListener('change', function(e) {
            currentMetric = e.target.value;
            fetchAndRender();
        });

        rangeButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                rangeButtons.forEach(b => {
                    b.classList.remove('bg-white', 'text-primary', 'shadow-sm');
                    b.classList.add('text-on-surface-variant');
                });
                this.classList.add('bg-white', 'text-primary', 'shadow-sm');
                this.classList.remove('text-on-surface-variant');
                
                currentHours = parseInt(this.dataset.range);
                // Reset forensic inputs when using quick range
                startPicker.clear();
                endPicker.clear();
                fetchAndRender();
            });
        });

        forensicBtn.addEventListener('click', function() {
            if (!forensicStart.value || !forensicEnd.value) {
                alert('Please select both start and end datetime.');
                return;
            }

            const start = new Date(forensicStart.value);
            const end = new Date(forensicEnd.value);

            if (end <= start) {
                alert('End datetime must be after start datetime.');
                return;
            }

            const diffDays = (end - start) / (1000 * 60 * 60 * 24);
            if (diffDays > 180) {
                alert('Maximum range is 180 days.');
                return;
            }

            // Remove active state from quick range buttons
            rangeButtons.forEach(b => {
                b.classList.remove('bg-white', 'text-primary', 'shadow-sm');
                b.classList.add('text-on-surface-variant');
            });

            // Set currentHours for label formatting logic
            currentHours = diffDays * 24;
            
            fetchAndRender(start, end);
        });

        function fetchAndRender(customStart = null, customEnd = null) {
            let start, end;
            
            if (customStart && customEnd) {
                start = customStart;
                end = customEnd;
            } else {
                end = new Date();
                start = new Date(end.getTime() - currentHours * 60 * 60 * 1000);
            }

            fetch(`/api/charts/device?device_id=${deviceId}&start_date=${start.toISOString()}&end_date=${end.toISOString()}`)
                .then(res => res.json())
                .then(response => {
                    const data = response.data || [];
                    renderChart(data);
                })
                .catch(err => {
                    console.error('Error fetching chart data:', err);
                    renderEmptyChart();
                });
        }

        function renderChart(data) {
            if (chartInstance) chartInstance.destroy();
            
            if (data.length === 0) {
                renderEmptyChart();
                return;
            }

            const powerData = data.map(item => item.power_kw || 0);
            const actualMaxPower = powerData.length > 0 ? Math.max(...powerData) : 0;
            const powerAxisMax = Math.max(actualMaxPower * 1.1, 6.8);

            const voltageData = data.map(item => item.voltage || 0);

            const labels = data.map(item => {
                const d = new Date(item.timestamp);
                if (currentHours > 24) {
                    return d.getDate().toString().padStart(2, '0') + '/' + (d.getMonth() + 1).toString().padStart(2, '0');
                }
                return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
            });

            let datasets = [];
            let unit = '';
            
            if (currentMetric === 'power') {
                unit = 'kW';
                datasets.push({
                    label: 'Active Power (kW)',
                    data: powerData,
                    yAxisID: 'y_power',
                    fill: true,
                    backgroundColor: 'rgba(0, 98, 140, 0.08)',
                    borderColor: '#00628c',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    tension: 0.25
                });
                
                // Add Voltage as secondary dataset
                datasets.push({
                    label: 'Voltage (V)',
                    data: voltageData,
                    yAxisID: 'y_voltage',
                    fill: false,
                    borderColor: 'rgba(255,140,0,0.75)',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [3, 3],
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    tension: 0.25
                });
            } else if (currentMetric === 'voltage') {
                unit = 'V';
                datasets.push({
                    label: 'Voltage (V)',
                    data: voltageData,
                    yAxisID: 'y_power', // Re-use left axis for single view
                    borderColor: 'rgba(255,140,0,0.75)',
                    borderWidth: 2,
                    borderDash: [3, 3],
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    tension: 0.25
                });
            } else if (currentMetric === 'current') {
                unit = 'A';
                datasets.push({
                    label: 'Current (A)',
                    data: data.map(item => item.current || 0),
                    yAxisID: 'y_power',
                    borderColor: '#10b981',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    tension: 0.25
                });
            } else if (currentMetric === 'pf') {
                unit = '';
                datasets.push({
                    label: 'PF',
                    data: data.map(item => item.power_factor || 0),
                    yAxisID: 'y_power',
                    borderColor: '#8b5cf6',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    tension: 0.25
                });
            }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: (currentMetric === 'power'),
                            position: 'top',
                            align: 'end',
                            labels: { boxWidth: 10, font: { size: 9, weight: 'bold' } }
                        },
                        tooltip: {
                            mode: 'index', intersect: false, backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleFont: { size: 10 }, bodyFont: { size: 11 },
                            callbacks: { 
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y.toFixed(2);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y_power: { 
                            type: 'linear',
                            position: 'left',
                            grid: { color: 'rgba(148, 163, 184, 0.1)', drawBorder: false }, 
                            ticks: { font: { size: 9 } },
                            title: { display: true, text: (currentMetric === 'power' ? 'kW' : unit), font: { size: 9, weight: 'bold' } },
                            min: 0,
                            max: (currentMetric === 'power' ? powerAxisMax : (currentMetric === 'voltage' ? 600 : undefined))
                        },
                        y_voltage: {
                            type: 'linear',
                            position: 'right',
                            display: (currentMetric === 'power'),
                            grid: { drawOnChartArea: false },
                            ticks: { font: { size: 9 } },
                            title: { display: true, text: 'V', font: { size: 9, weight: 'bold' } },
                            min: 0,
                            max: 600
                        },
                        x: { 
                            grid: { display: false }, 
                            ticks: { font: { size: 9 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } 
                        }
                    },
                    interaction: { intersect: false }
                }
            });
        }

        function renderEmptyChart() {
            if (chartInstance) chartInstance.destroy();
            chartInstance = new Chart(ctx, { type: 'line', data: { labels: ['No Data'], datasets: [] }, options: { maintainAspectRatio: false } });
        }

        fetchAndRender();

        // Telemetry Logic
        let currentPage = 1;
        let lastPage = 1;
        const machineId = "{{ $machine->id }}";
        
        function formatTimestamp(isoString) {
            const d = new Date(isoString);
            const yyyy = d.getFullYear();
            const mm   = String(d.getMonth() + 1).padStart(2, '0');
            const dd   = String(d.getDate()).padStart(2, '0');
            const hh   = String(d.getHours()).padStart(2, '0');
            const min  = String(d.getMinutes()).padStart(2, '0');
            const ss   = String(d.getSeconds()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd} ${hh}:${min}:${ss}`;
        }

        async function fetchReadings(page = 1) {
            const tbody = document.querySelector('tbody');
            const nextBtn = document.getElementById('next-page');
            const prevBtn = document.getElementById('prev-page');
            const pageDisplay = document.getElementById('current-page-display');
            const lastPageDisplay = document.getElementById('last-page-display');
            const totalDisplay = document.getElementById('total-readings-display');

            try {
                const response = await fetch(`/api/machines/${machineId}/readings?page=${page}&limit=15`);
                const result = await response.json();

                if (result.status === 'success') {
                    const paginator = result.data;
                    const readings = paginator.data;
                    tbody.innerHTML = '';
                    
                    if (readings.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-10 text-center text-outline italic">No telemetry data.</td></tr>';
                    }

                    readings.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.className = 'border-b border-surface-container-low hover:bg-surface-container-low transition-colors';
                        const power = parseFloat(row.power_kw);
                        const timestamp = formatTimestamp(row.recorded_at);
                        const statusHtml = power > 40
                            ? '<span class="bg-error-container text-error px-1.5 py-0.5 rounded text-[8px] font-black uppercase">Spike</span>'
                            : (power <= 0
                                ? '<span class="bg-surface-container-highest text-outline px-1.5 py-0.5 rounded text-[8px] font-black uppercase">Offline</span>'
                                : '<span class="bg-secondary-container text-on-secondary-container px-1.5 py-0.5 rounded text-[8px] font-black uppercase">Optimal</span>');

                        tr.innerHTML = `
                            <td class="px-4 py-2 font-mono text-[10px] text-outline">${timestamp}</td>
                            <td class="px-4 py-2 text-right font-black text-primary">${power.toFixed(2)}</td>
                            <td class="px-4 py-2 text-right text-on-surface-variant">${parseFloat(row.voltage).toFixed(1)}</td>
                            <td class="px-4 py-2 text-right text-on-surface-variant">${parseFloat(row.current).toFixed(1)}</td>
                            <td class="px-4 py-2 text-right text-on-surface-variant">${parseFloat(row.power_factor || 1.0).toFixed(2)}</td>
                            <td class="px-4 py-2 text-right font-bold text-on-surface">${parseFloat(row.kwh_total).toFixed(2)}</td>
                            <td class="px-4 py-2 text-center">${statusHtml}</td>
                            <td class="px-4 py-2 text-right">
                                <button class="text-primary hover:underline text-[9px] font-black uppercase detail-btn"
                                    data-timestamp="${timestamp}"
                                    data-power="${power.toFixed(2)}"
                                    data-voltage="${parseFloat(row.voltage).toFixed(1)}"
                                    data-current="${parseFloat(row.current).toFixed(1)}"
                                    data-pf="${parseFloat(row.power_factor || 1.0).toFixed(2)}"
                                    data-kwh="${parseFloat(row.kwh_total).toFixed(2)}">
                                    Detail
                                </button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    currentPage = paginator.current_page;
                    lastPage = paginator.last_page;
                    pageDisplay.innerText = currentPage;
                    lastPageDisplay.innerText = lastPage;
                    totalDisplay.innerText = paginator.total;
                    prevBtn.disabled = currentPage <= 1;
                    nextBtn.disabled = currentPage >= lastPage;
                }
            } catch (error) { console.error('Error fetching readings:', error); }
        }

        document.getElementById('next-page').addEventListener('click', () => { if (currentPage < lastPage) fetchReadings(currentPage + 1); });
        document.getElementById('prev-page').addEventListener('click', () => { if (currentPage > 1) fetchReadings(currentPage - 1); });
        fetchReadings(1);

        // Modal Logic
        window.openModal = function(data) {
            document.getElementById('modal-timestamp').innerText = data.timestamp;
            document.getElementById('modal-power').innerText = data.power;
            document.getElementById('modal-voltage').innerText = data.voltage;
            document.getElementById('modal-current').innerText = data.current;
            document.getElementById('modal-pf').innerText = data.pf;
            document.getElementById('modal-kwh').innerText = data.kwh;
            
            const statusEl = document.getElementById('modal-status');
            const power = parseFloat(data.power);
            if (power > 40) statusEl.innerHTML = '<span class="bg-error-container text-error px-2 py-1 rounded text-[8px] font-black uppercase">Spike</span>';
            else if (power <= 0) statusEl.innerHTML = '<span class="bg-surface-container-highest text-outline px-2 py-1 rounded text-[8px] font-black uppercase">Offline</span>';
            else statusEl.innerHTML = '<span class="bg-secondary-container text-on-secondary-container px-2 py-1 rounded text-[8px] font-black uppercase">Optimal</span>';

            const modal = document.getElementById('details-modal');
            const content = document.getElementById('modal-content');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => { modal.classList.remove('pointer-events-none'); content.classList.remove('scale-95', 'opacity-0'); }, 10);
        };

        window.closeModal = function() {
            const modal = document.getElementById('details-modal');
            const content = document.getElementById('modal-content');
            content.classList.add('scale-95', 'opacity-0');
            modal.classList.add('pointer-events-none');
            setTimeout(() => { modal.classList.remove('flex'); modal.classList.add('hidden'); }, 300);
        };

        document.body.addEventListener('click', function(e) {
            if (e.target.classList.contains('detail-btn')) { openModal(e.target.dataset); }
        });

        // Export Logic
        const btnExport = document.getElementById('btn-export-excel');
        if (btnExport) {
            btnExport.addEventListener('click', function() {
                const machineId = this.dataset.machineId;
                let start = forensicStart.value;
                let end = forensicEnd.value;

                if (!start || !end) {
                    // If custom range is empty, use the current active quick range (1H, 4H, 12H, etc.)
                    const now = new Date();
                    const startDateObj = new Date(now.getTime() - currentHours * 60 * 60 * 1000);
                    
                    start = startDateObj.toISOString().slice(0, 16).replace('T', ' ');
                    end = now.toISOString().slice(0, 16).replace('T', ' ');
                    
                    const confirmQuickRange = confirm(`Anda belum memilih custom periode.\nDownload data berdasarkan rentang chart yang aktif (${currentHours} jam terakhir)?`);
                    if (!confirmQuickRange) return;
                }

                // Client-side validation for 7 days
                const startDate = new Date(start);
                const endDate = new Date(end);
                const diffDays = (endDate - startDate) / (1000 * 60 * 60 * 24);

                if (diffDays > 7) {
                    alert('⚠️ PERIODE TERLALU BESAR\n\nBatas maksimal download adalah 7 hari (' + diffDays.toFixed(1) + ' hari terpilih).\n\nSilakan pilih rentang waktu yang lebih pendek untuk menjaga kestabilan sistem.');
                    return;
                }

                if (diffDays <= 0) {
                    alert('End date harus setelah start date.');
                    return;
                }

                // Redirect to export route
                const exportUrl = `/monitoring/meters/${machineId}/export?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`;
                window.location.href = exportUrl;
            });
        }

        // Reset Logic
        const btnLogReset = document.getElementById('btn-log-reset');
        if (btnLogReset) {
            btnLogReset.addEventListener('click', async function () {
                const machineId = this.dataset.machineId;
                const confirmed = confirm(`⚠️ LOG RESET METER\n\nLakukan ini SEBELUM reset fisik.\nLanjutkan?`);
                if (!confirmed) return;
                const notes = prompt('Catatan (opsional):');
                this.disabled = true; this.innerText = 'SAVING...';
                try {
                    const response = await fetch(`/api/machines/${machineId}/reset`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                        body: JSON.stringify({ notes: notes || '' })
                    });
                    const result = await response.json();
                    if (response.ok && result.status === 'success') { alert(`✅ Berhasil!`); window.location.reload(); }
                    else { alert('❌ Gagal: ' + (result.message || 'Error')); this.disabled = false; this.innerHTML = '<span class="material-symbols-outlined text-sm">restart_alt</span> Log New Reset'; }
                } catch (err) { alert('❌ Error: ' + err.message); this.disabled = false; this.innerHTML = '<span class="material-symbols-outlined text-sm">restart_alt</span> Log New Reset'; }
            });
        }
    });
</script>
@endsection
