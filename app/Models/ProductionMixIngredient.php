<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionMixIngredient extends Model
{
    protected $fillable = [
        'production_mix_id',
        'raw_material_id',
        'quantity_used',
        'input_quantity',
        'input_unit',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:4',
        'input_quantity' => 'decimal:4',
    ];

    public function productionMix()
    {
        return $this->belongsTo(ProductionMix::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}