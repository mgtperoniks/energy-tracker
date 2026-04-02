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
                    <span class="text-[10px] font-bold uppercase tracking-wider text-outline">Consumption (Total)</span>
                    <span class="material-symbols-outlined text-primary" data-icon="electric_bolt">electric_bolt</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-black tracking-tighter text-on-surface">1,248.4</span>
                    <span class="text-xl font-bold text-outline">kWh</span>
                </div>
                <div class="mt-4 flex items-center gap-2 text-secondary text-sm font-bold">
                    <span class="material-symbols-outlined text-sm" data-icon="trending_down">trending_down</span>
                    -4.2% vs Last 24h
                </div>
            </div>

            <!-- Secondary Metrics -->
            <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase tracking-wider text-outline block mb-2">Voltage</span>
                <div class="text-3xl font-bold tracking-tight text-on-surface">401.2 <span class="text-sm font-medium text-outline">V</span></div>
                <div class="w-full bg-surface-container-low h-1 mt-4 rounded-full overflow-hidden">
                    <div class="bg-primary w-4/5 h-full"></div>
                </div>
            </div>
            
            <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase tracking-wider text-outline block mb-2">Current</span>
                <div class="text-3xl font-bold tracking-tight text-on-surface">15.8 <span class="text-sm font-medium text-outline">A</span></div>
                <div class="w-full bg-surface-container-low h-1 mt-4 rounded-full overflow-hidden">
                    <div class="bg-tertiary w-3/5 h-full"></div>
                </div>
            </div>
            
            <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm">
                <span class="text-[10px] font-bold uppercase tracking-wider text-outline block mb-2">Power Factor</span>
                <div class="text-3xl font-bold tracking-tight text-on-surface">0.94</div>
                <div class="mt-4 flex items-center gap-1 text-secondary text-[10px] font-bold">
                    <span class="material-symbols-outlined text-xs" data-icon="check_circle">check_circle</span>
                    OPTIMAL RANGE
                </div>
            </div>
        </div>

        <!-- Power History Chart Section -->
        <div class="bg-surface-container-lowest p-8 rounded-lg shadow-sm mb-8 relative overflow-hidden">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-on-surface">Power History</h2>
                    <p class="text-xs text-on-surface-variant">Active Load (kW) over last 12 hours</p>
                </div>
                <div class="flex gap-4">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-primary rounded-sm"></span>
                        <span class="text-xs font-bold text-on-surface">Active Power</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-secondary-container rounded-sm border border-secondary"></span>
                        <span class="text-xs font-bold text-on-surface">Baseline</span>
                    </div>
                </div>
            </div>

            <!-- Simulated Technical Chart -->
            <div class="h-[300px] w-full relative technical-grid border border-outline-variant/20">
                <!-- Y-Axis Labels -->
                <div class="absolute left-[-30px] top-0 bottom-0 flex flex-col justify-between text-[10px] font-bold text-outline py-2">
                    <span>50.0</span>
                    <span>37.5</span>
                    <span>25.0</span>
                    <span>12.5</span>
                    <span>0.0</span>
                </div>
                
                <!-- Chart Lines (Visual Representation) -->
                <div class="absolute inset-0 flex items-end">
                    <!-- Baseline -->
                    <div class="absolute bottom-[20%] w-full border-t border-dashed border-outline-variant"></div>
                    
                    <!-- Area/Line Chart -->
                    <div class="w-full h-full chart-line bg-gradient-to-t from-primary/10 to-primary/40 relative">
                        <svg class="absolute inset-0 w-full h-full" preserveaspectratio="none" viewbox="0 0 100 100">
                            <polyline fill="none" points="0,80 10,75 20,85 30,60 40,65 50,40 60,45 70,20 80,25 90,10 100,15" stroke="#00628c" stroke-width="2" vector-effect="non-scaling-stroke"></polyline>
                        </svg>
                    </div>
                </div>

                <!-- Tooltip Hover Simulation -->
                <div class="absolute left-[70%] top-[20%] -translate-x-1/2">
                    <div class="bg-on-surface text-surface text-[10px] p-2 rounded shadow-xl backdrop-blur-md bg-opacity-90">
                        <div class="font-bold border-b border-surface/20 pb-1 mb-1">14:45:02</div>
                        <div>Power: 42.8 kW</div>
                        <div>Load: 85.6%</div>
                    </div>
                    <div class="w-px h-full absolute top-[100%] left-1/2 bg-primary"></div>
                </div>
            </div>

            <!-- X-Axis Labels -->
            <div class="flex justify-between mt-4 text-[10px] font-bold text-outline uppercase tracking-widest px-2">
                <span>06:00</span>
                <span>08:00</span>
                <span>10:00</span>
                <span>12:00</span>
                <span>14:00</span>
                <span>16:00</span>
                <span>18:00</span>
            </div>
        </div>

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
                        <tr class="border-b border-surface-container-low hover:bg-surface-container-low transition-colors">
                            <td class="px-8 py-4 font-mono text-xs">2023-10-24 14:55:01</td>
                            <td class="px-4 py-4 text-right font-bold">42.82</td>
                            <td class="px-4 py-4 text-right">401.2</td>
                            <td class="px-4 py-4 text-right">15.8</td>
                            <td class="px-4 py-4 text-center">
                                <span class="bg-secondary-container text-on-secondary-container px-2 py-1 rounded-full text-[10px] font-bold uppercase">Optimal</span>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <button class="text-primary hover:underline text-xs font-bold">Details</button>
                            </td>
                        </tr>
                        <tr class="border-b border-surface-container-low hover:bg-surface-container-low transition-colors">
                            <td class="px-8 py-4 font-mono text-xs">2023-10-24 14:54:56</td>
                            <td class="px-4 py-4 text-right font-bold">42.79</td>
                            <td class="px-4 py-4 text-right">401.1</td>
                            <td class="px-4 py-4 text-right">15.7</td>
                            <td class="px-4 py-4 text-center">
                                <span class="bg-secondary-container text-on-secondary-container px-2 py-1 rounded-full text-[10px] font-bold uppercase">Optimal</span>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <button class="text-primary hover:underline text-xs font-bold">Details</button>
                            </td>
                        </tr>
                        <tr class="border-b border-surface-container-low hover:bg-surface-container-low transition-colors">
                            <td class="px-8 py-4 font-mono text-xs">2023-10-24 14:54:51</td>
                            <td class="px-4 py-4 text-right font-bold">45.12</td>
                            <td class="px-4 py-4 text-right">400.9</td>
                            <td class="px-4 py-4 text-right">16.4</td>
                            <td class="px-4 py-4 text-center">
                                <span class="bg-tertiary-container/20 text-tertiary px-2 py-1 rounded-full text-[10px] font-bold uppercase">Spike</span>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <button class="text-primary hover:underline text-xs font-bold">Details</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-8 py-4 font-mono text-xs">2023-10-24 14:54:46</td>
                            <td class="px-4 py-4 text-right font-bold">42.80</td>
                            <td class="px-4 py-4 text-right">401.2</td>
                            <td class="px-4 py-4 text-right">15.8</td>
                            <td class="px-4 py-4 text-center">
                                <span class="bg-secondary-container text-on-secondary-container px-2 py-1 rounded-full text-[10px] font-bold uppercase">Optimal</span>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <button class="text-primary hover:underline text-xs font-bold">Details</button>
                            </td>
                        </tr>
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
