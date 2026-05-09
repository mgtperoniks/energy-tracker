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
        Schema::table('audit_log_events', function (Blueprint $table) {
            $table->integer('sequence_no')->default(1)->after('audit_log_id');
            
            // Unique constraint per log to prevent racing sequence duplicates
            $table->unique(['audit_log_id', 'sequence_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_log_events', function (Blueprint $table) {
            $table->dropUnique(['audit_log_id', 'sequence_no']);
            $table->dropColumn('sequence_no');
        });
    }
};
