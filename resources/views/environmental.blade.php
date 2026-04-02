@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 p-8 min-h-screen bg-background">
    <!-- A. FACILITY SUMMARY (Top Row) -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 mt-4">
        <div class="bg-surface-container-lowest p-6 rounded-lg border border-outline-variant/20 shadow-sm flex flex-col justify-between h-32">
            <span class="label-sm text-[10px] font-bold text-on-surface-variant tracking-widest uppercase">Avg Warehouse Temp</span>
            <div class="flex items-baseline gap-2">
                <span class="mono-value text-3xl font-bold text-primary">22.4</span>
                <span class="text-on-surface-variant font-medium">°C</span>
            </div>
            <div class="flex items-center gap-1 text-[10px] font-bold text-secondary">
                <span class="material-symbols-outlined text-xs">trending_flat</span>
                <span>OPTIMAL</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg border border-outline-variant/20 shadow-sm flex flex-col justify-between h-32">
            <span class="label-sm text-[10px] font-bold text-on-surface-variant tracking-widest uppercase">Avg Warehouse Humidity</span>
            <div class="flex items-baseline gap-2">
                <span class="mono-value text-3xl font-bold text-primary">45</span>
                <span class="text-on-surface-variant font-medium">%</span>
            </div>
            <div class="flex items-center gap-1 text-[10px] font-bold text-secondary">
                <span class="material-symbols-outlined text-xs">check_circle</span>
                <span>WITHIN RANGE</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg border border-outline-variant/20 shadow-sm flex flex-col justify-between h-32">
            <span class="label-sm text-[10px] font-bold text-on-surface-variant tracking-widest uppercase">Avg CNC Zone Temp</span>
            <div class="flex items-baseline gap-2">
                <span class="mono-value text-3xl font-bold text-primary">24.1</span>
                <span class="text-on-surface-variant font-medium">°C</span>
            </div>
            <div class="flex items-center gap-1 text-[10px] font-bold text-tertiary">
                <span class="material-symbols-outlined text-xs">trending_up</span>
                <span>+0.4°C TREND</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg border border-outline-variant/20 shadow-sm flex flex-col justify-between h-32">
            <span class="label-sm text-[10px] font-bold text-on-surface-variant tracking-widest uppercase">Avg Office Temp</span>
            <div class="flex items-baseline gap-2">
                <span class="mono-value text-3xl font-bold text-primary">21.8</span>
                <span class="text-on-surface-variant font-medium">°C</span>
            </div>
            <div class="flex items-center gap-1 text-[10px] font-bold text-secondary">
                <span class="material-symbols-outlined text-xs">horizontal_rule</span>
                <span>STABLE</span>
            </div>
        </div>
    </section>

    <!-- B. ZONE MONITORING (Main Section) -->
    <section class="mb-8">
        <h2 class="headline-sm text-lg font-bold mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">grid_view</span>
            Live Zone Monitoring
        </h2>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Zone Card: Warehouse A -->
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-lg p-6 relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="font-bold text-on-surface">Warehouse A</h3>
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-tighter">Zone ID: WH-01</p>
                    </div>
                    <span class="px-2 py-1 bg-secondary-container text-on-secondary-container text-[10px] font-black rounded-full">NORMAL</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase">Temp</p>
                        <div class="flex items-baseline">
                            <span class="mono-value text-4xl font-bold text-on-surface">22.4</span>
                            <span class="text-lg ml-1 text-on-surface-variant">°C</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase">Humidity</p>
                        <div class="flex items-baseline">
                            <span class="mono-value text-4xl font-bold text-on-surface">45</span>
                            <span class="text-lg ml-1 text-on-surface-variant">%</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-surface-variant">
                    <p class="text-[10px] font-bold text-on-surface-variant uppercase mb-2">Last 1h Trend</p>
                    <div class="h-10 w-full flex items-end gap-[2px]">
                        <div class="bg-primary/20 w-full h-[40%]"></div>
                        <div class="bg-primary/20 w-full h-[45%]"></div>
                        <div class="bg-primary/20 w-full h-[42%]"></div>
                        <div class="bg-primary/20 w-full h-[38%]"></div>
                        <div class="bg-primary/20 w-full h-[41%]"></div>
                        <div class="bg-primary/20 w-full h-[44%]"></div>
                        <div class="bg-primary/60 w-full h-[48%]"></div>
                        <div class="bg-primary/60 w-full h-[46%]"></div>
                        <div class="bg-primary/60 w-full h-[44%]"></div>
                        <div class="bg-primary w-full h-[43%]"></div>
                    </div>
                </div>
            </div>

            <!-- Zone Card: CNC Production -->
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-lg p-6 relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="font-bold text-on-surface">CNC Production</h3>
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-tighter">Zone ID: PR-04</p>
                    </div>
                    <span class="px-2 py-1 bg-tertiary-fixed text-on-tertiary-fixed text-[10px] font-black rounded-full">WARNING</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase">Temp</p>
                        <div class="flex items-baseline">
                            <span class="mono-value text-4xl font-bold text-tertiary">24.1</span>
                            <span class="text-lg ml-1 text-on-surface-variant">°C</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase">Humidity</p>
                        <div class="flex items-baseline">
                            <span class="mono-value text-4xl font-bold text-on-surface">38</span>
                            <span class="text-lg ml-1 text-on-surface-variant">%</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-surface-variant">
                    <p class="text-[10px] font-bold text-on-surface-variant uppercase mb-2">Last 1h Trend</p>
                    <div class="h-10 w-full flex items-end gap-[2px]">
                        <div class="bg-tertiary/20 w-full h-[60%]"></div>
                        <div class="bg-tertiary/20 w-full h-[65%]"></div>
                        <div class="bg-tertiary/20 w-full h-[70%]"></div>
                        <div class="bg-tertiary/20 w-full h-[68%]"></div>
                        <div class="bg-tertiary/20 w-full h-[72%]"></div>
                        <div class="bg-tertiary/20 w-full h-[75%]"></div>
                        <div class="bg-tertiary/60 w-full h-[78%]"></div>
                        <div class="bg-tertiary/60 w-full h-[80%]"></div>
                        <div class="bg-tertiary/60 w-full h-[82%]"></div>
                        <div class="bg-tertiary w-full h-[85%]"></div>
                    </div>
                </div>
            </div>

            <!-- Zone Card: Main Office -->
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-lg p-6 relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="font-bold text-on-surface">Main Office</h3>
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-tighter">Zone ID: OF-09</p>
                    </div>
                    <span class="px-2 py-1 bg-secondary-container text-on-secondary-container text-[10px] font-black rounded-full">NORMAL</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase">Temp</p>
                        <div class="flex items-baseline">
                            <span class="mono-value text-4xl font-bold text-on-surface">21.8</span>
                            <span class="text-lg ml-1 text-on-surface-variant">°C</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase">Humidity</p>
                        <div class="flex items-baseline">
                            <span class="mono-value text-4xl font-bold text-on-surface">42</span>
                            <span class="text-lg ml-1 text-on-surface-variant">%</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-surface-variant">
                    <p class="text-[10px] font-bold text-on-surface-variant uppercase mb-2">Last 1h Trend</p>
                    <div class="h-10 w-full flex items-end gap-[2px]">
                        <div class="bg-primary/20 w-full h-[30%]"></div>
                        <div class="bg-primary/20 w-full h-[32%]"></div>
                        <div class="bg-primary/20 w-full h-[31%]"></div>
                        <div class="bg-primary/20 w-full h-[33%]"></div>
                        <div class="bg-primary/20 w-full h-[34%]"></div>
                        <div class="bg-primary/20 w-full h-[32%]"></div>
                        <div class="bg-primary/60 w-full h-[31%]"></div>
                        <div class="bg-primary/60 w-full h-[30%]"></div>
                        <div class="bg-primary/60 w-full h-[32%]"></div>
                        <div class="bg-primary w-full h-[33%]"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- C. DETAILED TRENDS (Bottom Section) -->
    <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- Temperature History Chart Container -->
        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-sm uppercase tracking-wide">Temperature History (Last 24h)</h3>
                <div class="flex gap-4">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-[2px] bg-primary"></span>
                        <span class="text-[10px] font-bold text-on-surface-variant">WH A</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-[2px] bg-tertiary"></span>
                        <span class="text-[10px] font-bold text-on-surface-variant">CNC</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-[2px] bg-secondary"></span>
                        <span class="text-[10px] font-bold text-on-surface-variant">OFFICE</span>
                    </div>
                </div>
            </div>
            
            <div class="relative h-64 w-full border-l border-b border-outline-variant/40 flex flex-col justify-between">
                <!-- Grid Lines -->
                <div class="absolute inset-0 flex flex-col justify-between py-1 pointer-events-none">
                    <div class="border-t border-outline-variant/10 w-full flex justify-end"><span class="text-[9px] text-on-surface-variant pr-1">26°C</span></div>
                    <div class="border-t border-outline-variant/10 w-full flex justify-end"><span class="text-[9px] text-on-surface-variant pr-1">24°C</span></div>
                    <div class="border-t border-outline-variant/10 w-full flex justify-end"><span class="text-[9px] text-on-surface-variant pr-1">22°C</span></div>
                    <div class="border-t border-outline-variant/10 w-full flex justify-end"><span class="text-[9px] text-on-surface-variant pr-1">20°C</span></div>
                    <div class="border-t border-outline-variant/10 w-full flex justify-end"><span class="text-[9px] text-on-surface-variant pr-1">18°C</span></div>
                </div>
                
                <!-- SVG Chart Overlay (Mock) -->
                <svg class="absolute inset-0 w-full h-full" preserveaspectratio="none">
                    <!-- WH A Line -->
                    <polyline class="text-primary" fill="none" points="0,180 50,175 100,170 150,172 200,165 250,160 300,158 350,162 400,165 450,168 500,170" stroke="currentColor" stroke-width="2"></polyline>
                    <!-- CNC Line -->
                    <polyline class="text-tertiary" fill="none" points="0,150 50,145 100,135 150,130 200,125 250,120 300,115 350,118 400,122 450,125 500,120" stroke="currentColor" stroke-width="2"></polyline>
                    <!-- OFFICE Line -->
                    <polyline class="text-secondary" fill="none" points="0,200 50,205 100,210 150,208 200,205 250,200 300,195 350,192 400,195 450,198 500,200" stroke="currentColor" stroke-width="2"></polyline>
                </svg>

                <!-- Time Labels -->
                <div class="absolute -bottom-6 w-full flex justify-between px-2">
                    <span class="text-[9px] font-bold text-on-surface-variant">00:00</span>
                    <span class="text-[9px] font-bold text-on-surface-variant">06:00</span>
                    <span class="text-[9px] font-bold text-on-surface-variant">12:00</span>
                    <span class="text-[9px] font-bold text-on-surface-variant">18:00</span>
                    <span class="text-[9px] font-bold text-on-surface-variant">23:59</span>
                </div>
            </div>
        </div>

        <!-- Relative Humidity Chart Container -->
        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-sm uppercase tracking-wide">Relative Humidity (Last 24h)</h3>
                <div class="flex gap-4">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-[2px] bg-primary"></span>
                        <span class="text-[10px] font-bold text-on-surface-variant">WH A</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-[2px] bg-tertiary"></span>
                        <span class="text-[10px] font-bold text-on-surface-variant">CNC</span>
                    </div>
                </div>
            </div>
            
            <div class="relative h-64 w-full border-l border-b border-outline-variant/40 flex flex-col justify-between">
                <!-- Grid Lines -->
                <div class="absolute inset-0 flex flex-col justify-between py-1 pointer-events-none">
                    <div class="border-t border-outline-variant/10 w-full flex justify-end"><span class="text-[9px] text-on-surface-variant pr-1">60%</span></div>
                    <div class="border-t border-outline-variant/10 w-full flex justify-end"><span class="text-[9px] text-on-surface-variant pr-1">50%</span></div>
                    <div class="border-t border-outline-variant/10 w-full flex justify-end"><span class="text-[9px] text-on-surface-variant pr-1">40%</span></div>
                    <div class="border-t border-outline-variant/10 w-full flex justify-end"><span class="text-[9px] text-on-surface-variant pr-1">30%</span></div>
                    <div class="border-t border-outline-variant/10 w-full flex justify-end"><span class="text-[9px] text-on-surface-variant pr-1">20%</span></div>
                </div>
                
                <!-- SVG Chart Overlay (Mock) -->
                <svg class="absolute inset-0 w-full h-full" preserveaspectratio="none">
                    <!-- WH A Line -->
                    <polyline class="text-primary" fill="none" points="0,120 50,115 100,118 150,125 200,130 250,125 300,120 350,115 400,110 450,112 500,115" stroke="currentColor" stroke-width="2"></polyline>
                    <!-- CNC Line -->
                    <polyline class="text-tertiary" fill="none" points="0,180 50,185 100,190 150,200 200,210 250,215 300,220 350,215 400,210 450,205 500,200" stroke="currentColor" stroke-width="2"></polyline>
                </svg>

                <!-- Time Labels -->
                <div class="absolute -bottom-6 w-full flex justify-between px-2">
                    <span class="text-[9px] font-bold text-on-surface-variant">00:00</span>
                    <span class="text-[9px] font-bold text-on-surface-variant">06:00</span>
                    <span class="text-[9px] font-bold text-on-surface-variant">12:00</span>
                    <span class="text-[9px] font-bold text-on-surface-variant">18:00</span>
                    <span class="text-[9px] font-bold text-on-surface-variant">23:59</span>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
