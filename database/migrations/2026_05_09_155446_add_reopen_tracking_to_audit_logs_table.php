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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->timestamp('reopened_at')->nullable()->after('resolved_at');
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete()->after('reopened_at');
            $table->integer('reopen_count')->default(0)->after('reopened_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['reopened_by']);
            $table->dropColumn(['reopened_at', 'reopened_by', 'reopen_count']);
        });
    }
};
