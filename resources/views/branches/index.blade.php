@extends('layouts.sidebar')

@section('page-title', 'Branches')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-building me-2"></i>Branches</h2>
    <a href="{{ route('branches.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Add New Branch
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
                        <th>Branch Name</th>
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
                                $customers = $branch->customers_list ?? [];
                            @endphp
                            @if(count($customers) > 0)
                                <div class="d-flex flex-column gap-1">
                                    @foreach(array_slice($customers, 0, 2) as $customer)
                                        <small>
                                            <i class="bi bi-person-circle text-primary me-1"></i>
                                            {{ $customer }}
                                        </small>
                                    @endforeach
                                    @if(count($customers) > 2)
                                        <small class="text-muted">+{{ count($customers) - 2 }} more</small>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">No customers</span>
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
                        <td>
                            <a href="{{ route('branch-inventory.show', $branch) }}" 
                               class="btn btn-sm btn-info me-1 mb-1"
                               title="View Inventory">
                                <i class="bi bi-box-seam me-1"></i>Inventory
                            </a>
                            <a href="{{ route('branches.edit', $branch) }}" 
                               class="btn btn-sm btn-warning me-1 mb-1"
                               title="Edit Branch">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <form action="{{ route('branches.destroy', $branch) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('Delete {{ $branch->name }}?\n\nNote: Cannot delete branches with inventory.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="btn btn-sm btn-danger mb-1"
                                        title="Delete Branch">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <div class="d-flex flex-column align-items-center">
                                <i class="bi bi-building display-1 mb-3 opacity-25"></i>
                                <h5 class="text-muted">No branches yet</h5>
                                <p class="mb-3">Create your first branch to start managing inventory</p>
                                <a href="{{ route('branches.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Add New Branch
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
                Showing {{ $branches->count() }} of {{ $branches->total() }} branch(es)
            </div>
            <div>
                {{ $branches->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection