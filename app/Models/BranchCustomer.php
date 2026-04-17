<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchCustomer extends Model
{
    protected $table = 'branch_customers';

    protected $fillable = [
        'branch_id',
        'name',
        'phone',
        'email',
        'address',
        'notes',
        'sort_order',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
