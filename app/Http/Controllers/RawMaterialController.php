<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RawMaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = RawMaterial::with(['recipes.finishedProduct'])
            ->orderBy('name');

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Stock level filter (no ?filter = show all materials)
        if ($request->filter === 'out') {
            $query->where('quantity', 0);
        } elseif ($request->filter === 'low') {
            $query->whereColumn('quantity', '<=', 'minimum_stock')
                  ->where('quantity', '>', 0);
        } elseif ($request->filter === 'good') {
            $query->whereColumn('quantity', '>', 'minimum_stock');
        }
        // no filter param = all materials

        $rawMaterials = $query->paginate(15)->withQueryString();

        return view('raw-materials.index', compact('rawMaterials'));
    }

    public function create()
    {
        return view('raw-materials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('raw_materials', 'name'),
            ],
            'category'      => 'required|in:ingredient,packaging',
            'unit'          => 'required|string|max:50',
            'quantity'      => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'unit_price'    => 'required|numeric|min:0',
            'description'   => 'nullable|string',
        ], [
            'name.unique' => 'A material with this name already exists. Please use a different name.',
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('raw_materials', 'name')->ignore($rawMaterial->id),
            ],
            'category'      => 'required|in:ingredient,packaging',
            'unit'          => 'required|string|max:50',
            'minimum_stock' => 'required|numeric|min:0',
            'unit_price'    => 'required|numeric|min:0',
            'description'   => 'nullable|string',
        ], [
            'name.unique' => 'Another material already has this name. Please use a different name.',
        ]);

        $rawMaterial->update($validated);

        return redirect()->route('raw-materials.index')
            ->with('success', 'Raw material updated successfully!');
    }

    public function destroy(RawMaterial $rawMaterial)
    {
        try {
            $usedInProducts = $rawMaterial->recipes()->with('finishedProduct')->get();

            if ($usedInProducts->count() > 0) {
                $productNames = $usedInProducts->pluck('finishedProduct.name')->unique()->implode(', ');
                return redirect()->route('raw-materials.index')
                    ->with('error', "⚠️ Cannot delete '{$rawMaterial->name}'! This material is used in: {$productNames}. Please remove it from these recipes first.");
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
            'purpose'       => 'required|string|max:255',
            'usage_date'    => 'required|date',
            'notes'         => 'nullable|string',
        ]);

        if ($validated['quantity_used'] > $rawMaterial->quantity) {
            return back()
                ->withInput()
                ->with('error', "⚠️ Insufficient stock! Available: {$rawMaterial->quantity} {$rawMaterial->unit}, Requested: {$validated['quantity_used']} {$rawMaterial->unit}. Please restock first or reduce the quantity.");
        }

        try {
            DB::beginTransaction();

            $validated['raw_material_id'] = $rawMaterial->id;
            $validated['user_id']         = Auth::id();

            if (Schema::hasTable('raw_material_usages')) {
                RawMaterialUsage::create($validated);
            }

            DB::table('raw_materials')
                ->where('id', $rawMaterial->id)
                ->decrement('quantity', $validated['quantity_used']);

            DB::commit();

            $newQuantity = $rawMaterial->quantity - $validated['quantity_used'];

            return redirect()->route('raw-materials.show', $rawMaterial)
                ->with('success', "✅ Usage recorded! Used: {$validated['quantity_used']} {$rawMaterial->unit}. Remaining: {$newQuantity} {$rawMaterial->unit}.");

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
            'restock_date'   => 'required|date',
            'supplier'       => 'nullable|string|max:255',
            'cost'           => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            DB::table('raw_materials')
                ->where('id', $rawMaterial->id)
                ->increment('quantity', $validated['quantity_added']);

            if (isset($validated['cost']) && $validated['cost'] > 0) {
                $newUnitPrice = $validated['cost'] / $validated['quantity_added'];
                DB::table('raw_materials')
                    ->where('id', $rawMaterial->id)
                    ->update(['unit_price' => $newUnitPrice]);
            }

            if (Schema::hasTable('raw_material_usages')) {
                DB::table('raw_material_usages')->insert([
                    'raw_material_id' => $rawMaterial->id,
                    'quantity_used'   => -$validated['quantity_added'],
                    'purpose'         => 'restock',
                    'notes'           => "Restocked from " . ($validated['supplier'] ?? 'supplier') .
                                        ($validated['cost'] ? ". Total cost: ₱" . number_format($validated['cost'], 2) : "") .
                                        ($validated['notes'] ? ". " . $validated['notes'] : ""),
                    'usage_date'      => $validated['restock_date'],
                    'user_id'         => Auth::id(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            DB::commit();

            $newQuantity = $rawMaterial->quantity + $validated['quantity_added'];

            return redirect()->route('raw-materials.show', $rawMaterial)
                ->with('success', "✅ Stock added! Added: {$validated['quantity_added']} {$rawMaterial->unit}. New Total: {$newQuantity} {$rawMaterial->unit}.");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Material restock error: " . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Failed to restock: ' . $e->getMessage());
        }
    }
}