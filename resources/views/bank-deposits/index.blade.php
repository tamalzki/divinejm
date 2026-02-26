@extends('layouts.sidebar')

@section('page-title', 'Bank Deposits')

@section('content')

<div class="d-flex justify-content-between mb-3">
    <h4><i class="bi bi-bank me-2"></i>Bank Deposits</h4>
    <a href="{{ route('bank-deposits.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle me-2"></i>New Deposit
    </a>
</div>

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Bank</th>
                    <th>Account</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deposits as $deposit)
                <tr>
                    <td>{{ $deposit->deposit_date->format('Y-m-d') }}</td>
                    <td>{{ $deposit->bank_name }}</td>
                    <td>{{ $deposit->account_number }}</td>
                    <td class="text-end text-success fw-bold">
                        ₱{{ number_format($deposit->amount, 2) }}
                    </td>
                     <td class="text-center">
    <a href="{{ route('bank-deposits.edit', $deposit->id) }}" 
       class="btn btn-sm btn-warning">
        <i class="bi bi-pencil">Edit</i>
    </a>

    <form action="{{ route('bank-deposits.destroy', $deposit->id) }}" 
          method="POST" 
          style="display:inline-block;"
          onsubmit="return confirm('Delete this deposit?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-danger">
            <i class="bi bi-trash">Delete</i>
        </button>
    </form>
</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection