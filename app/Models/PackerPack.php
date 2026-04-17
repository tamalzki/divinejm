<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackerPack extends Model
{
    protected $fillable = [
        'packer_report_id',
        'finished_product_id',
        'packer_name',
        'quantity',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function report()
    {
        return $this->belongsTo(PackerReport::class, 'packer_report_id');
    }

    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
