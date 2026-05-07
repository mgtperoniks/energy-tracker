@forelse($notifications as $item)
    <div class="px-4 py-3 hover:bg-surface-container-low transition-colors border-b border-outline/10 cursor-pointer" onclick="markReadAndGo({{ $item->id }}, '{{ route('analytics.audit', ['device_id' => $item->auditLog->device_id, 'status' => 'open']) }}')">
        <div class="flex gap-3">
            <div class="w-2 h-2 mt-1.5 rounded-full flex-shrink-0 {{ 
                $item->severity == 'CRITICAL' ? 'bg-error' : 
                ($item->severity == 'ERROR' ? 'bg-error-container' : 'bg-tertiary') 
            }}"></div>
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start">
                    <span class="text-[10px] font-black uppercase tracking-widest text-outline">{{ $item->auditLog->event_code }}</span>
                    <span class="text-[9px] text-outline-variant">{{ $item->created_at->diffForHumans(null, true) }}</span>
                </div>
                <p class="text-xs font-bold text-on-surface truncate">{{ $item->auditLog->device->name ?? 'System' }}</p>
                <p class="text-[10px] text-on-surface-variant line-clamp-2 mt-0.5">{{ $item->message }}</p>
            </div>
        </div>
    </div>
@empty
    <div class="px-6 py-10 text-center text-outline italic text-xs">
        No new notifications.
    </div>
@endforelse
