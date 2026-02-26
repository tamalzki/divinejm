@extends('layouts.sidebar')

@section('page-title', 'Edit Branch')

@section('content')
<div class="mb-4">
    <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Branches
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-warning">
        <i class="bi bi-pencil me-2"></i>Edit Branch: {{ $branch->name }}
    </div>
    <div class="card-body">
        <form action="{{ route('branches.update', $branch) }}" method="POST" id="branchForm">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label fw-bold">Branch Name <span class="text-danger">*</span></label>
                    <input type="text" 
                           name="name" 
                           class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $branch->name) }}" 
                           placeholder="e.g., Mers Main Branch" 
                           required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Branch Code <span class="text-danger">*</span></label>
                    <input type="text" 
                           name="code" 
                           class="form-control @error('code') is-invalid @enderror" 
                           value="{{ old('code', $branch->code) }}" 
                           placeholder="e.g., MAIN" 
                           style="text-transform: uppercase;"
                           required>
                    <small class="text-muted">Unique identifier (auto uppercase)</small>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Address</label>
                <textarea name="address" 
                          class="form-control @error('address') is-invalid @enderror" 
                          rows="2" 
                          placeholder="Full branch address">{{ old('address', $branch->address) }}</textarea>
                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Branch Phone Number</label>
                <input type="text" 
                       name="phone" 
                       class="form-control @error('phone') is-invalid @enderror" 
                       value="{{ old('phone', $branch->phone) }}" 
                       placeholder="+63 xxx xxx xxxx">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <!-- Customers Section -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-bold mb-0">Customers</label>
                    <button type="button" class="btn btn-sm btn-success" onclick="addCustomer()">
                        <i class="bi bi-person-plus me-1"></i>Add Customer
                    </button>
                </div>
                
                <div id="customersContainer" class="border rounded p-3 bg-light">
                    <div id="customersList">
                        <!-- Customers will be added here dynamically -->
                        @if(!$branch->customers || count($branch->customers) === 0)
                            <p class="text-muted text-center mb-0" id="emptyMessage">No customers added yet. Click "Add Customer" to add one.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input type="checkbox" 
                           name="is_active" 
                           class="form-check-input" 
                           id="is_active" 
                           value="1" 
                           {{ old('is_active', $branch->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        <strong>Active Branch</strong>
                    </label>
                    <small class="text-muted d-block">Inactive branches won't appear in transfer options</small>
                </div>
            </div>

            @if($branch->inventory_count > 0)
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle me-2"></i>
                This branch currently has <strong>{{ $branch->inventory_count }} product(s)</strong> in inventory.
            </div>
            @endif

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-save me-2"></i>Update Branch
                </button>
                <a href="{{ route('branches.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
let customerIndex = 0;

function addCustomer() {
    const emptyMessage = document.getElementById('emptyMessage');
    if (emptyMessage) {
        emptyMessage.remove();
    }

    const customerHtml = `
        <div class="card mb-2" id="customer-${customerIndex}">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-5">
                        <input type="text" 
                               name="customers[${customerIndex}][name]" 
                               class="form-control form-control-sm" 
                               placeholder="Customer Name"
                               required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" 
                               name="customers[${customerIndex}][phone]" 
                               class="form-control form-control-sm" 
                               placeholder="Phone Number">
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" 
                                class="btn btn-sm btn-danger" 
                                onclick="removeCustomer(${customerIndex})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('customersList').insertAdjacentHTML('beforeend', customerHtml);
    customerIndex++;
}

function removeCustomer(index) {
    const customerElement = document.getElementById(`customer-${index}`);
    customerElement.remove();

    // Check if no customers left
    const customersList = document.getElementById('customersList');
    if (customersList.children.length === 0) {
        customersList.innerHTML = '<p class="text-muted text-center mb-0" id="emptyMessage">No customers added yet. Click "Add Customer" to add one.</p>';
    }
}

// Load existing customers when page loads
document.addEventListener('DOMContentLoaded', function() {
    @if(old('customers'))
        // If there are validation errors, restore old customers
        @foreach(old('customers', []) as $index => $customer)
            addCustomer();
            document.querySelector(`[name="customers[${customerIndex - 1}][name]"]`).value = "{{ $customer['name'] ?? '' }}";
            document.querySelector(`[name="customers[${customerIndex - 1}][phone]"]`).value = "{{ $customer['phone'] ?? '' }}";
        @endforeach
    @elseif({{ json_encode($branch->customers ?? []) }})
        // Load existing customers from database
        const existingCustomers = @json($branch->customers ?? []);
        if (existingCustomers && existingCustomers.length > 0) {
            existingCustomers.forEach(customer => {
                addCustomer();
                document.querySelector(`[name="customers[${customerIndex - 1}][name]"]`).value = customer.name || '';
                document.querySelector(`[name="customers[${customerIndex - 1}][phone]"]`).value = customer.phone || '';
            });
        }
    @endif
});
</script>
@endsection