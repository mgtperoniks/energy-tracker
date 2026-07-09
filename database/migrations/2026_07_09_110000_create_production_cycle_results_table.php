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
        Schema::create('production_cycle_results', function (Blueprint $table) {
            $table->id();
            
            // melting_tag_id is the primary reference to the starting tag of the cycle.
            // Prepared as a decoupled, relational data layer for production output,
            // allowing easy expansion of manufacturing metrics without altering the core historian timeline.
            $table->foreignId('melting_tag_id')
                ->unique()
                ->comment('Foreign key to operational_event_tags.id starting the cycle')
                ->constrained('operational_event_tags')
                ->cascadeOnDelete();
                
            $table->decimal('actual_output_kg', 8, 2)->nullable()->comment('Actual cast output in kilograms');
            $table->decimal('return_material_kg', 8, 2)->nullable()->comment('Return material/runner/sprue in kilograms');
            $table->text('remark')->nullable()->comment('Operator or QC observations');
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_cycle_results');
    }
};
