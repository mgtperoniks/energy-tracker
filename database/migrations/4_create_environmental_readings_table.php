<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environmental_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->double('temperature')->nullable();
            $table->double('humidity')->nullable();
            $table->timestamp('recorded_at');
            
            $table->index(['device_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environmental_readings');
    }
};
