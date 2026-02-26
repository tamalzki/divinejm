<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchInventory extends Model
{
    use HasFactory;

    // CRITICAL: Specify the actual table name since it's singular
    protected $table = 'branch_inventory';

    protected $fillable = [
        'branch_id',
        'finished_product_id',
        'batch_number',
        'quantity',
        'expiration_date',
        'batch_expiry_date', // Added based on your screenshot
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'batch_expiry_date' => 'date',
        'quantity' => 'decimal:2',
        'is_expired' => 'boolean',
    ];

    /**
     * Get the branch that owns this inventory
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the finished product
     */
    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    /**
     * Get the production mix (if you want to track it)
     */
    public function productionMix()
    {
        return $this->belongsTo(ProductionMix::class, 'batch_number', 'batch_number');
    }
}