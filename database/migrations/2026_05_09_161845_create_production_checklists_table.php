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
        Schema::create('production_checklists', function (Blueprint $table) {
            $table->id();
            $table->date('check_date')->unique();
            $table->json('items_json'); // Array of {item, expected, actual, notes, action}
            $table->string('inspector_name')->nullable();
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_checklists');
    }
};
