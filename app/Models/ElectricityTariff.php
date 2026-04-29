<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectricityTariff extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'rate_per_kwh'   => 'decimal:2',
        'effective_date' => 'date',
        'is_active'      => 'boolean'
    ];

    protected static function booted()
    {
        // Business rule: Hanya boleh ada 1 active tariff
        static::saving(function ($tariff) {
            if ($tariff->is_active) {
                // Deactivate others
                $query = static::where('id', '!=', $tariff->id ?? 0);
                
                // Jika future expansion digunakan, pastikan deactivate hanya dalam scope yang sama
                if ($tariff->scope_type && $tariff->scope_id) {
                    $query->where('scope_type', $tariff->scope_type)
                          ->where('scope_id', $tariff->scope_id);
                } else {
                    $query->whereNull('scope_type');
                }
                
                $query->update(['is_active' => false]);
            }
        });
    }

    public static function getRateForDate($date)
    {
        $tariff = self::where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();
            
        return $tariff ? (float) $tariff->rate_per_kwh : 0.0;
    }
}
