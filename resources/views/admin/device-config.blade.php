@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Device Configuration</h1>
            <p class="text-on-surface-variant text-sm mt-1">Manajemen parameter fisik dan konektivitas power meter.</p>
        </div>

        @if(session('success'))
            <div class="bg-primary-container/30 border-l-4 border-primary text-primary-dark px-4 py-3 rounded mb-8 text-sm font-bold">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-surface-container-lowest rounded-xl shadow-[0_24px_40px_-4px_rgba(25,28,30,0.05)] overflow-hidden border border-surface-container-low">
            <div class="p-6 border-b border-surface-container-low bg-surface-container-low/30 flex justify-between items-center">
                <h3 class="text-sm font-bold tracking-tight text-on-surface uppercase">Registered Devices</h3>
                <span class="text-[10px] font-bold text-outline bg-surface-container-high px-2 py-1 rounded">Total: {{ $devices->count() }}</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface-container-low text-[10px] font-black text-on-surface-variant uppercase tracking-widest border-b border-surface-container-low">
                            <th class="px-6 py-4">Device Name</th>
                            <th class="px-6 py-4">Machine Link</th>
                            <th class="px-6 py-4 text-center">Slave ID</th>
                            <th class="px-6 py-4 text-center">API Token</th>
                            <th class="px-6 py-4 text-center">Idle Monitor</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @foreach($devices as $device)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-on-surface">{{ $device->name }}</div>
                                <div class="text-[10px] text-outline font-mono">{{ $device->communication_type }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($device->machine)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md bg-sky-50 text-sky-700 text-[10px] font-bold border border-sky-100">
                                        {{ $device->machine->code }} - {{ $device->machine->name }}
                                    </span>
                                @else
                                    <span class="text-xs italic text-outline">Unlinked</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-sm font-bold text-primary">
                                {{ $device->slave_id }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <code class="text-[10px] bg-surface-container-high px-2 py-1 rounded text-outline font-mono">
                                        {{ Str::limit($device->api_token, 10, '...') }}
                                    </code>
                                    <button onclick="copyToClipboard('{{ $device->api_token }}', this)" class="p-1 hover:bg-primary/10 rounded-md transition-all text-primary flex items-center justify-center min-w-[24px]">
                                        <span class="material-symbols-outlined text-sm">content_copy</span>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($device->monitor_idle_consumption)
                                    <span class="material-symbols-outlined text-secondary text-lg">check_circle</span>
                                @else
                                    <span class="material-symbols-outlined text-outline/30 text-lg">cancel</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($device->is_online)
                                    <span class="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container text-[10px] font-bold uppercase tracking-wide">Online</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant text-[10px] font-bold uppercase tracking-wide">Offline</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="openEditModal({{ json_encode($device) }})" class="p-2 text-primary hover:bg-primary/10 rounded-full transition-all">
                                    <span class="material-symbols-outlined text-xl">edit_note</span>
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
                <span class="material-symbols-outlined text-primary">settings_input_component</span>
                Configure Device
            </h3>
            <button onclick="closeEditModal()" class="text-outline hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" action="{{ route('admin.device-config.update') }}" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="device_id" id="modal_device_id">
            
            <div>
                <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Display Name</label>
                <input type="text" name="name" id="modal_name" class="w-full bg-surface border border-outline-variant rounded-lg text-sm p-3 text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Machine Link</label>
                    <select name="machine_id" id="modal_machine_id" class="w-full bg-surface border border-outline-variant rounded-lg text-sm p-3 text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                        <option value="">-- No Machine --</option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->id }}">{{ $machine->code }} - {{ $machine->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Slave ID (Modbus)</label>
                    <input type="number" name="slave_id" id="modal_slave_id" class="w-full bg-surface border border-outline-variant rounded-lg text-sm p-3 text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                </div>
            </div>

            <div class="p-4 bg-surface-container-low rounded-xl border border-surface-container-high flex items-center justify-between">
                <div>
                    <h4 class="text-xs font-bold text-on-surface">Monitor Idle Consumption</h4>
                    <p class="text-[10px] text-outline mt-0.5">Aktifkan deteksi kebocoran daya saat mesin mati.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="monitor_idle_consumption" id="modal_idle" class="sr-only peer">
                    <div class="w-11 h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary"></div>
                </label>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="px-6 py-2.5 text-xs font-bold text-outline uppercase tracking-widest hover:bg-surface-container-high rounded-lg transition-all">Cancel</button>
                <button type="submit" class="px-8 py-2.5 bg-primary text-white text-xs font-bold uppercase tracking-widest rounded-lg shadow-lg shadow-primary/20 hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    Update Config
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(device) {
        document.getElementById('modal_device_id').value = device.id;
        document.getElementById('modal_name').value = device.name;
        document.getElementById('modal_machine_id').value = device.machine_id || '';
        document.getElementById('modal_slave_id').value = device.slave_id;
        document.getElementById('modal_idle').checked = device.monitor_idle_consumption;
        
        const modal = document.getElementById('editModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function copyToClipboard(text, btn) {
        const icon = btn.querySelector('.material-symbols-outlined');
        const originalIcon = icon.innerText;

        function showSuccess() {
            icon.innerText = 'check';
            btn.classList.add('text-green-600', 'bg-green-50');
            
            setTimeout(() => {
                icon.innerText = originalIcon;
                btn.classList.remove('text-green-600', 'bg-green-50');
            }, 2000);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showSuccess();
            }).catch(err => {
                console.error('Failed to copy: ', err);
                fallbackCopy(text, showSuccess);
            });
        } else {
            fallbackCopy(text, showSuccess);
        }
    }

    function fallbackCopy(text, callback) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        
        // Ensure textarea is not visible but part of DOM
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        textArea.style.top = "0";
        document.body.appendChild(textArea);
        
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                callback();
            }
        } catch (err) {
            console.error('Fallback copy failed', err);
        }
        
        document.body.removeChild(textArea);
    }

    // Close on background click
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
</script>
@endsection
