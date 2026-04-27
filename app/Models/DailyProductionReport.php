<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyProductionReport extends Model
{
    protected $fillable = [
        'production_date',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'production_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entries()
    {
        return $this->hasMany(DailyProductionEntry::class);
    }

    public function packerReport()
    {
        return $this->hasOne(PackerReport::class);
    }
}
