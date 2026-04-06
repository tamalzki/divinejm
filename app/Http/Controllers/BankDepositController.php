<?php

namespace App\Http\Controllers;

use App\Models\BankDeposit;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankDepositController extends Controller
{
    public function index()
    {
        $deposits = BankDeposit::orderBy('deposit_date', 'desc')->get();

        // Cash position from PAID sales grouped by payment mode
        $paidSales = Sale::where('payment_status', 'paid')->get();

        $cashCollected = $paidSales->where('payment_mode', 'cash')->sum('amount_paid');
        $gcashCollected = $paidSales->where('payment_mode', 'gcash')->sum('amount_paid');
        $chequeCollected = $paidSales->where('payment_mode', 'cheque')->sum('amount_paid');
        $bankCollected = $paidSales->where('payment_mode', 'bank_transfer')->sum('amount_paid');

        // Cash on hand = cash collected minus what's already been deposited
        $totalDeposited = $deposits->sum('amount');
        $cashOnHand = max(0, $cashCollected - $totalDeposited);

        return view('bank-deposits.index', compact(
            'deposits',
            'cashCollected',
            'gcashCollected',
            'chequeCollected',
            'bankCollected',
            'cashOnHand',
            'totalDeposited'
        ));
    }

    public function create()
    {
        $totalDeposited = BankDeposit::sum('amount');
        $cashCollected = Sale::where('payment_status', 'paid')
            ->where('payment_mode', 'cash')
            ->sum('amount_paid');
        $cashOnHand = max(0, $cashCollected - $totalDeposited);

        return view('bank-deposits.create', compact('cashOnHand'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'deposit_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        BankDeposit::create([
            'bank_name' => $validated['bank_name'],
            'amount' => $validated['amount'],
            'deposit_date' => $validated['deposit_date'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('bank-deposits.index')
            ->with('success', 'Deposit recorded successfully.');
    }

    public function edit(BankDeposit $bankDeposit)
    {
        return view('bank-deposits.edit', compact('bankDeposit'));
    }

    public function update(Request $request, BankDeposit $bankDeposit)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'deposit_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $bankDeposit->update($validated);

        return redirect()->route('bank-deposits.index')
            ->with('success', 'Bank deposit updated successfully.');
    }

    public function destroy(BankDeposit $bankDeposit)
    {
        $bankDeposit->delete();

        return redirect()->route('bank-deposits.index')
            ->with('success', 'Bank deposit deleted successfully.');
    }
}
