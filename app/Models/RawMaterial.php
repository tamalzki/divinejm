<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'category',  // NEW
        'quantity',
        'minimum_stock',
        'unit_price',
        'description'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function isLowStock()
    {
        return $this->quantity <= $this->minimum_stock;
    }

    public function usages()
    {
        return $this->hasMany(RawMaterialUsage::class);
    }

    // NEW: Relationship to recipes
    public function recipes()
    {
        return $this->hasMany(ProductRecipe::class);
    }

    // NEW: Get products that use this raw material
    public function finishedProducts()
    {
        return $this->belongsToMany(FinishedProduct::class, 'product_recipes')
            ->withPivot('quantity_needed', 'cost_per_unit')
            ->withTimestamps();
    }
}