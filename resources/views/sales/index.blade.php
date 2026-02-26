@extends('layouts.sidebar')

@section('page-title', 'Sales')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cart-check me-2"></i>Sales</h2>
    <a href="{{ route('sales.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>New Sale
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('sales.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Area/Branch</label>
                <select name="branch_id" class="form-select">
                    <option value="">All Areas</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Payment Status</label>
                <select name="payment_status" class="form-select">
                    <option value="">All Status</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="to_be_collected" {{ request('payment_status') == 'to_be_collected' ? 'selected' : '' }}>To Be Collected</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">DR Number</label>
                <input type="text" name="dr_number" class="form-control" placeholder="DR#" value="{{ request('dr_number') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label">Customer</label>
                <input type="text" name="customer_name" class="form-control" placeholder="Customer" value="{{ request('customer_name') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Date Range</label>
                <div class="input-group">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    <span class="input-group-text">to</span>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-2"></i>Filter
                </button>
                <a href="{{ route('sales.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Sales Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Total Sales</h6>
                <h3 class="mb-0">₱{{ number_format($sales->sum('total_amount'), 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Amount Collected</h6>
                <h3 class="mb-0">₱{{ number_format($sales->sum('amount_paid'), 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h6 class="card-title">To Be Collected</h6>
                <h3 class="mb-0">₱{{ number_format($sales->sum('balance'), 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Total Transactions</h6>
                <h3 class="mb-0">{{ $sales->total() }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Sales Table -->
<div class="card">
    <div class="card-body">
        @if($sales->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>DR#</th>
                        <th>Date</th>
                        <th>Area</th>
                        <th>Customer</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Payment Status</th>
                        <th>Payment Mode</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales as $sale)
                    <tr>
                        <td>
                            <strong class="text-primary">{{ $sale->dr_number }}</strong>
                        </td>
                        <td><small>{{ $sale->sale_date->format('M d, Y') }}</small></td>
                        <td>{{ $sale->branch->name }}</td>
                        <td>{{ $sale->customer_name }}</td>
                        <td class="text-end">
                            <strong>₱{{ number_format($sale->total_amount, 2) }}</strong>
                        </td>
                        <td class="text-end text-success">
                            ₱{{ number_format($sale->amount_paid, 2) }}
                        </td>
                        <td class="text-end {{ $sale->balance > 0 ? 'text-danger' : 'text-muted' }}">
                            ₱{{ number_format($sale->balance, 2) }}
                        </td>
                        <td>
                            <span class="badge bg-{{ $sale->payment_status_badge }}">
                                {{ $sale->payment_status_label }}
                            </span>
                        </td>
                        <td>
                            <small>{{ $sale->payment_mode_label }}</small>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('sales.edit', $sale) }}" class="btn btn-warning" title="Edit Payment">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $sale->id }}" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal{{ $sale->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title">Delete Sale</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete this sale?</p>
                                            <div class="alert alert-warning">
                                                <strong>DR#:</strong> {{ $sale->dr_number }}<br>
                                                <strong>Customer:</strong> {{ $sale->customer_name }}<br>
                                                <strong>Amount:</strong> ₱{{ number_format($sale->total_amount, 2) }}
                                            </div>
                                            <p class="text-danger"><strong>Note:</strong> This will return the inventory to the branch.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="bi bi-trash me-2"></i>Delete Sale
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $sales->links() }}
        </div>
        @else
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox display-1 opacity-25 d-block mb-3"></i>
            <h5>No Sales Found</h5>
            <p class="mb-3">Start recording sales by clicking the "New Sale" button above</p>
            <a href="{{ route('sales.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Create First Sale
            </a>
        </div>
        @endif
    </div>
</div>
@endsection