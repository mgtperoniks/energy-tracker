@extends('layouts.app')

@section('content')
<!-- Flatpickr for 24h Datetime Picker (Localized for Offline) -->
<link rel="stylesheet" href="{{ asset('assets/vendor/flatpickr/flatpickr.min.css') }}">
<script src="{{ asset('assets/vendor/flatpickr/flatpickr.min.js') }}"></script>

<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-4 md:p-6 max-w-7xl mx-auto">
        <!-- Alerts -->
        @if(session('success'))
            <div
                class="bg-secondary-container text-on-secondary-container p-3 rounded text-xs font-bold mb-4 flex items-center gap-2 border border-secondary/20">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div
                class="bg-error-container text-error p-3 rounded text-xs font-bold mb-4 flex items-center gap-2 border border-error/20">
                <span class="material-symbols-outlined text-sm">error</span>
                {{ session('error') }}
            </div>
        @endif

        <!-- Header Section - Compact -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 mb-4">
            <div>
                <div class="flex items-center gap-2">
                    @php
                        $statusColor = match ($opStatus) {
                            'Offline' => 'bg-outline',
                            'Low Voltage' => 'bg-error',
                            'Idle' => 'bg-tertiary',
                            'Mixed Load' => 'bg-primary',
                            default => 'bg-secondary'
                        };
                    @endphp
                    <span
                        class="inline-block w-1.5 h-1.5 rounded-full {{ $statusColor }} @if($opStatus == 'Running') animate-pulse @endif"></span>
                    <span class="text-[9px] uppercase tracking-widest text-outline font-bold">{{ $opStatus }}</span>
                </div>
                <h1 class="text-xl font-black tracking-tight text-on-surface">{{ $machine->name }}</h1>
                <p class="text-on-surface-variant text-[10px] mt-0.5">Code: {{ $machine->code }} | Modbus TCP/RS485</p>
            </div>
            <div class="flex gap-2">
                <button id="btn-export-excel" data-machine-id="{{ $machine->id }}"
                    class="px-3 py-1.5 bg-surface-container-high text-on-surface text-[10px] font-bold rounded hover:bg-surface-container-highest transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">download</span>
                    Export
                </button>
                <button
                    class="px-3 py-1.5 bg-primary text-white text-[10px] font-bold rounded hover:brightness-110 transition-all flex items-center gap-1.5">
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
                    <span
                        class="text-lg font-black tracking-tighter text-on-surface">{{ number_format($todayCost, 0) }}</span>
                </div>
            </div>
            <!-- Consumption -->
            <div class="bg-surface-container-lowest p-3 rounded border-b-2 border-primary-container shadow-sm">
                <span class="text-[8px] font-black uppercase tracking-wider text-outline block mb-1">Today Usage</span>
                <div class="flex items-baseline gap-1">
                    <span
                        class="text-lg font-black tracking-tighter text-on-surface">{{ number_format($todayConsumption, 1) }}</span>
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
                        {{ number_format($machine->latestReading->voltage, 1) }} <span
                            class="text-[10px] font-bold text-outline">V</span>
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
                        {{ number_format($machine->latestReading->current, 1) }} <span
                            class="text-[10px] font-bold text-outline">A</span>
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
                    <select id="chart-metric-toggle"
                        class="bg-surface-container-low text-[9px] p-1 rounded font-bold text-on-surface-variant outline-none border-none">
                        <option value="power">Active Power</option>
                        <option value="voltage">Voltage</option>
                        <option value="current">Current</option>
                        <option value="pf">PF</option>
                    </select>
                </div>
            </div>

            <div id="chart-container"
                class="chart-wrapper relative h-[420px] w-full bg-white dark:bg-slate-900 rounded-lg shadow-inner overflow-hidden">
                <!-- Forensic Mode Active Badge -->
                <div id="forensic-mode-badge" class="absolute top-2 left-1/2 -translate-x-1/2 z-20 hidden">
                    <span class="px-3 py-1 bg-primary/10 text-primary font-black text-[10px] uppercase tracking-widest rounded-full border border-primary/20 shadow-sm">
                        Forensic Mode Active
                    </span>
                </div>
                <canvas id="powerChart" style="pointer-events: auto;"></canvas>
            </div>

            <!-- Simple Forensic Scrubber (0-14H Offset) -->
            <div id="forensic-scrubber-container" class="mt-2 px-2 hidden">
                <div class="flex justify-between text-[9px] font-black text-outline uppercase tracking-widest mb-1">
                    <span>18h Ago</span>
                    <span>Now</span>
                </div>
                <input 
                    type="range" 
                    id="forensic-scrubber" 
                    min="0" 
                    max="14" 
                    step="1" 
                    value="14" 
                    class="w-full h-1.5 bg-surface-container rounded-lg appearance-none cursor-pointer accent-primary"
                >
            </div>

            <div class="mt-2 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div
                        class="flex items-center p-1 bg-surface-container-low rounded-lg border border-surface-container">
                        <button onclick="updateRange(1)" id="btn-1h"
                            class="px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all hover:bg-white hover:shadow-sm text-outline">1H</button>
                        <button onclick="updateRange(4)" id="btn-4h"
                            class="px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all hover:bg-white hover:shadow-sm text-outline">4H</button>
                        <button onclick="updateRange(12)" id="btn-12h"
                            class="px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all hover:bg-white hover:shadow-sm text-outline">12H</button>
                        <button onclick="updateRange(24)" id="btn-24h"
                            class="px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all hover:bg-white hover:shadow-sm text-outline">24H</button>
                        <button onclick="updateRange(168)" id="btn-7d"
                            class="px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all hover:bg-white hover:shadow-sm text-outline">7D</button>
                    </div>

                    <!-- Forensic Date/Time Filter (Restored Professional UI) -->
                    <div
                        class="flex items-center gap-1 bg-surface-container-low p-1 rounded-lg border border-surface-container ml-2">
                        <div class="flex flex-col px-1">
                            <label class="text-[7px] font-black uppercase text-outline">Start Datetime</label>
                            <input type="datetime-local" id="forensic-start"
                                class="bg-transparent text-[9px] font-bold text-on-surface outline-none">
                        </div>
                        <div class="flex flex-col px-1 border-l border-surface-container">
                            <label class="text-[7px] font-black uppercase text-outline">End Datetime</label>
                            <input type="datetime-local" id="forensic-end"
                                class="bg-transparent text-[9px] font-bold text-on-surface outline-none">
                        </div>
                        <button onclick="applyForensicRange()"
                            class="ml-1 px-3 py-2 bg-primary text-white rounded hover:brightness-110 transition-all flex items-center justify-center">
                            <span class="text-[8px] font-black uppercase">Generate</span>
                        </button>
                    </div>
                </div>
                <div id="forensic-helper" class="text-[10px] font-medium text-outline hidden">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3 h-3 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Forensic tagging enabled. Use arrows to navigate historical window.
                    </span>
                </div>
            </div>

            <!-- PHASE RECONSTRUCTION TABLE -->
            <div id="phase-reconstruction-section"
                class="mt-6 pt-6 border-t border-surface-container-low hidden animate-in slide-in-from-top duration-500">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">timeline</span>
                        Operational Phases
                    </h4>
                    <div class="flex gap-2">
                        <button onclick="exportPhases('excel')" class="px-3 py-1 bg-green-600 text-white rounded text-[8px] font-black uppercase hover:bg-green-700 transition-all flex items-center gap-1">
                            <span class="material-symbols-outlined text-[10px]">table_chart</span>
                            Excel
                        </button>
                        <button onclick="exportPhases('pdf')" class="px-3 py-1 bg-red-600 text-white rounded text-[8px] font-black uppercase hover:bg-red-700 transition-all flex items-center gap-1">
                            <span class="material-symbols-outlined text-[10px]">picture_as_pdf</span>
                            PDF
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto rounded border border-surface-container-low">
                    <table class="w-full text-left text-[10px]" id="phase-table">
                        <thead>
                            <tr
                                class="bg-surface-container-low font-black text-on-surface-variant uppercase tracking-tighter">
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

        <!-- Patch 1: Proper Timeline Container ID -->
        <div class="bg-surface-container-lowest rounded border border-surface-container shadow-sm mb-4">
            <div
                class="px-4 py-2 border-b border-surface-container-low bg-surface-container-low/30 flex justify-between items-center">
                <h2 class="text-[10px] font-black text-on-surface uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">history_edu</span>
                    Operational Timeline
                </h2>
            </div>
            <div id="timeline-content" class="p-3 space-y-2 max-h-[400px] overflow-y-auto">
                <div class="text-center py-4 text-outline italic text-[10px]">Loading timeline...</div>
            </div>
        </div>

        <!-- CREATE/EDIT OPERATIONAL TAG MODAL -->
        <div id="tag-modal"
            class="fixed inset-0 z-[70] hidden flex items-center justify-center p-4 bg-black/60 backdrop-blur-md transition-all animate-in fade-in duration-300">
            <div class="bg-white w-full max-w-sm rounded shadow-2xl border border-surface-container overflow-hidden">
                <div
                    class="px-4 py-3 bg-surface-container-low/50 border-b border-surface-container flex justify-between items-center">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-outline" id="tag-modal-title">
                        Create Operational Tag</h3>
                    <button onclick="closeTagModal()" class="text-outline hover:text-on-surface transition-colors">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
                <div class="p-4 space-y-3">
                    <input type="hidden" id="tag-id" value="">

                    <div>
                        <label class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Selected
                            Timestamp</label>
                        <input type="text" id="tag-timestamp"
                            class="w-full bg-surface-container-low text-xs px-2 py-1.5 rounded font-mono font-bold text-on-surface outline-none border border-surface-container"
                            readonly>
                    </div>

                    <div>
                        <label class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Event
                            Type</label>
                        <select id="tag-event-type"
                            class="w-full bg-white text-xs px-2 py-1.5 rounded font-bold text-on-surface outline-none border border-surface-container focus:border-primary">
                            <option value="start">Start (Furnace/Process starts)</option>
                            <option value="melting">Melting (Active heating)</option>
                            <option value="idle">Idle (Waiting/Hold condition)</option>
                            <option value="test">Test (Spectro/Quality check)</option>
                            <option value="pour">Pour (Pouring/Tapping process)</option>
                            <option value="end">End (Production finished)</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Shift</label>
                        <select id="tag-shift"
                            class="w-full bg-white text-xs px-2 py-1.5 rounded font-bold text-on-surface outline-none border border-surface-container focus:border-primary">
                            <option value="1">Shift 1</option>
                            <option value="2">Shift 2</option>
                            <option value="3">Shift 3</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Notes
                            (Optional)</label>
                        <textarea id="tag-notes"
                            class="w-full bg-white text-xs px-2 py-1.5 rounded font-medium text-on-surface outline-none border border-surface-container focus:border-primary h-16 resize-none"></textarea>
                    </div>
                </div>
                <div
                    class="px-4 py-3 bg-surface-container-low/50 border-t border-surface-container flex gap-2 justify-end">
                    @if(auth()->user()->role === 'admin')
                        <button id="btn-delete-tag"
                            class="hidden px-4 py-2 bg-error-container text-error font-black rounded text-[9px] uppercase tracking-widest hover:brightness-110 transition-all shadow-sm">Forensic
                            Delete</button>
                    @endif
                    <button onclick="saveTag()"
                        class="px-4 py-2 bg-primary text-white font-black rounded text-[9px] uppercase tracking-widest hover:brightness-110 transition-all shadow-sm">Save
                        Tag</button>
                </div>
            </div>
        </div>

        <!-- DELETE CONFIRMATION MODAL -->
        <div id="delete-tag-modal"
            class="fixed inset-0 z-[80] hidden flex items-center justify-center p-4 bg-black/60 backdrop-blur-md transition-all animate-in fade-in duration-300">
            <div class="bg-white w-full max-w-sm rounded shadow-2xl border border-surface-container overflow-hidden">
                <div class="px-4 py-3 bg-error/10 border-b border-error/20 flex justify-between items-center">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-error">Delete Forensic Tag</h3>
                    <button onclick="closeDeleteModal()" class="text-outline hover:text-on-surface transition-colors">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
                <div class="p-4 space-y-3">
                    <input type="hidden" id="delete-tag-id">
                    <div class="bg-surface-container-low p-2 rounded border border-surface-container">
                        <p class="text-[9px] text-outline font-bold uppercase tracking-widest mb-1">Target Tag</p>
                        <p class="text-[11px] font-black text-on-surface" id="delete-tag-info"></p>
                    </div>
                    <div>
                        <label class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Reason
                            for deletion (Min 10 chars)</label>
                        <textarea id="delete-tag-reason"
                            class="w-full bg-white text-xs px-2 py-1.5 rounded font-medium text-on-surface outline-none border border-surface-container focus:border-error h-24 resize-none"
                            placeholder="Provide forensic explanation for this deletion..."></textarea>
                    </div>
                </div>
                <div
                    class="px-4 py-3 bg-surface-container-low/50 border-t border-surface-container flex gap-2 justify-end">
                    <button onclick="closeDeleteModal()"
                        class="px-4 py-2 text-outline font-black rounded text-[9px] uppercase tracking-widest hover:bg-surface-container transition-all">Cancel</button>
                    <button onclick="confirmDeleteTag()"
                        class="px-4 py-2 bg-error text-white font-black rounded text-[9px] uppercase tracking-widest hover:brightness-110 transition-all shadow-sm">Confirm
                        Delete</button>
                </div>
            </div>
        </div>

        <!-- Event Log Section - Full Width Compact -->
        <div class="bg-surface-container-lowest rounded border border-surface-container shadow-sm mb-4">
            <div
                class="px-4 py-2 border-b border-surface-container-low bg-surface-container-low/30 flex justify-between items-center">
                <h2 class="text-[10px] font-black text-on-surface uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">warning</span>
                    Anomaly & Event Log
                </h2>
                <span class="text-[9px] font-bold text-outline">Showing last 10 entries</span>
            </div>
            <div class="p-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-2">
                @forelse($eventLogs as $log)
                    <div
                        class="border-l-2 @if($log->status == 'WARNING') border-tertiary @else border-error @endif pl-2 py-1 bg-surface-container-low/30 rounded-r">
                        <p class="text-[8px] font-mono font-bold text-outline">{{ $log->event_at->format('H:i:s') }}</p>
                        <p class="text-[9px] font-bold text-on-surface leading-tight truncate" title="{{ $log->message }}">
                            {{ $log->message }}</p>
                    </div>
                @empty
                    <div class="col-span-full py-2 text-center text-[10px] text-outline italic">No anomalous events
                        recorded.</div>
                @endforelse
            </div>
        </div>

        <!-- Historian Health Panel (Patch 3: Industrial Watchdog) -->
        <div class="bg-surface-container-lowest rounded border border-surface-container shadow-sm mb-4">
            <div
                class="px-3 py-1.5 border-b border-surface-container-low bg-surface-container-low/30 flex justify-between items-center">
                <h2 class="text-[9px] font-black text-on-surface uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-[10px]">monitor_heart</span>
                    Historian Health Diagnostics
                </h2>
                <div class="flex items-center gap-3">
                    <span id="health-stall-indicator"
                        class="hidden px-2 py-0.5 bg-amber-100 text-amber-700 font-black text-[8px] rounded uppercase animate-pulse">Historian
                        Stale</span>
                    <span id="health-last-refresh" class="text-[8px] font-mono text-outline">Waiting...</span>
                </div>
            </div>
            <div class="p-2 flex flex-wrap gap-4 text-[9px] uppercase tracking-widest text-outline">
                <div>Telemetry: <span id="health-telemetry-freshness" class="font-black text-on-surface">-</span></div>
                <div>Points: <span id="health-telemetry" class="font-black text-on-surface">0</span></div>
                <div>Tags: <span id="health-tags" class="font-black text-on-surface">0</span></div>
                <div>Phases: <span id="health-phases" class="font-black text-on-surface">0</span></div>
                <div>Forensic: <span id="health-forensic" class="font-black text-on-surface">OFF</span></div>
                <div>Status: <span id="health-status" class="font-black text-primary">STABLE</span></div>
            </div>
        </div>

        <!-- Raw Telemetry - Full Width Priority -->
        <div class="bg-surface-container-lowest rounded border border-surface-container shadow-sm mb-4">
            <div
                class="px-4 py-3 border-b border-surface-container-low flex justify-between items-center bg-surface-container-low/50">
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

            <!-- Patch 6: Telemetry Table Hardening -->
            <div class="overflow-x-auto max-h-[600px] overflow-y-auto relative">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 z-10 bg-surface-container-low">
                        <tr
                            class="text-[9px] font-black text-on-surface-variant uppercase tracking-widest border-b border-surface-container">
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
                            <td colspan="8" class="px-4 py-10 text-center text-outline italic">Loading telemetry
                                stream...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="px-4 py-2 bg-surface-container-low flex justify-between items-center border-t border-surface-container-low">
                <div class="flex items-center gap-2">
                    <button id="prev-page"
                        class="p-1 hover:bg-primary/10 rounded-full transition-colors disabled:opacity-20">
                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                    </button>
                    <span class="text-[9px] font-black text-outline uppercase tracking-widest">
                        Page <span id="current-page-display">1</span> / <span id="last-page-display">1</span>
                    </span>
                    <button id="next-page"
                        class="p-1 hover:bg-primary/10 rounded-full transition-colors disabled:opacity-20">
                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                    </button>
                </div>
                <div class="text-[9px] text-outline font-black uppercase tracking-widest">
                    Total: <span id="total-readings-display">0</span> Records
                </div>
            </div>
        </div>
    </div>
