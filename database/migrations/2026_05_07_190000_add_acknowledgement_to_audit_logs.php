<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('acknowledged_by')->after('device_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->after('detected_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['acknowledged_by']);
            $table->dropColumn(['acknowledged_by', 'acknowledged_at']);
        });
    }
};
