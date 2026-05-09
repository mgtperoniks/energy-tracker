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
            $table->enum('data_source', ['live', 'manual_backfill', 'recovered', 'estimated'])
                  ->default('live')
                  ->after('recorded_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('power_readings_daily', function (Blueprint $table) {
            $table->dropColumn('data_source');
        });
    }
};
