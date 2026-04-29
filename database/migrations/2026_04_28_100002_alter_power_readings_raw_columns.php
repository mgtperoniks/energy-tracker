<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('power_readings_raw', function (Blueprint $table) {
            $table->decimal('kwh_total', 14, 3)->change();
            $table->decimal('power_kw', 10, 3)->nullable()->change();
            $table->decimal('voltage', 8, 2)->nullable()->change();
            $table->decimal('current', 8, 2)->nullable()->change();
            $table->decimal('power_factor', 5, 3)->nullable()->change();

            // Create new unique index FIRST so foreign key can use it
            $table->unique(['device_id', 'recorded_at'], 'unique_device_time_raw');
            $table->index('recorded_at', 'power_readings_raw_recorded_at_index');

            // THEN drop the old composite index
            $table->dropIndex('power_readings_device_id_recorded_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('power_readings_raw', function (Blueprint $table) {
            $table->dropIndex('power_readings_raw_recorded_at_index');
            $table->dropUnique('unique_device_time_raw');
            
            // Re-create old index
            $table->index(['device_id', 'recorded_at'], 'power_readings_device_id_recorded_at_index');

            $table->double('kwh_total')->change();
            $table->double('power_kw')->nullable()->change();
            $table->double('voltage')->nullable()->change();
            $table->double('current')->nullable()->change();
            $table->double('power_factor')->nullable()->change();
        });
    }
};
