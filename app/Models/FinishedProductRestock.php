<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedProductRestock extends Model
{
    protected $fillable = [
        'finished_product_id',
        'quantity_added',
        'batch_number',
        'production_date',
        'expiry_date',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'quantity_added' => 'decimal:2',
        'production_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}