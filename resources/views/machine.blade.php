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
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta name="csrf-token" content="{{ csrf_token() }}">
                <title>{{ $machine->name }} - Industrial Historian</title>
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
                </div>
            </div>
            
            <div id="chart-container" class="relative h-[450px] w-full bg-white dark:bg-slate-900 rounded-lg shadow-inner overflow-hidden">
                <!-- Forensic Navigation Arrows -->
                <div id="forensic-nav" class="hidden">
                    <button onclick="shiftForensic(-1)" class="absolute left-2 top-1/2 -translate-y-1/2 z-20 p-3 bg-black/5 hover:bg-black/10 rounded-full transition-all group">
                        <svg class="w-6 h-6 text-slate-400 group-hover:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button onclick="shiftForensic(1)" class="absolute right-2 top-1/2 -translate-y-1/2 z-20 p-3 bg-black/5 hover:bg-black/10 rounded-full transition-all group">
                        <svg class="w-6 h-6 text-slate-400 group-hover:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div class="absolute top-2 left-1/2 -translate-x-1/2 z-20">
                        <span class="px-3 py-1 bg-primary/10 text-primary font-black text-[10px] uppercase tracking-widest rounded-full border border-primary/20 shadow-sm">
                            Forensic Mode Active
                        </span>
                    </div>
                </div>
                <canvas id="powerChart"></canvas>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center p-1 bg-surface-container-low rounded-lg border border-surface-container">
                    <button onclick="updateRange(1)" id="btn-1h" class="px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all hover:bg-white hover:shadow-sm text-outline">1H</button>
                    <button onclick="updateRange(4)" id="btn-4h" class="px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all hover:bg-white hover:shadow-sm text-outline">4H</button>
                    <button onclick="updateRange(12)" id="btn-12h" class="px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all hover:bg-white hover:shadow-sm text-outline">12H</button>
                    <button onclick="updateRange(24)" id="btn-24h" class="px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all hover:bg-white hover:shadow-sm text-outline">24H</button>
                    <button onclick="updateRange(168)" id="btn-7d" class="px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all hover:bg-white hover:shadow-sm text-outline">7D</button>
                </div>
                <div id="forensic-helper" class="text-[10px] font-medium text-outline hidden">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3 h-3 text-primary" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                        Forensic tagging enabled. Use arrows to navigate historical window.
                    </span>
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

        <!-- Patch 1: Proper Timeline Container ID -->
        <div class="bg-surface-container-lowest rounded border border-surface-container shadow-sm mb-4">
            <div class="px-4 py-2 border-b border-surface-container-low bg-surface-container-low/30 flex justify-between items-center">
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
                        <textarea id="tag-notes" class="w-full bg-white text-xs px-2 py-1.5 rounded font-medium text-on-surface outline-none border border-surface-container focus:border-primary h-16 resize-none"></textarea>
                    </div>
                </div>
                <div class="px-4 py-3 bg-surface-container-low/50 border-t border-surface-container flex gap-2 justify-end">
                    @if(auth()->user()->role === 'admin')
                        <button id="btn-delete-tag" class="hidden px-4 py-2 bg-error-container text-error font-black rounded text-[9px] uppercase tracking-widest hover:brightness-110 transition-all shadow-sm">Forensic Delete</button>
                    @endif
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
    </div>
</main>

