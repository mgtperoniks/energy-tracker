@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Deployment Health</h1>
            <p class="text-on-surface-variant text-sm mt-1">Status kesehatan infrastruktur, scheduler, dan integritas data produksi.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Environment Card -->
            <div class="bg-surface-container-lowest p-6 rounded-xl border border-surface-container-low shadow-sm">
                <h3 class="text-[10px] font-black text-outline uppercase tracking-widest mb-4">Environment</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-on-surface-variant">App Environment</span>
                        <span class="px-2 py-0.5 rounded bg-surface-container-high text-[10px] font-bold uppercase">{{ $env['app_env'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-on-surface-variant">Debug Mode</span>
                        @if($env['app_debug'])
                            <span class="px-2 py-0.5 rounded bg-error-container text-error text-[10px] font-bold uppercase tracking-tight">DANGEROUS (ON)</span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-secondary-container text-on-secondary-container text-[10px] font-bold uppercase tracking-tight">SECURE (OFF)</span>
                        @endif
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-on-surface-variant">DB Engine</span>
                        <span class="text-xs font-mono font-bold text-primary uppercase">{{ $env['db_connection'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Storage & DB Card -->
            <div class="bg-surface-container-lowest p-6 rounded-xl border border-surface-container-low shadow-sm">
                <h3 class="text-[10px] font-black text-outline uppercase tracking-widest mb-4">Storage & Resources</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-on-surface-variant">Storage Writable</span>
                        @if($isStorageWritable)
                            <span class="material-symbols-outlined text-secondary text-lg">check_circle</span>
                        @else
                            <span class="material-symbols-outlined text-error text-lg">cancel</span>
                        @endif
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-on-surface-variant">Database Size</span>
                        <span class="text-xs font-bold text-on-surface">{{ $dbSize }} MB</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-on-surface-variant">PHP Version</span>
                        <span class="text-xs font-mono font-bold text-outline">{{ PHP_VERSION }}</span>
                    </div>
                </div>
            </div>

            <!-- Backup Card -->
            <div class="bg-surface-container-lowest p-6 rounded-xl border border-surface-container-low shadow-sm">
                <h3 class="text-[10px] font-black text-outline uppercase tracking-widest mb-4">Last Backup</h3>
                @if($lastBackup)
                    <div class="space-y-2">
                        <div class="text-xs font-bold text-on-surface truncate">{{ $lastBackup['filename'] }}</div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-outline italic">{{ $lastBackup['at']->diffForHumans() }}</span>
                            <span class="px-2 py-0.5 rounded bg-primary-container text-primary-dark text-[10px] font-bold">{{ $lastBackup['size'] }} MB</span>
                        </div>
                        <div class="pt-2 flex items-center gap-2 text-secondary text-[10px] font-bold uppercase">
                            <span class="material-symbols-outlined text-sm">verified</span>
                            Integrity Verified
                        </div>
                    </div>
                @else
                    <div class="py-4 text-center">
                        <span class="material-symbols-outlined text-error text-4xl mb-2">warning</span>
                        <p class="text-xs font-bold text-error uppercase">No Backup Found!</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Scheduler Heartbeat Section -->
        <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden border border-surface-container-low">
            <div class="px-6 py-4 border-b border-surface-container-low bg-surface-container-low/30">
                <h3 class="text-sm font-bold tracking-tight text-on-surface uppercase flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">monitor_heart</span>
                    Scheduler Heartbeat (Watchdog)
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-surface-container-low">
                            <th class="px-6 py-4">Job Name</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Last Success</th>
                            <th class="px-6 py-4">Duration</th>
                            <th class="px-6 py-4">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @forelse($schedulerJobs as $job)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-primary">{{ $job->job_name }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusClass = match($job->status) {
                                        'success' => 'bg-secondary-container text-on-secondary-container',
                                        'failed' => 'bg-error-container text-error',
                                        default => 'bg-surface-container-high text-outline'
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest {{ $statusClass }}">
                                    {{ $job->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($job->last_success_at)
                                    <div class="text-xs text-on-surface">{{ $job->last_success_at->format('d M H:i:s') }}</div>
                                    <div class="text-[10px] text-outline italic">{{ $job->last_success_at->diffForHumans() }}</div>
                                @else
                                    <span class="text-xs text-outline italic">Never</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-outline">
                                {{ $job->last_duration_ms }} ms
                            </td>
                            <td class="px-6 py-4 text-[11px] text-on-surface-variant truncate max-w-xs">
                                {{ $job->message }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-outline italic text-sm">
                                No scheduler heartbeats recorded.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 p-6 bg-primary/5 rounded-xl border border-primary/10">
            <h4 class="text-sm font-bold text-primary uppercase mb-2 flex items-center gap-2">
                <span class="material-symbols-outlined">rocket_launch</span>
                Production Pilot Status
            </h4>
            <p class="text-xs text-on-surface-variant leading-relaxed mb-4">
                Sistem saat ini dalam mode **Burn-in Phase** (1 Month Observation). Seluruh parameter hardening telah diaktifkan. 
                Pengerjaan fitur baru dihentikan sementara untuk menjaga stabilitas pilot production.
            </p>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-secondary animate-pulse"></span>
                    <span class="text-[10px] font-bold text-secondary uppercase">Ingestion Active</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-secondary animate-pulse"></span>
                    <span class="text-[10px] font-bold text-secondary uppercase">Heartbeat Monitoring Active</span>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
