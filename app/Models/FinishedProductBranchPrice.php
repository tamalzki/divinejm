<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedProductBranchPrice extends Model
{
    protected $fillable = [
        'finished_product_id',
        'branch_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
