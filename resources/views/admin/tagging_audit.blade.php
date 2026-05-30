@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        
        <!-- HEADER & TITLE -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Tagging Audit Logs</h1>
                <p class="text-on-surface-variant text-sm mt-1">Forensic trail of manual historian tagging operations.</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm border border-surface-container-low mb-8">
            <form action="{{ route('analytics.tagging-audit') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Action</label>
                    <select name="action" class="w-full bg-surface border border-outline-variant rounded-md text-xs p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-bold">
                        <option value="">All Actions</option>
                        <option value="CREATE" {{ request('action') == 'CREATE' ? 'selected' : '' }}>CREATE</option>
                        <option value="EDIT" {{ request('action') == 'EDIT' ? 'selected' : '' }}>EDIT</option>
                        <option value="DELETE" {{ request('action') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">User</label>
                    <select name="user_id" class="w-full bg-surface border border-outline-variant rounded-md text-xs p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-bold">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-1">Tag Type</label>
                    <select name="tag_type" class="w-full bg-surface border border-outline-variant rounded-md text-xs p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-bold">
                        <option value="">All Types</option>
                        <option value="start" {{ request('tag_type') == 'start' ? 'selected' : '' }}>START</option>
                        <option value="melting" {{ request('tag_type') == 'melting' ? 'selected' : '' }}>MELTING</option>
                        <option value="idle" {{ request('tag_type') == 'idle' ? 'selected' : '' }}>IDLE</option>
                        <option value="test" {{ request('tag_type') == 'test' ? 'selected' : '' }}>TEST</option>
                        <option value="pour" {{ request('tag_type') == 'pour' ? 'selected' : '' }}>POUR</option>
                        <option value="end" {{ request('tag_type') == 'end' ? 'selected' : '' }}>END</option>
                    </select>
                </div>
                <div class="flex gap-2 h-[36px]">
                    <button type="submit" class="px-4 py-2 bg-primary text-on-primary font-bold rounded-md hover:bg-primary/90 transition-colors flex-1 flex items-center justify-center gap-2 text-xs uppercase tracking-wider">
                        <span class="material-symbols-outlined text-sm">filter_alt</span> Filter
                    </button>
                    <a href="{{ route('analytics.tagging-audit') }}" class="px-4 py-2 bg-surface-container-high text-on-surface font-bold rounded-md hover:bg-surface-container-highest transition-colors flex-1 flex items-center justify-center gap-2 text-xs uppercase tracking-wider border border-outline/10">
                        <span class="material-symbols-outlined text-sm">restart_alt</span> Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-surface-container-lowest rounded-lg shadow-sm border border-surface-container-low overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead>
                        <tr class="bg-surface-container-low font-black text-on-surface-variant uppercase tracking-widest border-b border-outline-variant/30 text-[10px]">
                            <th class="px-6 py-4">Timestamp (WIB)</th>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Action</th>
                            <th class="px-6 py-4">Tag Type</th>
                            <th class="px-6 py-4">Reason / Revision Notes</th>
                            <th class="px-6 py-4">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @foreach($logs as $log)
                        <tr class="hover:bg-surface-container-low/30 transition-colors">
                            <td class="px-6 py-4 font-mono">{{ $log->event_at->format('d M Y H:i:s') }}</td>
                            <td class="px-6 py-4 font-bold text-on-surface">{{ $log->user->name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest 
                                    @if($log->action == 'CREATE') bg-secondary-container text-on-secondary-container 
                                    @elseif($log->action == 'EDIT') bg-tertiary-container text-on-tertiary-container 
                                    @elseif($log->action == 'DELETE') bg-error-container text-error @endif">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 uppercase font-bold text-on-surface">{{ $log->tag_type }}</td>
                            <td class="px-6 py-4 text-outline italic">{{ $log->reason ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <details class="cursor-pointer">
                                    <summary class="text-primary font-bold hover:underline">View JSON</summary>
                                    <div class="mt-2 p-3 bg-slate-900 text-slate-100 rounded text-[9px] font-mono max-w-xs overflow-auto shadow-inner border border-slate-800">
                                        <div class="mb-2"><span class="text-sky-400 font-bold">OLD:</span> {{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</div>
                                        <div><span class="text-emerald-400 font-bold">NEW:</span> {{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</div>
                                    </div>
                                </details>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-surface-container-low">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>
</main>
@endsection
