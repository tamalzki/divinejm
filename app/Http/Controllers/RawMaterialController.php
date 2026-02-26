<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RawMaterialController extends Controller
{
    public function index()
    {
        $rawMaterials = RawMaterial::with(['recipes.finishedProduct'])
            ->orderBy('name')
            ->paginate(15);
        return view('raw-materials.index', compact('rawMaterials'));
    }

    public function create()
    {
        return view('raw-materials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:ingredient,packaging',
            'unit' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        RawMaterial::create($validated);

        return redirect()->route('raw-materials.index')
            ->with('success', 'Raw material added successfully!');
    }

    public function show(RawMaterial $rawMaterial)
    {
        $rawMaterial->load(['recipes.finishedProduct', 'usages.user']);
        
        $usageHistory = $rawMaterial->usages()
            ->with('user')
            ->orderBy('usage_date', 'desc')
            ->paginate(10);
        
        return view('raw-materials.show', compact('rawMaterial', 'usageHistory'));
    }

    public function edit(RawMaterial $rawMaterial)
    {
        return view('raw-materials.edit', compact('rawMaterial'));
    }

    public function update(Request $request, RawMaterial $rawMaterial)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:ingredient,packaging',
            'unit' => 'required|string|max:50',
            'minimum_stock' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $rawMaterial->update($validated);

        return redirect()->route('raw-materials.index')
            ->with('success', 'Raw material updated successfully!');
    }

    public function destroy(RawMaterial $rawMaterial)
    {
        try {
            // Check if material is used in any recipes
            $usedInProducts = $rawMaterial->recipes()->with('finishedProduct')->get();
            
            if ($usedInProducts->count() > 0) {
                $productNames = $usedInProducts->pluck('finishedProduct.name')->unique()->implode(', ');
                return redirect()->route('raw-materials.index')
                    ->with('error', "⚠️ Cannot delete '{$rawMaterial->name}'!\n\nThis material is used in these products:\n{$productNames}\n\nPlease remove it from these recipes first.");
            }

            $rawMaterial->delete();

            return redirect()->route('raw-materials.index')
                ->with('success', "✅ Material '{$rawMaterial->name}' deleted successfully!");
                
        } catch (\Exception $e) {
            return redirect()->route('raw-materials.index')
                ->with('error', 'Failed to delete material: ' . $e->getMessage());
        }
    }

    public function recordUsage(Request $request, RawMaterial $rawMaterial)
    {
        $validated = $request->validate([
            'quantity_used' => 'required|numeric|min:0.01',
            'purpose' => 'required|string|max:255',
            'usage_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Check if enough stock
        if ($validated['quantity_used'] > $rawMaterial->quantity) {
            return back()
                ->withInput()
                ->with('error', "⚠️ Insufficient stock!\n\nAvailable: {$rawMaterial->quantity} {$rawMaterial->unit}\nRequested: {$validated['quantity_used']} {$rawMaterial->unit}\n\nPlease restock first or reduce the quantity.");
        }

        try {
            DB::beginTransaction();

            // Record usage
            $validated['raw_material_id'] = $rawMaterial->id;
            $validated['user_id'] = Auth::id();
            
            if (Schema::hasTable('raw_material_usages')) {
                RawMaterialUsage::create($validated);
            }

            // Deduct from inventory using direct query for reliability
            DB::table('raw_materials')
                ->where('id', $rawMaterial->id)
                ->decrement('quantity', $validated['quantity_used']);

            DB::commit();

            $newQuantity = $rawMaterial->quantity - $validated['quantity_used'];

            return redirect()->route('raw-materials.show', $rawMaterial)
                ->with('success', "✅ Usage recorded successfully!\n\nUsed: {$validated['quantity_used']} {$rawMaterial->unit}\nRemaining: {$newQuantity} {$rawMaterial->unit}");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Material usage error: " . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Failed to record usage: ' . $e->getMessage());
        }
    }

    public function restock(Request $request, RawMaterial $rawMaterial)
    {
        $validated = $request->validate([
            'quantity_added' => 'required|numeric|min:0.01',
            'restock_date' => 'required|date',
            'supplier' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Add to inventory using direct query
            DB::table('raw_materials')
                ->where('id', $rawMaterial->id)
                ->increment('quantity', $validated['quantity_added']);

            // Optionally update unit price if cost is provided
            if (isset($validated['cost']) && $validated['cost'] > 0) {
                $newUnitPrice = $validated['cost'] / $validated['quantity_added'];
                DB::table('raw_materials')
                    ->where('id', $rawMaterial->id)
                    ->update(['unit_price' => $newUnitPrice]);
            }

            // Record restock in usage table (negative quantity for restock)
            if (Schema::hasTable('raw_material_usages')) {
                DB::table('raw_material_usages')->insert([
                    'raw_material_id' => $rawMaterial->id,
                    'quantity_used' => -$validated['quantity_added'], // Negative = addition
                    'purpose' => 'restock',
                    'notes' => "Restocked from " . ($validated['supplier'] ?? 'supplier') . 
                               ($validated['cost'] ? ". Total cost: ₱" . number_format($validated['cost'], 2) : "") . 
                               ($validated['notes'] ? ". " . $validated['notes'] : ""),
                    'usage_date' => $validated['restock_date'],
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            $newQuantity = $rawMaterial->quantity + $validated['quantity_added'];
            
            return redirect()->route('raw-materials.show', $rawMaterial)
                ->with('success', "✅ Stock added successfully!\n\nAdded: {$validated['quantity_added']} {$rawMaterial->unit}\nNew Total: {$newQuantity} {$rawMaterial->unit}");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Material restock error: " . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Failed to restock: ' . $e->getMessage());
        }
    }
}