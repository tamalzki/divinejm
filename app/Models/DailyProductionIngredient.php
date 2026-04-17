<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyProductionIngredient extends Model
{
    protected $fillable = [
        'daily_production_entry_id',
        'raw_material_id',
        'quantity_used',
        'input_quantity',
        'input_unit',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:4',
        'input_quantity' => 'decimal:4',
    ];

    public function entry()
    {
        return $this->belongsTo(DailyProductionEntry::class, 'daily_production_entry_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
