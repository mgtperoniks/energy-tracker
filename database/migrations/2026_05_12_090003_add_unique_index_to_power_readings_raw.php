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
        Schema::table('power_readings_raw', function (Blueprint $table) {
            // Drop non-unique index if it exists (added in previous turn)
            try {
                $table->dropIndex(['recorded_at', 'telemetry_quality']);
            } catch (\Exception $e) {}

            // Ensure unique historian entries (device_id + recorded_at)
            $table->unique(['device_id', 'recorded_at'], 'unique_device_timestamp');
            
            // Re-add helper index for quality analytics
            $table->index(['recorded_at', 'telemetry_quality']);
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