<script src="{{ asset('assets/js/chart.js') }}"></script>
<script src="{{ asset('assets/js/chartjs-plugin-annotation.min.js') }}"></script>
<script src="{{ asset('assets/vendor/date-fns/date-fns.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chartjs-adapter-date-fns/chartjs-adapter-date-fns.bundle.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('powerChart').getContext('2d');
        const phaseSection = document.getElementById('phase-reconstruction-section');
        const phaseTableBody = document.getElementById('phase-tbody');
        const tagModal = document.getElementById('tag-modal');
        
        let chartInstance = null;
        let baseAnnotations = {};
        let currentHours = 4; // Default: 4H Forensic Mode
        let currentMetric = 'power';
        let cachedData = [];
        let visualRange = { min: null, max: null };
        let currentTags = [];
        
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

            return fetch(url, finalOptions).then(function(response) {
                if (!response.ok) {
                    if (response.status === 419) {
                        alert('Industrial Session Expired. Refreshing dashboard...');
                        location.reload();
                        return;
                    }
                    return response.text().then(function(text) {
                        let msg = 'Backend Error';
                        try {
                            const json = JSON.parse(text);
                            msg = json.message || json.error || msg;
                        } catch(e) {
                            msg = text.substring(0, 100);
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            }).catch(function(err) {
                console.error('Fetch Failed:', err);
                // Graceful alert instead of crash
                if (err.message !== 'Industrial Session Expired. Refreshing dashboard...') {
                    // alert('Communication Failure: ' + err.message);
                }
                throw err;
            });
        }

        // Patch 3 & 4: Range & Forensic Navigation Logic
        window.updateRange = function(h) {
            currentHours = h;
            
            ['1h', '4h', '12h', '24h', '7d'].forEach(function(id) {
                const btn = document.getElementById('btn-' + id);
                if (btn) btn.classList.remove('bg-white', 'shadow-sm', 'text-primary');
            });
            const activeBtn = document.getElementById('btn-' + (h === 168 ? '7d' : h + 'h'));
            if (activeBtn) activeBtn.classList.add('bg-white', 'shadow-sm', 'text-primary');

            const nav = document.getElementById('forensic-nav');
            const helper = document.getElementById('forensic-helper');
            if (h === 4) {
                if (nav) nav.classList.remove('hidden');
                if (helper) helper.classList.remove('hidden');
            } else {
                if (nav) nav.classList.add('hidden');
                if (helper) helper.classList.add('hidden');
            }

            initDashboard();
        };

        window.shiftForensic = function(direction) {
            if (currentHours !== 4 || !cachedData.length) return;
            
            const shiftMs = direction * (60 * 60 * 1000); // 1 hour shift
            const newMin = visualRange.min + shiftMs;
            const newMax = visualRange.max + shiftMs;
            
            const cacheMin = new Date(cachedData[0].timestamp).getTime();
            const cacheMax = new Date(cachedData[cachedData.length - 1].timestamp).getTime();
            
            if (newMin < cacheMin || newMax > cacheMax) {
                console.warn('Forensic Boundary Reached (18H Cache)');
                return;
            }
            
            visualRange.min = newMin;
            visualRange.max = newMax;
            
            if (chartInstance) {
                chartInstance.options.scales.x.min = newMin;
                chartInstance.options.scales.x.max = newMax;
                chartInstance.update('none');
            }
        };

        function loadChartData() {
            const fetchHours = (currentHours === 4) ? 18 : currentHours;
            const end = new Date();
            const start = new Date(end.getTime() - (fetchHours * 60 * 60 * 1000));

            return safeFetch(`{{ url('api/charts/device') }}?device_id=${deviceId}&start_date=${start.toISOString()}&end_date=${end.toISOString()}`)
                .then(function(response) {
                    cachedData = response.data || [];
                    updateHealthPanel('telemetry', cachedData.length);
                    updateHealthPanel('forensic', currentHours === 4 ? 'ACTIVE' : 'OFF');
                    
                    if (currentHours === 4) {
                        const vEnd = end.getTime();
                        visualRange = { min: vEnd - (4 * 60 * 60 * 1000), max: vEnd };
                    } else {
                        visualRange = { min: start.getTime(), max: end.getTime() };
                    }
                    renderChart(cachedData);
                });
        }

        // Patch 2 & 9: Stable Render (No free-drag pan)
        // Patch 9: Chart decimation (Max 3000 points)
        function renderChart(data) {
            if (chartInstance) chartInstance.destroy();
            
            // Decimation logic: step = ceil(total / 3000)
            const maxPoints = 3000;
            const step = Math.max(1, Math.ceil(data.length / maxPoints));
            const decimatedData = (step === 1) ? data : data.filter((_, i) => i % step === 0);

            const labels = decimatedData.map(item => item.timestamp);
            const powerData = decimatedData.map(item => item.power_kw);
            
            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Active Power (kW)',
                        data: powerData,
                        borderColor: '#00628c',
                        backgroundColor: 'rgba(0, 98, 140, 0.05)',
                        fill: true,
                        pointRadius: 0,
                        borderWidth: 1.5,
                        tension: 0.1,
                        spanGaps: true
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    animation: false,
                    plugins: {
                        legend: { display: false },
                        annotation: { annotations: {} },
                        decimation: { enabled: false } // We do manual decimation for fidelity
                    },
                    scales: {
                        x: { 
                            type: 'time', 
                            min: visualRange.min, 
                            max: visualRange.max,
                            grid: { display: false }
                        },
                        y: { min: 0, grid: { color: 'rgba(0,0,0,0.05)' } }
                    }
                }
            });

            // Patch 5 & 6: Safe Click Tagging
            ctx.canvas.onclick = function(e) {
                if (isReadonly || currentHours !== 4) return;
                const points = chartInstance.getElementsAtEventForMode(e, 'index', { intersect: false }, true);
                if (points.length) {
                    const firstPoint = points[0];
                    const label = decimatedData[firstPoint.index].timestamp;
                    openTagModal(label);
                }
            };
        }

        // --- TAGGING & PHASES ---

        window.openTagModal = function(timestamp, tagData = null) {
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
                    deleteBtn.onclick = function() { deleteTag(tagData.id); };
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

        window.closeTagModal = function() { tagModal.classList.add('hidden'); };

        window.saveTag = function() {
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
                .then(function() {
                    closeTagModal();
                    initDashboard();
                })
                .catch(function(err) { alert(err.message || 'Validation failed'); });
        };

        window.deleteTag = function(id) {
            const reason = prompt('Industrial Delete Reason (Min 10 chars):');
            if (!reason || reason.length < 10) return alert('Invalid reason.');
            
            if (!confirm('Confirm soft delete?')) return;
            
            safeFetch(`/api/tags/${id}`, { method: 'DELETE', body: JSON.stringify({ reason: reason }) })
                .then(function() {
                    closeTagModal();
                    initDashboard();
                });
        };

        function updateHealthPanel(key, value) {
            const el = document.getElementById('health-' + key);
            if (el) el.textContent = value;
            document.getElementById('health-last-refresh').textContent = new Date().toLocaleTimeString();
        }

        function loadTags() {
            safeFetch(`{{ url('api/machines') }}/${deviceId}/tags`)
                .then(function(tags) {
                    currentTags = tags;
                    updateHealthPanel('tags', tags.length);
                    renderTimeline(tags);
                    drawTagAnnotations(tags);
                });
        }

        function loadPhases() {
            safeFetch(`{{ url('api/machines') }}/${deviceId}/phases`)
                .then(function(phases) {
                    updateHealthPanel('phases', phases.length);
                    renderPhases(phases);
                });
        }

        // Patch 4: Filter Tag Annotations by Visible Window
        function drawTagAnnotations(tags) {
            if (!chartInstance || !visualRange.min || !visualRange.max) return;
            const annotations = {};
            
            tags.forEach(function(t) {
                if (t.deleted_at) return;
                
                const time = new Date(t.event_time).getTime();
                // Performance: Only render if within visual range
                if (time < visualRange.min || time > visualRange.max) return;

                annotations[`tag_${t.id}`] = {
                    type: 'line', xMin: t.event_time, xMax: t.event_time,
                    borderColor: getEventColor(t.event_type), borderWidth: 2, borderDash: [4, 4],
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
            });
            chartInstance.options.plugins.annotation.annotations = annotations;
            chartInstance.update('none');
        }

        function renderTimeline(tags) {
            const container = document.getElementById('timeline-content');
            if (!container) return;
            container.innerHTML = '';
            tags.forEach(function(t) {
                const div = document.createElement('div');
                div.className = 'p-2 rounded border border-surface-container bg-white mb-2 cursor-pointer';
                div.onclick = function() { openTagModal(t.event_time, t); };
                div.innerHTML = `
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-bold text-[10px]">${formatWIB(t.event_time).split(' ')[1]}</span>
                        <span class="px-1 py-0.5 rounded text-white text-[8px] uppercase font-black" style="background: ${getEventColor(t.event_type)}">${t.event_type}</span>
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

            phases.forEach(function(p) {
                const tr = document.createElement('tr');
                tr.className = 'border-b border-surface-container hover:bg-surface-container-low transition-colors';
                
                const statusBadge = p.status === 'OPEN' 
                    ? '<span class="px-2 py-0.5 bg-primary/10 text-primary rounded font-black text-[8px] animate-pulse uppercase">Active</span>'
                    : '<span class="px-2 py-0.5 bg-outline/10 text-outline rounded font-black text-[8px] uppercase">Closed</span>';

                tr.innerHTML = `
                    <td class="px-4 py-2 font-mono text-[10px] text-outline">${formatWIB(p.start_time).split(' ')[1]}</td>
                    <td class="px-4 py-2 font-mono text-[10px] text-outline">${formatWIB(p.end_time).split(' ')[1]}</td>
                    <td class="px-4 py-2 text-center">${statusBadge}</td>
                    <td class="px-4 py-2 text-primary font-black uppercase text-[10px]">${p.phase_name}</td>
                    <td class="px-4 py-2 text-right font-bold text-on-surface">${p.duration_human}</td>
                    <td class="px-4 py-2 text-right font-medium text-outline">${p.avg_kw}</td>
                    <td class="px-4 py-2 text-right font-medium text-outline">${p.peak_kw}</td>
                    <td class="px-4 py-2 text-right font-black text-on-surface">${p.usage_kwh}</td>
                    <td class="px-4 py-2 text-right font-black text-tertiary">Rp ${p.est_cost.toLocaleString()}</td>
                `;
                phaseTableBody.appendChild(tr);
            });
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

        function initDashboard() {
            loadChartData().then(function() {
                loadTags();
                loadPhases();
            });
        }

        initDashboard();
    });
</script>
