<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\ElectricityTariff;
use App\Models\PollerLog;
use App\Services\TariffService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected TariffService $tariffService;

    public function __construct(TariffService $tariffService)
    {
        $this->tariffService = $tariffService;
    }

    public function tariffs()
    {
        $today = now()->toDateString();

        // 1. Active Tariff: Paling baru, asalkan <= hari ini
        $activeTariff = ElectricityTariff::where('effective_date', '<=', $today)
            ->orderBy('effective_date', 'desc')
            ->first();

        // 2. Upcoming Tariffs: Akan aktif di masa depan (> hari ini)
        $upcomingTariffs = ElectricityTariff::where('effective_date', '>', $today)
            ->orderBy('effective_date', 'asc')
            ->get();

        // 3. Historical Tariffs: Sudah lewat (<= hari ini), di-filter selain ID aktif
        $historicalTariffs = collect();
        if ($activeTariff) {
            $historicalTariffs = ElectricityTariff::where('effective_date', '<=', $today)
                ->where('id', '!=', $activeTariff->id)
                ->orderBy('effective_date', 'desc')
                ->get();
        }

        return view('admin.tariffs', compact('activeTariff', 'upcomingTariffs', 'historicalTariffs'));
    }

    public function storeTariff(Request $request)
    {
        $validated = $request->validate([
            'rate_per_kwh' => 'required|numeric|min:1',
            // Validasi Unique agar tidak ada 2 tarif rebutan di hari yang sama
            'effective_date' => 'required|date|unique:electricity_tariffs,effective_date', 
            'notes' => 'nullable|string|max:255',
        ]);

        ElectricityTariff::create([
            'rate_per_kwh' => $validated['rate_per_kwh'],
            'effective_date' => $validated['effective_date'],
            'notes' => $validated['notes'],
            'is_active' => false, // Default is false, scheduler/sync will handle
        ]);

        // Sync hanya jika tarif yang dibuat memiliki effective_date hari ini atau sebelumnya
        if ($validated['effective_date'] <= now()->toDateString()) {
            $this->tariffService->syncActiveTariffHelper();
        }

        return redirect()->route('admin.tariffs')->with('success', 'Tarif berhasil dijadwalkan.');
    }

    // Placeholders for future phases
    public function thresholds(Request $request)
    {
        $selectedDeviceId = $request->query('device_id');
        $devices = Device::orderBy('name')->get();

        // Default values from SettingService (if specific device is selected, get() resolves the inheritance)
        $thresholds = [
            'low_voltage_threshold' => \App\Services\SettingService::get('low_voltage_threshold', $selectedDeviceId, 20),
            'low_voltage_duration' => \App\Services\SettingService::get('low_voltage_duration', $selectedDeviceId, 5),
            'min_operating_kw' => \App\Services\SettingService::get('min_operating_kw', $selectedDeviceId, 1),
            'idle_power_threshold' => \App\Services\SettingService::get('idle_power_threshold', $selectedDeviceId, 2),
            'idle_duration' => \App\Services\SettingService::get('idle_duration', $selectedDeviceId, 10),
            'auto_reset_max_new_raw' => \App\Services\SettingService::get('auto_reset_max_new_raw', $selectedDeviceId, 0.5),
        ];

        // We also want to know if these are overrides. Let's fetch explicitly if it's device mode
        $overrides = [];
        if ($selectedDeviceId) {
            $deviceSettings = \App\Models\Setting::where('scope_type', 'device')
                ->where('scope_id', $selectedDeviceId)
                ->pluck('value', 'key')->toArray();
            $overrides = $deviceSettings;
        }

        // Global values for placeholders when in override mode
        $globals = [
            'low_voltage_threshold' => \App\Services\SettingService::get('low_voltage_threshold', null, 20),
            'low_voltage_duration' => \App\Services\SettingService::get('low_voltage_duration', null, 5),
            'min_operating_kw' => \App\Services\SettingService::get('min_operating_kw', null, 1),
            'idle_power_threshold' => \App\Services\SettingService::get('idle_power_threshold', null, 2),
            'idle_duration' => \App\Services\SettingService::get('idle_duration', null, 10),
            'auto_reset_max_new_raw' => \App\Services\SettingService::get('auto_reset_max_new_raw', null, 0.5),
        ];

        return view('admin.thresholds', compact('devices', 'selectedDeviceId', 'thresholds', 'overrides', 'globals'));
    }

    public function updateThresholds(Request $request)
    {
        $deviceId = $request->input('device_id');
        $scopeType = $deviceId ? 'device' : 'global';
        $scopeId = $deviceId ?: null;

        $keys = [
            'low_voltage_threshold' => 'number',
            'low_voltage_duration' => 'integer',
            'min_operating_kw' => 'number',
            'idle_power_threshold' => 'number',
            'idle_duration' => 'integer',
            'auto_reset_max_new_raw' => 'number',
        ];

        foreach ($keys as $key => $dataType) {
            $value = $request->input($key);
            
            if ($deviceId && $value === null) {
                \App\Services\SettingService::forget($key, $scopeType, $scopeId);
            } elseif ($value !== null) {
                $val = $dataType === 'integer' ? (int)$value : (float)$value;
                \App\Services\SettingService::set($key, $val, $dataType, $scopeType, $scopeId);
            }
        }

        return redirect()->back()->with('success', 'Thresholds berhasil diperbarui.');
    }

    public function deviceConfig()
    {
        $devices = Device::with('machine')->orderBy('name')->get();
        $machines = \App\Models\Machine::orderBy('name')->get();
        
        return view('admin.device-config', compact('devices', 'machines'));
    }

    public function updateDeviceConfig(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'name' => 'required|string|max:255',
            'machine_id' => 'nullable|exists:machines,id',
            'slave_id' => 'required|integer|unique:devices,slave_id,' . $request->input('device_id'),
            'monitor_idle_consumption' => 'boolean',
        ]);

        $device = Device::findOrFail($validated['device_id']);
        $oldData = $device->only(['slave_id', 'machine_id', 'monitor_idle_consumption']);
        
        $newMonitorIdle = $request->has('monitor_idle_consumption');

        $device->update([
            'name' => $validated['name'],
            'machine_id' => $validated['machine_id'],
            'slave_id' => $validated['slave_id'],
            'monitor_idle_consumption' => $newMonitorIdle,
        ]);

        // Audit Logging
        $changes = [];
        if ($oldData['slave_id'] != $validated['slave_id']) {
            $changes[] = "Slave ID: {$oldData['slave_id']} -> {$validated['slave_id']}";
        }
        if ($oldData['machine_id'] != $validated['machine_id']) {
            $oldMachine = $oldData['machine_id'] ? \App\Models\Machine::find($oldData['machine_id'])?->name : 'None';
            $newMachine = $validated['machine_id'] ? \App\Models\Machine::find($validated['machine_id'])?->name : 'None';
            $changes[] = "Machine: {$oldMachine} -> {$newMachine}";
        }
        if ($oldData['monitor_idle_consumption'] != $newMonitorIdle) {
            $oldVal = $oldData['monitor_idle_consumption'] ? 'ON' : 'OFF';
            $newVal = $newMonitorIdle ? 'ON' : 'OFF';
            $changes[] = "Idle Monitor: {$oldVal} -> {$newVal}";
        }

        if (!empty($changes)) {
            PollerLog::create([
                'device_id' => $device->id,
                'status' => 'SYSTEM_CONFIG',
                'message' => 'Device config updated: ' . implode(', ', $changes),
                'event_at' => now()
            ]);
        }

        return redirect()->back()->with('success', 'Konfigurasi device berhasil diperbarui.');
    }

    public function pollerLogs(Request $request)
    {
        $deviceId = $request->query('device_id');
        $status = $request->query('status');
        
        $devices = Device::orderBy('name')->get();
        
        $query = PollerLog::with('device.machine')->orderBy('event_at', 'desc');
        
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $logs = $query->paginate(50);
        
        return view('admin.poller-logs', compact('logs', 'devices', 'deviceId', 'status'));
    }

    public function resetHistory()
    {
        return view('admin.reset-history');
    }

    public function deploymentHealth()
    {
        // 1. Scheduler Status
        $schedulerJobs = \App\Models\SchedulerRun::all();
        
        // 2. Environment Status
        $env = [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'db_connection' => config('database.default'),
        ];

        // 3. Storage Permissions
        $storagePath = storage_path('framework/views');
        $isStorageWritable = is_writable($storagePath);

        // 4. Database Size (MySQL specific)
        $dbName = config('database.connections.mysql.database');
        $dbSize = \Illuminate\Support\Facades\DB::select("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
            FROM information_schema.TABLES 
            WHERE table_schema = ?", [$dbName])[0]->size_mb ?? 0;

        // 5. Backup Status
        $backupDir = storage_path('app/backups');
        $lastBackup = null;
        if (file_exists($backupDir)) {
            $files = glob($backupDir . DIRECTORY_SEPARATOR . "*.sql");
            if (!empty($files)) {
                usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
                $lastBackup = [
                    'filename' => basename($files[0]),
                    'size' => round(filesize($files[0]) / 1024 / 1024, 2),
                    'at' => \Carbon\Carbon::createFromTimestamp(filemtime($files[0])),
                ];
            }
        }

        return view('admin.deployment-health', compact('schedulerJobs', 'env', 'isStorageWritable', 'dbSize', 'lastBackup'));
    }
}
