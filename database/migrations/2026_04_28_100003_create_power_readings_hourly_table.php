<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('power_readings_hourly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            
            $table->decimal('kwh_total', 14, 3);
            $table->decimal('kwh_usage', 14, 3);
            
            $table->decimal('avg_power_kw', 10, 3)->nullable();
            $table->decimal('min_power_kw', 10, 3)->nullable();
            $table->decimal('max_power_kw', 10, 3)->nullable();
            
            $table->decimal('avg_voltage', 8, 2)->nullable();
            $table->decimal('avg_current', 8, 2)->nullable();
            $table->decimal('avg_power_factor', 5, 3)->nullable();
            
            $table->timestamp('recorded_at'); // 12:00:00
            
            $table->unique(['device_id', 'recorded_at'], 'unique_device_time_hourly');
            $table->index('recorded_at', 'idx_hourly_recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('power_readings_hourly');
    }
};
