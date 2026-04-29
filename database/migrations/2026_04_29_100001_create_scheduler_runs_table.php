<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduler_runs', function (Blueprint $table) {
            $table->id();
            $table->string('job_name')->unique();
            $table->timestamp('last_success_at')->nullable();
            $table->integer('last_duration_ms')->default(0);
            $table->string('status', 20)->default('idle'); // idle, success, failed
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduler_runs');
    }
};
