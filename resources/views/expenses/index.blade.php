@extends('layouts.sidebar')

@section('page-title', 'Expenses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cash-stack me-2"></i>Expenses</h2>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Add New Expense
    </a>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body py-2">
                <div class="text-muted small">Total Expense This Month</div>
                <div class="fw-bold fs-6 text-danger">₱{{ number_format($monthTotal, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body py-2">
                <div class="text-muted small">Today</div>
                <div class="fw-bold fs-6">₱{{ number_format($todayTotal, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body py-2">
                <div class="text-muted small">Total Records</div>
                <div class="fw-bold fs-6">{{ number_format($totalRecords) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('expenses.index') }}" class="row g-2 mb-3">
            <div class="col-md-9">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    class="form-control form-control-sm"
                    placeholder="Search description, category, or payment method"
                >
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-search me-1"></i>Search
                </button>
                @if($search !== '')
                    <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Notes</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        <td><span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $expense->category)) }}</span></td>
                        <td class="fw-bold">{{ $expense->description }}</td>
                        <td class="text-danger fw-bold">₱{{ number_format($expense->amount, 2) }}</td>
                        <td>
                            <span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $expense->payment_method)) }}</span>
                        </td>
                        <td>{{ $expense->notes ? $expense->notes : '—' }}</td>
                        <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-warning me-1">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-4 d-block mb-3"></i>
                            No expenses recorded yet. Add your first expense!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@endsection