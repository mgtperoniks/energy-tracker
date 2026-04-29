<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electricity_tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('rate_per_kwh', 10, 2);
            $table->date('effective_date');
            $table->boolean('is_active')->default(true);
            
            // Future expansion for scoped tariffs
            $table->string('scope_type')->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();
            
            $table->timestamps();
            
            $table->index(['effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electricity_tariffs');
    }
};
