<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RawMaterialController extends Controller
{
    /** @return list<string> */
    protected function allowedRawMaterialUnitKeys(): array
    {
        return array_keys(config('raw_materials.units', []));
    }

    /** @return array<int, \Illuminate\Contracts\Validation\Rule|string> */
    protected function rawMaterialUnitValidationRule(?RawMaterial $existing = null): array
    {
        $allowed = $this->allowedRawMaterialUnitKeys();
        if ($existing && $existing->unit !== null && $existing->unit !== '' && ! in_array($existing->unit, $allowed, true)) {
            $allowed[] = $existing->unit;
        }

        return ['required', 'string', Rule::in($allowed)];
    }

    public function index(Request $request)
    {
        $query = RawMaterial::with(['recipes.finishedProduct'])
            ->orderBy('name');

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
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
            'category' => 'required|in:ingredient,packaging',
            'unit' => $this->rawMaterialUnitValidationRule(),
            'quantity' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ], [
            'name.unique' => 'A material with this name already exists. Please use a different name.',
        ]);

        RawMaterial::create($validated);

        return redirect()->route('raw-materials.index')
            ->with('success', 'Raw material added successfully!');
    }

    public function show(Request $request, RawMaterial $rawMaterial)
    {
        $rawMaterial->load(['recipes.finishedProduct', 'usages.user']);

        $usageHistory = $rawMaterial->usages()
            ->with('user')
            ->when($request->filled('type'), function ($q) use ($request) {
                if ($request->type === 'restock') {
                    $q->where('purpose', 'restock');
                } elseif ($request->type === 'adjustment') {
                    $q->where('purpose', 'adjustment');
                } elseif ($request->type === 'usage') {
                    $q->whereNotIn('purpose', ['restock', 'adjustment']);
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->search.'%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('notes', 'like', $term)
                        ->orWhere('purpose', 'like', $term);
                });
            })
            ->orderBy('usage_date', 'desc')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

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
            'category' => 'required|in:ingredient,packaging',
            'unit' => $this->rawMaterialUnitValidationRule($rawMaterial),
            'minimum_stock' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
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
                ->with('error', 'Failed to delete material: '.$e->getMessage());
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

        if ($validated['quantity_used'] > $rawMaterial->quantity) {
            return back()
                ->withInput()
                ->with('error', "⚠️ Insufficient stock! Available: {$rawMaterial->quantity} {$rawMaterial->unit}, Requested: {$validated['quantity_used']} {$rawMaterial->unit}. Please restock first or reduce the quantity.");
        }

        try {
            DB::beginTransaction();

            $validated['raw_material_id'] = $rawMaterial->id;
            $validated['user_id'] = Auth::id();

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
            \Log::error('Material usage error: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Failed to record usage: '.$e->getMessage());
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
                    'quantity_used' => -$validated['quantity_added'],
                    'purpose' => 'restock',
                    'notes' => 'Restocked from '.($validated['supplier'] ?? 'supplier').
                                        ($validated['cost'] ? '. Total cost: ₱'.number_format($validated['cost'], 2) : '').
                                        ($validated['notes'] ? '. '.$validated['notes'] : ''),
                    'usage_date' => $validated['restock_date'],
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            $newQuantity = $rawMaterial->quantity + $validated['quantity_added'];

            return redirect()->route('raw-materials.show', $rawMaterial)
                ->with('success', "✅ Stock added! Added: {$validated['quantity_added']} {$rawMaterial->unit}. New Total: {$newQuantity} {$rawMaterial->unit}.");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Material restock error: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Failed to restock: '.$e->getMessage());
        }
    }

    /**
     * Set on-hand quantity to an exact value (cycle count / correction). Logs an adjustment row.
     */
    public function adjust(Request $request, RawMaterial $rawMaterial)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0',
            'adjustment_date' => 'required|date',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $locked = RawMaterial::whereKey($rawMaterial->id)->lockForUpdate()->firstOrFail();
            $oldQty = (float) $locked->quantity;
            $newQty = (float) $validated['quantity'];
            $delta = round($newQty - $oldQty, 2);

            if (abs($delta) < 0.0001) {
                DB::commit();

                return redirect()
                    ->back()
                    ->with('success', 'Stock is already '.$newQty.' '.$locked->unit.'. No change saved.');
            }

            $locked->quantity = $newQty;
            $locked->save();

            if (Schema::hasTable('raw_material_usages')) {
                $noteParts = ['Physical count / stock correction', 'from '.number_format($oldQty, 2).' → '.number_format($newQty, 2).' '.$locked->unit];
                if (! empty($validated['reason'])) {
                    $noteParts[] = $validated['reason'];
                }
                RawMaterialUsage::create([
                    'raw_material_id' => $locked->id,
                    'quantity_used' => -$delta,
                    'purpose' => 'adjustment',
                    'notes' => implode(' — ', $noteParts),
                    'usage_date' => $validated['adjustment_date'],
                    'user_id' => Auth::id(),
                ]);
            }

            DB::commit();

            Log::info('Raw material stock adjusted', [
                'raw_material_id' => $locked->id,
                'old_quantity' => $oldQty,
                'new_quantity' => $newQty,
                'user_id' => Auth::id(),
            ]);

            $dir = $delta > 0 ? 'increased' : 'decreased';

            return redirect()
                ->back()
                ->with('success', 'Stock '.$dir.' from '.number_format($oldQty, 2).' to '.number_format($newQty, 2).' '.$locked->unit.'.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Raw material adjust failed', ['id' => $rawMaterial->id, 'message' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Could not adjust stock. Please try again.');
        }
    }
}
