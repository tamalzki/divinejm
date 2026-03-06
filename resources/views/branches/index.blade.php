@extends('layouts.sidebar')

@section('page-title', 'Areas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-building me-2"></i>Areas</h2>
    <a href="{{ route('branches.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Add New Area
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

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Area Name</th>
                        <th>Code</th>
                        <th>Address</th>
                        <th>Customers</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                    <tr>
                        <td>
                            <div class="fw-semibold">
                                <i class="bi bi-shop text-primary me-2"></i>{{ $branch->name }}
                            </div>
                            @if($branch->phone)
                            <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $branch->phone }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $branch->code }}</span>
                        </td>
                        <td>
                            <small>{{ $branch->address ?? '-' }}</small>
                        </td>
                        <td>
                            @php
                                $customers = $branch->customers ?? [];
                                $total     = count($customers);
                                $preview   = array_slice($customers, 0, 3);
                            @endphp
                            @if($total > 0)
                                <div class="d-flex flex-column gap-1">
                                    @foreach($preview as $customer)
                                        <small>
                                            <i class="bi bi-person-circle text-primary me-1"></i>
                                            {{ $customer['name'] ?? $customer }}
                                        </small>
                                    @endforeach
                                    @if($total > 3)
                                        <small>
                                            <span class="badge bg-secondary">+{{ $total - 3 }} more</span>
                                        </small>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">No customers</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info">{{ $branch->inventory_count }}</span>
                        </td>
                        <td>
                            @if($branch->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td style="white-space: nowrap;">
                            <a href="{{ route('branches.show', $branch) }}" 
                               class="btn btn-sm btn-outline-primary me-1"
                               title="View Area">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="{{ route('branch-inventory.show', $branch) }}" 
                               class="btn btn-sm btn-outline-info me-1"
                               title="View Inventory">
                                <i class="bi bi-box-seam"></i> Inventory
                            </a>
                            <a href="{{ route('branches.edit', $branch) }}" 
                               class="btn btn-sm btn-outline-warning me-1"
                               title="Edit Area">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <form action="{{ route('branches.destroy', $branch) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('Delete {{ $branch->name }}?\n\nNote: Cannot delete areas with inventory.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete Area">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <div class="d-flex flex-column align-items-center">
                                <i class="bi bi-building display-1 mb-3 opacity-25"></i>
                                <h5 class="text-muted">No areas yet</h5>
                                <p class="mb-3">Create your first area to start managing inventory</p>
                                <a href="{{ route('branches.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Add New Area
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($branches->count() > 0)
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                Showing {{ $branches->count() }} of {{ $branches->total() }} area(s)
            </div>
            <div>
                {{ $branches->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection