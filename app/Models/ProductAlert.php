<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAlert extends Model
{
    protected $fillable = [
        'product_type',
        'product_id',
        'product_name',
        'current_stock',
        'minimum_stock',
        'is_resolved'
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'is_resolved' => 'boolean',
    ];
}