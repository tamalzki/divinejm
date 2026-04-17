<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function inventory()
    {
        return $this->hasMany(\App\Models\BranchInventory::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function branchCustomers(): HasMany
    {
        return $this->hasMany(BranchCustomer::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Customer names for dropdowns (unique, stable order by sort_order).
     */
    public function getCustomersListAttribute(): array
    {
        if ($this->relationLoaded('branchCustomers')) {
            return $this->branchCustomers->pluck('name')->unique()->values()->all();
        }

        if ($this->branchCustomers()->exists()) {
            return $this->branchCustomers()->pluck('name')->unique()->values()->all();
        }

        return [];
    }

    /**
     * @param  array<int, array{name?: string, phone?: string}>  $rows
     */
    public function syncCustomersFromForm(array $rows): void
    {
        $this->branchCustomers()->delete();

        foreach (array_values($rows) as $i => $row) {
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '') {
                continue;
            }

            $phoneRaw = $row['phone'] ?? null;
            $phone = $phoneRaw !== null && $phoneRaw !== '' ? trim((string) $phoneRaw) : null;

            $this->branchCustomers()->create([
                'name' => $name,
                'phone' => $phone ?: null,
                'sort_order' => $i,
            ]);
        }

        $this->unsetRelation('branchCustomers');
    }

    public function addCustomer(string $customerName): void
    {
        $name = trim($customerName);
        if ($name === '') {
            return;
        }

        if ($this->branchCustomers()->where('name', $name)->exists()) {
            return;
        }

        $nextOrder = (int) $this->branchCustomers()->max('sort_order') + 1;

        $this->branchCustomers()->create([
            'name' => $name,
            'phone' => null,
            'sort_order' => $nextOrder,
        ]);

        $this->unsetRelation('branchCustomers');
    }
}
