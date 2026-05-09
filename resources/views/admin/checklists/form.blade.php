@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="mb-8">
            <a href="{{ route('admin.checklists.index') }}" class="inline-flex items-center text-primary font-bold text-sm mb-4 hover:underline gap-1">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Back to logs
            </a>
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Daily Operational Validation</h1>
            <p class="text-on-surface-variant text-sm mt-1">Recording facility readiness for {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</p>
        </div>

        <form action="{{ route('admin.checklists.store') }}" method="POST" class="space-y-8">
            @csrf
            <input type="hidden" name="check_date" value="{{ $date }}">

            <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline/10">
                <div class="max-w-md">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-outline mb-2">Inspector Name</label>
                    <input type="text" name="inspector_name" value="{{ $checklist->inspector_name ?? auth()->user()->name }}" required
                           class="w-full bg-surface border border-outline-variant rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline/10 overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline/10">
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-outline w-1/4">Check Item</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-outline w-1/4">Expected / Actual</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-outline">Notes & Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline/5">
                        @foreach($items as $index => $item)
                        <tr>
                            <td class="px-6 py-6 align-top">
                                <div class="font-bold text-on-surface text-base">{{ $item['item'] }}</div>
                                <input type="hidden" name="items[{{ $index }}][item]" value="{{ $item['item'] }}">
                                <input type="hidden" name="items[{{ $index }}][expected]" value="{{ $item['expected'] }}">
                            </td>
                            <td class="px-6 py-6 align-top space-y-4">
                                <div>
                                    <span class="text-[9px] font-bold text-outline uppercase block mb-1">Expected Result</span>
                                    <div class="text-xs text-on-surface-variant italic">{{ $item['expected'] }}</div>
                                </div>
                                <div>
                                    <span class="text-[9px] font-bold text-primary uppercase block mb-1">Actual Result</span>
                                    <input type="text" name="items[{{ $index }}][actual]" value="{{ $item['actual'] }}" placeholder="Describe finding..."
                                           class="w-full bg-surface border border-outline-variant rounded-md p-2 text-xs focus:ring-1 focus:ring-primary outline-none">
                                </div>
                            </td>
                            <td class="px-6 py-6 align-top space-y-4">
                                <div>
                                    <span class="text-[9px] font-bold text-outline uppercase block mb-1">Internal Notes</span>
                                    <textarea name="items[{{ $index }}][notes]" rows="2" class="w-full bg-surface border border-outline-variant rounded-md p-2 text-xs focus:ring-1 focus:ring-primary outline-none">{{ $item['notes'] }}</textarea>
                                </div>
                                <div>
                                    <span class="text-[9px] font-bold text-error uppercase block mb-1">Action Needed</span>
                                    <input type="text" name="items[{{ $index }}][action]" value="{{ $item['action'] }}" placeholder="Remediation steps..."
                                           class="w-full bg-surface border border-outline-variant rounded-md p-2 text-xs focus:ring-1 focus:ring-primary outline-none">
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-4">
                <button type="submit" class="px-8 py-3 bg-primary text-on-primary font-black rounded-xl hover:bg-primary/90 transition-all shadow-md flex items-center gap-2">
                    <span class="material-symbols-outlined">verified</span>
                    Finalize & Complete Observation
                </button>
            </div>
        </form>
    </div>
</main>
@endsection
