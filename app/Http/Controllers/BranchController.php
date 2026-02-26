<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount('inventory')
            ->orderBy('name')
            ->paginate(15);
            
        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        return view('branches.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'customers' => 'nullable|array',
            'customers.*.name' => 'required|string|max:255',
            'customers.*.phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        // Auto-uppercase the code
        $validated['code'] = strtoupper($validated['code']);
        $validated['is_active'] = $request->has('is_active');
        
        // Convert customers array to proper format
        if (isset($validated['customers'])) {
            $validated['customers'] = array_values($validated['customers']);
        }

        Branch::create($validated);

        return redirect()->route('branches.index')
            ->with('success', "Branch '{$validated['name']}' created successfully!");
    }

    public function edit(Branch $branch)
    {
        $branch->loadCount('inventory');
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,' . $branch->id,
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'customers' => 'nullable|array',
            'customers.*.name' => 'required|string|max:255',
            'customers.*.phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        // Auto-uppercase the code
        $validated['code'] = strtoupper($validated['code']);
        $validated['is_active'] = $request->has('is_active');
        
        // Convert customers array to proper format
        if (isset($validated['customers'])) {
            $validated['customers'] = array_values($validated['customers']);
        } else {
            $validated['customers'] = [];
        }

        $branch->update($validated);

        return redirect()->route('branches.index')
            ->with('success', "Branch '{$validated['name']}' updated successfully!");
    }

    public function destroy(Branch $branch)
    {
        // Check if branch has inventory
        if ($branch->inventory()->count() > 0) {
            return back()->with('error', "Cannot delete '{$branch->name}' - branch has {$branch->inventory()->count()} products in inventory!");
        }

        $branchName = $branch->name;
        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', "Branch '{$branchName}' deleted successfully!");
    }
}