<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_energy_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->date('date');
            $table->double('kwh_usage');
            $table->timestamps();
            
            $table->unique(['machine_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_energy_summaries');
    }
};
