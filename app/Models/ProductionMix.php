<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionMix extends Model
{
    protected $fillable = [
        'finished_product_id',
        'batch_number',
        'expected_output',
        'multiplier',           // NEW
        'actual_output',
        'rejected_quantity',    // NEW
        'expiration_date',
        'barcode',
        'status',
        'mix_date',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'expected_output' => 'decimal:2',
        'actual_output' => 'decimal:2',
        'rejected_quantity' => 'decimal:2',  // NEW
        'multiplier' => 'integer',            // NEW
        'expiration_date' => 'date',
        'mix_date' => 'date',
    ];

    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function ingredients()
    {
        return $this->hasMany(ProductionMixIngredient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(FinishedProduct::class, 'finished_product_id');
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    // Total expected output (base output * multiplier)
    public function getTotalExpectedOutputAttribute()
    {
        return $this->expected_output * $this->multiplier;
    }

    // Good output (actual - rejected)
    public function getGoodOutputAttribute()
    {
        return $this->actual_output - $this->rejected_quantity;
    }

    // Yield rate (good output / total expected)
    public function getYieldRateAttribute()
    {
        if ($this->total_expected_output == 0) {
            return 0;
        }
        return ($this->good_output / $this->total_expected_output) * 100;
    }

    // Rejection rate
    public function getRejectionRateAttribute()
    {
        if ($this->actual_output == 0) {
            return 0;
        }
        return ($this->rejected_quantity / $this->actual_output) * 100;
    }

    public function getVarianceAttribute()
    {
        if (!$this->actual_output) {
            return null;
        }
        return $this->actual_output - $this->total_expected_output;
    }

    public function getVariancePercentageAttribute()
    {
        if (!$this->actual_output || $this->total_expected_output == 0) {
            return null;
        }
        return (($this->actual_output - $this->total_expected_output) / $this->total_expected_output) * 100;
    }

    public function getTotalCostAttribute()
    {
        return $this->ingredients->sum(function($ingredient) {
            return $ingredient->quantity_used * $ingredient->rawMaterial->unit_price;
        });
    }

    public function getCostPerUnitAttribute()
    {
        if (!$this->good_output || $this->good_output == 0) {
            return 0;
        }
        return $this->total_cost / $this->good_output;
    }
}