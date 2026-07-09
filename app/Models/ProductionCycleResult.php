<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionCycleResult extends Model
{
    protected $table = 'production_cycle_results';

    protected $guarded = ['id'];

    protected $casts = [
        'actual_output_kg' => 'float',
        'return_material_kg' => 'float',
    ];

    /**
     * Get the operational event tag that started the cycle.
     */
    public function meltingTag()
    {
        return $this->belongsTo(OperationalEventTag::class, 'melting_tag_id');
    }

    /**
     * Get the user who recorded this production result.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this production result.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
