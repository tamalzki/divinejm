<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'customers',
        'is_active',
    ];

    protected $casts = [
        'customers' => 'array',
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

    // Helper to get customers from JSON - UPDATED TO HANDLE OBJECTS
    public function getCustomersListAttribute()
    {
        $customers = $this->customers;
        
        // If customers is a string, decode it
        if (is_string($customers)) {
            $customers = json_decode($customers, true) ?? [];
        }
        
        // If it's not an array, return empty array
        if (!is_array($customers)) {
            return [];
        }
        
        // Extract customer names if they're objects
        $customerNames = array_map(function($customer) {
            // If customer is an array with a 'name' field
            if (is_array($customer) && isset($customer['name'])) {
                return $customer['name'];
            }
            // If customer is an object with a 'name' property
            elseif (is_object($customer) && isset($customer->name)) {
                return $customer->name;
            }
            // Otherwise assume it's already a string
            return $customer;
        }, $customers);
        
        // Return unique customer names
        return array_values(array_unique($customerNames));
    }

    // Helper to add customer - UPDATED TO SAVE AS STRING ARRAY
    public function addCustomer($customerName)
    {
        $customers = $this->customers_list;
        
        if (!in_array($customerName, $customers)) {
            $customers[] = $customerName;
            // Save as simple string array (not objects)
            $this->customers = $customers;
            $this->save();
        }
    }
}