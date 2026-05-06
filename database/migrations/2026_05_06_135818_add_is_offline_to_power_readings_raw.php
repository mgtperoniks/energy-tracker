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
        Schema::table('power_readings_raw', function (Blueprint $table) {
            $table->boolean('is_offline')->default(false)->after('power_factor');
        });
    }

    public function down(): void
    {
        Schema::table('power_readings_raw', function (Blueprint $table) {
            $table->dropColumn('is_offline');
        });
    }
};
