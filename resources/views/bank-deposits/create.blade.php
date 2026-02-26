@extends('layouts.sidebar')

@section('page-title', 'Deposit to Bank')

@section('content')

<div class="mb-4">
    <a href="{{ route('bank-deposits.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-2"></i>Back
    </a>
</div>

<div class="card mb-4 border-success">
    <div class="card-header bg-success text-white">
        <i class="bi bi-bank me-2"></i>Deposit Details
    </div>
    <div class="card-body">

        <div class="alert alert-info">
            <strong>Cash on Hand:</strong> ₱{{ number_format($cashOnHand, 2) }}
        </div>

        <form method="POST" action="{{ route('bank-deposits.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-bold">Bank Name</label>
                <input type="text" name="bank_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Account Number</label>
                <input type="text" name="account_number" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Deposit Date</label>
                <input type="date" name="deposit_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Notes</label>
                <textarea name="notes" class="form-control"></textarea>
            </div>

            <button class="btn btn-success btn-lg w-100">
                <i class="bi bi-check-circle me-2"></i>Confirm Deposit
            </button>
        </form>

    </div>
</div>

@endsection