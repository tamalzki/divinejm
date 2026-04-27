<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialPriceHistory extends Model
{
    protected $table = 'raw_material_price_history';

    protected $fillable = [
        'raw_material_id',
        'old_price',
        'new_price',
        'notes',
        'changed_by',
    ];

    protected $casts = [
        'old_price' => 'decimal:4',
        'new_price' => 'decimal:4',
    ];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
