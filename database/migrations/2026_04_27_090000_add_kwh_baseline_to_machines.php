<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add kwh_baseline to machines table.
 *
 * Purpose:
 *   The power meter stores cumulative energy (E Del) since its last reset.
 *   When a meter is reset (e.g. annually), the counter goes back to 0.
 *   To maintain a continuous lifetime total in Laravel, we store a running
 *   baseline = sum of all kWh recorded BEFORE each reset event.
 *
 *   Lifetime Total (kWh) = kwh_baseline + current_meter_reading (kwh_total)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            // Cumulative kWh sum from all previous meter periods (before resets).
            // Default 0. Updated every time the meter is reset.
            $table->double('kwh_baseline')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn('kwh_baseline');
        });
    }
};
