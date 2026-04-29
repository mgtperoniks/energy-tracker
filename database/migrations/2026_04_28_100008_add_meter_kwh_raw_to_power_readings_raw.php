<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('power_readings_raw', function (Blueprint $table) {
            $table->decimal('meter_kwh_raw', 14, 3)->default(0)->after('device_id');
        });
    }

    public function down(): void
    {
        Schema::table('power_readings_raw', function (Blueprint $table) {
            $table->dropColumn('meter_kwh_raw');
        });
    }
};
