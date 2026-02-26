<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'finished_product_id',
        'branch_id',
        'batch_number',  // ADDED: Critical for tracking batches
        'movement_type',
        'quantity',
        'movement_date',
        'reference_number',
        'notes',
        'user_id',
        'expiration_date',  // ADDED: To track expiry dates
        'production_mix_id',  // ADDED: To link to production batch
        'to_branch_id',  // ADDED: For your transfer relationships
        'from_branch_id',  // ADDED: For your transfer relationships
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'movement_date' => 'date',
        'expiration_date' => 'date',  // ADDED
    ];

    // Relationships
    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function productionMix()
    {
        return $this->belongsTo(ProductionMix::class);
    }

    // Helper method to get movement type label
    public function getMovementTypeLabelAttribute()
    {
        return match($this->movement_type) {
            'production' => 'Production',
            'transfer_out' => 'Transfer Out',
            'transfer_in' => 'Transfer In',
            'return' => 'Return',
            'return_bo' => 'Return BO',  // ADDED
            'extra_free' => 'Extra/Free (Expense)',  // ADDED
            'adjustment' => 'Adjustment',
            default => ucfirst($this->movement_type)
        };
    }
}