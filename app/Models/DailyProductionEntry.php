<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyProductionEntry extends Model
{
    protected $fillable = [
        'daily_production_report_id',
        'finished_product_id',
        'number_of_mix',
        'standard_yield',
        'actual_yield',
        'rejects',
        'unfinished',
        'unpacked',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'number_of_mix' => 'integer',
        'standard_yield' => 'decimal:2',
        'actual_yield' => 'decimal:2',
        'rejects' => 'decimal:2',
        'unpacked' => 'decimal:2',
    ];

    public function report()
    {
        return $this->belongsTo(DailyProductionReport::class, 'daily_production_report_id');
    }

    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients()
    {
        return $this->hasMany(DailyProductionIngredient::class);
    }
}
