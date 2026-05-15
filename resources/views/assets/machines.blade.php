@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Machine Management</h1>
            <p class="text-on-surface-variant text-sm mt-1">Kelola nama tampilan dan identifikasi area mesin yang dimonitor.</p>
        </div>

        @if(session('success'))
            <div class="bg-primary-container/30 border-l-4 border-primary text-primary-dark px-4 py-3 rounded mb-8 text-sm font-bold">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden border border-surface-container-low">
            <div class="p-6 border-b border-surface-container-low bg-surface-container-low/30 flex justify-between items-center">
                <h3 class="text-sm font-bold tracking-tight text-on-surface uppercase">Registered Machines</h3>
                <span class="text-[10px] font-bold text-outline bg-surface-container-high px-2 py-1 rounded">Total: {{ $machines->count() }}</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-surface-container-low">
                            <th class="px-6 py-4">Machine Code</th>
                            <th class="px-6 py-4">Display Name</th>
                            <th class="px-6 py-4">Location</th>
                            <th class="px-6 py-4 text-center">Connected Devices</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @foreach($machines as $machine)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-mono font-black text-primary bg-primary/5 px-2 py-1 rounded border border-primary/10">
                                    {{ $machine->code }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-on-surface">{{ $machine->name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-medium text-outline flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">location_on</span>
                                    {{ $machine->location ? $machine->location->name : 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center min-w-[24px] h-6 rounded-full bg-surface-container-highest text-[10px] font-black text-on-surface">
                                    {{ $machine->devices->count() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="openEditModal({{ json_encode($machine) }})" class="p-2 text-primary hover:bg-primary/10 rounded-full transition-all">
                                    <span class="material-symbols-outlined text-xl">edit</span>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-surface-container-lowest w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-surface-container-low animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-surface-container-low bg-surface-container-low/30 flex justify-between items-center">
            <h3 class="text-sm font-bold tracking-tight text-on-surface uppercase flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">precision_manufacturing</span>
                Configure Machine
            </h3>
            <button onclick="closeEditModal()" class="text-outline hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" action="{{ route('assets.machines.update') }}" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="id" id="modal_machine_id">
            
            <div>
                <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Machine Code</label>
                <input type="text" name="code" id="modal_code" class="w-full bg-surface border border-outline-variant rounded-lg text-sm p-3 text-on-surface font-mono focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                <p class="text-[9px] text-outline/60 mt-1 italic">Identifier unik mesin (Contoh: PM-03)</p>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Display Name</label>
                <input type="text" name="name" id="modal_name" class="w-full bg-surface border border-outline-variant rounded-lg text-sm p-3 text-on-surface font-bold focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                <p class="text-[9px] text-outline/60 mt-1 italic">Nama yang akan muncul di Sidebar dan Dashboard (Contoh: Dapur 3)</p>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="px-6 py-2.5 text-xs font-bold text-outline uppercase tracking-widest hover:bg-surface-container-high rounded-lg transition-all">Cancel</button>
                <button type="submit" class="px-8 py-2.5 bg-primary text-white text-xs font-bold uppercase tracking-widest rounded-lg shadow-lg shadow-primary/20 hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">save</span>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(machine) {
        document.getElementById('modal_machine_id').value = machine.id;
        document.getElementById('modal_code').value = machine.code;
        document.getElementById('modal_name').value = machine.name;
        
        const modal = document.getElementById('editModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close on background click
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
</script>
@endsection
