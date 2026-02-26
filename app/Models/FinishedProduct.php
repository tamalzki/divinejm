<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedProduct extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'product_type',
        'quantity',              // DEPRECATED - keep for backwards compatibility but don't use
        'minimum_stock',
        'cost_price',
        'selling_price',
        'total_cost',
        'stock_on_hand',        // PRIMARY: Stock available in warehouse
        'stock_out',            // PRIMARY: Stock deployed to branches
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

    // RELATIONSHIPS
    public function sales()
    {
        return $this->hasMany(Sale::class);
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

    // COMPUTED PROPERTIES
    
    /**
     * Get total inventory (warehouse + branches)
     */
    public function getTotalInventoryAttribute()
    {
        return $this->stock_on_hand + $this->stock_out;
    }

    /**
     * Get available stock in warehouse
     */
    public function getAvailableStockAttribute()
    {
        return $this->stock_on_hand;
    }

    /**
     * Get deployed stock (in branches)
     */
    public function getDeployedStockAttribute()
    {
        return $this->stock_out;
    }

    /**
     * Check if product is low on stock (based on warehouse stock)
     */
    public function isLowStock()
    {
        return $this->stock_on_hand <= $this->minimum_stock;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute()
    {
        if ($this->stock_on_hand == 0 && $this->stock_out == 0) {
            return 'out_of_stock';
        } elseif ($this->stock_on_hand == 0 && $this->stock_out > 0) {
            return 'all_deployed';
        } elseif ($this->isLowStock()) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    /**
     * Get stock status badge color
     */
    public function getStockBadgeColorAttribute()
    {
        return match($this->stock_status) {
            'out_of_stock' => 'danger',
            'all_deployed' => 'warning',
            'low_stock' => 'warning',
            'in_stock' => 'success',
            default => 'secondary'
        };
    }

    /**
     * Get stock status label
     */
    public function getStockStatusLabelAttribute()
    {
        return match($this->stock_status) {
            'out_of_stock' => 'Out of Stock',
            'all_deployed' => 'All Deployed',
            'low_stock' => 'Low Stock',
            'in_stock' => 'In Stock',
            default => 'Unknown'
        };
    }

    // PRODUCT TYPE HELPERS
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

    // EXPIRY HELPERS
    public function isExpiringSoon($days = 7)
    {
        if (!$this->expiry_date) {
            return false;
        }
        
        return $this->expiry_date->diffInDays(now()) <= $days && !$this->is_expired;
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
        if (!$this->expiry_date) {
            return null;
        }
        
        return max(0, $this->expiry_date->diffInDays(now()));
    }

    // STOCK MOVEMENT HELPERS
    
    /**
     * Add stock from production/restock
     */
    public function addStock($quantity)
    {
        $this->increment('stock_on_hand', $quantity);
        // Don't update 'quantity' field anymore
    }

    /**
     * Deploy stock to branch
     */
    public function deployToBranch($quantity)
    {
        if ($quantity > $this->stock_on_hand) {
            throw new \Exception("Insufficient stock. Available: {$this->stock_on_hand}");
        }

        $this->decrement('stock_on_hand', $quantity);
        $this->increment('stock_out', $quantity);
    }

    /**
     * Return stock from branch
     */
    public function returnFromBranch($quantity)
    {
        if ($quantity > $this->stock_out) {
            throw new \Exception("Cannot return more than deployed. Deployed: {$this->stock_out}");
        }

        $this->increment('stock_on_hand', $quantity);
        $this->decrement('stock_out', $quantity);
    }

    /**
     * Sell stock from warehouse
     */
    public function sellStock($quantity)
    {
        if ($quantity > $this->stock_on_hand) {
            throw new \Exception("Insufficient stock for sale. Available: {$this->stock_on_hand}");
        }

        $this->decrement('stock_on_hand', $quantity);
    }

    // ADD THIS TO YOUR FinishedProduct MODEL
// File: app/Models/FinishedProduct.php

// Add this method to the existing FinishedProduct class:

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
}