</main>

<script src="{{ asset('assets/js/chart.js') }}"></script>
<script src="{{ asset('assets/js/chartjs-plugin-annotation.min.js') }}"></script>
<script src="{{ asset('assets/vendor/date-fns/date-fns.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chartjs-adapter-date-fns/chartjs-adapter-date-fns.bundle.min.js') }}"></script>
<script>
    // Patch: Global Crash Detector
    window.onerror = function (msg, src, line, col, err) {
        console.error('GLOBAL JS CRASH:', msg, 'LINE:', line, 'COL:', col);
        const statusEl = document.getElementById('health-status');
        if (statusEl) {
            statusEl.textContent = 'JS CRASH';
            statusEl.className = 'font-black text-error';
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        const canvasEl = document.getElementById('powerChart');
        if (!canvasEl) { console.error('Canvas not found'); return; }
        const ctx = canvasEl.getContext('2d');
        if (!ctx) { console.error('Canvas context failed'); return; }

        console.log('Historian Init - CTX:', ctx);
        console.log('Historian Init - Chart exists:', typeof Chart);
        // console.log('Historian Init - dateFns exists:', typeof dateFns);
        const phaseSection = document.getElementById('phase-reconstruction-section');
        const phaseTableBody = document.getElementById('phase-tbody');
        const tagModal = document.getElementById('tag-modal');

        let chartInstance = null;
        let baseAnnotations = {};
        let currentHours = 4; // Default: 4H Forensic Mode
        let currentMetric = 'power';
        let cachedData = [];
        // Hardened Initialization: Default to last 4H
        let visualRange = { 
            min: Date.now() - (4 * 60 * 60 * 1000), 
            max: Date.now() 
        };
        let currentTags = [];
        let currentPhases = [];
        let decimatedData = []; // Store for annotation index mapping
        let activeRequests = {}; // Temporarily unused in recovery
        let forensicOffsetHours = 0;
        let forensicBusy = false; // Patch 5: Spam Protection

        // Patch 11: Centralized Timestamp Normalization
        function getTelemetryTs(item) {
            if (!item) return null;
            return item.timestamp || item.recorded_at || item.event_time || item.created_at || null;
        }

        const deviceId = {{ $machine->devices->first() ? $machine->devices->first()->id : 'null' }};
        const isReadonly = !['adminqcflange@peroniks.com', 'adminqcfitting@peroniks.com'].includes('{{ auth()->user()->email }}');

        // Patch 6: Hardened safeFetch with non-JSON fallback
        function safeFetch(url, options) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const defaultHeaders = {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };

            const finalOptions = options || {};
            finalOptions.headers = Object.assign({}, defaultHeaders, finalOptions.headers || {});

            return fetch(url, finalOptions).then(function (response) {
                if (!response.ok) {
                    if (response.status === 419) {
                        alert('Industrial Session Expired. Refreshing dashboard...');
                        location.reload();
                        return;
                    }
                    return response.text().then(function (text) {
                        let msg = 'Backend Error (' + response.status + ')';
                        try {
                            if (text.trim().startsWith('{')) {
                                const json = JSON.parse(text);
                                msg = json.message || json.error || msg;
                            } else {
                                console.error('Non-JSON Error Response:', text.substring(0, 200));
                            }
                        } catch (e) {
                            console.error('Error parsing backend response:', e);
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            }).catch(function (err) {
                console.error('Fetch Failed:', err);
                // Graceful alert instead of crash
                if (err.message !== 'Industrial Session Expired. Refreshing dashboard...') {
                    // alert('Communication Failure: ' + err.message);
                }
                throw err;
            });
        }

        // Patch 3 & 4: Range & Forensic Navigation Logic
        window.updateRange = function (h) {
            currentHours = h;

            ['1h', '4h', '12h', '24h', '7d'].forEach(function (id) {
                const btn = document.getElementById('btn-' + id);
                if (btn) btn.classList.remove('bg-white', 'shadow-sm', 'text-primary');
            });
            const activeBtn = document.getElementById('btn-' + (h === 168 ? '7d' : h + 'h'));
            if (activeBtn) activeBtn.classList.add('bg-white', 'shadow-sm', 'text-primary');

            const scrubberContainer = document.getElementById('forensic-scrubber-container');
            const badge = document.getElementById('forensic-mode-badge');
            const helper = document.getElementById('forensic-helper');
            
            if (h === 1 || h === 4) {
                if (scrubberContainer) scrubberContainer.classList.remove('hidden');
                if (badge) badge.classList.remove('hidden');
                if (helper) helper.classList.remove('hidden');
                
                // Reset offset on mode entry (14 = Now, Offset = 0)
                forensicOffsetHours = 0;
                const scrubber = document.getElementById('forensic-scrubber');
                if (scrubber) scrubber.value = 14;
            } else {
                if (scrubberContainer) scrubberContainer.classList.add('hidden');
                if (badge) badge.classList.add('hidden');
                if (helper) helper.classList.add('hidden');
            }

            initDashboard();
        };

        window.applyForensicRange = function () {
            const startVal = document.getElementById('forensic-start').value;
            const endVal = document.getElementById('forensic-end').value;
            if (!startVal || !endVal) return alert('Please select both Start and End datetimes.');

            const start = new Date(startVal);
            const end = new Date(endVal);

            if (end <= start) return alert('End time must be after Start time.');

            // Limit to 24H for performance safety
            if ((end - start) > (24 * 60 * 60 * 1000)) {
                if (!confirm('Range exceeds 24H. This may affect terminal performance. Continue?')) return;
            }

            safeFetch(`{{ url('api/charts/device') }}?device_id=${deviceId}&start_date=${start.toISOString()}&end_date=${end.toISOString()}`)
                .then(function (response) {
                    cachedData = response.data || [];

                    // Strict Array Guard
                    if (!Array.isArray(cachedData)) {
                        console.error('Telemetry payload is not array', cachedData);
                        return;
                    }

                    renderChart(cachedData);
                    
                    // Historical Mode Bypass: Use explicit range for tags/phases
                    const startISO = start.toISOString();
                    const endISO = end.toISOString();
                    loadTags(startISO, endISO);
                    loadPhases(startISO, endISO);
                })
                .catch(function (err) {
                    console.error('Forensic Range Failed:', err);
                    updateHealthPanel('status', 'FETCH FAIL', 'text-error');
                });
        };

        function updateRangeUI(h) {
            ['1h', '4h', '12h', '24h', '7d'].forEach(function (id) {
                const btn = document.getElementById('btn-' + id);
                if (btn) btn.classList.remove('bg-white', 'shadow-sm', 'text-primary');
            });
            const key = h === 168 ? '7d' : h + 'h';
            const activeBtn = document.getElementById('btn-' + key);
            if (activeBtn) activeBtn.classList.add('bg-white', 'shadow-sm', 'text-primary');
        }

        // Simple Scrubber Logic
        const forensicScrubber = document.getElementById('forensic-scrubber');
        if (forensicScrubber) {
            forensicScrubber.addEventListener('input', function() {
                if (![1, 4].includes(currentHours) || !cachedData.length) return;
                
                // Invert: Value 14 (Right) = Now (Offset 0), Value 0 (Left) = 18H Ago (Offset 14)
                forensicOffsetHours = 14 - parseInt(forensicScrubber.value);
                
                const lastDataPoint = cachedData[cachedData.length - 1];
                const lastTs = getTelemetryTs(lastDataPoint);
                if (!lastTs) return;

                const cacheEnd = new Date(lastTs).getTime();
                
                visualRange.max = cacheEnd - (forensicOffsetHours * 60 * 60 * 1000);
                visualRange.min = visualRange.max - (currentHours * 60 * 60 * 1000);
                
                renderChart(cachedData);
            });
        }

        function loadChartData() {
            try {
                const fetchHours = [1, 4].includes(currentHours) ? 18 : currentHours;
                const end = new Date();
                const start = new Date(end.getTime() - (fetchHours * 60 * 60 * 1000));

                return safeFetch(`{{ url('api/charts/device') }}?device_id=${deviceId}&start_date=${start.toISOString()}&end_date=${end.toISOString()}`)
                    .then(function (response) {
                        cachedData = response.data || [];
                        console.log('Telemetry sample', cachedData[0]); // Temporary diagnostic
                        updateHealthPanel('telemetry', cachedData.length);
                        updateHealthPanel('forensic', [1, 4].includes(currentHours) ? 'ACTIVE' : 'OFF');

                        if (cachedData.length > 0) {
                            const lastPoint = cachedData[cachedData.length - 1];
                            const lastTs = getTelemetryTs(lastPoint);
                            
                            if (lastTs) {
                                const cacheEnd = new Date(lastTs).getTime();
                                // Forensic Logic: Offset from end of available data
                                visualRange.max = cacheEnd - (forensicOffsetHours * 60 * 60 * 1000);
                                visualRange.min = visualRange.max - (currentHours * 60 * 60 * 1000);
                            }
                        } else {
                            // Fallback if no data
                            visualRange.max = Date.now();
                            visualRange.min = visualRange.max - (currentHours * 60 * 60 * 1000);
                        }

                        // Strict Array Guard
                        if (!Array.isArray(cachedData)) {
                            console.error('Telemetry payload is not array', cachedData);
                            return;
                        }

                        renderChart(cachedData);

                        // Lifecycle: Load Tags and Phases using exact same range as telemetry
                        const startISO = start.toISOString();
                        const endISO = end.toISOString();
                        loadTags(startISO, endISO);
                        loadPhases(startISO, endISO);
                    })
                    .catch(function (e) {
                        console.error('Chart Data Error:', e);
                        updateHealthPanel('status', 'CHART ERR', 'text-error');
                    });
            } catch (err) {
                console.error('loadChartData Crash:', err);
            }
        }

        // Patch 2 & 9: Stable Render (No free-drag pan)
        // Patch 9: Chart decimation (Max 3000 points)
        // Patch 10: Industrial Phase Overlay Plugin (Native Canvas Rendering)
        const industrialPhaseOverlayPlugin = {
            id: 'industrialPhaseOverlay',
            beforeDatasetsDraw(chart) {
                if (!currentPhases.length || !decimatedData.length) return;

                // Patch: Stabilize Chronological Ordering & Filter Invalid Ranges
                const sortedPhases = [...currentPhases]
                    .filter(p => p.start_time_iso && p.end_time_iso && new Date(p.end_time_iso) > new Date(p.start_time_iso))
                    .sort((a, b) => new Date(a.start_time_iso) - new Date(b.start_time_iso));

                const { ctx, chartArea, scales: { x: xScale } } = chart;

                sortedPhases.forEach(function (p) {
                    const startTs = new Date(p.start_time_iso).getTime();
                    const endTs = new Date(p.end_time_iso).getTime();

                    // Performance: Only render if overlaps visible viewport
                    if (endTs < visualRange.min || startTs > visualRange.max) return;

                    // Map timestamps to Indices for Category Scale Precision
                    let idxMin = -1;
                    let idxMax = -1;
                    for (let d = 0; d < decimatedData.length; d++) {
                        const pointTs = getTelemetryTs(decimatedData[d]);
                        if (!pointTs) continue;
                        const ts = new Date(pointTs).getTime();
                        if (idxMin === -1 && ts >= startTs) idxMin = d;
                        if (ts <= endTs) idxMax = d;
                    }

                    // Patch: Index Range Guards
                    if (idxMin < 0 || idxMax < 0 || idxMax < idxMin) return;
                        const xStart = xScale.getPixelForValue(idxMin);
                        const xEnd = xScale.getPixelForValue(idxMax);
                        const width = xEnd - xStart;

                        // Only draw if within chartArea
                        const xDrawStart = Math.max(xStart, chartArea.left);
                        const xDrawEnd = Math.min(xEnd, chartArea.right);
                        const drawWidth = xDrawEnd - xDrawStart;

                        if (drawWidth <= 0) return;

                        ctx.save();
                        ctx.fillStyle = getPhaseOverlayColor(p.phase.toLowerCase());
                        ctx.fillRect(xDrawStart, chartArea.top, drawWidth, chartArea.bottom - chartArea.top);

                        // Draw Adaptive Labels (Visibility Tuning Patch)
                        if (width > 18) {
                            const shortNames = {
                                melting: 'MELT',
                                pour: 'POUR',
                                idle: 'IDLE',
                                start: 'HEAT',
                                test: 'TEST',
                                end: 'END'
                            };

                            const centerX = xStart + (width / 2);
                            const safeCenterX = Math.max(chartArea.left + 10, Math.min(centerX, chartArea.right - 10));

                            ctx.save();
                            ctx.textAlign = 'center';

                            if (width > 120) {
                                ctx.font = 'bold 10px Inter, sans-serif';
                                // Backplates
                                ctx.fillStyle = 'rgba(255,255,255,0.55)';
                                ctx.fillRect(safeCenterX - 22, chartArea.top + 24, 44, 12);
                                ctx.fillRect(safeCenterX - 18, chartArea.top + 38, 36, 12);

                                ctx.fillStyle = 'rgba(0,0,0,0.72)';
                                ctx.fillText(p.phase_name.toUpperCase(), safeCenterX, chartArea.top + 34);
                                ctx.fillText(p.duration_human, safeCenterX, chartArea.top + 48);
                            } else if (width > 60) {
                                ctx.font = 'bold 9px Inter, sans-serif';
                                const shortLabel = shortNames[p.phase.toLowerCase()] || p.phase_name.toUpperCase();
                                // Backplates
                                ctx.fillStyle = 'rgba(255,255,255,0.55)';
                                ctx.fillRect(safeCenterX - 18, chartArea.top + 26, 36, 12);
                                ctx.fillRect(safeCenterX - 18, chartArea.top + 38, 36, 12);

                                ctx.fillStyle = 'rgba(0,0,0,0.72)';
                                ctx.fillText(shortLabel, safeCenterX, chartArea.top + 36);
                                ctx.fillText(p.duration_human, safeCenterX, chartArea.top + 48);
                            } else if (width > 40) {
                                ctx.font = 'bold 8px Inter, sans-serif';
                                // Backplate
                                ctx.fillStyle = 'rgba(255,255,255,0.55)';
                                ctx.fillRect(safeCenterX - 16, chartArea.top + 30, 32, 14);

                                ctx.fillStyle = 'rgba(0,0,0,0.72)';
                                ctx.fillText(p.duration_human, safeCenterX, chartArea.top + 40);
                            } else {
                                // TINY DURATION STACK MODE (18px to 40px)
                                ctx.font = 'bold 7px Inter, sans-serif';
                                const duration = p.duration_human.replace('m', '');
                                const yBase = chartArea.top + 38;

                                ctx.fillStyle = 'rgba(255,255,255,0.65)';
                                ctx.fillRect(safeCenterX - 8, yBase - 10, 16, 18);

                                ctx.fillStyle = 'rgba(0,0,0,0.70)';
                                ctx.fillText(duration, safeCenterX, yBase);
                                ctx.fillText('m', safeCenterX, yBase + 8);
                            }
                            ctx.restore();
                        }
                        ctx.restore();
                });
            }
        };

        function renderChart(data) {
            try {
                if (chartInstance) {
                    chartInstance.options.plugins.annotation.annotations = {};
                    chartInstance.destroy();
                    chartInstance = null;
                }

                // Patch 1: Filter data to visualRange for Category Scale Precision
                const filteredData = data.filter(function (item) {
                    const itemTs = getTelemetryTs(item);
                    if (!itemTs) return false;
                    const ts = new Date(itemTs).getTime();
                    // Fallback to true if visualRange is not properly set
                    const inMin = visualRange.min ? ts >= visualRange.min : true;
                    const inMax = visualRange.max ? ts <= visualRange.max : true;
                    return inMin && inMax;
                });

                // Decimation logic: step = ceil(total / 3000)
                const maxPoints = 3000;
                const step = Math.max(1, Math.ceil(filteredData.length / maxPoints));
                decimatedData = (step === 1) ? filteredData : filteredData.filter((_, i) => i % step === 0);

                // Category Scale: Labels MUST match data points
                const labels = decimatedData.map(function (item) {
                    return formatWIB(getTelemetryTs(item)).split(' ')[1]; // Time only
                });
                const powerData = decimatedData.map(item => item.power_kw);
                const voltageData = decimatedData.map(item => item.voltage);

                // Patch 9: Handler cleanup before destruction
                if (canvasEl) canvasEl.onclick = null;

                // Patch 5: Harden Chart Constructor
                try {
                    chartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Active Power (kW)',
                                    data: powerData,
                                    borderColor: '#00628c',
                                    backgroundColor: 'rgba(0, 98, 140, 0.05)',
                                    fill: true,
                                    pointRadius: 0,
                                    borderWidth: 2,
                                    tension: 0.1,
                                    spanGaps: true,
                                    yAxisID: 'y'
                                },
                                {
                                    label: 'Voltage (V)',
                                    data: voltageData,
                                    borderColor: 'rgba(249,115,22,0.75)', // Reduced dominance
                                    backgroundColor: 'transparent',
                                    fill: false,
                                    pointRadius: 0,
                                    borderWidth: 1,
                                    tension: 0.1,
                                    spanGaps: true,
                                    yAxisID: 'y1'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: true },
                                annotation: { annotations: {} },
                                decimation: { enabled: false },
                                industrialPhaseOverlay: true // Enable custom plugin
                            },
                            scales: {
                                x: {
                                    type: 'category', // Patch 3: Temporary Diagnostic (Windows 7 Compatibility)
                                    grid: { display: false },
                                    ticks: {
                                        maxRotation: 0,
                                        autoSkip: true,
                                        maxTicksLimit: 10
                                    }
                                },
                                y: {
                                    title: { display: true, text: 'Power (kW)', font: { size: 10, weight: 'bold' } },
                                    min: 0,
                                    grid: { color: 'rgba(0,0,0,0.05)' }
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    title: { display: true, text: 'Voltage (V)', font: { size: 10, weight: 'bold' } },
                                    grid: { drawOnChartArea: false },
                                    min: 0
                                }
                            }
                        },
                        plugins: [industrialPhaseOverlayPlugin] // Register custom plugin
                    });
                } catch (chartErr) {
                    console.error('CHART INIT FAILED', chartErr);
                    updateHealthPanel('status', 'CHART FAIL', 'text-error');
                    return;
                }

                // Patch 5 & 6: Safe Click Tagging
                if (canvasEl) {
                    canvasEl.onclick = function (e) {
                        if (isReadonly || currentHours !== 4) return;
                        const points = chartInstance.getElementsAtEventForMode(e, 'index', { intersect: false }, true);
                        if (points.length) {
                            const firstPoint = points[0];
                            const label = getTelemetryTs(decimatedData[firstPoint.index]);
                            if (label) openTagModal(label);
                        }
                    };
                }

                // Redraw annotations (Tags + Phases)
                redrawAnnotations();

            } catch (err) {
                console.error('renderChart failed', err);
            }
        }

        // --- TAGGING & PHASES ---

        window.openTagModal = function (timestamp, tagData = null) {
            if (isReadonly || (currentHours !== 4 && !tagData)) return;

            const tsInput = document.getElementById('tag-timestamp');
            tsInput.value = formatWIB(timestamp);
            tsInput.setAttribute('data-iso', timestamp); // Patch 7: Store ISO

            const idField = document.getElementById('tag-id');
            const typeField = document.getElementById('tag-event-type');
            const shiftField = document.getElementById('tag-shift');
            const notesField = document.getElementById('tag-notes');
            const deleteBtn = document.getElementById('btn-delete-tag');

            if (tagData) {
                idField.value = tagData.id;
                typeField.value = tagData.event_type;
                shiftField.value = tagData.shift || '1';
                notesField.value = tagData.notes || '';
                if (deleteBtn && !isReadonly) {
                    deleteBtn.classList.remove('hidden');
                    deleteBtn.onclick = function () { deleteTag(tagData.id); };
                }
            } else {
                idField.value = '';
                typeField.value = 'start';
                shiftField.value = '1';
                notesField.value = '';
                if (deleteBtn) deleteBtn.classList.add('hidden');
            }

            tagModal.classList.remove('hidden');
        };

        window.closeTagModal = function () { tagModal.classList.add('hidden'); };

        window.saveTag = function () {
            const id = document.getElementById('tag-id').value;
            const url = id ? `/api/tags/${id}` : `/api/machines/${deviceId}/tags`;
            const method = id ? 'PUT' : 'POST';

            // Patch 7: Store Raw ISO Timestamp internally
            const rawTimestamp = document.getElementById('tag-timestamp').getAttribute('data-iso') ||
                document.getElementById('tag-timestamp').value;

            const payload = {
                event_time: rawTimestamp,
                event_type: document.getElementById('tag-event-type').value,
                shift: document.getElementById('tag-shift').value,
                notes: document.getElementById('tag-notes').value
            };

            safeFetch(url, { method: method, body: JSON.stringify(payload) })
                .then(function () {
                    closeTagModal();
                    initDashboard();
                })
                .catch(function (err) { alert(err.message || 'Validation failed'); });
        };

        window.openDeleteModal = function (id, timestamp, type) {
            if (isReadonly) return;
            document.getElementById('delete-tag-id').value = id;
            document.getElementById('delete-tag-info').textContent = formatWIB(timestamp).split(' ')[1] + ' - ' + type.toUpperCase();
            document.getElementById('delete-tag-reason').value = '';
            document.getElementById('delete-tag-modal').classList.remove('hidden');
        };

        window.closeDeleteModal = function () {
            document.getElementById('delete-tag-modal').classList.add('hidden');
        };

        window.confirmDeleteTag = function () {
            const id = document.getElementById('delete-tag-id').value;
            const reason = document.getElementById('delete-tag-reason').value;

            if (!reason || reason.length < 10) {
                return alert('Forensic reason must be at least 10 characters.');
            }

            safeFetch(`/api/tags/${id}`, {
                method: 'DELETE',
                body: JSON.stringify({ reason: reason })
            })
                .then(function () {
                    closeDeleteModal();
                    closeTagModal();
                    initDashboard();
                })
                .catch(function (err) { alert(err.message || 'Deletion failed'); });
        };

        function updateHealthPanel(key, value, textColor = 'text-on-surface') {
            const el = document.getElementById('health-' + key);
            if (el) {
                el.textContent = value;
                el.className = 'font-black ' + textColor;
            }
            document.getElementById('health-last-refresh').textContent = new Date().toLocaleTimeString();
        }

        function loadTags(start = null, end = null) {
            try {
                let url = `{{ url('api/machines') }}/${deviceId}/tags`;
                if (start && end) url += `?start=${start}&end=${end}`;

                safeFetch(url)
                    .then(function (tags) {
                        currentTags = tags;
                        updateHealthPanel('tags', tags.length);
                        renderTimeline(tags);
                        redrawAnnotations();
                    })
                    .catch(function (e) { updateHealthPanel('status', 'TAG ERR', 'text-error'); });
            } catch (err) { console.error('loadTags Crash:', err); }
        }

        function loadPhases(start = null, end = null) {
            try {
                let url = `{{ url('api/machines') }}/${deviceId}/phases`;
                if (start && end) url += `?start=${start}&end=${end}`;

                safeFetch(url)
                    .then(function (phases) {
                        currentPhases = phases;
                        updateHealthPanel('phases', phases.length);
                        renderPhases(phases);
                        redrawAnnotations();
                    })
                    .catch(function (e) { updateHealthPanel('status', 'PHASE ERR', 'text-error'); });
            } catch (err) { console.error('loadPhases Crash:', err); }
        }

        // Patch 4: Consolidated Industrial Process Annotations (Tags Only)
        function redrawAnnotations() {
            if (!chartInstance || !visualRange.min || !visualRange.max || !decimatedData.length) return;
            const annotations = {};

            // 1. Tag Event Lines (Preserved from SCADA design)
            currentTags.forEach(function (t) {
                if (t.deleted_at) return;
                const time = new Date(t.event_time).getTime();
                if (time < visualRange.min || time > visualRange.max) return;

                // Map timestamp to nearest Index
                let idxTag = -1;
                for (let d = 0; d < decimatedData.length; d++) {
                    const pointTs = getTelemetryTs(decimatedData[d]);
                    if (!pointTs) continue;
                    const ts = new Date(pointTs).getTime();
                    if (ts >= time) { idxTag = d; break; }
                }

                if (idxTag !== -1) {
                    annotations['tag_' + t.id] = {
                        type: 'line',
                        xMin: idxTag,
                        xMax: idxTag,
                        borderColor: getEventColor(t.event_type),
                        borderWidth: 2,
                        borderDash: [4, 4],
                        label: {
                            content: t.event_type.toUpperCase(),
                            display: true,
                            position: 'start',
                            backgroundColor: getEventColor(t.event_type),
                            color: '#fff',
                            font: { size: 9, weight: 'bold' },
                            padding: 4
                        }
                    };
                }
            });

            chartInstance.options.plugins.annotation.annotations = annotations;
            chartInstance.update('none');
        }

        function getPhaseOverlayColor(type) {
            const c = {
                start: 'rgba(255, 193, 7, 0.12)',     // PRE-HEATING
                melting: 'rgba(255, 87, 34, 0.15)',   // MELTING
                pour: 'rgba(33, 150, 243, 0.12)',      // POURING
                idle: 'rgba(158, 158, 158, 0.10)',     // IDLE
                test: 'rgba(158, 158, 158, 0.10)',     // TEST (using IDLE color)
                end: 'rgba(244, 67, 54, 0.10)'         // END
            };
            return c[type] || 'rgba(158, 158, 158, 0.05)';
        }

        function renderTimeline(tags) {
            const container = document.getElementById('timeline-content');
            if (!container) return;
            container.innerHTML = '';

            if (tags.length === 0) {
                container.innerHTML = '<div class="text-center py-4 text-outline italic text-[10px]">No historical tags found.</div>';
                return;
            }

            tags.forEach(function (t) {
                const div = document.createElement('div');
                div.className = 'p-3 rounded-lg border border-surface-container bg-white shadow-sm mb-3 cursor-pointer hover:border-primary transition-all group';
                div.onclick = function () { openTagModal(t.event_time, t); };

                const color = getEventColor(t.event_type);

                const deleteBtn = isReadonly ? '' : `
                    <button onclick="event.stopPropagation(); openDeleteModal(${t.id}, '${t.event_time}', '${t.event_type}')" 
                            class="px-2 py-1 border border-error/40 text-error hover:bg-error hover:text-white rounded text-[8px] font-black transition-all">
                        DELETE
                    </button>
                `;

                div.innerHTML = `
                    <div class="flex justify-between items-center mb-1.5">
                        <div class="flex items-center gap-2">
                            <span class="font-black text-[11px] text-on-surface tracking-tighter">${formatWIB(t.event_time).split(' ')[1]}</span>
                            <span class="px-2 py-0.5 rounded text-white text-[9px] uppercase font-black shadow-sm group-hover:scale-105 transition-transform" style="background: ${color}">${t.event_type}</span>
                        </div>
                        ${deleteBtn}
                    </div>
                    <div class="text-[9px] text-outline line-clamp-1 font-medium italic group-hover:text-on-surface">
                        ${t.notes || 'No forensic notes recorded...'}
                    </div>
                `;
                container.appendChild(div);
            });
        }

        // Patch 2: Structural Operational Phase Table
        function renderPhases(phases) {
            if (!phaseTableBody) return;
            phaseTableBody.innerHTML = '';

            if (phases.length === 0) {
                phaseSection.classList.add('hidden');
                return;
            }
            phaseSection.classList.remove('hidden');

            phases.forEach(function (p) {
                const tr = document.createElement('tr');
                tr.className = 'border-b border-surface-container hover:bg-surface-container-low transition-colors';

                const statusBadge = p.status === 'OPEN'
                    ? '<span class="px-2 py-0.5 bg-primary/10 text-primary rounded font-black text-[8px] animate-pulse uppercase">Active</span>'
                    : '<span class="px-2 py-0.5 bg-outline/10 text-outline rounded font-black text-[8px] uppercase">Closed</span>';

                const phaseColor = getEventColor(p.phase_name.toLowerCase());

                tr.innerHTML = `
                    <td class="px-4 py-2 font-mono text-[10px] text-outline">${formatWIB(p.start_time).split(' ')[1]}</td>
                    <td class="px-4 py-2 font-mono text-[10px] text-outline">${formatWIB(p.end_time).split(' ')[1]}</td>
                    <td class="px-4 py-2 text-center">${statusBadge}</td>
                    <td class="px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full" style="background: ${phaseColor}"></span>
                            <span class="font-black uppercase text-[10px]" style="color: ${phaseColor}">${p.phase_name}</span>
                        </div>
                    </td>
                    <td class="px-4 py-2 text-right font-bold text-on-surface">${p.duration_human}</td>
                    <td class="px-4 py-2 text-right font-medium text-outline">${p.avg_kw}</td>
                    <td class="px-4 py-2 text-right font-medium text-outline">${p.peak_kw}</td>
                    <td class="px-4 py-2 text-right font-black text-on-surface">${p.usage_kwh}</td>
                    <td class="px-4 py-2 text-right font-black text-tertiary">Rp ${p.est_cost.toLocaleString()}</td>
                `;
                phaseTableBody.appendChild(tr);
            });

            // Calculate & Append Total Row
            const totalUsage = phases.reduce((sum, p) => sum + (parseFloat(p.usage_kwh) || 0), 0);
            const totalCost = phases.reduce((sum, p) => sum + (parseFloat(p.est_cost) || 0), 0);

            const totalTr = document.createElement('tr');
            totalTr.className = 'bg-surface-container-low/50 font-black';
            totalTr.innerHTML = `
                <td colspan="7" class="px-4 py-2 text-right text-primary uppercase tracking-widest text-[9px]">Total Usage & Cost</td>
                <td class="px-4 py-2 text-right text-on-surface border-t-2 border-primary">${totalUsage.toFixed(2)}</td>
                <td class="px-4 py-2 text-right text-primary border-t-2 border-primary">Rp ${totalCost.toLocaleString()}</td>
            `;
            phaseTableBody.appendChild(totalTr);
        }

        function getEventColor(type) {
            const c = { start: '#10b981', melting: '#f97316', idle: '#94a3b8', test: '#eab308', pour: '#3b82f6', end: '#ef4444' };
            return c[type] || '#94a3b8';
        }

        // Patch 3: Browser-Safe Time Formatting (No double offset)
        function formatWIB(iso) {
            if (!iso) return '-';
            const date = new Date(iso);
            return new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jakarta',
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit',
                hour12: false
            }).format(date).replace(/\//g, '-');
        }

        function loadTelemetry(page = 1) {
            try {
                const url = `{{ url('api/machines') }}/${deviceId}/readings?limit=15&page=${page}`;
                return safeFetch(url)
                    .then(function (response) {
                        const data = response.data;
                        const tbody = document.getElementById('telemetry-tbody');
                        if (!tbody) return;

                        tbody.innerHTML = '';
                        if (!data.data || data.data.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="9" class="px-4 py-6 text-center text-outline italic text-[10px]">No telemetry stream recorded for this machine.</td></tr>';
                            return;
                        }

                        data.data.forEach(function (r) {
                            const tr = document.createElement('tr');
                            tr.className = 'border-b border-surface-container hover:bg-surface-container-low transition-colors';

                            // Patch 1: Null Safe Rendering
                            const pwr = r.power_kw !== null ? Number(r.power_kw).toFixed(2) : '-';
                            const vlt = r.voltage !== null ? Number(r.voltage).toFixed(1) : '-';
                            const cur = r.current !== null ? Number(r.current).toFixed(1) : '-';
                            const pf = r.power_factor !== null ? Number(r.power_factor).toFixed(2) : '-';
                            const kwh = r.kwh_total !== null ? Number(r.kwh_total).toFixed(2) : '-';

                            const statusColor = (r.power_kw || 0) > 2 ? 'text-primary' : 'text-outline';
                            const qualityBadge = r.telemetry_quality === 'GOOD'
                                ? '<span class="text-primary">GOOD</span>'
                                : '<span class="text-error font-bold">' + (r.telemetry_quality || 'NULL') + '</span>';

                            tr.innerHTML = `
                            <td class="px-4 py-2 font-mono text-outline">${formatWIB(getTelemetryTs(r))}</td>
                            <td class="px-4 py-2 text-right font-black ${statusColor}">${pwr}</td>
                            <td class="px-4 py-2 text-right text-outline">${vlt}</td>
                            <td class="px-4 py-2 text-right text-outline">${cur}</td>
                            <td class="px-4 py-2 text-right text-outline">${pf}</td>
                            <td class="px-4 py-2 text-right font-bold text-on-surface">${kwh}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase ${(r.power_kw || 0) > 2 ? 'bg-primary/10 text-primary' : 'bg-outline/10 text-outline'}">
                                    ${(r.power_kw || 0) > 2 ? 'Running' : 'Idle'}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center text-[9px] font-black">${qualityBadge}</td>
                            <td class="px-4 py-2 text-right">
                                <button onclick="openTagModal('${getTelemetryTs(r)}')" class="text-primary hover:underline font-black uppercase text-[9px]">Tag</button>
                            </td>
                        `;
                            tbody.appendChild(tr);
                        });

                        // Patch 4: Stall Detection
                        if (data.data.length > 0) {
                            const latestTs = getTelemetryTs(data.data[0]);
                            if (latestTs) {
                                const latest = new Date(latestTs);
                                const diffSec = (new Date() - latest) / 1000;
                                updateHealthPanel('telemetry-freshness', Math.floor(diffSec) + 's');

                                const stallIndicator = document.getElementById('health-stall-indicator');
                                if (diffSec > 180) { // 3 intervals (assuming 60s)
                                    stallIndicator.classList.remove('hidden');
                                    updateHealthPanel('status', 'STALE', 'text-amber-600');
                                } else {
                                    stallIndicator.classList.add('hidden');
                                    updateHealthPanel('status', 'STABLE', 'text-primary');
                                }
                            }
                        }

                        // Update Pagination UI
                        document.getElementById('current-page-display').textContent = data.current_page;
                        document.getElementById('last-page-display').textContent = data.last_page;
                        document.getElementById('total-readings-display').textContent = data.total;

                        const prevBtn = document.getElementById('prev-page');
                        const nextBtn = document.getElementById('next-page');
                        if (prevBtn) {
                            prevBtn.disabled = data.current_page === 1;
                            prevBtn.onclick = () => loadTelemetry(data.current_page - 1);
                        }
                        if (nextBtn) {
                            nextBtn.disabled = data.current_page === data.last_page;
                            nextBtn.onclick = () => loadTelemetry(data.current_page + 1);
                        }
                    })
                    .catch(function (err) {
                        console.error('Telemetry Load Error:', err);
                    });
            } catch (err) {
                console.error('loadTelemetry Crash:', err);
            }
        }


        window.exportPhases = function(format) {
            if (!currentPhases.length) return alert('No phase data available for export.');
            
            const startStr = document.getElementById('forensic-start').value;
            const endStr = document.getElementById('forensic-end').value;
            
            let url = format === 'excel' 
                ? `{{ url('api/machines') }}/${deviceId}/phases/export`
                : `{{ url('api/machines') }}/${deviceId}/phases/pdf`;
                
            const params = new URLSearchParams();
            
            // Priority 1: User-selected Forensic Range
            if (startStr && endStr) {
                params.append('start', new Date(startStr).toISOString());
                params.append('end', new Date(endStr).toISOString());
            } else {
                // Priority 2: Visual Scrubber Range
                params.append('start', new Date(visualRange.min).toISOString());
                params.append('end', new Date(visualRange.max).toISOString());
            }
            
            if (format === 'pdf') {
                window.open(url + '?' + params.toString(), '_blank');
            } else {
                window.location.href = url + '?' + params.toString();
            }
        };

        // Patch 7: Export Safety Hardening
        const exportBtn = document.querySelector('a[href*="export"]');
        if (exportBtn) {
            exportBtn.onclick = function (e) {
                if (cachedData.length === 0) {
                    e.preventDefault();
                    return alert('No data available for export.');
                }
                if (cachedData.length > 1440) {
                    if (!confirm('Warning: Data density is high (>1440 rows). Export may take time. Continue?')) {
                        e.preventDefault();
                        return;
                    }
                }

                const originalText = exportBtn.innerHTML;
                exportBtn.style.pointerEvents = 'none';
                exportBtn.style.opacity = '0.5';
                exportBtn.innerHTML = 'Generating...';

                setTimeout(function () {
                    exportBtn.style.pointerEvents = 'auto';
                    exportBtn.style.opacity = '1';
                    exportBtn.innerHTML = originalText;
                }, 5000); // Re-enable after 5s or on next interaction
            };
        }

        function initDashboard() {
            try {
                loadChartData();
                loadTelemetry();
            } catch (e) {
                console.error('Dashboard Init Failed:', e);
                updateHealthPanel('status', 'INIT FAIL', 'text-error');
            }
        }

        initDashboard();
        // Set auto-refresh for telemetry watchdog
        setInterval(loadTelemetry, 60000);
    });
</script>

@endsection