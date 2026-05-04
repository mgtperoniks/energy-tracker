@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-10 max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-on-surface mb-1">Facility Overview</h1>
                <p class="text-on-surface-variant text-sm">Real-time energy telemetry for Site A Heavy Industrial Wing</p>
            </div>
            <div class="flex items-center gap-3">
                <button class="bg-surface-container-high text-on-surface px-4 py-2 rounded-md text-sm font-medium hover:bg-surface-container-highest transition-colors">
                    Download Report
                </button>
                <button class="bg-gradient-to-r from-primary-container to-primary text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm hover:saturate-150 transition-all">
                    Adjust Thresholds
                </button>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <!-- KPI 1: Energy -->
            <div class="bg-surface-container-lowest p-6 rounded-xl shadow-[0_24px_40px_-4px_rgba(25,28,30,0.05)] flex flex-col gap-2 border-l-4 border-primary">
                <span class="text-[0.6875rem] font-bold uppercase tracking-widest text-on-surface-variant">Today's Energy</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold tracking-tight text-on-surface">{{ number_format($todayUsage ?? 0, 1) }}</span>
                    <span class="text-on-surface-variant font-medium">kWh</span>
                </div>
                <div class="flex items-center gap-1 text-secondary mt-1">
                    <span class="material-symbols-outlined text-sm">trending_flat</span>
                    <span class="text-xs font-semibold">Active Monitoring</span>
                </div>
            </div>
            
            <!-- KPI 2: Cost -->
            <div class="bg-surface-container-lowest p-6 rounded-xl shadow-[0_24px_40px_-4px_rgba(25,28,30,0.05)] flex flex-col gap-2 border-l-4 border-primary">
                <span class="text-[0.6875rem] font-bold uppercase tracking-widest text-on-surface-variant">Today's Cost</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-on-surface-variant font-medium mr-1">Rp</span>
                    <span class="text-3xl font-extrabold tracking-tight text-on-surface">{{ number_format($todayCost ?? 0, 0) }}</span>
                </div>
                <div class="flex items-center gap-1 text-tertiary mt-1">
                    <span class="material-symbols-outlined text-sm">payments</span>
                    <span class="text-xs font-semibold">@if($isEstimatedCost ?? false) Estimated @else Frozen @endif</span>
                </div>
            </div>

            <!-- KPI 3: Idle Leakage -->
            <div class="bg-surface-container-lowest p-6 rounded-xl shadow-[0_24px_40px_-4px_rgba(25,28,30,0.05)] flex flex-col gap-2 border-l-4 border-error">
                <span class="text-[0.6875rem] font-bold uppercase tracking-widest text-on-surface-variant">Idle Leakage</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-on-surface-variant font-medium mr-1">Rp</span>
                    <span class="text-3xl font-extrabold tracking-tight text-on-surface">{{ number_format($idleLeakageCostToday ?? 0, 0) }}</span>
                </div>
                <div class="flex items-center gap-1 text-error mt-1">
                    <span class="material-symbols-outlined text-sm">warning</span>
                    <span class="text-xs font-semibold">Waste Detected</span>
                </div>
            </div>

            <!-- KPI 4: Current Load -->
            <div class="bg-surface-container-lowest p-6 rounded-xl shadow-[0_24px_40px_-4px_rgba(25,28,30,0.05)] flex flex-col gap-2 border-l-4 border-secondary">
                <span class="text-[0.6875rem] font-bold uppercase tracking-widest text-on-surface-variant">Current Load</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold tracking-tight text-on-surface">{{ number_format($currentKw ?? 0, 1) }}</span>
                    <span class="text-on-surface-variant font-medium">kW</span>
                </div>
                <div class="flex items-center gap-1 text-secondary mt-1">
                    <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                    <span class="text-xs font-semibold">Live Transmission</span>
                </div>
            </div>
        </div>

        <!-- Bento Layout Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Chart (Asymmetric Span) -->
            <div class="lg:col-span-2 bg-surface-container-lowest p-6 rounded-xl shadow-[0_24px_40px_-4px_rgba(25,28,30,0.05)]">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-lg font-bold tracking-tight text-on-surface leading-none">Power Consumption Trend</h3>
                        <p class="text-xs text-on-surface-variant mt-1">12-hour aggregate industrial load (kW)</p>
                    </div>
                    <div class="flex bg-surface-container-low p-1 rounded-md">
                        <button class="px-3 py-1 text-xs font-bold bg-white shadow-sm rounded">Live</button>
                        <button class="px-3 py-1 text-xs font-medium text-on-surface-variant">12H</button>
                        <button class="px-3 py-1 text-xs font-medium text-on-surface-variant">7D</button>
                    </div>
                </div>
                
                <!-- Real Chart Canvas -->
                <div class="w-full h-64 relative">
                    <canvas id="dashboardChart"></canvas>
                </div>

                <script src="{{ asset('assets/js/chart.js') }}"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const ctx = document.getElementById('dashboardChart').getContext('2d');
                        
                        const deviceId = {{ $machines->first()?->devices->first()?->id ?? 'null' }};
                        
                        if (!deviceId) {
                            renderEmptyChart();
                            return;
                        }

                        const end = new Date();
                        const start = new Date(end.getTime() - 12 * 60 * 60 * 1000);

                        fetch(`/api/charts/device?device_id=${deviceId}&start_date=${start.toISOString()}&end_date=${end.toISOString()}`)
                            .then(res => res.json())
                            .then(response => {
                                const data = response.data || [];
                                const labels = data.map(item => {
                                    const d = new Date(item.timestamp);
                                    return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
                                });
                                const values = data.map(item => item.power_kw || 0);

                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: labels.length > 0 ? labels : ['00:00', '06:00', '12:00', '18:00'],
                                        datasets: [{
                                            label: 'Aggregate Power (kW)',
                                            data: values.length > 0 ? values : [0, 0, 0, 0],
                                            fill: true,
                                            backgroundColor: 'rgba(0, 98, 140, 0.1)',
                                            borderColor: '#00628c',
                                            borderWidth: 3,
                                            pointRadius: 0,
                                            pointHoverRadius: 4,
                                            pointBackgroundColor: '#00628c',
                                            pointBorderColor: '#fff',
                                            pointBorderWidth: 2,
                                            tension: 0.4,
                                            spanGaps: false
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: {
                                                backgroundColor: '#191c1e',
                                                padding: 12,
                                                cornerRadius: 8,
                                                titleFont: { size: 12, weight: 'bold' },
                                                bodyFont: { size: 13 },
                                                callbacks: {
                                                    label: (context) => ` Load: ${context.parsed.y.toFixed(2)} kW`
                                                }
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                grid: { color: 'rgba(0, 0, 0, 0.05)' },
                                                ticks: {
                                                    font: { size: 10, weight: 'bold' },
                                                    callback: (value) => value + 'kW'
                                                }
                                            },
                                            x: {
                                                grid: { display: false },
                                                ticks: { font: { size: 10, weight: 'bold' }, maxTicksLimit: 12 }
                                            }
                                        }
                                    }
                                });
                            })
                            .catch(err => {
                                console.error('Error fetching chart data:', err);
                                renderEmptyChart();
                            });

                        function renderEmptyChart() {
                            new Chart(ctx, {
                                type: 'line',
                                data: { labels: ['No Data'], datasets: [{ data: [0] }] },
                                options: { maintainAspectRatio: false }
                            });
                        }
                    });
                </script>
            </div>

            <!-- Secondary Data Visualization / Status Summary -->
            <div class="bg-surface-container-lowest p-6 rounded-xl shadow-[0_24px_40px_-4px_rgba(25,28,30,0.05)] flex flex-col">
                <h3 class="text-lg font-bold tracking-tight text-on-surface mb-6 leading-none">Anomaly Summary</h3>
                
                <div class="space-y-6 flex-1">
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-xs font-bold text-on-surface-variant">Low Voltage Anomalies</span>
                            <span class="text-xs font-bold text-error">{{ $anomalySummary['low_voltage'] ?? 0 }} Events</span>
                        </div>
                        <div class="h-2 w-full bg-surface-container-low rounded-full overflow-hidden">
                            <div class="h-full bg-error w-[{{ min(($anomalySummary['low_voltage'] ?? 0) * 10, 100) }}%] rounded-full"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-xs font-bold text-on-surface-variant">Idle Leaks</span>
                            <span class="text-xs font-bold text-tertiary">{{ $anomalySummary['idle_leak'] ?? 0 }} Events</span>
                        </div>
                        <div class="h-2 w-full bg-surface-container-low rounded-full overflow-hidden">
                            <div class="h-full bg-tertiary w-[{{ min(($anomalySummary['idle_leak'] ?? 0) * 10, 100) }}%] rounded-full"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-xs font-bold text-on-surface-variant">Offline Meters</span>
                            <span class="text-xs font-bold text-primary">{{ $offlineDevices ?? 0 }} Node</span>
                        </div>
                        <div class="h-2 w-full bg-surface-container-low rounded-full overflow-hidden">
                            <div class="h-full bg-primary w-[{{ min(($offlineDevices ?? 0) * 10, 100) }}%] rounded-full"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-outline-variant/10">
                    <div class="flex items-center gap-3">
                        @if(($anomalySummary['low_voltage'] ?? 0) > 0 || ($anomalySummary['idle_leak'] ?? 0) > 0)
                        <div class="p-3 bg-error-container text-on-error-container rounded-lg">
                            <span class="material-symbols-outlined text-2xl">warning</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-error">Anomalies Detected</p>
                            <p class="text-[10px] text-on-surface-variant">Check Audit Reports</p>
                        </div>
                        @else
                        <div class="p-3 bg-secondary-container text-on-secondary-container rounded-lg">
                            <span class="material-symbols-outlined text-2xl">check_circle</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-on-surface">All Nodes Nominal</p>
                            <p class="text-[10px] text-on-surface-variant">Last full sync: 45s ago</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Ledger Table (Full Width) -->
            <div class="lg:col-span-3 bg-surface-container-lowest rounded-xl shadow-[0_24px_40px_-4px_rgba(25,28,30,0.05)] overflow-hidden">
                <div class="p-6 border-b border-outline-variant/10 flex items-center justify-between">
                    <h3 class="text-lg font-bold tracking-tight text-on-surface">Machine Status Node Ledger</h3>
                    <div class="flex items-center gap-4">
                        <span class="text-xs text-on-surface-variant font-medium">Auto-refresh active</span>
                        <span class="material-symbols-outlined text-on-surface-variant cursor-pointer">filter_list</span>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-surface-container-low">
                                <th class="px-6 py-4 text-[10px] font-extrabold uppercase tracking-widest text-on-surface-variant">Name</th>
                                <th class="px-6 py-4 text-[10px] font-extrabold uppercase tracking-widest text-on-surface-variant">Type</th>
                                <th class="px-6 py-4 text-[10px] font-extrabold uppercase tracking-widest text-on-surface-variant">kWh Today</th>
                                <th class="px-6 py-4 text-[10px] font-extrabold uppercase tracking-widest text-on-surface-variant">Status</th>
                                <th class="px-6 py-4 text-[10px] font-extrabold uppercase tracking-widest text-on-surface-variant text-right">Last Update</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            @foreach($machines as $machine)
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="px-6 py-5">
                                    <a href="{{ route('monitoring.meters', ['id' => $machine->id]) }}" class="font-bold text-primary hover:underline">{{ $machine->name }}</a>
                                    <div class="text-[10px] text-on-surface-variant font-mono">{{ $machine->code }}</div>
                                </td>
                                <td class="px-6 py-5 text-sm text-on-surface-variant">Power Meter Node</td>
                                <td class="px-6 py-5 font-mono text-sm">{{ number_format($machine->todaySummary->kwh_usage ?? 0, 1) }}</td>
                                <td class="px-6 py-5">
                                    <span class="px-2 py-1 rounded-full bg-secondary-container text-on-secondary-container text-[10px] font-bold uppercase tracking-wide">Nominal</span>
                                </td>
                                <td class="px-6 py-5 text-right text-xs text-on-surface-variant">
                                    {{ $machine->latestReading ? ($machine->latestReading->recorded_at ? $machine->latestReading->recorded_at->diffForHumans() : 'No Data') : 'No Data' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Contextual FAB (Dashboard only) -->
<div class="fixed bottom-20 md:bottom-8 right-6 md:right-8 z-50">
    <button class="h-14 w-14 bg-gradient-to-br from-primary-container to-primary text-white rounded-full shadow-2xl flex items-center justify-center hover:saturate-150 transition-all active:scale-95 group">
        <span class="material-symbols-outlined text-2xl group-hover:rotate-90 transition-transform duration-300">add</span>
    </button>
</div>
@endsection
