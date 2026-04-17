<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackerReport extends Model
{
    protected $fillable = [
        'pack_date',
        'expiration_date',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'pack_date' => 'date',
        'expiration_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function packs()
    {
        return $this->hasMany(PackerPack::class);
    }
}
