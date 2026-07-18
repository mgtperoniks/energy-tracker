<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: add_downtime_to_event_type_enum
 *
 * Context:
 *   The original CREATE TABLE migration (2026_05_11_101853) already listed
 *   'downtime' in the ENUM definition, but the table was created before that
 *   value was added to the file, resulting in a database schema drift:
 *
 *   Actual DB ENUM: ('start','melting','idle','test','pour','end')
 *   Required ENUM:  ('start','melting','idle','test','pour','end','downtime')
 *
 * Strategy:
 *   - Use raw DB::statement() with MODIFY COLUMN because Laravel Blueprint
 *     ->enum() only works on CREATE TABLE, not ALTER TABLE for existing columns.
 *   - Preserve all existing values (start, melting, idle, test, pour, end)
 *     for full backward compatibility with historical data.
 *   - 'idle' and 'test' are retained even if deprecated in the UI, because
 *     existing records may still reference them.
 *
 * Safety:
 *   - MySQL MODIFY COLUMN on ENUM is a metadata-only operation when the
 *     column has no index changes — no table rebuild required for adding values.
 *   - This migration is idempotent-safe: adding an already-existing value to
 *     an ENUM is a no-op in MySQL (no error, no data loss).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE operational_event_tags
            MODIFY COLUMN event_type
            ENUM('start', 'melting', 'idle', 'test', 'pour', 'end', 'downtime')
            NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * Removes 'downtime' from the ENUM.
     * WARNING: Only safe to run if no rows contain event_type = 'downtime'.
     * If rows exist with 'downtime', MySQL will reject this rollback.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE operational_event_tags
            MODIFY COLUMN event_type
            ENUM('start', 'melting', 'idle', 'test', 'pour', 'end')
            NOT NULL
        ");
    }
};
