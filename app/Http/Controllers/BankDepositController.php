<?php

namespace App\Http\Controllers;

use App\Models\BankDeposit;
use App\Models\Expense;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankDepositController extends Controller
{
    public function index()
    {
        $deposits = BankDeposit::with('expense')->orderBy('deposit_date', 'desc')->get();

        // Cash position from PAID sales grouped by payment mode
        $paidSales = Sale::where('payment_status', 'paid')->get();

        $cashCollected    = $paidSales->where('payment_mode', 'cash')->sum('amount_paid');
        $gcashCollected   = $paidSales->where('payment_mode', 'gcash')->sum('amount_paid');
        $chequeCollected  = $paidSales->where('payment_mode', 'cheque')->sum('amount_paid');
        $bankCollected    = $paidSales->where('payment_mode', 'bank_transfer')->sum('amount_paid');

        // Check expenses that need to be deposited (check payment method)
        $checkExpenses = Expense::where('payment_method', 'check')
            ->orderBy('expense_date', 'desc')
            ->get();
        $totalCheckExpenses = $checkExpenses->sum('amount');

        // Cash on hand = cash collected minus cash deposits already made
        $totalDeposited  = $deposits->sum('amount');
        $cashDeposited   = $deposits->where('deposit_type', 'cash_deposit')->sum('amount');
        $cashOnHand      = max(0, $cashCollected - $cashDeposited);

        return view('bank-deposits.index', compact(
            'deposits',
            'cashCollected',
            'gcashCollected',
            'chequeCollected',
            'bankCollected',
            'cashOnHand',
            'totalDeposited',
            'checkExpenses',
            'totalCheckExpenses'
        ));
    }

    public function create()
    {
        $totalCashDeposited = BankDeposit::where('deposit_type', 'cash_deposit')->sum('amount');
        $cashCollected = Sale::where('payment_status', 'paid')
            ->where('payment_mode', 'cash')
            ->sum('amount_paid');
        $cashOnHand = max(0, $cashCollected - $totalCashDeposited);

        // Pending check expenses (not yet linked to a deposit)
        $linkedExpenseIds = BankDeposit::where('deposit_type', 'check_deposit')
            ->whereNotNull('expense_id')
            ->pluck('expense_id');

        $checkExpenses = Expense::where('payment_method', 'check')
            ->whereNotIn('id', $linkedExpenseIds)
            ->orderBy('expense_date', 'desc')
            ->get();

        return view('bank-deposits.create', compact('cashOnHand', 'checkExpenses'));
    }

    public function store(Request $request)
    {
        $depositType = $request->input('deposit_type', 'cash_deposit');

        $rules = [
            'deposit_type'  => 'required|in:cash_deposit,check_deposit',
            'bank_name'     => 'required|string|max:255',
            'amount'        => 'required|numeric|min:0.01',
            'deposit_date'  => 'required|date',
            'notes'         => 'nullable|string',
        ];

        if ($depositType === 'check_deposit') {
            $rules['expense_id'] = 'nullable|exists:expenses,id';
        }

        $validated = $request->validate($rules);

        $sourceType = null;
        if ($depositType === 'cash_deposit') {
            $sourceType = 'sales';
        } elseif ($depositType === 'check_deposit' && ! empty($validated['expense_id'])) {
            $sourceType = 'expense';
        }

        BankDeposit::create([
            'bank_name'    => $validated['bank_name'],
            'deposit_type' => $validated['deposit_type'],
            'source_type'  => $sourceType,
            'expense_id'   => $validated['expense_id'] ?? null,
            'amount'       => $validated['amount'],
            'deposit_date' => $validated['deposit_date'],
            'notes'        => $validated['notes'] ?? null,
            'created_by'   => Auth::id(),
        ]);

        return redirect()->route('bank-deposits.index')
            ->with('success', 'Deposit recorded successfully.');
    }

    public function edit(BankDeposit $bankDeposit)
    {
        $checkExpenses = Expense::where('payment_method', 'check')
            ->orderBy('expense_date', 'desc')
            ->get();

        return view('bank-deposits.edit', compact('bankDeposit', 'checkExpenses'));
    }

    public function update(Request $request, BankDeposit $bankDeposit)
    {
        $validated = $request->validate([
            'bank_name'    => 'required|string|max:255',
            'deposit_type' => 'required|in:cash_deposit,check_deposit',
            'expense_id'   => 'nullable|exists:expenses,id',
            'amount'       => 'required|numeric|min:0',
            'deposit_date' => 'required|date',
            'notes'        => 'nullable|string',
        ]);

        $sourceType = null;
        if ($validated['deposit_type'] === 'cash_deposit') {
            $sourceType = 'sales';
        } elseif ($validated['deposit_type'] === 'check_deposit' && ! empty($validated['expense_id'])) {
            $sourceType = 'expense';
        }

        $bankDeposit->update(array_merge($validated, ['source_type' => $sourceType]));

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
