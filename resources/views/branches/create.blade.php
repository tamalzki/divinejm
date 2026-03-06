@extends('layouts.sidebar')

@section('page-title', 'Add Area')

@section('content')
<div class="mb-4">
    <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Areas
    </a>
</div>

{{-- Error Summary Banner --}}
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <div class="d-flex align-items-center mb-1">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <strong>The area could not be saved. Please fix the following:</strong>
    </div>
    <ul class="mb-0 ps-3 mt-1">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-plus-circle me-2"></i>Add New Area
    </div>
    <div class="card-body">

        <p class="text-muted small mb-3">
            Fields marked <span class="text-danger fw-bold">*</span> are required.
        </p>

        <form action="{{ route('branches.store') }}" method="POST" id="branchForm">
            @csrf

            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label fw-bold">Area Name <span class="text-danger">*</span></label>
                    <input type="text"
                           name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}"
                           placeholder="e.g., Panabo"
                           maxlength="255"
                           required
                           autofocus>
                    @error('name')
                        <div class="invalid-feedback"><i class="bi bi-x-circle me-1"></i>{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Area Code <span class="text-danger">*</span></label>
                    <input type="text"
                           name="code"
                           class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code') }}"
                           placeholder="e.g., MAIN"
                           style="text-transform: uppercase;"
                           maxlength="50"
                           required>
                    <small class="text-muted">Unique identifier (auto uppercase)</small>
                    @error('code')
                        <div class="invalid-feedback"><i class="bi bi-x-circle me-1"></i>{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Address</label>
                <textarea name="address"
                          class="form-control @error('address') is-invalid @enderror"
                          rows="2"
                          placeholder="Full area address"
                          maxlength="500">{{ old('address') }}</textarea>
                @error('address')
                    <div class="invalid-feedback"><i class="bi bi-x-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Phone Number</label>
                <input type="text"
                       name="phone"
                       class="form-control @error('phone') is-invalid @enderror"
                       value="{{ old('phone') }}"
                       placeholder="+63 xxx xxx xxxx"
                       maxlength="50">
                @error('phone')
                    <div class="invalid-feedback"><i class="bi bi-x-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>

            <!-- Customers Section -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div>
                        <label class="form-label fw-bold mb-0">Customers</label>
                        <small class="text-muted d-block">If adding customers, <strong>name is required</strong> for each row.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" onclick="addCustomer()">
                        <i class="bi bi-person-plus me-1"></i>Add Customer
                    </button>
                </div>

                @if($errors->hasAny(['customers', 'customers.*.name']))
                <div class="alert alert-warning py-2 mb-2">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    One or more customer rows are missing a name. Please fill them in or remove the empty rows.
                </div>
                @endif

                <div id="customersContainer" class="border rounded p-3 bg-light">
                    <div id="customersList">
                        <p class="text-muted text-center mb-0" id="emptyMessage">
                            No customers added yet. Click "Add Customer" to add one.
                        </p>
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
                           {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        <strong>Active Area</strong>
                    </label>
                    <small class="text-muted d-block">Inactive areas won't appear in transfer options</small>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="bi bi-save me-2"></i>Create Area
                </button>
                <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
let customerIndex = 0;

function addCustomer(name = '', phone = '', hasError = false) {
    const emptyMessage = document.getElementById('emptyMessage');
    if (emptyMessage) emptyMessage.remove();

    const idx = customerIndex;
    const errorClass = hasError ? 'is-invalid' : '';
    const errorMsg   = hasError
        ? `<div class="invalid-feedback d-block"><i class="bi bi-x-circle me-1"></i>Customer name is required.</div>`
        : '';

    const customerHtml = `
        <div class="card mb-2 ${hasError ? 'border-danger' : ''}" id="customer-${idx}">
            <div class="card-body py-2">
                <div class="row align-items-start g-2">
                    <div class="col-md-5">
                        <input type="text"
                               name="customers[${idx}][name]"
                               class="form-control form-control-sm ${errorClass}"
                               placeholder="Customer Name *"
                               value="${escapeHtml(name)}">
                        ${errorMsg}
                    </div>
                    <div class="col-md-5">
                        <input type="text"
                               name="customers[${idx}][phone]"
                               class="form-control form-control-sm"
                               placeholder="Phone Number (optional)"
                               value="${escapeHtml(phone)}">
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                onclick="removeCustomer(${idx})"
                                title="Remove customer">
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
    document.getElementById(`customer-${index}`).remove();
    const list = document.getElementById('customersList');
    if (list.children.length === 0) {
        list.innerHTML = '<p class="text-muted text-center mb-0" id="emptyMessage">No customers added yet. Click "Add Customer" to add one.</p>';
    }
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}

// Prevent double-submit
document.getElementById('branchForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
});

// Restore customer rows after validation failure, highlighting rows with errors
document.addEventListener('DOMContentLoaded', function () {
    @if(old('customers'))
        const oldCustomers = @json(old('customers', []));

        // Build set of indexes that failed name validation
        const errorIndexes = new Set([
            @foreach($errors->keys() as $key)
                @if(Str::startsWith($key, 'customers.') && Str::endsWith($key, '.name'))
                    {{ (int) explode('.', $key)[1] }},
                @endif
            @endforeach
        ]);

        Object.values(oldCustomers).forEach(function (customer, i) {
            addCustomer(customer.name || '', customer.phone || '', errorIndexes.has(i));
        });
    @endif
});
</script>
@endsection