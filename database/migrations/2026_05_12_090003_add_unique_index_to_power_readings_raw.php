<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Patch 2 & 3: Raw SQL Index Detection (Portability)
        $indexes = collect(DB::select("SHOW INDEX FROM power_readings_raw"))->pluck('Key_name')->unique();

        Schema::table('power_readings_raw', function (Blueprint $table) use ($indexes) {
            if (!$indexes->contains('unique_device_timestamp') && !$indexes->contains('uidx_device_timestamp')) {
                 $table->unique(['device_id', 'recorded_at'], 'unique_device_timestamp');
            }
            
            if (!$indexes->contains('power_readings_raw_recorded_at_telemetry_quality_index')) {
                $table->index(['recorded_at', 'telemetry_quality']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('power_readings_raw', function (Blueprint $table) {
            $table->dropUnique('unique_device_timestamp');
        });
    }
};
