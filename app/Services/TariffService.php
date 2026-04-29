<?php

namespace App\Services;

use App\Models\ElectricityTariff;

class TariffService
{
    /**
     * Sinkronisasi flag is_active berdasarkan effective_date.
     * Dapat dipanggil setelah pembuatan tarif atau oleh scheduler harian.
     */
    public function syncActiveTariffHelper(): void
    {
        $trueActiveId = ElectricityTariff::where('effective_date', '<=', now()->toDateString())
            ->orderBy('effective_date', 'desc')
            ->value('id');

        if ($trueActiveId) {
            ElectricityTariff::where('id', '!=', $trueActiveId)->update(['is_active' => false]);
            ElectricityTariff::where('id', $trueActiveId)->update(['is_active' => true]);
        } else {
            // Jika tidak ada tarif aktif, jadikan semua false
            ElectricityTariff::query()->update(['is_active' => false]);
        }
    }
}
