<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('power_readings_hourly', function (Blueprint $table) {
            $table->integer('sample_count')->default(0)->after('avg_power_factor');
        });

        Schema::table('power_readings_daily', function (Blueprint $table) {
            $table->integer('total_sample_count')->default(0)->after('avg_power_factor');
        });
    }

    public function down(): void
    {
        Schema::table('power_readings_hourly', function (Blueprint $table) {
            $table->dropColumn('sample_count');
        });

        Schema::table('power_readings_daily', function (Blueprint $table) {
            $table->dropColumn('total_sample_count');
        });
    }
};
