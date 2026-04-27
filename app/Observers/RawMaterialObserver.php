<?php

namespace App\Observers;

use App\Models\RawMaterial;
use App\Models\RawMaterialPriceHistory;
use Illuminate\Support\Facades\Auth;

class RawMaterialObserver
{
    public function updating(RawMaterial $rawMaterial): void
    {
        if ($rawMaterial->isDirty('unit_price')) {
            RawMaterialPriceHistory::create([
                'raw_material_id' => $rawMaterial->id,
                'old_price'       => $rawMaterial->getOriginal('unit_price'),
                'new_price'       => $rawMaterial->unit_price,
                'changed_by'      => Auth::id(),
            ]);
        }
    }
}
