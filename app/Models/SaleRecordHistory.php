<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRecordHistory extends Model
{
    protected $fillable = [
        'sale_id',
        'user_id',
        'lines',
        'total_amount',
        'payment_status_snapshot',
    ];

    protected $casts = [
        'lines' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
