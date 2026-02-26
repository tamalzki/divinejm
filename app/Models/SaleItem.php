<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'finished_product_id',
        'batch_number',
        'quantity_deployed',
        'quantity_sold',
        'quantity_unsold',
        'quantity_bo',
        'quantity_replaced',
        'unit_price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity_deployed' => 'decimal:2',
        'quantity_sold' => 'decimal:2',
        'quantity_unsold' => 'decimal:2',
        'quantity_bo' => 'decimal:2',
        'quantity_replaced' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($item) {
            // Calculate subtotal
            $item->subtotal = $item->quantity_sold * $item->unit_price;
        });

        static::saved(function ($item) {
            // Update parent sale total
            $item->sale->recalculateTotal();
        });

        static::deleted(function ($item) {
            // Update parent sale total
            $item->sale->recalculateTotal();
        });
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    // Helper attributes
    public function getHasBoAttribute()
    {
        return $this->quantity_bo > 0;
    }

    public function getHasUnsoldAttribute()
    {
        return $this->quantity_unsold > 0;
    }

    public function getNetQuantityAttribute()
    {
        return $this->quantity_sold - $this->quantity_bo - $this->quantity_replaced;
    }

    public function getTotalAccountedAttribute()
    {
        return $this->quantity_sold + $this->quantity_unsold + $this->quantity_bo;
    }
}