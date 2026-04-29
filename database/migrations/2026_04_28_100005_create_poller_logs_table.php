<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poller_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('status', 50); // offline, error, warning
            $table->text('message')->nullable();
            $table->timestamp('event_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poller_logs');
    }
};
