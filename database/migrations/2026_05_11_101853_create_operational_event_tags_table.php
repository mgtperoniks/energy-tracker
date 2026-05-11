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
        Schema::create('operational_event_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tagged_by')->constrained('users');
            $table->enum('event_type', ['start', 'melting', 'idle', 'test', 'pour', 'end']);
            $table->timestamp('event_time');
            $table->text('notes')->nullable();
            $table->string('shift')->nullable();
            $table->string('source_reference')->nullable();
            $table->foreignId('edited_by')->nullable()->constrained('users');
            $table->timestamp('edited_at')->nullable();
            $table->text('revision_notes')->nullable();
            $table->timestamps();

            // Index for faster chronological queries per device
            $table->index(['device_id', 'event_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_event_tags');
    }
};
