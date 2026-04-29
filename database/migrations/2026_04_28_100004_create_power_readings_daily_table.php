<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('power_readings_daily', function (Blueprint $table) {
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
            
            $table->date('recorded_date');
            
            $table->unique(['device_id', 'recorded_date'], 'unique_device_date_daily');
            $table->index('recorded_date', 'idx_daily_recorded_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('power_readings_daily');
    }
};
