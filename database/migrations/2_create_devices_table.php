<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['power_meter', 'temperature_sensor', 'humidity_sensor']);
            $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete();
            $table->integer('slave_id');
            $table->string('communication_type', 50)->default('RS485');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
