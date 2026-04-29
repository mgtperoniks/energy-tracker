@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Threshold Management</h1>
                <p class="text-on-surface-variant text-sm mt-1">Konfigurasi sensitivitas deteksi anomali operasional.</p>
            </div>
            
            <form method="GET" action="{{ route('admin.thresholds') }}" class="w-full md:w-auto flex items-center gap-2">
                <select name="device_id" class="w-full md:w-64 bg-surface border {{ $selectedDeviceId ? 'border-tertiary ring-1 ring-tertiary' : 'border-outline-variant' }} rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" onchange="this.form.submit()">
                    <option value="">-- GLOBAL DEFAULTS --</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}" {{ $selectedDeviceId == $device->id ? 'selected' : '' }}>
                            Override: {{ $device->name }} {{ $device->machine ? '('.$device->machine->name.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if(session('success'))
            <div class="bg-primary-container/30 border-l-4 border-primary text-primary-dark px-4 py-3 rounded mb-8 text-sm font-bold">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.thresholds.update') }}">
            @csrf
            @if($selectedDeviceId)
                <input type="hidden" name="device_id" value="{{ $selectedDeviceId }}">
                <div class="mb-6 p-4 bg-tertiary-container/20 border border-tertiary-container rounded-lg flex items-start gap-3">
                    <span class="material-symbols-outlined text-tertiary">info</span>
                    <div>
                        <h3 class="text-sm font-bold text-tertiary uppercase tracking-wider">Device Override Mode</h3>
                        <p class="text-xs text-on-surface-variant mt-1">Anda sedang mengatur threshold spesifik untuk device ini. Kosongkan field jika ingin menggunakan nilai Global Default.</p>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                
                <!-- Voltage Anomaly Detector -->
                <div class="bg-surface-container-lowest rounded-lg shadow-sm border border-surface-container-low overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-surface-container-low bg-surface-container-low/30">
                        <h2 class="text-sm font-bold tracking-tight text-on-surface uppercase flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-error">electric_bolt</span>
                            Low Voltage Detector
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Voltage Threshold (V)</label>
                            <input type="number" step="0.1" name="low_voltage_threshold" value="{{ $selectedDeviceId && !isset($overrides['low_voltage_threshold']) ? '' : $thresholds['low_voltage_threshold'] }}" placeholder="{{ $selectedDeviceId ? 'Global: ' . $globals['low_voltage_threshold'] : '' }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all {{ $selectedDeviceId && isset($overrides['low_voltage_threshold']) ? 'border-tertiary bg-tertiary-container/5' : '' }}">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Duration (Minutes)</label>
                            <input type="number" step="1" name="low_voltage_duration" value="{{ $selectedDeviceId && !isset($overrides['low_voltage_duration']) ? '' : $thresholds['low_voltage_duration'] }}" placeholder="{{ $selectedDeviceId ? 'Global: ' . $globals['low_voltage_duration'] : '' }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all {{ $selectedDeviceId && isset($overrides['low_voltage_duration']) ? 'border-tertiary bg-tertiary-container/5' : '' }}">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Min Operating Power (kW)</label>
                            <input type="number" step="0.1" name="min_operating_kw" value="{{ $selectedDeviceId && !isset($overrides['min_operating_kw']) ? '' : $thresholds['min_operating_kw'] }}" placeholder="{{ $selectedDeviceId ? 'Global: ' . $globals['min_operating_kw'] : '' }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all {{ $selectedDeviceId && isset($overrides['min_operating_kw']) ? 'border-tertiary bg-tertiary-container/5' : '' }}">
                            <p class="text-[10px] text-outline mt-1 italic">Mesin dianggap sedang menyala jika daya melampaui ini.</p>
                        </div>
                    </div>
                </div>

                <!-- Idle Consumption Detector -->
                <div class="bg-surface-container-lowest rounded-lg shadow-sm border border-surface-container-low overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-surface-container-low bg-surface-container-low/30">
                        <h2 class="text-sm font-bold tracking-tight text-on-surface uppercase flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-secondary">snooze</span>
                            Idle Leakage Detector
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Idle Power Threshold (kW)</label>
                            <input type="number" step="0.1" name="idle_power_threshold" value="{{ $selectedDeviceId && !isset($overrides['idle_power_threshold']) ? '' : $thresholds['idle_power_threshold'] }}" placeholder="{{ $selectedDeviceId ? 'Global: ' . $globals['idle_power_threshold'] : '' }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all {{ $selectedDeviceId && isset($overrides['idle_power_threshold']) ? 'border-tertiary bg-tertiary-container/5' : '' }}">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Duration (Minutes)</label>
                            <input type="number" step="1" name="idle_duration" value="{{ $selectedDeviceId && !isset($overrides['idle_duration']) ? '' : $thresholds['idle_duration'] }}" placeholder="{{ $selectedDeviceId ? 'Global: ' . $globals['idle_duration'] : '' }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all {{ $selectedDeviceId && isset($overrides['idle_duration']) ? 'border-tertiary bg-tertiary-container/5' : '' }}">
                        </div>
                    </div>
                </div>
                
                <!-- System Resilience -->
                <div class="bg-surface-container-lowest rounded-lg shadow-sm border border-surface-container-low overflow-hidden flex flex-col lg:col-span-2">
                    <div class="px-6 py-4 border-b border-surface-container-low bg-surface-container-low/30">
                        <h2 class="text-sm font-bold tracking-tight text-on-surface uppercase flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-primary">security</span>
                            System Resilience
                        </h2>
                    </div>
                    <div class="p-6 space-y-4 lg:w-1/2">
                        <div>
                            <label class="block text-[10px] font-bold text-outline uppercase tracking-wider mb-2">Auto-Reset Max kWh (Safety Limit)</label>
                            <input type="number" step="0.1" name="auto_reset_max_new_raw" value="{{ $selectedDeviceId && !isset($overrides['auto_reset_max_new_raw']) ? '' : $thresholds['auto_reset_max_new_raw'] }}" placeholder="{{ $selectedDeviceId ? 'Global: ' . $globals['auto_reset_max_new_raw'] : '' }}" class="w-full bg-surface border border-outline-variant rounded-md text-sm p-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all {{ $selectedDeviceId && isset($overrides['auto_reset_max_new_raw']) ? 'border-tertiary bg-tertiary-container/5' : '' }}">
                            <p class="text-[10px] text-outline mt-1 italic">Batas kWh maksimal yang dianggap wajar saat alat me-reset diri sebelum dianggap sebagai lonjakan tidak valid.</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-8 py-3 bg-primary text-white font-bold rounded-md hover:bg-primary/90 transition-colors uppercase tracking-wider text-xs flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">save</span>
                    Save Settings
                </button>
            </div>
        </form>

    </div>
</main>
@endsection
