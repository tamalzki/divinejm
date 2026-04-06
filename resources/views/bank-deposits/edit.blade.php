@extends('layouts.sidebar')

@section('page-title', 'Edit Bank Deposit')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Edit Bank Deposit</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('bank-deposits.update', $bankDeposit->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-bold">Bank Name</label>
                <input type="text" name="bank_name" class="form-control"
                       value="{{ old('bank_name', $bankDeposit->bank_name) }}" required>
            </div>

            <div class="mb-3">
                <label>Date</label>
                <input type="date" name="deposit_date" class="form-control"
                       value="{{ old('deposit_date', $bankDeposit->deposit_date->format('Y-m-d')) }}" required>
            </div>

            <div class="mb-3">
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control"
                       value="{{ old('amount', $bankDeposit->amount) }}" required>
            </div>

            <div class="mb-3">
                <label>Notes</label>
                <textarea name="notes" class="form-control">{{ old('notes', $bankDeposit->notes) }}</textarea>
            </div>

            <button type="submit" class="btn btn-success">Update</button>
            <a href="{{ route('bank-deposits.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
