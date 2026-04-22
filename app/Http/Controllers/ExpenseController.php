<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $expensesQuery = Expense::query();

        if ($search !== '') {
            $expensesQuery->where(function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%");
            });
        }

        $expenses = $expensesQuery
            ->orderBy('expense_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        $monthTotal = Expense::whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');
        $todayTotal = Expense::whereDate('expense_date', now()->toDateString())->sum('amount');
        $totalRecords = Expense::count();

        return view('expenses.index', compact('expenses', 'search', 'monthTotal', 'todayTotal', 'totalRecords'));
    }

    public function create()
    {
        return view('expenses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,bank_transfer,check,other',
            'notes' => 'nullable|string',
        ]);

        Expense::create($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense added successfully!');
    }

    public function show(Expense $expense)
    {
        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        return view('expenses.edit', compact('expense'));
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,bank_transfer,check,other',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully!');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully!');
    }
}
