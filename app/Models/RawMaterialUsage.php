<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialUsage extends Model
{
    protected $fillable = [
        'raw_material_id',
        'quantity_used',
        'purpose',
        'notes',
        'usage_date',
        'user_id'
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'usage_date' => 'date',
    ];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}