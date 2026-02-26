<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchCustomer extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'phone',
        'email',
        'address',
        'notes',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'branch_customer_id');
    }
}