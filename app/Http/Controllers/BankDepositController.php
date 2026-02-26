<?php

namespace App\Http\Controllers;

use App\Models\BankDeposit;
use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BankDepositController extends Controller
{
    public function index()
    {
        $deposits = BankDeposit::orderBy('deposit_date', 'desc')->get();

        return view('bank-deposits.index', compact('deposits'));
    }

    public function create()
    {
        // Available cash from sales (cash only)
        $availableCash = Sale::where('payment_method', 'cash')
            ->sum('total_amount');

        $totalDeposited = BankDeposit::sum('amount');

        $cashOnHand = $availableCash - $totalDeposited;

        return view('bank-deposits.create', compact('cashOnHand'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'deposit_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        BankDeposit::create([
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'amount' => $validated['amount'],
            'deposit_date' => $validated['deposit_date'],
            'notes' => $validated['notes'],
            'created_by' => Auth::id()
        ]);

        return redirect()->route('bank-deposits.index')
            ->with('success', '✅ Deposit recorded successfully!');
    }

    public function edit(BankDeposit $bankDeposit)
{
    return view('bank-deposits.edit', compact('bankDeposit'));
}

public function update(Request $request, BankDeposit $bankDeposit)
{
    $request->validate([
        'deposit_date' => 'required|date',
        'amount' => 'required|numeric|min:0',
        'reference_number' => 'nullable|string|max:255',
        'remarks' => 'nullable|string'
    ]);

    $bankDeposit->update($request->all());

    return redirect()
        ->route('bank-deposits.index')
        ->with('success', 'Bank deposit updated successfully.');
}

public function destroy(BankDeposit $bankDeposit)
{
    $bankDeposit->delete();

    return redirect()
        ->route('bank-deposits.index')
        ->with('success', 'Bank deposit deleted successfully.');
}
}