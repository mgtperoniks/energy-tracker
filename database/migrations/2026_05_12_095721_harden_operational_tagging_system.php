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
        // Patch 3: Safe Migration Replay (Idempotency)
        if (Schema::hasTable('operational_event_tags')) {
            Schema::table('operational_event_tags', function (Blueprint $blueprint) {
                if (!Schema::hasColumn('operational_event_tags', 'deleted_at')) {
                    $blueprint->softDeletes();
                }
                if (!Schema::hasColumn('operational_event_tags', 'edited_by')) {
                    $blueprint->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
                    $blueprint->timestamp('edited_at')->nullable();
                }
                if (!Schema::hasColumn('operational_event_tags', 'deleted_by')) {
                    $blueprint->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                    $blueprint->text('delete_reason')->nullable();
                }
            });
        }

        if (!Schema::hasTable('tagging_audit_logs')) {
            Schema::create('tagging_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained();
                $table->unsignedBigInteger('tag_id');
                $table->string('action'); // CREATE, EDIT, DELETE
                $table->string('tag_type');
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->text('reason')->nullable();
                $table->timestamp('event_at');
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();

                $table->index('tag_id');
                $table->index('event_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagging_audit_logs');
        Schema::table('operational_event_tags', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['deleted_by']);
            $table->dropColumn(['deleted_by', 'delete_reason']);
        });
    }
};
