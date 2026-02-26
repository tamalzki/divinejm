<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'customer_name',
        'dr_number',
        'sale_date',
        'total_amount',
        'amount_paid',
        'balance',
        'payment_status',
        'payment_mode',
        'payment_reference',
        'payment_date',
        'notes',
        'status',
        'user_id',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($sale) {
            // Calculate balance
            $sale->balance = $sale->total_amount - $sale->amount_paid;
            
            // Auto-update payment status
            if ($sale->amount_paid >= $sale->total_amount) {
                $sale->payment_status = 'paid';
            } elseif ($sale->amount_paid > 0) {
                $sale->payment_status = 'partial';
            } else {
                $sale->payment_status = 'to_be_collected';
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function recalculateTotal()
    {
        $this->total_amount = $this->items()->sum('subtotal');
        $this->balance = $this->total_amount - $this->amount_paid;
        $this->saveQuietly();
    }

    public function getPaymentStatusBadgeAttribute()
    {
        return match($this->payment_status) {
            'paid' => 'success',
            'partial' => 'warning',
            'to_be_collected' => 'danger',
            default => 'secondary'
        };
    }

    public function getPaymentStatusLabelAttribute()
    {
        return match($this->payment_status) {
            'paid' => 'Paid',
            'partial' => 'Partially Paid',
            'to_be_collected' => 'To Be Collected',
            default => ucfirst($this->payment_status)
        };
    }

    public function getPaymentModeLabelAttribute()
    {
        return match($this->payment_mode) {
            'cash' => 'Cash',
            'gcash' => 'GCash',
            'cheque' => 'Cheque',
            'bank_transfer' => 'Bank Transfer',
            'other' => 'Other',
            default => 'N/A'
        };
    }

    // Scopes
    public function scopeToBeCollected($query)
    {
        return $query->whereIn('payment_status', ['to_be_collected', 'partial']);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDrNumber($query, $drNumber)
    {
        return $query->where('dr_number', $drNumber);
    }
}