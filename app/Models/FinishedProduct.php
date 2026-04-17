<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedProduct extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'barcode',              // ← ADDED
        'product_type',
        'quantity',
        'minimum_stock',
        'cost_price',
        'selling_price',
        'total_cost',
        'stock_on_hand',
        'stock_out',
        'description',
        'manufacturing_date',
        'expiry_date',
        'is_expired',
        'shelf_life_days',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'stock_on_hand' => 'decimal:2',
        'stock_out' => 'decimal:2',
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'is_expired' => 'boolean',
    ];

    // ── RELATIONSHIPS ─────────────────────────────────────────────

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'finished_product_id');
    }

    public function restocks()
    {
        return $this->hasMany(FinishedProductRestock::class);
    }

    public function recipes()
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function rawMaterials()
    {
        return $this->belongsToMany(RawMaterial::class, 'product_recipes')
            ->withPivot('quantity_needed', 'cost_per_unit')
            ->withTimestamps();
    }

    public function branchInventory()
    {
        return $this->hasMany(BranchInventory::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_inventory')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function productionMixes()
    {
        return $this->hasMany(ProductionMix::class);
    }

    public function latestMix()
    {
        return $this->hasOne(ProductionMix::class)->latest();
    }

    public function completedMixes()
    {
        return $this->hasMany(ProductionMix::class)->where('status', 'completed');
    }

    public function pendingMixes()
    {
        return $this->hasMany(ProductionMix::class)->where('status', 'pending');
    }

    // ── COMPUTED PROPERTIES ───────────────────────────────────────

    public function getTotalInventoryAttribute()
    {
        return $this->stock_on_hand + $this->stock_out;
    }

    public function getAvailableStockAttribute()
    {
        return $this->stock_on_hand;
    }

    public function getDeployedStockAttribute()
    {
        return $this->stock_out;
    }

    public function isLowStock()
    {
        return $this->stock_on_hand <= $this->minimum_stock;
    }

    public function getStockStatusAttribute()
    {
        if ($this->stock_on_hand == 0 && $this->stock_out == 0) {
            return 'out_of_stock';
        }
        if ($this->stock_on_hand == 0 && $this->stock_out > 0) {
            return 'all_deployed';
        }
        if ($this->isLowStock()) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    public function getStockBadgeColorAttribute()
    {
        return match ($this->stock_status) {
            'out_of_stock' => 'danger',
            'all_deployed' => 'warning',
            'low_stock' => 'warning',
            'in_stock' => 'success',
            default => 'secondary',
        };
    }

    public function getStockStatusLabelAttribute()
    {
        return match ($this->stock_status) {
            'out_of_stock' => 'Out of Stock',
            'all_deployed' => 'All Deployed',
            'low_stock' => 'Low Stock',
            'in_stock' => 'In Stock',
            default => 'Unknown',
        };
    }

    // ── PRODUCT TYPE HELPERS ──────────────────────────────────────

    public function isManufactured()
    {
        return $this->product_type === 'manufactured';
    }

    public function isConsigned()
    {
        return $this->product_type === 'consigned';
    }

    public function calculateTotalCost()
    {
        if ($this->isManufactured()) {
            return $this->recipes()->sum(\DB::raw('quantity_needed * cost_per_unit'));
        }

        return $this->cost_price;
    }

    // ── EXPIRY HELPERS ────────────────────────────────────────────

    public function isExpiringSoon($days = 7)
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->diffInDays(now()) <= $days && ! $this->is_expired;
    }

    public function checkExpiry()
    {
        if ($this->expiry_date && $this->expiry_date < now()) {
            $this->update(['is_expired' => true]);

            return true;
        }

        return false;
    }

    public function daysUntilExpiry()
    {
        if (! $this->expiry_date) {
            return null;
        }

        return max(0, $this->expiry_date->diffInDays(now()));
    }

    // ── STOCK MOVEMENT HELPERS ────────────────────────────────────

    public function addStock($quantity)
    {
        $this->increment('stock_on_hand', $quantity);
    }

    public function deployToBranch($quantity)
    {
        $this->decrement('stock_on_hand', $quantity);
        $this->increment('stock_out', $quantity);
    }

    public function returnFromBranch($quantity)
    {
        if ($quantity > $this->stock_out) {
            throw new \Exception("Cannot return more than deployed. Deployed: {$this->stock_out}");
        }
        $this->increment('stock_on_hand', $quantity);
        $this->decrement('stock_out', $quantity);
    }

    public function sellStock($quantity)
    {
        $this->decrement('stock_on_hand', $quantity);
    }

    // ── BARCODE HELPER ────────────────────────────────────────────

    /**
     * Generate a unique barcode string for this product.
     * Format: DJM-XXXXXX  e.g. DJM-000042
     * Called after create() so the ID is available.
     */
    public function generateBarcode(): string
    {
        $base = 'DJM-'.str_pad($this->id, 6, '0', STR_PAD_LEFT);
        $candidate = $base;
        $suffix = 0;

        while (
            static::where('barcode', $candidate)
                ->where('id', '!=', $this->id)
                ->exists()
        ) {
            $suffix++;
            $candidate = $base.'-'.$suffix;
        }

        return $candidate;
    }
}
