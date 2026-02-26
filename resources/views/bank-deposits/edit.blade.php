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
                <label>Date</label>
                <input type="date" name="deposit_date" class="form-control"
                       value="{{ $bankDeposit->deposit_date }}" required>
            </div>

            <div class="mb-3">
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control"
                       value="{{ $bankDeposit->amount }}" required>
            </div>

            <div class="mb-3">
                <label>Reference</label>
                <input type="text" name="reference_number" class="form-control"
                       value="{{ $bankDeposit->reference_number }}">
            </div>

            <div class="mb-3">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control">{{ $bankDeposit->remarks }}</textarea>
            </div>

            <button type="submit" class="btn btn-success">Update</button>
            <a href="{{ route('bank-deposits.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection