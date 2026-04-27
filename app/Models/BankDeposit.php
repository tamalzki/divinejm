<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankDeposit extends Model
{
    protected $fillable = [
        'bank_name',
        'deposit_type',
        'source_type',
        'expense_id',
        'amount',
        'deposit_date',
        'notes',
        'created_by',
    ];

    public function expense()
    {
        return $this->belongsTo(\App\Models\Expense::class);
    }

    protected $casts = [
        'deposit_date' => 'date',
        'amount' => 'decimal:2',
    ];
}
