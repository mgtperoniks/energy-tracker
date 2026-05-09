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
        Schema::table('power_readings_daily', function (Blueprint $table) {
            $table->unsignedBigInteger('tariff_id_snapshot')->nullable()->after('energy_cost');
            $table->decimal('tariff_rate_snapshot', 14, 2)->nullable()->after('tariff_id_snapshot');
            
            $table->foreign('tariff_id_snapshot')->references('id')->on('electricity_tariffs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('power_readings_daily', function (Blueprint $table) {
            $table->dropForeign(['tariff_id_snapshot']);
            $table->dropColumn(['tariff_id_snapshot', 'tariff_rate_snapshot']);
        });
    }
};
