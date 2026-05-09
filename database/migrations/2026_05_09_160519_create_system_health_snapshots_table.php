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
        Schema::create('system_health_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->unique();
            $table->decimal('telemetry_score', 5, 2)->default(0);
            $table->decimal('incident_score', 5, 2)->default(0);
            $table->decimal('accounting_score', 5, 2)->default(0);
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->json('payload_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_health_snapshots');
    }
};
