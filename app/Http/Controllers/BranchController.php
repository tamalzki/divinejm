<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::with(['branchCustomers' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->withCount(['inventory', 'branchCustomers'])
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
        ], [
            'name.required' => 'Area name is required.',
            'name.max' => 'Area name must not exceed 255 characters.',
            'code.required' => 'Area code is required.',
            'code.max' => 'Area code must not exceed 50 characters.',
            'code.unique' => 'This area code is already in use. Please choose a different code.',
            'address.max' => 'Address must not exceed 500 characters.',
            'phone.max' => 'Phone number must not exceed 50 characters.',
            'customers.*.name.required' => 'Each customer row must have a name. Please fill in the name or remove the empty row.',
            'customers.*.name.max' => 'A customer name must not exceed 255 characters.',
            'customers.*.phone.max' => 'A customer phone number must not exceed 50 characters.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);
        $validated['is_active'] = $request->has('is_active');

        if (! empty($validated['customers'])) {
            $validated['customers'] = array_values($validated['customers']);
        } else {
            $validated['customers'] = [];
        }

        $customerRows = $validated['customers'];
        unset($validated['customers']);

        $branch = DB::transaction(function () use ($validated, $customerRows) {
            $branch = Branch::create($validated);
            $branch->syncCustomersFromForm($customerRows);

            return $branch;
        });

        return redirect()->route('branches.index')
            ->with('success', "Area \"{$validated['name']}\" created successfully!");
    }

    public function show(Branch $branch)
    {
        $branch->load(['branchCustomers' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->loadCount('inventory');

        return view('branches.show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        $branch->load(['branchCustomers' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->loadCount('inventory');

        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,'.$branch->id,
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'customers' => 'nullable|array',
            'customers.*.name' => 'required|string|max:255',
            'customers.*.phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Area name is required.',
            'name.max' => 'Area name must not exceed 255 characters.',
            'code.required' => 'Area code is required.',
            'code.max' => 'Area code must not exceed 50 characters.',
            'code.unique' => 'This area code is already in use. Please choose a different code.',
            'address.max' => 'Address must not exceed 500 characters.',
            'phone.max' => 'Phone number must not exceed 50 characters.',
            'customers.*.name.required' => 'Each customer row must have a name. Please fill in the name or remove the empty row.',
            'customers.*.name.max' => 'A customer name must not exceed 255 characters.',
            'customers.*.phone.max' => 'A customer phone number must not exceed 50 characters.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);
        $validated['is_active'] = $request->has('is_active');

        if (! empty($validated['customers'])) {
            $validated['customers'] = array_values($validated['customers']);
        } else {
            $validated['customers'] = [];
        }

        $customerRows = $validated['customers'];
        unset($validated['customers']);

        DB::transaction(function () use ($branch, $validated, $customerRows) {
            $branch->update($validated);
            $branch->syncCustomersFromForm($customerRows);
        });

        return redirect()->route('branches.show', $branch)
            ->with('success', "Area \"{$validated['name']}\" updated successfully!");
    }

    public function destroy(Branch $branch)
    {
        if ($branch->inventory()->count() > 0) {
            return back()->with('error', "Cannot delete \"{$branch->name}\" — this area has {$branch->inventory()->count()} product(s) in inventory. Remove the inventory first.");
        }

        $branchName = $branch->name;
        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', "Area \"{$branchName}\" deleted successfully!");
    }
}
