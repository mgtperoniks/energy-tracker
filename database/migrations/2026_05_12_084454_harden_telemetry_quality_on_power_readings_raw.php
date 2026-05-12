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
            $table->string('telemetry_quality', 20)->default('GOOD')->after('is_offline');
            $table->float('poll_duration_sec')->nullable()->after('telemetry_quality');
            $table->string('meter_boot_id', 50)->nullable()->after('poll_duration_sec');
            $table->boolean('gap_detected')->default(false)->after('meter_boot_id');
            
            // Add index for quality and recorded_at for analytics performance
            $table->index(['recorded_at', 'telemetry_quality']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('power_readings_raw', function (Blueprint $table) {
            $table->dropIndex(['recorded_at', 'telemetry_quality']);
            $table->dropColumn(['telemetry_quality', 'poll_duration_sec', 'meter_boot_id', 'gap_detected']);
        });
    }
};
