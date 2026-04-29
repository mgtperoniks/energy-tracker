<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('power_readings_raw', function (Blueprint $table) {
            // Standalone index for global date-based pruning
            $table->index('recorded_at', 'raw_recorded_at_idx');
        });

        Schema::table('power_readings_hourly', function (Blueprint $table) {
            $table->index('recorded_at', 'hourly_recorded_at_idx');
        });
    }

    public function down()
    {
        Schema::table('power_readings_raw', function (Blueprint $table) {
            $table->dropIndex('raw_recorded_at_idx');
        });

        Schema::table('power_readings_hourly', function (Blueprint $table) {
            $table->dropIndex('hourly_recorded_at_idx');
        });
    }
};
