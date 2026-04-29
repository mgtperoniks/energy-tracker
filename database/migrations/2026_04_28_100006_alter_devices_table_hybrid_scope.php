<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('scope_type')->nullable()->after('type'); // machine, department, panel, dll
            $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
            $table->decimal('active_baseline_kwh', 14, 3)->default(0)->after('communication_type');
            
            $table->index(['scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['scope_type', 'scope_id']);
            $table->dropColumn(['scope_type', 'scope_id', 'active_baseline_kwh']);
        });
    }
};
