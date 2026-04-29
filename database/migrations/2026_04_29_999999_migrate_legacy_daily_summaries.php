<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Data Migration: daily_energy_summaries -> power_readings_daily
        // Kita memetakan machine_id ke device_id (device pertama yang ditemukan untuk mesin tersebut)
        if (Schema::hasTable('daily_energy_summaries') && Schema::hasTable('power_readings_daily')) {
            $summaries = DB::table('daily_energy_summaries')
                ->join('devices', 'daily_energy_summaries.machine_id', '=', 'devices.machine_id')
                ->select(
                    'devices.id as device_id',
                    'daily_energy_summaries.date as recorded_date',
                    'daily_energy_summaries.kwh_usage'
                )
                // Pastikan hanya satu baris per kombinasi device & date
                ->groupBy('devices.id', 'daily_energy_summaries.date', 'daily_energy_summaries.kwh_usage')
                ->get();

            foreach ($summaries as $summary) {
                // Cari kwh_total terakhir pada hari tersebut dari power_readings_raw (jika ada)
                // Jika tidak ada, gunakan 0.000 sebagai placeholder agar tidak break unique constraint/null
                $lastReading = DB::table('power_readings_raw')
                    ->where('device_id', $summary->device_id)
                    ->whereDate('recorded_at', $summary->recorded_date)
                    ->orderBy('recorded_at', 'desc')
                    ->first();

                DB::table('power_readings_daily')->updateOrInsert(
                    [
                        'device_id' => $summary->device_id,
                        'recorded_date' => $summary->recorded_date
                    ],
                    [
                        'kwh_usage' => $summary->kwh_usage,
                        'kwh_total' => $lastReading ? $lastReading->kwh_total : 0.000,
                        'avg_power_kw' => null,
                        'avg_voltage' => null,
                        'avg_current' => null,
                        'avg_power_factor' => null,
                    ]
                );
            }
        }
        
        // 2. Note: power_readings -> power_readings_raw sudah dihandle oleh rename migration.
        // Data lama di power_readings_raw perlu penyesuaian kwh_total jika sebelumnya null
        DB::table('power_readings_raw')->whereNull('kwh_total')->update(['kwh_total' => 0.000]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migration tidak di-revert secara destruktif di sini 
        // karena data asli masih ada di daily_energy_summaries
    }
};
