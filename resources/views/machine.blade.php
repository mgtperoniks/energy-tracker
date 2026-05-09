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
            <!-- Active Power -->
            <div class="bg-surface-container-lowest p-3 rounded border-b-2 border-secondary-container shadow-sm">
                <span class="text-[8px] font-black uppercase tracking-wider text-outline block mb-1">Current Load</span>
                <div class="flex items-baseline gap-1">
                    <span class="text-lg font-black tracking-tighter text-on-surface">
                        @if($currentLoadKw !== null && $currentLoadKw > 0)
                            {{ number_format($currentLoadKw, 2) }}
                        @elseif($currentLoadKw === 0)
                            0.00
                        @else
                            <span class="text-outline italic text-sm">N/A</span>
                        @endif
                    </span>
                    <span class="text-[10px] font-bold text-outline">kW</span>
                </div>
            </div>
            <!-- Voltage -->
            <div class="bg-surface-container-lowest p-3 rounded border-b border-surface-container shadow-sm">
                <span class="text-[8px] font-black uppercase tracking-wider text-outline block mb-1">Voltage</span>
                <div class="text-lg font-black tracking-tighter text-on-surface">
                    @if($machine->latestReading && $machine->latestReading->voltage !== null)
                        {{ number_format($machine->latestReading->voltage, 1) }} <span class="text-[10px] font-bold text-outline">V</span>
                    @else
                        <span class="text-outline italic text-sm">N/A</span>
                    @endif
                </div>
            </div>
            <!-- Current -->
            <div class="bg-surface-container-lowest p-3 rounded border-b border-surface-container shadow-sm">
                <span class="text-[8px] font-black uppercase tracking-wider text-outline block mb-1">Current</span>
                <div class="text-lg font-black tracking-tighter text-on-surface">
                    @if($machine->latestReading && $machine->latestReading->current !== null)
                        {{ number_format($machine->latestReading->current, 1) }} <span class="text-[10px] font-bold text-outline">A</span>
                    @else
                        <span class="text-outline italic text-sm">N/A</span>
                    @endif
                </div>
            </div>
            <!-- PF -->
            <div class="bg-surface-container-lowest p-3 rounded border-b border-surface-container shadow-sm">
                <span class="text-[8px] font-black uppercase tracking-wider text-outline block mb-1">Power Factor</span>
                <div class="text-lg font-black tracking-tighter text-on-surface">
                    @if($machine->latestReading && $machine->latestReading->power_factor !== null)
                        {{ number_format($machine->latestReading->power_factor, 2) }}
                    @else
                        <span class="text-outline italic text-sm">N/A</span>
                    @endif
                </div>
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

                    <button id="btn-analyze-cycles" class="px-3 py-1 bg-secondary text-on-secondary text-[9px] font-black rounded hover:brightness-110 transition-all flex items-center gap-1.5 uppercase tracking-widest">
                        <span class="material-symbols-outlined text-sm">analytics</span>
                        Analyze Cycles
                    </button>

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

            <!-- CYCLE ANALYTICS LEDGER -->
            <div id="cycle-analyzer-section" class="mt-6 pt-6 border-t border-surface-container-low hidden animate-in slide-in-from-top duration-500">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-secondary flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">cycle</span>
                        Detected Operational Cycles
                    </h4>
                    <div id="cycle-summary-chips" class="flex gap-2">
                        <!-- Filled by JS -->
                    </div>
                </div>
                <div class="overflow-x-auto rounded border border-surface-container-low">
                    <table class="w-full text-left text-[10px]" id="cycle-table">
                        <thead>
                            <tr class="bg-surface-container-low font-black text-on-surface-variant uppercase tracking-tighter">
                                <th class="px-4 py-2 text-center">No</th>
                                <th class="px-4 py-2">Start / End</th>
                                <th class="px-4 py-2 text-right">Duration</th>
                                <th class="px-4 py-2 text-right">Peak (kW)</th>
                                <th class="px-4 py-2 text-right">Avg (kW)</th>
                                <th class="px-4 py-2 text-right">Energy (kWh)</th>
                                <th class="px-4 py-2 text-right">Idle Gap</th>
                                <th class="px-4 py-2 text-right text-error">Waste kW</th>
                                <th class="px-4 py-2 text-right">Z-Score</th>
                                <th class="px-4 py-2 text-center">Suspicion</th>
                                <th class="px-4 py-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low bg-white">
                            <!-- Filled by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CYCLE DETAIL MODAL -->
        <div id="cycle-modal" class="fixed inset-0 z-[70] hidden flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
            <div class="bg-white w-full max-w-md rounded-lg shadow-2xl border border-surface-container overflow-hidden">
                <div class="px-6 py-4 bg-secondary text-on-secondary flex justify-between items-center">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-widest" id="cycle-modal-title">Cycle Detail</h3>
                        <p class="text-[9px] opacity-80" id="cycle-modal-subtitle"></p>
                    </div>
                    <button onclick="closeCycleModal()" class="hover:bg-white/10 p-1 rounded-full transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div class="p-3 bg-surface-container-low rounded">
                            <span class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Duration</span>
                            <div class="text-xl font-black text-on-surface" id="cycle-detail-duration"></div>
                        </div>
                        <div class="p-3 bg-primary-container/20 rounded">
                            <span class="text-[8px] font-black text-primary uppercase tracking-widest block mb-1">Total Energy</span>
                            <div class="text-xl font-black text-primary" id="cycle-detail-energy"></div>
                        </div>
                        <div>
                            <span class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Average Power</span>
                            <div class="text-base font-bold text-on-surface" id="cycle-detail-avg"></div>
                        </div>
                        <div>
                            <span class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Peak Power</span>
                            <div class="text-base font-bold text-on-surface" id="cycle-detail-peak"></div>
                        </div>
                        <div>
                            <span class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Idle Gap (Before)</span>
                            <div class="text-base font-bold text-outline" id="cycle-detail-gap"></div>
                        </div>
                        <div class="p-3 bg-tertiary-container/10 rounded border border-tertiary/10">
                            <span class="text-[8px] font-black text-tertiary uppercase tracking-widest block mb-1">Estimated Energy Cost</span>
                            <div class="text-xl font-black text-tertiary" id="cycle-detail-cost"></div>
                        </div>
                        <div class="p-3 bg-error-container/5 rounded border border-error/10">
                            <span class="text-[8px] font-black text-error uppercase tracking-widest block mb-1">Idle Cost Leakage</span>
                            <div class="text-xl font-black text-error" id="cycle-detail-idle-cost"></div>
                        </div>
                        <div class="p-3 bg-error-container/10 rounded col-span-2 border border-error/10">
                            <span class="text-[8px] font-black text-error uppercase tracking-widest block mb-1">Audit Metadata</span>
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div class="text-outline">Threshold Source: <span class="font-bold text-on-surface" id="cycle-modal-thresh-source"></span></div>
                                <div class="text-outline">Z-Score: <span class="font-bold text-on-surface" id="cycle-modal-zscore"></span></div>
                                <div class="text-outline">Standby Baseline: <span class="font-bold text-on-surface" id="cycle-modal-standby"></span> kW</div>
                                <div class="text-outline text-error">Waste Power: <span class="font-bold text-error" id="cycle-modal-waste"></span> kW</div>
                            </div>
                        </div>
                    </div>
                    <div id="cycle-suspicion-banner" class="mb-4 p-3 rounded flex items-center justify-between border">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-lg" id="suspicion-icon">report</span>
                            <div>
                                <p class="text-[10px] font-black uppercase">Suspicion Audit: <span id="suspicion-label"></span></p>
                                <p class="text-[9px] opacity-80">Security classification for forensic investigation.</p>
                            </div>
                        </div>
                        <div class="text-xl font-black" id="suspicion-score-val"></div>
                    </div>
                    <div id="cycle-anomaly-warning" class="hidden p-3 bg-error-container text-error rounded flex items-start gap-3 border border-error/20">
                        <span class="material-symbols-outlined text-lg">warning</span>
                        <div>
                            <p class="text-[10px] font-black uppercase">Anomaly Detected</p>
                            <p class="text-[11px] font-medium leading-tight mt-0.5">Cycle duration is significantly above average. Potential operational inefficiency.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-surface-container-low border-t border-surface-container">
                    <button onclick="closeCycleModal()" class="w-full py-2.5 bg-secondary text-on-secondary font-black rounded text-[10px] uppercase tracking-widest hover:brightness-110 transition-all shadow-md">Close Drilldown</button>
                </div>
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
<script src="{{ asset('assets/js/chartjs-plugin-annotation.min.js') }}"></script>
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
                
                // Reset forensic filter state (Ghost Filter Fix)
                currentFilters.start = null;
                currentFilters.end = null;

                // Reload historian global
                fetchReadings(1);

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
            
            // Forensic Mode: Apply filter to both Chart AND Ledger
            currentFilters.start = start.toISOString();
            currentFilters.end = end.toISOString();
            
            fetchAndRender(start, end);
            fetchReadings(1); // Manually refresh ledger ONLY on Forensic Search
        });

        let currentFilters = { start: null, end: null };

        function fetchAndRender(customStart = null, customEnd = null) {
            let start, end;
            
            if (customStart && customEnd) {
                start = customStart;
                end = customEnd;
            } else {
                end = new Date();
                start = new Date(end.getTime() - currentHours * 60 * 60 * 1000);
            }

            fetch(`{{ url('api/charts/device') }}?device_id=${deviceId}&start_date=${start.toISOString()}&end_date=${end.toISOString()}`)
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

            const powerData = data.map(item =>
                item.power_kw !== null ? Number(item.power_kw) : null
            );
            const validPowerData = powerData.filter(v => v !== null);
            const actualMaxPower = validPowerData.length > 0
                ? Math.max(...validPowerData)
                : 0;
            const powerAxisMax = Math.max(actualMaxPower * 1.1, 6.8);

            const voltageData = data.map(item =>
                item.voltage !== null ? Number(item.voltage) : null
            );

            const labels = data.map(item => item.timestamp);

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
                    tension: 0.25,
                    spanGaps: false
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
                    tension: 0.25,
                    spanGaps: false
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
                    tension: 0.25,
                    spanGaps: false
                });
            } else if (currentMetric === 'current') {
                unit = 'A';
                datasets.push({
                    label: 'Current (A)',
                    data: data.map(item =>
                        item.current !== null ? Number(item.current) : null
                    ),
                    yAxisID: 'y_power',
                    borderColor: '#10b981',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    tension: 0.25,
                    spanGaps: false
                });
            } else if (currentMetric === 'pf') {
                unit = '';
                datasets.push({
                    label: 'PF',
                    data: data.map(item =>
                        item.power_factor !== null ? Number(item.power_factor) : null
                    ),
                    yAxisID: 'y_power',
                    borderColor: '#8b5cf6',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    tension: 0.25,
                    spanGaps: false
                });
            }

            // Calculate annotations for downtime
            let chartAnnotations = {};
            let downtimeStart = -1;
            let regionCount = 0;
            let lastActiveIndex = -1;

            powerData.forEach((val, idx) => {
                if (val === null) {
                    if (downtimeStart === -1) downtimeStart = idx;
                } else {
                    lastActiveIndex = idx;
                    if (downtimeStart !== -1) {
                        chartAnnotations['downtime_' + regionCount++] = {
                            type: 'box',
                            xMin: labels[downtimeStart],
                            xMax: labels[idx],
                            backgroundColor: 'rgba(150, 150, 150, 0.12)',
                            borderWidth: 0
                        };
                        downtimeStart = -1;
                    }
                }
            });

            if (downtimeStart !== -1) {
                chartAnnotations['downtime_' + regionCount++] = {
                    type: 'box',
                    xMin: labels[downtimeStart],
                    xMax: labels[powerData.length - 1],
                    backgroundColor: 'rgba(150, 150, 150, 0.12)',
                    borderWidth: 0
                };
            }

            if (lastActiveIndex !== -1 && lastActiveIndex < powerData.length - 1) {
                chartAnnotations['lastActiveLine'] = {
                    type: 'line',
                    xMin: labels[lastActiveIndex],
                    xMax: labels[lastActiveIndex],
                    borderColor: 'rgba(239, 68, 68, 0.6)',
                    borderWidth: 1.5,
                    label: {
                        content: 'Machine OFF',
                        display: true,
                        position: 'start',
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        color: '#fff',
                        font: { size: 9, weight: 'bold' },
                        padding: 4
                    }
                };
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
                                title: function(context) {
                                    const d = new Date(context[0].label);
                                    return d.toLocaleString([], { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y.toFixed(2);
                                    } else {
                                        label += 'No Data';
                                    }
                                    return label;
                                }
                            }
                        },
                        annotation: {
                            annotations: chartAnnotations
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
                            ticks: { 
                                font: { size: 9 }, 
                                maxRotation: 0, 
                                autoSkip: true, 
                                maxTicksLimit: 8,
                                callback: function(val, index) {
                                    const label = this.getLabelForValue(val);
                                    const d = new Date(label);
                                    if (currentHours > 24) {
                                        return d.getDate().toString().padStart(2, '0') + '/' + (d.getMonth() + 1).toString().padStart(2, '0');
                                    }
                                    return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
                                }
                            } 
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
                let url = `{{ url('api/machines') }}/${machineId}/readings?page=${page}&limit=15`;
                if (currentFilters.start && currentFilters.end) {
                    url += `&start_date=${encodeURIComponent(currentFilters.start)}&end_date=${encodeURIComponent(currentFilters.end)}`;
                }
                const response = await fetch(url);
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
                        const statusHtml = row.status_badge;

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
                                    data-kwh="${parseFloat(row.kwh_total).toFixed(2)}"
                                    data-status_badge='${row.status_badge}'>
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
            statusEl.innerHTML = data.status_badge;

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
                
                // SOURCE OF TRUTH: Use official forensic state, not raw inputs
                let start = currentFilters.start;
                let end = currentFilters.end;

                if (!start || !end) {
                    // Fallback to default 12h only if NO forensic filter is active
                    const now = new Date();
                    const startDateObj = new Date(now.getTime() - 12 * 60 * 60 * 1000);
                    
                    start = startDateObj.toISOString();
                    end = now.toISOString();
                }

                // Client-side validation for 7 days
                const startDate = new Date(start);
                const endDate = new Date(end);
                const diffDays = (endDate - startDate) / (1000 * 60 * 60 * 24);

                if (diffDays > 7) {
                    alert('⚠️ PERIODE TERLALU BESAR\n\nBatas maksimal download adalah 7 hari.\n\nSilakan pilih rentang waktu yang lebih pendek.');
                    return;
                }

                if (diffDays <= 0) {
                    alert('End date harus setelah start date.');
                    return;
                }

                // Redirect to export route
                const originalContent = btnExport.innerHTML;
                btnExport.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">sync</span> Exporting...';
                btnExport.disabled = true;

                const exportUrl = `/monitoring/meters/${machineId}/export?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`;
                console.log('Final Export URL:', exportUrl);
                window.location.href = exportUrl;

                // Reset button after a delay (since we can't easily detect download completion)
                setTimeout(() => {
                    btnExport.innerHTML = originalContent;
                    btnExport.disabled = false;
                }, 5000);
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
        // --- CYCLE ANALYZER JS ---
        const btnAnalyze = document.getElementById('btn-analyze-cycles');
        const cycleSection = document.getElementById('cycle-analyzer-section');
        const cycleTableBody = document.querySelector('#cycle-table tbody');
        const cycleSummaryChips = document.getElementById('cycle-summary-chips');
        
        let activeCycles = [];
        let audit_data_source = 'N/A';

        function suspicionColor(level) {
            switch(level) {
                case 'critical': return 'bg-error text-on-error';
                case 'suspicious': return 'bg-orange-500 text-white';
                case 'watch': return 'bg-yellow-400 text-on-surface';
                default: return 'bg-primary-container text-on-primary-container';
            }
        }

        function suspicionBannerColor(level) {
            switch(level) {
                case 'critical': return 'bg-error/10 border-error/20 text-error';
                case 'suspicious': return 'bg-orange-50 border-orange-200 text-orange-700';
                case 'watch': return 'bg-yellow-50 border-yellow-200 text-yellow-700';
                default: return 'bg-surface-container-low border-surface-container text-on-surface-variant';
            }
        }

        if (btnAnalyze) {
            btnAnalyze.addEventListener('click', function() {
                // Determine current range from chart
                let end = new Date();
                let start = new Date(end.getTime() - currentHours * 60 * 60 * 1000);
                
                // If forensic filter is active, use those dates
                if (currentFilters.start && currentFilters.end) {
                    start = new Date(currentFilters.start);
                    end = new Date(currentFilters.end);
                }

                this.disabled = true;
                this.innerHTML = '<span class="animate-spin material-symbols-outlined text-sm">sync</span> Analyzing...';

                fetch(`{{ route('analytics.power.cycles') }}?device_id=${deviceId}&start=${start.toISOString()}&end=${end.toISOString()}`)
                    .then(res => res.json())
                    .then(data => {
                        this.disabled = false;
                        this.innerHTML = '<span class="material-symbols-outlined text-sm">analytics</span> Analyze Cycles';
                        
                        if (data.cycles.length === 0) {
                            alert('No cycles detected for the selected range.');
                            return;
                        }

                        activeCycles = data.cycles;
                        audit_data_source = data.threshold_source;
                        renderCycleData(data);
                        drawCycleAnnotations(data);
                        
                        cycleSection.classList.remove('hidden');
                        cycleSection.scrollIntoView({ behavior: 'smooth' });
                    })
                    .catch(err => {
                        this.disabled = false;
                        this.innerHTML = '<span class="material-symbols-outlined text-sm">analytics</span> Analyze Cycles';
                        console.error('Cycle analysis failed:', err);
                        alert('Analysis failed. Check console for details.');
                    });
            });
        }

        function renderCycleData(data) {
            // Render Summary Chips
            cycleSummaryChips.innerHTML = `
                <div class="px-2 py-1 bg-secondary/10 text-secondary text-[9px] font-black rounded border border-secondary/20">TOTAL: ${data.summary.total_cycles}</div>
                <div class="px-2 py-1 bg-primary/10 text-primary text-[9px] font-black rounded border border-primary/20">AVG DUR: ${data.summary.avg_duration}m</div>
                <div class="px-2 py-1 bg-tertiary/10 text-tertiary text-[9px] font-black rounded border border-primary/20">AVG GAP: ${data.summary.avg_idle_gap}m</div>
            `;

            // Render Table
            cycleTableBody.innerHTML = '';
            data.cycles.forEach(c => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-surface-container-low transition-colors cursor-pointer';
                tr.onclick = () => openCycleDetail(c.cycle_number - 1);
                
                const startTime = new Date(c.start_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const endTime = new Date(c.end_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                tr.innerHTML = `
                    <td class="px-4 py-3 text-center font-black text-outline">${c.cycle_number}</td>
                    <td class="px-4 py-3 font-mono">${startTime} - ${endTime}</td>
                    <td class="px-4 py-3 text-right font-black text-on-surface">${c.duration_minutes}m</td>
                    <td class="px-4 py-3 text-right text-outline">${Math.round(c.peak_kw)}</td>
                    <td class="px-4 py-3 text-right text-outline">${Math.round(c.avg_kw)}</td>
                    <td class="px-4 py-3 text-right font-black text-primary">${c.energy_estimate_kwh.toFixed(1)}</td>
                    <td class="px-4 py-3 text-right text-outline">${c.idle_gap_minutes}m</td>
                    <td class="px-4 py-3 text-right font-black text-error">${c.waste_kw}</td>
                    <td class="px-4 py-3 text-right text-outline">${c.z_score}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase shadow-sm ${suspicionColor(c.suspicion_level)}">
                            ${c.suspicion_level} (${c.suspicion_score})
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button class="text-secondary font-black hover:underline uppercase tracking-widest text-[8px]">Drilldown</button>
                    </td>
                `;
                cycleTableBody.appendChild(tr);
            });
        }

        function drawCycleAnnotations(data) {
            if (!chartInstance) return;

            // Get existing annotations
            const currentAnnotations = chartInstance.options.plugins.annotation.annotations;
            
            // Clear previous cycle annotations
            Object.keys(currentAnnotations).forEach(key => {
                if (key.startsWith('cycle_')) delete currentAnnotations[key];
            });
            
            data.cycles.forEach(c => {
                const cycleKey = `cycle_${c.cycle_number}`;
                
                currentAnnotations[cycleKey] = {
                    type: 'box',
                    xMin: c.start_time,
                    xMax: c.end_time,
                    yMin: 0,
                    yMax: c.peak_kw,
                    backgroundColor: c.abnormal ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)',
                    borderColor: c.abnormal ? 'rgba(239, 68, 68, 0.5)' : 'rgba(16, 185, 129, 0.5)',
                    borderWidth: 1,
                    label: {
                        display: true,
                        content: [`#${c.cycle_number}`, `${c.duration_minutes}m`],
                        position: 'start',
                        color: c.abnormal ? '#ef4444' : '#10b981',
                        font: { size: 9, weight: 'bold' },
                        padding: 4
                    }
                };
            });

            chartInstance.update();
        }

        window.openCycleDetail = function(index) {
            const cycle = activeCycles[index];
            if (!cycle) return;

            const setText = (id, text) => {
                const el = document.getElementById(id);
                if (el) el.innerText = text;
            };

            setText('cycle-modal-title', `Cycle #${cycle.cycle_number} Details`);
            setText('cycle-modal-subtitle', `${new Date(cycle.start_time).toLocaleString()} - ${new Date(cycle.end_time).toLocaleTimeString()}`);
            
            setText('cycle-detail-duration', `${cycle.duration_minutes}m`);
            setText('cycle-detail-energy', `${cycle.energy_estimate_kwh.toFixed(2)} kWh`);
            setText('cycle-detail-avg', `${cycle.avg_kw.toFixed(1)} kW`);
            setText('cycle-detail-peak', `${cycle.peak_kw.toFixed(1)} kW`);
            setText('cycle-detail-gap', `${cycle.idle_gap_minutes}m`);
            
            // Estimated Cost (Mock tariff 1444.7)
            const cost = cycle.energy_estimate_kwh * 1444.7;
            setText('cycle-detail-cost', `Rp ${Math.round(cost).toLocaleString()}`);
            
            // Idle Leakage
            setText('cycle-detail-idle-cost', `Rp ${cycle.idle_cost_loss.toLocaleString()}`);
            
            // Audit Metadata
            setText('cycle-modal-thresh-source', audit_data_source);
            setText('cycle-modal-zscore', cycle.z_score);
            setText('cycle-modal-standby', cycle.standby_baseline_kw);
            setText('cycle-modal-waste', cycle.waste_kw);

            // Suspicion Banner
            const banner = document.getElementById('cycle-suspicion-banner');
            const scoreVal = document.getElementById('suspicion-score-val');
            const labelEl = document.getElementById('suspicion-label');

            if (banner && scoreVal && labelEl) {
                banner.className = `mb-4 p-3 rounded flex items-center justify-between border ${suspicionBannerColor(cycle.suspicion_level)}`;
                scoreVal.innerText = cycle.suspicion_score;
                labelEl.innerText = cycle.suspicion_level.toUpperCase();
            }
            
            const warning = document.getElementById('cycle-anomaly-warning');
            if (warning) {
                if (cycle.abnormal) warning.classList.remove('hidden');
                else warning.classList.add('hidden');
            }

            const modal = document.getElementById('cycle-modal');
            if (modal) modal.classList.remove('hidden');
        };

        window.closeCycleModal = function() {
            document.getElementById('cycle-modal').classList.add('hidden');
        };

    });
</script>
@endsection
