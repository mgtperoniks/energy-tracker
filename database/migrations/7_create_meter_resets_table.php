<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create meter_resets table.
 *
 * Purpose:
 *   Tracks every event when a physical power meter is reset to 0.
 *   This provides an audit trail and allows the system to correctly
 *   reconstruct lifetime energy totals even after multiple resets.
 *
 * Usage:
 *   When operator resets the meter:
 *   1. Record a row here with kwh_at_reset = last known kwh_total
 *   2. Add kwh_at_reset to machines.kwh_baseline
 *   3. Future readings start accumulating from 0 again naturally
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();

            // The last kwh_total reading BEFORE the reset (captured from power_readings)
            $table->double('kwh_at_reset');

            // Notes from operator (e.g. "Annual reset - 1 May 2026")
            $table->string('notes')->nullable();

            // Who performed the reset (optional audit trail)
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('reset_at');
            $table->timestamps();

            $table->index(['machine_id', 'reset_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_resets');
    }
};
