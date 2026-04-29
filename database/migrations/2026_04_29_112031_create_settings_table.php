<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->json('value');
            $table->string('data_type')->default('number'); // number, integer, boolean, array, string
            $table->string('scope_type')->default('global'); // global, device, machine, department
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['key', 'scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
