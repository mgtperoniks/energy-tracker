<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('devices')->cascadeOnDelete();
            
            $table->string('event_code')->index(); // e.g. POWER_SPIKE, TELEMETRY_GAP
            $table->string('event_type')->index(); // e.g. DATA_ANOMALY, POWER_QUALITY
            $table->string('severity')->index();   // e.g. CRITICAL, ERROR, WARNING
            
            $table->string('fingerprint')->nullable()->index(); // Used for grouping recurring incidents
            
            $table->string('title');
            $table->text('message');
            $table->json('payload_json')->nullable();
            
            $table->timestamp('detected_at')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            
            $table->string('status')->default('open')->index(); // open, resolved, ignored
            $table->string('source_layer'); // poller, api, aggregation, system, device
            $table->string('root_cause')->nullable();
            
            $table->timestamps();

            // Composite Indexes
            $table->index(['device_id', 'detected_at']);
            $table->index(['event_type', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
