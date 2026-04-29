<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('power_readings_daily', function (Blueprint $table) {
            $table->decimal('energy_cost', 14, 2)->after('kwh_usage')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('power_readings_daily', function (Blueprint $table) {
            $table->dropColumn('energy_cost');
        });
    }
};
