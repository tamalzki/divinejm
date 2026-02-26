<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    protected $fillable = [
        'finished_product_id',
        'raw_material_id',
        'quantity_needed',
        'cost_per_unit'
    ];

    protected $casts = [
        'quantity_needed' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
    ];

    // Relationships
    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    // Calculate total cost for this ingredient
    public function getTotalCostAttribute()
    {
        return $this->quantity_needed * $this->cost_per_unit;
    }
}