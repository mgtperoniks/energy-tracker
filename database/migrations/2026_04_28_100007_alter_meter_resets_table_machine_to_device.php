<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meter_resets', function (Blueprint $table) {
            $table->dropForeign(['machine_id']);
            $table->dropIndex(['machine_id', 'reset_at']);
            
            $table->renameColumn('machine_id', 'device_id');
            
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
            $table->index(['device_id', 'reset_at']);
        });
    }

    public function down(): void
    {
        Schema::table('meter_resets', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropIndex(['device_id', 'reset_at']);
            
            $table->renameColumn('device_id', 'machine_id');
            
            $table->foreign('machine_id')->references('id')->on('machines')->cascadeOnDelete();
            $table->index(['machine_id', 'reset_at']);
        });
    }
};
