@extends('layouts.sidebar')

@section('page-title', 'View Branch')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Branches
    </a>
    <div class="d-flex gap-2">
        <a href="{{ route('branch-inventory.show', $branch) }}" class="btn btn-info text-white">
            <i class="bi bi-box-seam me-1"></i>View Inventory
        </a>
        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-warning">
            <i class="bi bi-pencil me-1"></i>Edit Branch
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Branch Details Card -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-building me-2"></i>Branch Details
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <small class="text-muted text-uppercase fw-bold">Branch Name</small>
                    <div class="fs-5 fw-semibold mt-1">
                        <i class="bi bi-shop text-primary me-2"></i>{{ $branch->name }}
                    </div>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <small class="text-muted text-uppercase fw-bold">Branch Code</small>
                    <div class="mt-1">
                        <span class="badge bg-secondary fs-6">{{ $branch->code }}</span>
                    </div>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <small class="text-muted text-uppercase fw-bold">Status</small>
                    <div class="mt-1">
                        @if($branch->is_active)
                            <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>Active</span>
                        @else
                            <span class="badge bg-secondary fs-6"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <small class="text-muted text-uppercase fw-bold">Address</small>
                    <div class="mt-1">
                        @if($branch->address)
                            <i class="bi bi-geo-alt text-muted me-1"></i>{{ $branch->address }}
                        @else
                            <span class="text-muted fst-italic">No address provided</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted text-uppercase fw-bold">Phone Number</small>
                    <div class="mt-1">
                        @if($branch->phone)
                            <i class="bi bi-telephone text-muted me-1"></i>{{ $branch->phone }}
                        @else
                            <span class="text-muted fst-italic">No phone number provided</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Card -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-info text-white">
                <i class="bi bi-bar-chart me-2"></i>Branch Stats
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="border rounded p-3 text-center bg-light">
                            <div class="display-6 fw-bold text-info">{{ $branch->inventory_count }}</div>
                            <small class="text-muted">Products in Inventory</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 text-center bg-light">
                            <div class="display-6 fw-bold text-primary">{{ count($branch->customers ?? []) }}</div>
                            <small class="text-muted">Customers</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <small class="text-muted text-uppercase fw-bold">Created</small>
                    <div class="mt-1">
                        <i class="bi bi-calendar text-muted me-1"></i>
                        {{ $branch->created_at->format('F j, Y') }}
                    </div>
                </div>

                <div class="mt-3">
                    <small class="text-muted text-uppercase fw-bold">Last Updated</small>
                    <div class="mt-1">
                        <i class="bi bi-clock text-muted me-1"></i>
                        {{ $branch->updated_at->diffForHumans() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers Card -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people me-2"></i>Customers ({{ count($branch->customers ?? []) }})</span>
                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-light">
                    <i class="bi bi-pencil me-1"></i>Manage Customers
                </a>
            </div>
            <div class="card-body">
                @php $customers = $branch->customers ?? []; @endphp
                @if(count($customers) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Customer Name</th>
                                    <th>Phone Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customers as $i => $customer)
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    <td>
                                        <i class="bi bi-person-circle text-primary me-2"></i>
                                        {{ $customer['name'] ?? '-' }}
                                    </td>
                                    <td>
                                        @if(!empty($customer['phone']))
                                            <i class="bi bi-telephone text-muted me-1"></i>{{ $customer['phone'] }}
                                        @else
                                            <span class="text-muted fst-italic">No phone</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-people display-4 opacity-25 d-block mb-2"></i>
                        No customers assigned to this branch yet.
                        <div class="mt-2">
                            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-success">
                                <i class="bi bi-person-plus me-1"></i>Add Customers
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection