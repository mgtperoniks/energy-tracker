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

                    <button id="btn-create-tag" class="px-3 py-1 bg-primary text-white text-[9px] font-black rounded hover:brightness-110 transition-all flex items-center gap-1.5 uppercase tracking-widest hidden">
                        <span class="material-symbols-outlined text-sm">add_location_alt</span>
                        Tag Selected Point
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
            <div class="flex justify-between items-center mb-2">
                <div class="flex gap-2 text-[8px] font-black uppercase tracking-widest" id="chart-legend">
                    <span class="px-1.5 py-0.5 rounded text-white shadow-sm" style="background: rgba(16, 185, 129, 0.8)">Start</span>
                    <span class="px-1.5 py-0.5 rounded text-white shadow-sm" style="background: rgba(249, 115, 22, 0.8)">Melting</span>
                    <span class="px-1.5 py-0.5 rounded text-white shadow-sm" style="background: rgba(148, 163, 184, 0.8)">Idle</span>
                    <span class="px-1.5 py-0.5 rounded text-white shadow-sm" style="background: rgba(234, 179, 8, 0.8)">Test</span>
                    <span class="px-1.5 py-0.5 rounded text-white shadow-sm" style="background: rgba(59, 130, 246, 0.8)">Pour</span>
                    <span class="px-1.5 py-0.5 rounded text-white shadow-sm" style="background: rgba(239, 68, 68, 0.8)">End</span>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="h-[330px] flex-grow min-w-0">
                    <canvas id="powerChart"></canvas>
                </div>
                <div class="w-64 border border-surface-container-low rounded bg-surface-container-lowest flex flex-col" id="timeline-panel">
                    <div class="px-3 py-2 border-b border-surface-container-low bg-surface-container-low/50 sticky top-0">
                        <h4 class="text-[9px] font-black uppercase tracking-widest text-on-surface">Timeline</h4>
                    </div>
                    <div class="flex-grow overflow-y-auto p-2 space-y-2 text-[10px]" id="timeline-content">
                        <!-- timeline items -->
                    </div>
                </div>
            </div>

            <!-- PHASE RECONSTRUCTION TABLE -->
            <div id="phase-reconstruction-section" class="mt-6 pt-6 border-t border-surface-container-low hidden animate-in slide-in-from-top duration-500">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">timeline</span>
                        Operational Phases
                    </h4>
                </div>
                <div class="overflow-x-auto rounded border border-surface-container-low">
                    <table class="w-full text-left text-[10px]" id="phase-table">
                        <thead>
                            <tr class="bg-surface-container-low font-black text-on-surface-variant uppercase tracking-tighter">
                                <th class="px-4 py-2">Start</th>
                                <th class="px-4 py-2">End</th>
                                <th class="px-4 py-2 text-center">Status</th>
                                <th class="px-4 py-2">Phase Name</th>
                                <th class="px-4 py-2 text-right">Dur</th>
                                <th class="px-4 py-2 text-right">Avg (kW)</th>
                                <th class="px-4 py-2 text-right">Peak (kW)</th>
                                <th class="px-4 py-2 text-right">Usage (kWh)</th>
                                <th class="px-4 py-2 text-right">Est. Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low bg-white" id="phase-tbody">
                            <!-- Filled by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE/EDIT OPERATIONAL TAG MODAL -->
        <div id="tag-modal" class="fixed inset-0 z-[70] hidden flex items-center justify-center p-4 bg-black/60 backdrop-blur-md transition-all animate-in fade-in duration-300">
            <div class="bg-white w-full max-w-sm rounded shadow-2xl border border-surface-container overflow-hidden">
                <div class="px-4 py-3 bg-surface-container-low/50 border-b border-surface-container flex justify-between items-center">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-outline" id="tag-modal-title">Create Operational Tag</h3>
                    <button onclick="closeTagModal()" class="text-outline hover:text-on-surface transition-colors">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
                <div class="p-4 space-y-3">
                    <input type="hidden" id="tag-id" value="">
                    
                    <div>
                        <label class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Selected Timestamp</label>
                        <input type="text" id="tag-timestamp" class="w-full bg-surface-container-low text-xs px-2 py-1.5 rounded font-mono font-bold text-on-surface outline-none border border-surface-container" readonly>
                        <p class="text-[8px] text-outline mt-0.5">Snapped to nearest telemetry reading.</p>
                    </div>

                    <div>
                        <label class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Event Type</label>
                        <select id="tag-event-type" class="w-full bg-white text-xs px-2 py-1.5 rounded font-bold text-on-surface outline-none border border-surface-container focus:border-primary">
                            <option value="start">Start (Furnace/Process starts)</option>
                            <option value="melting">Melting (Active heating)</option>
                            <option value="idle">Idle (Waiting/Hold condition)</option>
                            <option value="test">Test (Spectro/Quality check)</option>
                            <option value="pour">Pour (Pouring/Tapping process)</option>
                            <option value="end">End (Production finished)</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Shift</label>
                        <select id="tag-shift" class="w-full bg-white text-xs px-2 py-1.5 rounded font-bold text-on-surface outline-none border border-surface-container focus:border-primary">
                            <option value="1">Shift 1</option>
                            <option value="2">Shift 2</option>
                            <option value="3">Shift 3</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Notes (Optional)</label>
                        <textarea id="tag-notes" class="w-full bg-white text-xs px-2 py-1.5 rounded font-medium text-on-surface outline-none border border-surface-container focus:border-primary h-16 resize-none" placeholder="Add operator notes..."></textarea>
                    </div>
                </div>
                <div class="px-4 py-3 bg-surface-container-low/50 border-t border-surface-container flex gap-2 justify-end">
                    <button id="btn-delete-tag" class="hidden px-4 py-2 bg-error-container text-error font-black rounded text-[9px] uppercase tracking-widest hover:brightness-110 transition-all shadow-sm">Delete</button>
                    <button onclick="saveTag()" class="px-4 py-2 bg-primary text-white font-black rounded text-[9px] uppercase tracking-widest hover:brightness-110 transition-all shadow-sm">Save Tag</button>
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

        <!-- Historian Health Panel -->
        <div class="bg-surface-container-lowest rounded border border-surface-container shadow-sm mb-4">
            <div class="px-3 py-1.5 border-b border-surface-container-low bg-surface-container-low/30 flex justify-between items-center">
                <h2 class="text-[9px] font-black text-on-surface uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-[10px]">monitor_heart</span>
                    Historian Health Diagnostics
                </h2>
                <span id="health-last-refresh" class="text-[8px] font-mono text-outline">Waiting...</span>
            </div>
            <div class="p-2 flex flex-wrap gap-4 text-[9px] uppercase tracking-widest text-outline">
                <div>Chart: <span id="health-chart" class="font-black text-on-surface">INIT</span></div>
                <div>Tags: <span id="health-tags" class="font-black text-on-surface">0</span></div>
                <div>Phases: <span id="health-phases" class="font-black text-on-surface">0</span></div>
                <div>Telemetry: <span id="health-telemetry" class="font-black text-on-surface">0</span></div>
                <div>Forensic: <span id="health-forensic" class="font-black text-on-surface">OFF</span></div>
                <div>Mode: <span id="health-network" class="font-black text-primary">OFFLINE LAN</span></div>
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
                            <th class="px-4 py-2 text-center">Quality</th>
                            <th class="px-4 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-[11px]" id="telemetry-tbody">
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
<script src="{{ asset('assets/vendor/date-fns/date-fns.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chartjs-adapter-date-fns/chartjs-adapter-date-fns.bundle.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('powerChart').getContext('2d');
        let chartInstance = null;
        let baseAnnotations = {};
        let currentHours = 12;
        let currentMetric = 'power';
        
        const deviceId = {{ $machine->devices->first() ? $machine->devices->first()->id : 'null' }};
        if (!deviceId) {
            renderEmptyChart();
            return;
        }

        async function safeFetch(url, options = {}, retries = 1) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 15000); // 15s timeout
                options.signal = controller.signal;

                const response = await fetch(url, options);
                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                return await response.json();
            } catch (error) {
                if (retries > 0) {
                    console.warn(`[SafeFetch] Retrying ${url}...`);
                    return safeFetch(url, options, retries - 1);
                }
                console.error(`[SafeFetch] Failed: ${error.message}`);
                throw error;
            }
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

        function updateHealthPanel(key, val) {
            const el = document.getElementById('health-' + key);
            if (el) el.innerText = val;
            const ref = document.getElementById('health-last-refresh');
            if (ref) ref.innerText = 'Last Sync: ' + formatWIB(new Date().toISOString()).split(' ')[1];
        }

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
                updateHealthPanel('forensic', 'OFF');

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
            updateHealthPanel('forensic', 'ACTIVE');
            
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

            return safeFetch(`{{ url('api/charts/device') }}?device_id=${deviceId}&start_date=${start.toISOString()}&end_date=${end.toISOString()}`)
                .then(response => {
                    const data = response.data || [];
                    renderChart(data);
                })
                .catch(err => {
                    console.error('Error fetching chart data:', err);
                    renderEmptyChart();
                });
        }

        // TASK 8: Visualization Smoothing Logic
        function interpolateSmallGaps(dataArray, maxGapSize = 2) {
            const result = [...dataArray];
            let gapStart = -1;
            for (let i = 0; i < result.length; i++) {
                if (result[i] === null) {
                    if (gapStart === -1) gapStart = i;
                } else {
                    if (gapStart !== -1) {
                        let gapSize = i - gapStart;
                        if (gapSize <= maxGapSize && gapStart > 0) {
                            // Simple linear interpolation for visualization smoothing
                            let startVal = result[gapStart - 1];
                            let endVal = result[i];
                            for (let j = gapStart; j < i; j++) {
                                let factor = (j - (gapStart - 1)) / (i - (gapStart - 1));
                                result[j] = startVal + (endVal - startVal) * factor;
                            }
                        }
                        gapStart = -1;
                    }
                }
            }
            return result;
        }

        function renderChart(data) {
            try {
                if (chartInstance) chartInstance.destroy();
            
            if (data.length === 0) {
                renderEmptyChart();
                updateHealthPanel('chart', 'EMPTY');
                return;
            }
            updateHealthPanel('chart', 'LOADED');

            // Apply smoothing for visualization ONLY (Task 8)
            const rawPower = data.map(item => item.power_kw !== null ? Number(item.power_kw) : null);
            const powerData = interpolateSmallGaps(rawPower);
            
            const validPowerData = powerData.filter(v => v !== null);
            const actualMaxPower = validPowerData.length > 0
                ? Math.max(...validPowerData)
                : 0;
            const powerAxisMax = Math.max(actualMaxPower * 1.1, 6.8);

            const rawVoltage = data.map(item => item.voltage !== null ? Number(item.voltage) : null);
            const voltageData = interpolateSmallGaps(rawVoltage);

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
                const rawCurrent = data.map(item => item.current !== null ? Number(item.current) : null);
                datasets.push({
                    label: 'Current (A)',
                    data: interpolateSmallGaps(rawCurrent),
                    yAxisID: 'y_power',
                    borderColor: '#10b981',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    tension: 0.25,
                    spanGaps: false
                });
            } else if (currentMetric === 'pf') {
                unit = '';
                const rawPF = data.map(item => item.power_factor !== null ? Number(item.power_factor) : null);
                datasets.push({
                    label: 'PF',
                    data: interpolateSmallGaps(rawPF),
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

            baseAnnotations = window.structuredClone 
                ? structuredClone(chartAnnotations) 
                : JSON.parse(JSON.stringify(chartAnnotations));

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
                                    return formatWIB(context[0].label).substring(5, 16);
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
                            annotations: window.structuredClone 
                                ? structuredClone(baseAnnotations) 
                                : JSON.parse(JSON.stringify(baseAnnotations))
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
                            type: 'time',
                            time: {
                                tooltipFormat: 'dd/MM HH:mm',
                                displayFormats: {
                                    hour: 'HH:mm',
                                    day: 'dd/MM HH:mm'
                                }
                            },
                            grid: { display: false }, 
                            ticks: { 
                                font: { size: 9 }, 
                                maxRotation: 0, 
                                autoSkip: true, 
                                maxTicksLimit: 8
                            } 
                        }
                    },
                    interaction: { intersect: false },
                    onClick: (e) => {
                        const points = chartInstance.getElementsAtEventForMode(e, 'nearest', { intersect: false }, true);
                        if (points.length) {
                            const firstPoint = points[0];
                            const label = chartInstance.data.labels[firstPoint.index];
                            if(typeof openTagModal === 'function') openTagModal(label);
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Fatal Chart.js Error:', error);
            renderEmptyChart();
        }
    }

        function renderEmptyChart() {
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }
            chartInstance = new Chart(ctx, { type: 'line', data: { labels: ['No Data'], datasets: [] }, options: { maintainAspectRatio: false } });
            updateHealthPanel('chart', 'NO DATA');
        }

        // Telemetry Global State
        let currentPage = 1;
        let lastPage = 1;
        const machineId = "{{ $machine->id }}";

        // Async Flow Refactor
        async function initDashboard() {
            await fetchAndRender();
            await window.refreshTags();
            await window.refreshPhases();
            await fetchReadings(1);
        }
        
        // Execute deterministic init
        initDashboard();
        function formatWIB(isoString) {
            const d = new Date(isoString);
            const utc = d.getTime() + (d.getTimezoneOffset() * 60000);
            const nd = new Date(utc + (3600000 * 7)); // +7 for WIB
            const yyyy = nd.getFullYear();
            const mm   = String(nd.getMonth() + 1).padStart(2, '0');
            const dd   = String(nd.getDate()).padStart(2, '0');
            const hh   = String(nd.getHours()).padStart(2, '0');
            const min  = String(nd.getMinutes()).padStart(2, '0');
            const ss   = String(nd.getSeconds()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd} ${hh}:${min}:${ss}`;
        }

        async function fetchReadings(page = 1) {
            const tbody = document.getElementById('telemetry-tbody');
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
                const result = await safeFetch(url);

                if (result.status === 'success') {
                    const paginator = result.data;
                    const readings = paginator.data;
                    updateHealthPanel('telemetry', paginator.total);
                    
                    if (tbody) {
                        tbody.innerHTML = '';
                        
                        if (readings.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-10 text-center text-outline italic">No telemetry data.</td></tr>';
                        }
                    }

                    readings.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.className = 'border-b border-surface-container-low hover:bg-surface-container-low transition-colors';
                        const fmtNum = (v, dec=2) => { const n = parseFloat(v); return isNaN(n) ? (0).toFixed(dec) : n.toFixed(dec); };
                        const timestamp = formatWIB(row.recorded_at);
                        const statusHtml = row.status_badge;
                        const quality = row.telemetry_quality || 'GOOD';
                        const qualityColors = {
                            'GOOD': 'text-green-600',
                            'PARTIAL': 'text-orange-500',
                            'OFFLINE': 'text-slate-400',
                            'STALE': 'text-amber-600 font-black',
                            'INTERPOLATED': 'text-blue-500'
                        };
                        const qColor = qualityColors[quality] || 'text-outline';

                        tr.innerHTML = `
                            <td class="px-4 py-2 font-mono text-[10px] text-outline">${timestamp}</td>
                            <td class="px-4 py-2 text-right font-black text-primary">${fmtNum(row.power_kw)}</td>
                            <td class="px-4 py-2 text-right text-on-surface-variant">${fmtNum(row.voltage, 1)}</td>
                            <td class="px-4 py-2 text-right text-on-surface-variant">${fmtNum(row.current, 1)}</td>
                            <td class="px-4 py-2 text-right text-on-surface-variant">${fmtNum(row.power_factor)}</td>
                            <td class="px-4 py-2 text-right font-bold text-on-surface">${fmtNum(row.kwh_total)}</td>
                            <td class="px-4 py-2 text-center">${statusHtml}</td>
                            <td class="px-4 py-2 text-center font-black text-[9px] ${qColor}">${quality}</td>
                            <td class="px-4 py-2 text-right">
                                <button class="text-primary hover:underline text-[9px] font-black uppercase detail-btn"
                                    data-timestamp="${timestamp}"
                                    data-power="${fmtNum(row.power_kw)}"
                                    data-voltage="${fmtNum(row.voltage, 1)}"
                                    data-current="${fmtNum(row.current, 1)}"
                                    data-pf="${fmtNum(row.power_factor)}"
                                    data-kwh="${fmtNum(row.kwh_total)}"
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
            } catch (error) { 
                console.error('Error fetching readings:', error);
                const tbody = document.getElementById('telemetry-tbody');
                if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-10 text-center text-error italic">Failed to load telemetry stream.</td></tr>';
            }
        }

        document.getElementById('next-page').addEventListener('click', () => { if (currentPage < lastPage) fetchReadings(currentPage + 1); });
        document.getElementById('prev-page').addEventListener('click', () => { if (currentPage > 1) fetchReadings(currentPage - 1); });

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
                    const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
                    const result = await safeFetch(`/api/machines/${machineId}/reset`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ notes: notes || '' })
                    });
                    if (result.status === 'success') { alert(`✅ Berhasil!`); window.location.reload(); }
                    else { alert('❌ Gagal: ' + (result.message || 'Error')); this.disabled = false; this.innerHTML = '<span class="material-symbols-outlined text-sm">restart_alt</span> Log New Reset'; }
                } catch (err) { alert('❌ Error: ' + err.message); this.disabled = false; this.innerHTML = '<span class="material-symbols-outlined text-sm">restart_alt</span> Log New Reset'; }
            });
        }
        // --- MANUAL TAGGING JS ---
        const phaseSection = document.getElementById('phase-reconstruction-section');
        const phaseTableBody = document.getElementById('phase-tbody');
        const tagModal = document.getElementById('tag-modal');
        let currentTags = [];
        
        window.openTagModal = function(timestamp, tagData = null) {
            document.getElementById('tag-timestamp').value = formatWIB(timestamp);
            if (tagData) {
                document.getElementById('tag-id').value = tagData.id;
                document.getElementById('tag-event-type').value = tagData.event_type;
                document.getElementById('tag-shift').value = tagData.shift || '1';
                document.getElementById('tag-notes').value = tagData.notes || '';
                document.getElementById('tag-modal-title').innerText = 'Edit Operational Tag';
                document.getElementById('btn-delete-tag').classList.remove('hidden');
                document.getElementById('btn-delete-tag').onclick = () => deleteTag(tagData.id);
            } else {
                document.getElementById('tag-id').value = '';
                document.getElementById('tag-event-type').value = 'start';
                document.getElementById('tag-shift').value = '1';
                document.getElementById('tag-notes').value = '';
                document.getElementById('tag-modal-title').innerText = 'Create Operational Tag';
                document.getElementById('btn-delete-tag').classList.add('hidden');
            }
            tagModal.classList.remove('hidden');
        };

        window.closeTagModal = function() {
            tagModal.classList.add('hidden');
        };

        window.saveTag = async function() {
            const btnSave = document.querySelector('#tag-modal button[onclick="saveTag()"]');
            btnSave.disabled = true;
            btnSave.innerHTML = '<span class="animate-spin material-symbols-outlined text-[10px]">sync</span> Saving...';

            const id = document.getElementById('tag-id').value;
            const data = {
                event_time: document.getElementById('tag-timestamp').value,
                event_type: document.getElementById('tag-event-type').value,
                shift: document.getElementById('tag-shift').value,
                notes: document.getElementById('tag-notes').value
            };

            try {
                const url = id ? `/api/tags/${id}` : `/api/machines/${machineId}/tags`;
                const method = id ? 'PUT' : 'POST';
                const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
                
                // Wrap safeFetch but we need to handle 422 validations inside the safeFetch logic
                // Wait, safeFetch throws an error if response.ok is false! We need the JSON body.
                // It's better to just use raw fetch here for validation handling or adjust safeFetch.
                // Since safeFetch throws on !response.ok, and we need `result.status === 'VALID_WITH_WARNING'`,
                // let's just use raw fetch for saveTag because we need specific 422 JSON handling.
                const response = await fetch(url, {
                    method: method,
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken 
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                if (response.ok) {
                    closeTagModal();
                    await window.refreshTags();
                    await window.refreshPhases();
                } else {
                    if (result.status === 'VALID_WITH_WARNING') {
                        if(confirm('Warning: ' + result.message + '\n\nDo you want to proceed anyway?')) {
                            data.force = true;
                            // Retry with force
                            const retryResponse = await fetch(url, {
                                method: method,
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                                body: JSON.stringify(data)
                            });
                            if (retryResponse.ok) {
                                closeTagModal();
                                await window.refreshTags();
                                await window.refreshPhases();
                            } else {
                                alert((await retryResponse.json()).message || 'Validation failed.');
                            }
                        }
                    } else {
                        alert(result.message || 'Validation failed.');
                    }
                }
            } catch (e) {
                alert('Error saving tag.');
                console.error(e);
            } finally {
                btnSave.disabled = false;
                btnSave.innerText = 'SAVE TAG';
            }
        };

        window.deleteTag = async function(id) {
            if (!confirm('Are you sure you want to delete this tag?')) return;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
                const response = await fetch(`/api/tags/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                if (response.ok) {
                    closeTagModal();
                    await window.refreshTags();
                    await window.refreshPhases();
                }
            } catch (e) {
                alert('Error deleting tag.');
            }
        };

        window.refreshTags = async function() {
            let end = new Date();
            let start = new Date(end.getTime() - currentHours * 60 * 60 * 1000);
            
            if (currentFilters.start && currentFilters.end) {
                start = new Date(currentFilters.start);
                end = new Date(currentFilters.end);
            }

            try {
                const tags = await safeFetch(`/api/machines/${machineId}/tags?start=${start.toISOString()}&end=${end.toISOString()}`);
                currentTags = tags;
                updateHealthPanel('tags', tags.length);
                drawTagAnnotations(tags);
                renderTimeline(tags);
            } catch (e) {
                console.error("Error fetching tags", e);
            }
        };

        function renderTimeline(tags) {
            const container = document.getElementById('timeline-content');
            if (!container) return;
            container.innerHTML = '';
            
            if(tags.length === 0) {
                container.innerHTML = '<div class="text-center py-4 text-outline italic">No operational tags found.</div>';
                return;
            }

            tags.forEach(t => {
                const div = document.createElement('div');
                div.className = 'p-2 rounded border border-surface-container bg-white shadow-sm hover:bg-surface-container-lowest cursor-pointer transition-colors';
                div.onclick = () => openTagModal(t.event_time, t);
                
                const timeStr = formatWIB(t.event_time).split(' ')[1];
                const editedBadge = t.edited_at ? `<span class="px-1 py-0.5 ml-1 bg-surface-container-high text-[8px] rounded" title="Edited at ${formatWIB(t.edited_at)}">Edited</span>` : '';
                
                div.innerHTML = `
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-bold text-on-surface">${timeStr}</span>
                        <span class="px-1.5 py-0.5 rounded text-white text-[8px] uppercase tracking-widest shadow-sm" style="background: ${getEventColor(t.event_type)}">${t.event_type}</span>
                    </div>
                    <div class="flex justify-between items-center text-outline text-[8px]">
                        <span class="truncate pr-1">By: ${t.tagger ? t.tagger.name : 'System'}</span>
                        ${editedBadge}
                    </div>
                `;
                container.appendChild(div);
            });
        }

        window.refreshPhases = async function() {
            let end = new Date();
            let start = new Date(end.getTime() - currentHours * 60 * 60 * 1000);
            
            if (currentFilters.start && currentFilters.end) {
                start = new Date(currentFilters.start);
                end = new Date(currentFilters.end);
            }

            phaseTableBody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><span class="animate-spin material-symbols-outlined">sync</span> Loading phases...</td></tr>';
            try {
                const phases = await safeFetch(`/api/machines/${machineId}/phases?start=${start.toISOString()}&end=${end.toISOString()}`);
                updateHealthPanel('phases', phases.length);
                renderPhases(phases);
            } catch (e) {
                console.error("Error fetching phases", e);
                phaseTableBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-error">Failed to load phases.</td></tr>';
            }
        };

        function getEventColor(type) {
            const colors = {
                'start': 'rgba(16, 185, 129, 0.8)', // green
                'melting': 'rgba(249, 115, 22, 0.8)', // orange
                'idle': 'rgba(148, 163, 184, 0.8)', // slate
                'test': 'rgba(234, 179, 8, 0.8)', // yellow
                'pour': 'rgba(59, 130, 246, 0.8)', // blue
                'end': 'rgba(239, 68, 68, 0.8)' // red
            };
            return colors[type] || 'rgba(148, 163, 184, 0.8)';
        }

        function drawTagAnnotations(tags) {
            if (!chartInstance) return;
            
            const currentAnnotations = window.structuredClone 
                ? structuredClone(baseAnnotations || {}) 
                : JSON.parse(JSON.stringify(baseAnnotations || {}));
            
            tags.forEach(t => {
                currentAnnotations[`tag_${t.id}`] = {
                    type: 'line',
                    xMin: t.event_time,
                    xMax: t.event_time,
                    borderColor: getEventColor(t.event_type),
                    borderWidth: 2,
                    borderDash: [4, 4],
                    label: {
                        content: t.event_type.toUpperCase(),
                        display: true,
                        position: 'start',
                        backgroundColor: getEventColor(t.event_type),
                        color: '#fff',
                        font: { size: 9, weight: 'bold' }
                    },
                    enter(ctx, event) {
                        ctx.chart.canvas.style.cursor = 'pointer';
                    },
                    leave(ctx, event) {
                        ctx.chart.canvas.style.cursor = 'default';
                    },
                    click(ctx, event) {
                        openTagModal(t.event_time, t);
                    }
                };
            });

            chartInstance.options.plugins.annotation.annotations = currentAnnotations;
            chartInstance.update();
        }

        function renderPhases(phases) {
            phaseTableBody.innerHTML = '';
            if (phases.length > 0) {
                phaseSection.classList.remove('hidden');
                
                // Summaries
                let totalDur = 0;
                let totalUsage = 0;
                let totalPhases = phases.length;

                phases.forEach(p => {
                    totalDur += p.duration_minutes;
                    totalUsage += p.usage_kwh;
                    
                    const tr = document.createElement('tr');
                    tr.className = p.status === 'OPEN' ? 'bg-primary/5 hover:bg-primary/10 transition-colors' : 'hover:bg-surface-container-low transition-colors bg-white';
                    
                    const statusHtml = p.status === 'OPEN' 
                        ? '<span class="px-2 py-0.5 bg-primary/20 text-primary rounded font-black text-[8px] animate-pulse">IN PROGRESS</span>'
                        : '<span class="px-2 py-0.5 bg-surface-container-high text-outline rounded font-black text-[8px]">CLOSED</span>';
                        
                    tr.innerHTML = `
                        <td class="px-4 py-2 font-mono text-outline">${formatWIB(p.start_time).split(' ')[1]}</td>
                        <td class="px-4 py-2 font-mono text-outline">${formatWIB(p.end_time).split(' ')[1]}</td>
                        <td class="px-4 py-2 text-center">${statusHtml}</td>
                        <td class="px-4 py-2 font-black uppercase text-primary">${p.phase_name}</td>
                        <td class="px-4 py-2 text-right font-black">${p.duration_minutes}m</td>
                        <td class="px-4 py-2 text-right text-outline">${p.avg_kw}</td>
                        <td class="px-4 py-2 text-right text-outline">${p.peak_kw}</td>
                        <td class="px-4 py-2 text-right font-black text-on-surface">${p.usage_kwh}</td>
                        <td class="px-4 py-2 text-right font-black text-tertiary">Rp ${p.est_cost.toLocaleString()}</td>
                    `;
                    phaseTableBody.appendChild(tr);
                });
                
                // Add Summary Row
                const summaryTr = document.createElement('tr');
                summaryTr.className = 'bg-surface-container-low font-black text-[10px] text-on-surface uppercase';
                summaryTr.innerHTML = `
                    <td colspan="4" class="px-4 py-2 text-right">TOTALS (${totalPhases} PHASES)</td>
                    <td class="px-4 py-2 text-right text-primary">${totalDur}m</td>
                    <td colspan="2" class="px-4 py-2 text-right"></td>
                    <td class="px-4 py-2 text-right text-primary">${totalUsage.toFixed(2)} kWh</td>
                    <td class="px-4 py-2 text-right"></td>
                `;
                phaseTableBody.appendChild(summaryTr);
                
            } else {
                phaseSection.classList.add('hidden');
            }
        }
        // Intentionally left blank, initialization handled by initDashboard();

    });
</script>
@endsection
