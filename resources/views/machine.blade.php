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
            <div class="lg:col-span-2 bg-surface-container-lowest p-6 rounded-lg shadow-sm border-b-2 border-primary-container">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-outline">Consumption (Today)</span>
                    <span class="material-symbols-outlined text-primary" data-icon="electric_bolt">electric_bolt</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-black tracking-tight text-on-surface">{{ number_format($machine->todaySummary->kwh_usage ?? 0, 1) }}</span>
                    <span class="text-xl font-bold text-outline">kWh</span>
                </div>
                <div class="mt-4 flex items-center gap-2 text-secondary text-sm font-bold">
                    <span class="material-symbols-outlined text-sm" data-icon="trending_up">trending_up</span>
                    Real-time Summary
                </div>
            </div>

            <!-- Secondary Metrics -->
            <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase tracking-wider text-outline block mb-2">Voltage</span>
                <div class="text-3xl font-bold tracking-tight text-on-surface">{{ number_format($machine->latestReading->voltage ?? 0, 1) }} <span class="text-sm font-medium text-outline">V</span></div>
                <div class="w-full bg-surface-container-low h-1 mt-4 rounded-full overflow-hidden">
                    <div class="bg-primary h-full" style="width: {{ min(($machine->latestReading->voltage ?? 0) / 400 * 100, 100) }}%"></div>
                </div>
            </div>
            
            <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase tracking-wider text-outline block mb-2">Current</span>
                <div class="text-3xl font-bold tracking-tight text-on-surface">{{ number_format($machine->latestReading->current ?? 0, 1) }} <span class="text-sm font-medium text-outline">A</span></div>
                <div class="w-full bg-surface-container-low h-1 mt-4 rounded-full overflow-hidden">
                    <div class="bg-tertiary h-full" style="width: {{ min(($machine->latestReading->current ?? 0) / 100 * 100, 100) }}%"></div>
                </div>
            </div>
            
            <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase tracking-wider text-outline block mb-2">Power Factor</span>
                <div class="text-3xl font-bold tracking-tight text-on-surface">{{ number_format($machine->latestReading->power_factor ?? 0, 2) }}</div>
                <div class="mt-4 flex items-center gap-1 @if(($machine->latestReading->power_factor ?? 0) > 0.85) text-secondary @else text-rose-500 @endif text-[10px] font-bold">
                    <span class="material-symbols-outlined text-xs">@if(($machine->latestReading->power_factor ?? 0) > 0.85) check_circle @else warning @endif</span>
                    {{ ($machine->latestReading->power_factor ?? 0) > 0.85 ? 'OPTIMAL RANGE' : 'LOW PF DETECTED' }}
                </div>
            </div>
        </div>

        <!-- Power History Chart Section -->
        <div class="bg-surface-container-lowest p-8 rounded-lg shadow-sm mb-8 relative overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-on-surface">Power History</h2>
                    <p class="text-xs text-on-surface-variant">Active Load (kW) over last 24 hours</p>
                </div>
                <div class="flex gap-4">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-primary rounded-sm"></span>
                        <span class="text-xs font-bold text-on-surface">Active Power (kW)</span>
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
                const labels = {!! json_encode($historyLabels) !!};
                const values = {!! json_encode($historyValues) !!};

                const powerChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Active Power',
                            data: values,
                            fill: true,
                            backgroundColor: 'rgba(0, 98, 140, 0.1)',
                            borderColor: '#00628c',
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            tension: 0.3,
                            segment: {
                                borderColor: ctx => {
                                    if (ctx.p1.parsed.y > 40) return '#fb7185'; // Highlight spikes in red
                                    return undefined;
                                }
                            }
                        }]
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
                                    afterBody: function(items) {
                                        let kw = items[0].parsed.y;
                                        let load = ((kw / 50) * 100).toFixed(1);
                                        return 'Load: ' + load + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.1)',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: { size: 10, family: 'JetBrains Mono', weight: 'bold' },
                                    color: '#94a3b8',
                                    callback: function(value) { return value.toFixed(1) + ' kW'; }
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
                                @else
                                    <span class="bg-secondary-container text-on-secondary-container px-2 py-1 rounded-full text-[10px] font-bold uppercase">Optimal</span>
                                @endif
                            </td>
                            <td class="px-8 py-4 text-right">
                                <button class="text-primary hover:underline text-xs font-bold">Details</button>
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
                <button class="text-[10px] font-black text-on-surface-variant uppercase tracking-widest hover:text-primary transition-colors">Load Full History</button>
            </div>
        </div>
    </div>
</main>
@endsection
