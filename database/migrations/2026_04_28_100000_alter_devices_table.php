<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_online')->default(false);
            $table->decimal('last_kwh_total', 14, 3)->nullable();
            $table->unique('slave_id', 'devices_slave_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique('devices_slave_id_unique');
            $table->dropColumn(['last_seen_at', 'is_online', 'last_kwh_total']);
        });
    }
};
