<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Escalation Rules
        Schema::create('escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('severity')->index(); // WARNING, ERROR, CRITICAL
            $table->integer('threshold_minutes');
            $table->string('channel'); // email, telegram, whatsapp
            $table->string('recipient');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // 2. Incident Escalations History
        Schema::create('incident_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_log_id')->constrained('audit_logs')->cascadeOnDelete();
            $table->foreignId('rule_id')->constrained('escalation_rules')->cascadeOnDelete();
            
            $table->timestamp('escalated_at')->index();
            $table->string('channel');
            $table->string('recipient');
            $table->string('delivery_status')->default('sent'); // sent, failed
            $table->json('response_payload')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_escalations');
        Schema::dropIfExists('escalation_rules');
    }
};
