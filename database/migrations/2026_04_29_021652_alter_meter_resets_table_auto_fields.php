<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meter_resets', function (Blueprint $table) {
            // Append pelacakan kalkulasi tanpa menghapus kolom legacy
            $table->decimal('previous_raw_kwh', 14, 3)->after('device_id')->nullable();
            $table->decimal('new_raw_kwh', 14, 3)->after('previous_raw_kwh')->nullable();
            $table->decimal('previous_baseline_kwh', 14, 3)->after('new_raw_kwh')->nullable();
            $table->decimal('new_baseline_kwh', 14, 3)->after('previous_baseline_kwh')->nullable();
            $table->string('reason')->after('new_baseline_kwh')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('meter_resets', function (Blueprint $table) {
            $table->dropColumn([
                'previous_raw_kwh', 'new_raw_kwh', 
                'previous_baseline_kwh', 'new_baseline_kwh', 'reason'
            ]);
        });
    }
};
