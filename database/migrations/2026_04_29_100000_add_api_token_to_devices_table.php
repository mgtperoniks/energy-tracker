<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambahkan kolom as nullable dulu
        Schema::table('devices', function (Blueprint $table) {
            $table->string('api_token', 80)->nullable()->unique()->after('slave_id');
        });

        // 2. Generate token untuk data existing
        $devices = DB::table('devices')->get();
        foreach ($devices as $device) {
            DB::table('devices')
                ->where('id', $device->id)
                ->update(['api_token' => Str::random(60)]);
        }

        // 3. Ubah menjadi NOT NULL
        Schema::table('devices', function (Blueprint $table) {
            $table->string('api_token', 80)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }
};
