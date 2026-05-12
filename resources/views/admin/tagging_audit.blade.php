@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-8 min-h-screen bg-surface">
    <div class="p-4 md:p-6 max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-xl font-black tracking-tight text-on-surface uppercase">Tagging Audit Logs</h1>
                <p class="text-on-surface-variant text-[10px] mt-0.5">Forensic trail of manual historian tagging operations.</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-surface-container-lowest p-4 rounded border border-surface-container shadow-sm mb-6">
            <form action="{{ route('analytics.tagging-audit') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Action</label>
                    <select name="action" class="w-full bg-surface-container-low text-xs p-2 rounded font-bold outline-none">
                        <option value="">All Actions</option>
                        <option value="CREATE" {{ request('action') == 'CREATE' ? 'selected' : '' }}>CREATE</option>
                        <option value="EDIT" {{ request('action') == 'EDIT' ? 'selected' : '' }}>EDIT</option>
                        <option value="DELETE" {{ request('action') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                    </select>
                </div>
                <div>
                    <label class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">User</label>
                    <select name="user_id" class="w-full bg-surface-container-low text-xs p-2 rounded font-bold outline-none">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[8px] font-black text-outline uppercase tracking-widest block mb-1">Tag Type</label>
                    <select name="tag_type" class="w-full bg-surface-container-low text-xs p-2 rounded font-bold outline-none">
                        <option value="">All Types</option>
                        <option value="start" {{ request('tag_type') == 'start' ? 'selected' : '' }}>START</option>
                        <option value="melting" {{ request('tag_type') == 'melting' ? 'selected' : '' }}>MELTING</option>
                        <option value="idle" {{ request('tag_type') == 'idle' ? 'selected' : '' }}>IDLE</option>
                        <option value="test" {{ request('tag_type') == 'test' ? 'selected' : '' }}>TEST</option>
                        <option value="pour" {{ request('tag_type') == 'pour' ? 'selected' : '' }}>POUR</option>
                        <option value="end" {{ request('tag_type') == 'end' ? 'selected' : '' }}>END</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-primary text-white text-[10px] font-black py-2 rounded uppercase tracking-widest">Filter</button>
                    <a href="{{ route('analytics.tagging-audit') }}" class="flex-1 bg-surface-container text-outline text-[10px] font-black py-2 rounded text-center uppercase tracking-widest border border-outline/10">Reset</a>
                </div>
            </form>
        </div>

        <div class="bg-surface-container-lowest rounded border border-surface-container shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead>
                        <tr class="bg-surface-container-low font-black text-on-surface-variant uppercase tracking-widest">
                            <th class="px-4 py-3">Timestamp (WIB)</th>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3">Tag Type</th>
                            <th class="px-4 py-3">Reason / Revision Notes</th>
                            <th class="px-4 py-3">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @foreach($logs as $log)
                        <tr class="hover:bg-surface-container-low/30 transition-colors">
                            <td class="px-4 py-3 font-mono">{{ $log->event_at->format('d M Y H:i:s') }}</td>
                            <td class="px-4 py-3 font-bold">{{ $log->user->name }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest 
                                    @if($log->action == 'CREATE') bg-secondary-container text-on-secondary-container 
                                    @elseif($log->action == 'EDIT') bg-tertiary-container text-on-tertiary-container 
                                    @elseif($log->action == 'DELETE') bg-error-container text-error @endif">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3 uppercase font-bold">{{ $log->tag_type }}</td>
                            <td class="px-4 py-3 text-outline italic">{{ $log->reason ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <details class="cursor-pointer">
                                    <summary class="text-primary font-bold hover:underline">View JSON</summary>
                                    <div class="mt-2 p-2 bg-slate-900 text-slate-100 rounded text-[9px] font-mono max-w-xs overflow-auto">
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
            <div class="p-4 border-t border-surface-container-low">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>
</main>
@endsection
