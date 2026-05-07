@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        
        <!-- HEADER & KPI SUMMARY -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">System Audit Report</h1>
            <p class="text-on-surface-variant text-sm mt-1">Forensic-grade incident console & telemetry auditing.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-error-container p-4 rounded-lg shadow-sm border border-error/20">
                <span class="text-[10px] font-bold uppercase text-error">Critical Open</span>
                <div class="text-2xl font-black text-on-error-container">{{ $summary['critical_open'] }}</div>
            </div>
            <div class="bg-surface-container-highest p-4 rounded-lg shadow-sm border border-outline/20">
                <span class="text-[10px] font-bold uppercase text-outline">Acknowledged</span>
                <div class="text-2xl font-black text-on-surface">{{ $summary['acknowledged'] }}</div>
            </div>
            <div class="bg-surface-container-highest p-4 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase text-outline">Today Incidents</span>
                <div class="text-2xl font-black text-on-surface">{{ $summary['today_incidents'] }}</div>
            </div>
            <div class="bg-primary-container p-4 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase text-primary">Resolved Today</span>
                <div class="text-2xl font-black text-on-primary-container">{{ $summary['resolved_today'] }}</div>
            </div>
            <div class="bg-tertiary-container p-4 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase text-tertiary font-mono">MTTR (Avg)</span>
                <div class="text-2xl font-black text-on-tertiary-container">{{ $summary['mttr_minutes'] }} <small class="text-xs font-normal">m</small></div>
            </div>
            <div class="bg-surface-container-low p-4 rounded-lg shadow-sm border border-outline/20">
                <span class="text-[10px] font-bold uppercase text-outline font-mono">MTTA (Avg)</span>
                <div class="text-2xl font-black text-on-surface">{{ $summary['mtta_minutes'] }} <small class="text-xs font-normal">m</small></div>
            </div>
        </div>

        <!-- ADVANCED FILTERS -->
        <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm border border-surface-container-low mb-8">
            <form method="GET" action="{{ route('analytics.audit') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-7 gap-4 items-end">
                <input type="hidden" name="view_mode" value="{{ $viewMode }}">
                
                <div class="lg:col-span-1">
                    <label class="block text-[10px] font-bold text-outline uppercase mb-1">Severity</label>
                    <select name="severity" class="w-full bg-surface border border-outline-variant rounded-md text-xs p-2">
                        <option value="">ALL</option>
                        <option value="CRITICAL" {{ request('severity') == 'CRITICAL' ? 'selected' : '' }}>CRITICAL</option>
                        <option value="ERROR" {{ request('severity') == 'ERROR' ? 'selected' : '' }}>ERROR</option>
                        <option value="WARNING" {{ request('severity') == 'WARNING' ? 'selected' : '' }}>WARNING</option>
                        <option value="INFO" {{ request('severity') == 'INFO' ? 'selected' : '' }}>INFO</option>
                    </select>
                </div>

                <div class="lg:col-span-1">
                    <label class="block text-[10px] font-bold text-outline uppercase mb-1">Status</label>
                    <select name="status" class="w-full bg-surface border border-outline-variant rounded-md text-xs p-2">
                        <option value="">ALL</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>OPEN</option>
                        <option value="acknowledged" {{ request('status') == 'acknowledged' ? 'selected' : '' }}>ACKNOWLEDGED</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>RESOLVED</option>
                        <option value="ignored" {{ request('status') == 'ignored' ? 'selected' : '' }}>IGNORED</option>
                    </select>
                </div>

                <div class="lg:col-span-1">
                    <label class="block text-[10px] font-bold text-outline uppercase mb-1">Device</label>
                    <select name="device_id" class="w-full bg-surface border border-outline-variant rounded-md text-xs p-2">
                        <option value="">All Devices</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" {{ request('device_id') == $device->id ? 'selected' : '' }}>{{ $device->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-[10px] font-bold text-outline uppercase mb-1">Date Range</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full bg-surface border border-outline-variant rounded-md text-xs p-2">
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full bg-surface border border-outline-variant rounded-md text-xs p-2">
                    </div>
                </div>

                <div class="lg:col-span-2 flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary text-on-primary font-bold rounded-md hover:bg-primary/90 text-sm flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">search</span> Filter
                    </button>
                    
                    <div class="flex border border-outline-variant rounded-md overflow-hidden bg-surface">
                        <a href="{{ route('analytics.audit.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="p-2 hover:bg-primary-container hover:text-on-primary-container transition-colors" title="Export Excel">
                            <span class="material-symbols-outlined text-sm">description</span>
                        </a>
                        <a href="{{ route('analytics.audit.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="p-2 hover:bg-error-container hover:text-error transition-colors border-l border-outline-variant" title="Export PDF">
                            <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                        </a>
                    </div>

                    <div class="flex border border-outline-variant rounded-md overflow-hidden">
                        <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'flat']) }}" class="px-3 py-2 {{ $viewMode == 'flat' ? 'bg-secondary text-on-secondary' : 'bg-surface' }} text-xs font-bold">Flat</a>
                        <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'grouped']) }}" class="px-3 py-2 {{ $viewMode == 'grouped' ? 'bg-secondary text-on-secondary' : 'bg-surface' }} text-xs font-bold">Grouped</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- DATA TABLE -->
        <div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden border border-surface-container-low">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-outline-variant/30">
                            <th class="px-6 py-4">
                                {{ $viewMode == 'grouped' ? 'Last Seen / Count' : 'Timestamp / Duration' }}
                            </th>
                            <th class="px-6 py-4">Severity / Code</th>
                            <th class="px-6 py-4">Device</th>
                            <th class="px-6 py-4">Summary</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @forelse($logs as $log)
                            <tr class="hover:bg-surface-container-low/50 transition-colors cursor-pointer" onclick="openIncidentModal({{ $log->id ?? 0 }}, '{{ $log->fingerprint ?? '' }}')">
                                <td class="px-6 py-4">
                                    @if($viewMode == 'grouped')
                                        <div class="font-mono font-bold text-on-surface">{{ \Carbon\Carbon::parse($log->last_seen)->format('d M H:i') }}</div>
                                        <div class="text-[9px] text-primary mt-1 font-black uppercase tracking-tighter">Occurred {{ $log->incident_count }}x</div>
                                    @else
                                        <div class="font-mono font-bold text-on-surface">{{ $log->detected_at->format('d M H:i') }}</div>
                                        <div class="text-[9px] text-outline mt-1 font-medium italic">
                                            {{ $log->status == 'resolved' ? $log->duration_minutes . ' min duration' : $log->detected_at->diffForHumans() }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ 
                                        $log->severity == 'CRITICAL' ? 'bg-error text-on-error' : 
                                        ($log->severity == 'ERROR' ? 'bg-error-container text-error' : 
                                        ($log->severity == 'WARNING' ? 'bg-tertiary-container text-on-tertiary-container' : 'bg-surface-container-highest text-outline')) 
                                    }}">
                                        {{ $log->severity }}
                                    </span>
                                    <div class="mt-1 text-[10px] font-mono text-outline">{{ $log->event_code }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-on-surface">{{ $log->device->name ?? 'N/A' }}</div>
                                    <div class="text-[9px] text-outline">{{ $log->source_layer }} layer</div>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <div class="font-bold text-on-surface truncate">{{ $log->title }}</div>
                                    <div class="text-[10px] text-on-surface-variant line-clamp-1">{{ $log->message }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="flex items-center gap-1.5 font-bold {{ 
                                        $log->status == 'open' ? 'text-error' : 
                                        ($log->status == 'acknowledged' ? 'text-tertiary' : 
                                        ($log->status == 'resolved' ? 'text-primary' : 'text-outline')) 
                                    }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ 
                                            $log->status == 'open' ? 'bg-error' : 
                                            ($log->status == 'acknowledged' ? 'bg-tertiary' : 
                                            ($log->status == 'resolved' ? 'bg-primary' : 'bg-outline')) 
                                        }}"></span>
                                        {{ strtoupper($log->status) }}
                                    </span>
                                    @if($log->status == 'acknowledged')
                                        <div class="text-[9px] text-outline mt-1 italic">By {{ $log->acknowledger->name ?? 'User' }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2" onclick="event.stopPropagation()">
                                        @if($log->status == 'open')
                                            <form action="{{ route('analytics.audit.acknowledge', $log->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="p-1.5 text-tertiary hover:bg-tertiary-container rounded transition-colors" title="Acknowledge">
                                                    <span class="material-symbols-outlined text-sm">front_hand</span>
                                                </button>
                                            </form>
                                        @endif
                                        @if($log->status == 'open' || $log->status == 'acknowledged')
                                            <form action="{{ route('analytics.audit.resolve', $log->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="p-1.5 text-primary hover:bg-primary-container rounded transition-colors" title="Resolve">
                                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('analytics.audit.ignore', $log->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-outline hover:bg-surface-container-highest rounded transition-colors" title="Ignore">
                                                <span class="material-symbols-outlined text-sm">visibility_off</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-20 text-center text-outline italic">No incidents found matching your criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-surface-container-low">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</main>

<!-- DRILLDOWN MODAL (Conceptual - Needs Alpine.js or similar for full interactive) -->
<div id="incidentModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-surface-container-lowest w-full max-w-4xl rounded-xl shadow-2xl border border-outline/20 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 bg-surface-container-low border-b border-outline/10 flex justify-between items-center">
            <div>
                <h3 id="modalTitle" class="text-lg font-black text-on-surface">Incident Drilldown</h3>
                <p id="modalFingerprint" class="text-[10px] font-mono text-outline"></p>
            </div>
            <button onclick="closeIncidentModal()" class="p-2 hover:bg-error-container hover:text-error rounded-full transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-[10px] font-bold text-outline uppercase tracking-wider">Message Details</label>
                    <div id="modalMessage" class="mt-2 p-4 bg-surface rounded-lg text-sm border border-outline-variant/30 leading-relaxed"></div>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-outline uppercase tracking-wider">Payload Analysis (JSON)</label>
                    <pre id="modalPayload" class="mt-2 p-4 bg-surface-container-highest rounded-lg text-[10px] font-mono overflow-x-auto text-on-surface"></pre>
                </div>
            </div>
            
            <!-- ESCALATION TIMELINE -->
            <div class="mt-6 pt-6 border-t border-outline/10">
                <label class="text-[10px] font-bold text-outline uppercase tracking-wider mb-2 block">Escalation History</label>
                <div id="modalEscalations" class="space-y-3">
                    <div class="text-[10px] text-outline italic">No escalations triggered for this incident.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openIncidentModal(id, fingerprint) {
        // In a real app, this would fetch details via AJAX
        document.getElementById('incidentModal').classList.remove('hidden');
        document.getElementById('modalTitle').innerText = 'Investigating Incident #' + id;
        document.getElementById('modalFingerprint').innerText = 'Fingerprint: ' + fingerprint;
        // Mock content
        document.getElementById('modalMessage').innerText = 'Detailed forensic analysis for incident ' + id + ' would be loaded here via AJAX to keep the UI snappy.';
        document.getElementById('modalPayload').innerText = JSON.stringify({ device_id: id, status: 'analysed', forensic: true }, null, 2);
    }
    
    function closeIncidentModal() {
        document.getElementById('incidentModal').classList.add('hidden');
    }
</script>
@endsection
