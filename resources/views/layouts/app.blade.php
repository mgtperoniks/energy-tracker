<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Energy Tracker | Industrial Energy Monitoring</title>
    <script src="{{ asset('assets/js/tailwind.js') }}"></script>
    <link href="{{ asset('assets/css/local-fonts.css') }}" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "secondary-fixed": "#85faac",
                        "on-surface-variant": "#3f484f",
                        "surface-dim": "#d8dadd",
                        "surface-container-highest": "#e0e3e6",
                        "on-tertiary-fixed-variant": "#653e00",
                        "on-background": "#191c1e",
                        "primary-fixed-dim": "#86ceff",
                        "on-secondary": "#ffffff",
                        "surface-variant": "#e0e3e6",
                        "inverse-on-surface": "#eff1f4",
                        "inverse-primary": "#86ceff",
                        "secondary-fixed-dim": "#68dd92",
                        "surface-tint": "#00658f",
                        "surface-container-lowest": "#ffffff",
                        "tertiary-fixed": "#ffddb8",
                        "on-tertiary-fixed": "#2a1700",
                        "on-error-container": "#93000a",
                        "surface-container-low": "#f2f4f7",
                        "primary-fixed": "#c8e6ff",
                        "secondary": "#006d3b",
                        "inverse-surface": "#2d3133",
                        "surface-bright": "#f7f9fc",
                        "on-secondary-fixed": "#00210e",
                        "surface-container-high": "#e6e8eb",
                        "primary-container": "#007caf",
                        "on-tertiary": "#ffffff",
                        "error": "#ba1a1a",
                        "outline-variant": "#bec8d0",
                        "surface-container": "#eceef1",
                        "on-secondary-fixed-variant": "#00522b",
                        "background": "#f7f9fc",
                        "outline": "#6f7880",
                        "tertiary-container": "#a36700",
                        "surface": "#f7f9fc",
                        "secondary-container": "#85faac",
                        "on-primary-container": "#fcfcff",
                        "tertiary": "#825100",
                        "on-surface": "#191c1e",
                        "on-secondary-container": "#00743f",
                        "on-primary-fixed": "#001e2e",
                        "primary": "#00628c",
                        "on-error": "#ffffff",
                        "error-container": "#ffdad6",
                        "on-primary-fixed-variant": "#004c6d",
                        "on-tertiary-container": "#fffbff",
                        "on-primary": "#ffffff",
                        "tertiary-fixed-dim": "#ffb95f"
                    },
                    fontFamily: {
                        "headline": ["Inter"],
                        "body": ["Inter"],
                        "label": ["Inter"],
                        "mono": ["JetBrains Mono"]
                    },
                    borderRadius: {"DEFAULT": "0.125rem", "lg": "0.25rem", "xl": "0.5rem", "full": "0.75rem"},
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .mono-value { font-family: 'JetBrains Mono', monospace; }
        .technical-grid {
            background-image: linear-gradient(to right, rgba(190, 200, 208, 0.1) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(190, 200, 208, 0.1) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        .chart-line {
            clip-path: polygon(0 80%, 10% 75%, 20% 85%, 30% 60%, 40% 65%, 50% 40%, 60% 45%, 70% 20%, 80% 25%, 90% 10%, 100% 15%, 100% 100%, 0% 100%);
        }
        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-surface text-on-surface antialiased font-body selection:bg-primary-container selection:text-white">

<!-- TopNavBar -->
<header class="fixed top-0 w-full z-50 bg-gradient-to-r from-sky-700 to-sky-900 dark:from-sky-900 dark:to-slate-950 text-white shadow-lg flex items-center justify-between px-6 h-16 transition-all antialiased">
    <div class="flex items-center gap-4 md:gap-8">
        <span class="text-xl font-bold tracking-tight uppercase"><a href="{{ route('dashboard') }}">Energy Tracker</a></span>
    </div>
    <div class="flex items-center gap-4">
        <div class="relative group hidden sm:block border-l border-white/10 pl-4">
            <div class="absolute inset-y-0 left-4 pl-3 flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-sky-100/50 text-sm">search</span>
            </div>
            <input class="bg-white/10 border-none text-white text-sm rounded-md pl-10 pr-4 py-2 w-48 lg:w-64 focus:ring-1 focus:ring-white/30 placeholder-sky-100/50 outline-none" placeholder="Global Search..." type="text"/>
        </div>
        <!-- NOTIFICATION BELL -->
        <div class="relative flex items-center" id="notification-root">
            <button onclick="toggleNotificationDropdown()" class="p-2 hover:bg-white/10 rounded-full transition-colors active:opacity-80 flex relative" title="Notifications">
                <span class="material-symbols-outlined text-white">notifications</span>
                <span id="notif-badge" class="hidden absolute top-2 right-2 w-4 h-4 bg-red-600 text-[9px] font-black flex items-center justify-center rounded-full border border-sky-800">0</span>
            </button>
            
            <!-- DROPDOWN PANEL -->
            <div id="notif-dropdown" class="hidden absolute right-0 top-12 w-80 bg-surface-container-lowest rounded-xl shadow-2xl border border-outline/10 overflow-hidden text-on-surface z-[100]">
                <div class="px-4 py-3 bg-surface-container-low border-b border-outline/10 flex justify-between items-center">
                    <span class="text-xs font-black uppercase tracking-widest">Notifications</span>
                    <button onclick="markAllAsRead()" class="text-[10px] font-bold text-primary hover:underline">Mark all as read</button>
                </div>
                
                <div id="notif-items" class="max-h-96 overflow-y-auto custom-scrollbar">
                    <div class="px-6 py-10 text-center text-outline italic text-xs">Loading...</div>
                </div>
                
                <div class="bg-surface-container-low p-2 text-center border-t border-outline/10">
                    <a href="{{ route('analytics.audit') }}" class="text-[10px] font-bold text-outline hover:text-primary transition-colors uppercase tracking-widest">View All Audit Logs</a>
                </div>
            </div>
        </div>
        <button class="p-2 hover:bg-white/10 rounded-full transition-colors active:opacity-80 hidden md:flex" title="Settings">
            <span class="material-symbols-outlined text-white" data-icon="settings">settings</span>
        </button>
        <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="p-2 hover:bg-white/10 rounded-full transition-colors active:opacity-80 flex items-center justify-center" title="Logout">
                <span class="material-symbols-outlined text-white">account_circle</span>
            </button>
        </form>
    </div>
</header>

<!-- SideNavBar -->
<aside class="fixed left-0 top-16 h-[calc(100vh-64px)] w-64 bg-slate-50 dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 hidden md:flex flex-col py-4 z-40">
    <div class="px-6 mb-6">
        <h2 class="text-sky-700 dark:text-sky-400 font-bold uppercase tracking-widest text-[10px]">Industrial Site A</h2>
        <div class="flex items-center gap-2 mt-1">
            <span class="w-2 h-2 rounded-full bg-secondary"></span>
            <span class="text-slate-500 dark:text-slate-400 text-xs font-medium">Active Monitor</span>
        </div>
    </div>
    
    @php
        $currentRouteName = Route::currentRouteName();
    @endphp

    <nav class="flex-1 px-3 space-y-1 overflow-y-auto custom-scrollbar pb-4">
        <!-- 1. OVERVIEW -->
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all @if($currentRouteName == 'dashboard') text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif" href="{{ route('dashboard') }}">
            <span class="material-symbols-outlined">dashboard</span>
            <span>Overview</span>
        </a>

        <!-- 2. MONITORING -->
        <div class="pt-4 pb-1">
            <p class="px-4 text-[10px] font-bold tracking-wider text-slate-400 dark:text-slate-500 uppercase">Monitoring</p>
        </div>
        
        @php
            $sidebarMeters = \App\Models\Machine::orderBy('code')->get();
            $isMonitoringOpen = Str::startsWith($currentRouteName, 'monitoring.');
        @endphp

        <details class="group" @if($isMonitoringOpen) open @endif>
            <summary class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all cursor-pointer list-none @if(Str::startsWith($currentRouteName, 'monitoring.meters')) text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
                <span class="material-symbols-outlined shrink-0">electric_meter</span>
                <span class="flex-1 truncate">Power Meters</span>
                <span class="material-symbols-outlined transform transition-transform group-open:rotate-180 text-sm shrink-0">expand_more</span>
            </summary>
            <div class="mt-1 flex flex-col gap-y-1 border-l-2 border-slate-200 dark:border-slate-700 ml-6 pl-2 py-2 max-h-[320px] overflow-y-auto custom-scrollbar">
                @foreach($sidebarMeters as $meter)
                    <a href="{{ route('monitoring.meters', ['id' => $meter->id]) }}" 
                       class="flex items-center text-[11px] px-3 py-2 rounded-md hover:bg-sky-50 dark:hover:bg-sky-900/40 transition-colors @if($currentRouteName == 'monitoring.meters' && request()->route('id') == $meter->id) text-sky-700 dark:text-sky-400 font-bold bg-sky-100/50 dark:bg-sky-900/30 @else text-slate-600 dark:text-slate-400 font-medium @endif">
                        <span class="font-bold text-sky-700 dark:text-sky-400 shrink-0 uppercase">{{ $meter->code }}</span>
                        <span class="mx-1 text-slate-300 dark:text-slate-600 shrink-0">-</span>
                        <span class="truncate">{{ $meter->name }}</span>
                    </a>
                @endforeach
            </div>
        </details>
        
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all @if($currentRouteName == 'monitoring.environmental') text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif" href="{{ route('monitoring.environmental') }}">
            <span class="material-symbols-outlined">thermostat</span>
            <span>Environmental</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all @if($currentRouteName == 'monitoring.health') text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif" href="{{ route('monitoring.health') }}">
            <span class="material-symbols-outlined">monitor_heart</span>
            <span>System Health</span>
        </a>

        <!-- 3. ANALYTICS -->
        <div class="pt-4 pb-1">
            <p class="px-4 text-[10px] font-bold tracking-wider text-slate-400 dark:text-slate-500 uppercase">Analytics</p>
        </div>
        <details class="group" @if(Str::startsWith($currentRouteName, 'analytics.')) open @endif>
            <summary class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all cursor-pointer list-none @if(Str::startsWith($currentRouteName, 'analytics.')) text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
                <span class="material-symbols-outlined shrink-0">analytics</span>
                <span class="flex-1 truncate">Reports</span>
                <span class="material-symbols-outlined transform transition-transform group-open:rotate-180 text-sm shrink-0">expand_more</span>
            </summary>
            <div class="mt-1 flex flex-col gap-y-1 border-l-2 border-slate-200 dark:border-slate-700 ml-6 pl-2 py-2">
                <a href="{{ route('analytics.operational') }}" class="flex items-center text-[11px] px-3 py-2 rounded-md hover:bg-sky-50 dark:hover:bg-sky-900/40 transition-colors @if($currentRouteName == 'analytics.operational') text-sky-700 dark:text-sky-400 font-bold bg-sky-100/50 dark:bg-sky-900/30 @else text-slate-600 dark:text-slate-400 font-medium @endif">
                    Operational Report
                </a>
                <a href="{{ route('analytics.accounting') }}" class="flex items-center text-[11px] px-3 py-2 rounded-md hover:bg-sky-50 dark:hover:bg-sky-900/40 transition-colors @if($currentRouteName == 'analytics.accounting') text-sky-700 dark:text-sky-400 font-bold bg-sky-100/50 dark:bg-sky-900/30 @else text-slate-600 dark:text-slate-400 font-medium @endif">
                    Accounting Report
                </a>
                <a href="{{ route('analytics.audit') }}" class="flex items-center text-[11px] px-3 py-2 rounded-md hover:bg-sky-50 dark:hover:bg-sky-900/40 transition-colors @if($currentRouteName == 'analytics.audit') text-sky-700 dark:text-sky-400 font-bold bg-sky-100/50 dark:bg-sky-900/30 @else text-slate-600 dark:text-slate-400 font-medium @endif">
                    System Audit Trail
                </a>
                <a href="{{ route('analytics.tagging-audit') }}" class="flex items-center text-[11px] px-3 py-2 rounded-md hover:bg-sky-50 dark:hover:bg-sky-900/40 transition-colors @if($currentRouteName == 'analytics.tagging-audit') text-sky-700 dark:text-sky-400 font-bold bg-sky-100/50 dark:bg-sky-900/30 @else text-slate-600 dark:text-slate-400 font-medium @endif">
                    Tagging Audit Logs
                </a>
            </div>
        </details>

        <!-- 4. ASSETS -->
        <div class="pt-4 pb-1">
            <p class="px-4 text-[10px] font-bold tracking-wider text-slate-400 dark:text-slate-500 uppercase">Assets</p>
        </div>
        <details class="group" @if(Str::startsWith($currentRouteName, 'assets.')) open @endif>
            <summary class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all cursor-pointer list-none @if(Str::startsWith($currentRouteName, 'assets.')) text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
                <span class="material-symbols-outlined shrink-0">inventory_2</span>
                <span class="flex-1 truncate">Asset Management</span>
                <span class="material-symbols-outlined transform transition-transform group-open:rotate-180 text-sm shrink-0">expand_more</span>
            </summary>
            <div class="mt-1 flex flex-col gap-y-1 border-l-2 border-slate-200 dark:border-slate-700 ml-6 pl-2 py-2">
                <a href="{{ route('assets.machines') }}" class="flex items-center text-[11px] px-3 py-2 rounded-md hover:bg-sky-50 dark:hover:bg-sky-900/40 transition-colors @if($currentRouteName == 'assets.machines') text-sky-700 dark:text-sky-400 font-bold bg-sky-100/50 dark:bg-sky-900/30 @else text-slate-600 dark:text-slate-400 font-medium @endif">
                    Machines
                </a>
                <a href="{{ route('assets.devices') }}" class="flex items-center text-[11px] px-3 py-2 rounded-md hover:bg-sky-50 dark:hover:bg-sky-900/40 transition-colors @if($currentRouteName == 'assets.devices') text-sky-700 dark:text-sky-400 font-bold bg-sky-100/50 dark:bg-sky-900/30 @else text-slate-600 dark:text-slate-400 font-medium @endif">
                    Power Meters
                </a>
                <a href="{{ route('assets.departments') }}" class="flex items-center text-[11px] px-3 py-2 rounded-md hover:bg-sky-50 dark:hover:bg-sky-900/40 transition-colors @if($currentRouteName == 'assets.departments') text-sky-700 dark:text-sky-400 font-bold bg-sky-100/50 dark:bg-sky-900/30 @else text-slate-600 dark:text-slate-400 font-medium @endif">
                    Departments
                </a>
            </div>
        </details>

        <!-- 5. ADMINISTRATION -->
        <div class="pt-4 pb-1">
            <p class="px-4 text-[10px] font-bold tracking-wider text-slate-400 dark:text-slate-500 uppercase">Administration</p>
        </div>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all @if($currentRouteName == 'admin.tariffs') text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif" href="{{ route('admin.tariffs') }}">
            <span class="material-symbols-outlined">payments</span>
            <span>Tariff Management</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all @if($currentRouteName == 'admin.thresholds') text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif" href="{{ route('admin.thresholds') }}">
            <span class="material-symbols-outlined">tune</span>
            <span>Threshold Settings</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all @if($currentRouteName == 'admin.device-config') text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif" href="{{ route('admin.device-config') }}">
            <span class="material-symbols-outlined">settings_input_component</span>
            <span>Device Config</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all @if($currentRouteName == 'admin.poller-logs') text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif" href="{{ route('admin.poller-logs') }}">
            <span class="material-symbols-outlined">list_alt</span>
            <span>Poller Logs</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all @if($currentRouteName == 'admin.reset-history') text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif" href="{{ route('admin.reset-history') }}">
            <span class="material-symbols-outlined">history</span>
            <span>Reset History</span>
        </a>

        <div class="pt-4 pb-1">
            <p class="px-4 text-[10px] font-bold tracking-wider text-slate-400 dark:text-slate-500 uppercase">System Deployment</p>
        </div>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all @if($currentRouteName == 'admin.deployment-health') text-sky-700 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-900/20 @else text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-800 @endif" href="{{ route('admin.deployment-health') }}">
            <span class="material-symbols-outlined">health_and_safety</span>
            <span>Deployment Health</span>
        </a>

    </nav>

</aside>

<!-- Main Content Area -->
@yield('content')

<!-- Mobile Navigation Placeholder -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white shadow-[0_-4px_10px_rgba(0,0,0,0.05)] flex justify-around items-center z-50">
    <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 @if($currentRouteName == 'dashboard') text-primary @else text-outline @endif">
        <span class="material-symbols-outlined">dashboard</span>
        <span class="text-[10px] font-bold">Home</span>
    </a>
    <a href="{{ route('monitoring.meters') }}" class="flex flex-col items-center gap-1 @if(Str::startsWith($currentRouteName, 'monitoring.meters')) text-primary @else text-outline @endif">
        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">electric_meter</span>
        <span class="text-[10px] font-bold">Meters</span>
    </a>
    <a href="{{ route('analytics.operational') }}" class="flex flex-col items-center gap-1 @if(Str::startsWith($currentRouteName, 'analytics.')) text-primary @else text-outline @endif">
        <span class="material-symbols-outlined">analytics</span>
        <span class="text-[10px] font-bold">Reports</span>
    </a>
    <a href="#" class="flex flex-col items-center gap-1 text-outline">
        <span class="material-symbols-outlined">settings</span>
        <span class="text-[10px] font-bold">Settings</span>
    </a>
</nav>

<script>
    function toggleNotificationDropdown() {
        const dd = document.getElementById('notif-dropdown');
        dd.classList.toggle('hidden');
        if (!dd.classList.contains('hidden')) {
            loadNotifications();
        }
    }

    function loadNotifications() {
        fetch("{{ route('api.notifications.latest') }}")
            .then(res => res.json())
            .then(data => {
                document.getElementById('notif-items').innerHTML = data.html;
                updateBadge(data.count);
            });
    }

    function updateBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (count > 0) {
            badge.innerText = count > 9 ? '9+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function markReadAndGo(id, url) {
        fetch(`/api/notifications/${id}/read`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(() => {
            window.location.href = url;
        });
    }

    function markAllAsRead() {
        fetch("{{ route('api.notifications.read-all') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(() => {
            loadNotifications();
            updateBadge(0);
        });
    }

    // Polling Unread Count every 30s
    setInterval(() => {
        fetch("{{ route('api.notifications.count') }}")
            .then(res => res.json())
            .then(data => updateBadge(data.count));
    }, 30000);

    // Initial check
    fetch("{{ route('api.notifications.count') }}")
        .then(res => res.json())
        .then(data => updateBadge(data.count));

    // Close dropdown on click outside
    window.addEventListener('click', function(e) {
        if (!document.getElementById('notification-root').contains(e.target)) {
            document.getElementById('notif-dropdown').classList.add('hidden');
        }
    });
</script>
</body>
</html>
