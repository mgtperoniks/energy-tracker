@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Production Observations</h1>
                <p class="text-on-surface-variant text-sm mt-1">Daily operational validation & facility readiness logs.</p>
            </div>
            <a href="{{ route('admin.checklists.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-primary text-on-primary font-bold rounded-lg hover:bg-primary/90 transition-colors shadow-sm gap-2">
                <span class="material-symbols-outlined">add_task</span>
                New Observation
            </a>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline/10 overflow-hidden">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline/10">
                        <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-outline">Observation Date</th>
                        <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-outline">Inspector</th>
                        <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-outline">Status</th>
                        <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-outline">Metrics</th>
                        <th class="px-6 py-4 text-right text-xs font-black uppercase tracking-widest text-outline">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline/5">
                    @forelse($checklists as $cl)
                    <tr class="hover:bg-surface-container-low/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-on-surface">{{ $cl->check_date->format('d M Y') }}</div>
                            <div class="text-[10px] text-outline mt-0.5">{{ $cl->check_date->format('l') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container font-black text-xs">
                                    {{ substr($cl->inspector_name, 0, 1) }}
                                </div>
                                <span class="text-sm font-medium">{{ $cl->inspector_name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-md text-[10px] font-black uppercase {{ $cl->status == 'completed' ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container-highest text-outline' }}">
                                {{ $cl->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-1">
                                @foreach($cl->items_json as $item)
                                    <div class="w-2 h-2 rounded-full {{ $item['actual'] ? 'bg-primary' : 'bg-outline/20' }}" title="{{ $item['item'] }}"></div>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.checklists.create', ['date' => $cl->check_date->toDateString()]) }}" class="p-2 text-outline hover:bg-surface-container-highest rounded-lg transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </a>
                                <a href="{{ route('admin.checklists.pdf', $cl->id) }}" class="p-2 text-error hover:bg-error-container rounded-lg transition-colors" title="Download PDF">
                                    <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center text-outline italic">No observation logs found. Create your first one to begin tracking operational health.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-6">
            {{ $checklists->links() }}
        </div>
    </div>
</main>
@endsection
