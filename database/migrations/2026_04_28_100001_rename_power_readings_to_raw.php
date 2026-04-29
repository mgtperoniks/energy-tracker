<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('power_readings', 'power_readings_raw');
    }

    public function down(): void
    {
        Schema::rename('power_readings_raw', 'power_readings');
    }
};
