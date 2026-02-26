@extends('layouts.sidebar')

@section('page-title', 'New Sale')

@section('content')
<div class="mb-4">
    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Sales
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <h5><i class="bi bi-exclamation-triangle me-2"></i>Validation Errors:</h5>
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <!-- Left Column: Selection Form -->
    <div class="col-md-4">
        <div class="card shadow-sm sticky-top" style="top: 20px;">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-cart-plus me-2"></i>Sale Information</h5>
            </div>
            <div class="card-body">
                <!-- Step 1: Select Branch -->
                <div class="mb-3">
                    <label class="form-label fw-bold">1. Area/Branch <span class="text-danger">*</span></label>
                    <select id="branchSelect" class="form-select select2" required>
                        <option value="">-- Select Area --</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" data-name="{{ $branch->name }}">
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Step 2: Select Customer -->
                <div class="mb-3" id="customerSection" style="display: none;">
                    <label class="form-label fw-bold">2. Customer <span class="text-danger">*</span></label>
                    <select id="customerSelect" class="form-select select2">
                        <option value="">-- Select Customer --</option>
                    </select>
                    <small class="text-muted" id="customerHint">Select area first</small>
                </div>

                <!-- Step 3: Select DR Number -->
                <div class="mb-3" id="drSection" style="display: none;">
                    <label class="form-label fw-bold">3. DR Number <span class="text-danger">*</span></label>
                    <select id="drNumberSelect" class="form-select select2">
                        <option value="">-- Select DR --</option>
                    </select>
                    <small class="text-muted" id="drHint">Select customer first</small>
                    <div id="drWarning" class="alert alert-warning mt-2" style="display: none;">
                        <small><i class="bi bi-exclamation-triangle"></i> This DR already has a sale!</small>
                    </div>
                </div>

                <!-- Step 4: Sale Date -->
                <div class="mb-3" id="dateSection" style="display: none;">
                    <label class="form-label fw-bold">4. Sale Date <span class="text-danger">*</span></label>
                    <input type="date" 
                           id="saleDateInput" 
                           class="form-control" 
                           value="{{ date('Y-m-d') }}">
                </div>

                <hr>

                <!-- Selection Summary -->
                <div id="selectionSummary" style="display: none;">
                    <div class="alert alert-info mb-0">
                        <h6 class="mb-2">Selected:</h6>
                        <div><strong>Area:</strong> <span id="summaryBranch">-</span></div>
                        <div><strong>Customer:</strong> <span id="summaryCustomer">-</span></div>
                        <div><strong>DR#:</strong> <span id="summaryDR">-</span></div>
                        <div><strong>Date:</strong> <span id="summaryDate">-</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Products Entry -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Products</h5>
            </div>
            <div class="card-body">
                <div id="productsPlaceholder" class="text-center text-muted py-5">
                    <i class="bi bi-box display-1 opacity-25 d-block mb-3"></i>
                    <h5>Select area, customer, and DR number</h5>
                    <p class="mb-0">Then click "Load Products" to enter sale details</p>
                </div>

                <!-- Products Form -->
                <form id="saleForm" action="{{ route('sales.store') }}" method="POST" style="display: none;">
                    @csrf
                    <input type="hidden" name="branch_id" id="formBranchId">
                    <input type="hidden" name="customer_name" id="formCustomerName">
                    <input type="hidden" name="dr_number" id="formDrNumber">
                    <input type="hidden" name="sale_date" id="formSaleDate">

                    <!-- Products Table -->
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30%;">Product</th>
                                    <th style="width: 10%;">Batch#</th>
                                    <th style="width: 10%;" class="text-center">Available</th>
                                    <th style="width: 10%;" class="text-center">Sold</th>
                                    <th style="width: 10%;" class="text-center">Unsold</th>
                                    <th style="width: 10%;" class="text-center">BO</th>
                                    <th style="width: 10%;">Price</th>
                                    <th style="width: 10%;" class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <!-- Products will be loaded here -->
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="7" class="text-end"><strong>TOTAL:</strong></td>
                                    <td class="text-end">
                                        <strong id="grandTotal">₱0.00</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <hr>

                    <!-- Payment Information -->
                    <h6 class="mb-3"><i class="bi bi-cash-stack me-2"></i>Payment Information</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Status</label>
                            <select name="payment_status" id="paymentStatus" class="form-select">
                                <option value="to_be_collected" selected>To Be Collected</option>
                                <option value="paid">Paid</option>
                            </select>
                            <small class="text-muted">Select "Paid" to enter payment details</small>
                        </div>
                        <div class="col-md-6" id="paymentModeSection" style="display: none;">
                            <label class="form-label fw-bold">Payment Mode</label>
                            <select name="payment_mode" id="paymentMode" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="cheque">Cheque</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3" id="paymentDetailsSection" style="display: none;">
                        <div class="col-md-4">
                            <label class="form-label">Amount Paid</label>
                            <input type="number" 
                                   name="amount_paid" 
                                   id="amountPaid"
                                   class="form-control" 
                                   step="0.01" 
                                   min="0"
                                   placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment Reference</label>
                            <input type="text" 
                                   name="payment_reference" 
                                   class="form-control" 
                                   placeholder="Cheque #, GCash ref, etc.">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment Date</label>
                            <input type="date" 
                                   name="payment_date" 
                                   class="form-control" 
                                   value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" 
                                  class="form-control" 
                                  rows="2" 
                                  placeholder="Additional notes..."></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="bi bi-check-circle me-2"></i>Save Sale
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.table input.form-control-sm {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
}

.sticky-top {
    z-index: 100;
}

/* Select2 custom styling */
.select2-container .select2-selection--single {
    height: 38px !important;
    border: 1px solid #ced4da !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
}
</style>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let selectedBranch = null;
let selectedCustomer = null;
let selectedDR = null;
let drProducts = [];

// Initialize Select2 when document is ready
$(document).ready(function() {
    console.log('Initializing Select2...');
    
    // Initialize Select2 on branch select
    $('#branchSelect').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select Area --',
        allowClear: true,
        width: '100%'
    });

    // Branch Selection Handler
    $('#branchSelect').on('select2:select', function(e) {
        const branchId = $(this).val();
        const branchName = $(this).find('option:selected').data('name');
        
        console.log('Branch selected:', branchId, branchName);
        
        if (branchId) {
            selectedBranch = { id: branchId, name: branchName };
            document.getElementById('customerSection').style.display = 'block';
            loadCustomers(branchId);
            
            // Reset downstream selections
            resetCustomerSelection();
            resetDRSelection();
            resetProductsForm();
        }
    });

    $('#branchSelect').on('select2:clear', function(e) {
        console.log('Branch cleared');
        selectedBranch = null;
        document.getElementById('customerSection').style.display = 'none';
        resetDRSelection();
        resetProductsForm();
    });
});

// Load customers for selected branch
function loadCustomers(branchId) {
    document.getElementById('customerHint').innerHTML = '<i class="spinner-border spinner-border-sm"></i> Loading customers...';
    
    fetch(`/api/sales/customers/${branchId}`)
        .then(response => response.json())
        .then(data => {
            const select = $('#customerSelect');
            select.empty().append('<option value="">-- Select Customer --</option>');
            
            if (data.customers && data.customers.length > 0) {
                data.customers.forEach(customer => {
                    select.append(new Option(customer, customer));
                });
                document.getElementById('customerHint').textContent = `${data.customers.length} customer(s) available`;
            } else {
                document.getElementById('customerHint').textContent = 'No customers found for this area';
            }
            
            // Initialize/reinitialize Select2
            select.select2({
                theme: 'bootstrap-5',
                placeholder: '-- Select Customer --',
                allowClear: true,
                width: '100%'
            });
            
            // Customer Selection Handler
            select.off('select2:select').on('select2:select', function(e) {
                const customerName = $(this).val();
                console.log('Customer selected:', customerName);
                
                if (customerName) {
                    selectedCustomer = customerName;
                    document.getElementById('drSection').style.display = 'block';
                    loadDRNumbers();
                }
            });
            
            select.off('select2:clear').on('select2:clear', function(e) {
                console.log('Customer cleared');
                selectedCustomer = null;
                resetDRSelection();
                resetProductsForm();
            });
        })
        .catch(error => {
            console.error('Error loading customers:', error);
            document.getElementById('customerHint').textContent = 'Error loading customers';
        });
}

// Load DR numbers from stock movements for this branch
function loadDRNumbers() {
    document.getElementById('drHint').innerHTML = '<i class="spinner-border spinner-border-sm"></i> Loading DR numbers...';
    
    fetch(`/api/sales/dr-numbers/${selectedBranch.id}`)
        .then(response => response.json())
        .then(data => {
            const select = $('#drNumberSelect');
            select.empty().append('<option value="">-- Select DR --</option>');
            
            if (data.dr_numbers && data.dr_numbers.length > 0) {
                data.dr_numbers.forEach(dr => {
                    select.append(new Option(
                        `DR# ${dr.dr_number} (${dr.product_count} product${dr.product_count > 1 ? 's' : ''})`,
                        dr.dr_number
                    ));
                });
                document.getElementById('drHint').textContent = `${data.dr_numbers.length} DR(s) available`;
            } else {
                document.getElementById('drHint').textContent = 'No DRs found for this area';
            }
            
            document.getElementById('dateSection').style.display = 'block';
            
            // Initialize/reinitialize Select2
            select.select2({
                theme: 'bootstrap-5',
                placeholder: '-- Select DR --',
                allowClear: true,
                width: '100%'
            });
            
            // DR Selection Handler
            select.off('select2:select').on('select2:select', function(e) {
                const drNumber = $(this).val();
                console.log('DR selected:', drNumber);
                
                if (drNumber) {
                    selectedDR = drNumber;
                    checkDuplicateDR(drNumber);
                    
                    // Auto-load products immediately
                    const saleDate = document.getElementById('saleDateInput').value;
                    if (selectedBranch && selectedCustomer && selectedDR && saleDate) {
                        autoLoadProducts();
                    }
                }
            });
            
            select.off('select2:clear').on('select2:clear', function(e) {
                console.log('DR cleared');
                selectedDR = null;
                document.getElementById('drWarning').style.display = 'none';
                document.getElementById('selectionSummary').style.display = 'none';
                resetProductsForm();
            });
        })
        .catch(error => {
            console.error('Error loading DR numbers:', error);
            document.getElementById('drHint').textContent = 'Error loading DRs';
        });
}

document.getElementById('saleDateInput').addEventListener('change', function() {
    console.log('Date changed:', this.value);
    if (selectedBranch && selectedCustomer && selectedDR && this.value) {
        autoLoadProducts();
    }
});

function checkDuplicateDR(drNumber) {
    const url = `/api/sales/check-dr/${selectedBranch.id}/${encodeURIComponent(selectedCustomer)}/${encodeURIComponent(drNumber)}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                document.getElementById('drWarning').style.display = 'block';
            } else {
                document.getElementById('drWarning').style.display = 'none';
            }
        })
        .catch(error => console.error('Error:', error));
}

// Auto-load products (replaces manual button click)
function autoLoadProducts() {
    // Show summary
    updateSummary();
    document.getElementById('selectionSummary').style.display = 'block';
    
    // Set hidden form fields
    document.getElementById('formBranchId').value = selectedBranch.id;
    document.getElementById('formCustomerName').value = selectedCustomer;
    document.getElementById('formDrNumber').value = selectedDR;
    document.getElementById('formSaleDate').value = document.getElementById('saleDateInput').value;
    
    // Load products for this specific DR
    loadProductsForDR();
}

function updateSummary() {
    document.getElementById('summaryBranch').textContent = selectedBranch.name;
    document.getElementById('summaryCustomer').textContent = selectedCustomer;
    document.getElementById('summaryDR').textContent = selectedDR;
    document.getElementById('summaryDate').textContent = new Date(document.getElementById('saleDateInput').value).toLocaleDateString();
}

function loadProductsForDR() {
    // Show loading
    document.getElementById('productsPlaceholder').innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    
    fetch(`/api/sales/dr-products/${selectedBranch.id}/${encodeURIComponent(selectedDR)}`)
        .then(response => response.json())
        .then(data => {
            if (data.products && data.products.length > 0) {
                renderProducts(data.products);
                document.getElementById('productsPlaceholder').style.display = 'none';
                document.getElementById('saleForm').style.display = 'block';
                
                // Hide loading in summary
                document.querySelector('#selectionSummary .text-center').style.display = 'none';
            } else {
                document.getElementById('productsPlaceholder').innerHTML = `
                    <i class="bi bi-inbox display-1 opacity-25 d-block mb-3"></i>
                    <h5>No Products Found</h5>
                    <p class="mb-0">No products were deployed with this DR number</p>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('productsPlaceholder').innerHTML = `
                <i class="bi bi-exclamation-triangle display-1 text-danger opacity-25 d-block mb-3"></i>
                <h5>Error Loading Products</h5>
                <p class="mb-0">Please try again</p>
            `;
        });
}

function renderProducts(products) {
    const tbody = document.getElementById('productsTableBody');
    tbody.innerHTML = '';
    drProducts = products;
    
    products.forEach((product, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="hidden" name="items[${index}][finished_product_id]" value="${product.finished_product_id}">
                <input type="hidden" name="items[${index}][batch_number]" value="${product.batch_number ? product.batch_number : ''}">
                <input type="hidden" name="items[${index}][quantity_deployed]" value="${product.deployed_qty}">
                <strong>${product.product_name}</strong><br>
                <small class="text-muted">SKU: ${product.sku || 'N/A'}</small>
            </td>
            <td>
                <span class="badge bg-secondary">
    ${product.batch_number ? product.batch_number : 'N/A'}
</span>
            </td>
            <td class="text-center">
                <span class="badge bg-info">${product.deployed_qty}</span>
            </td>
            <td>
                <input type="number" 
                       name="items[${index}][quantity_sold]" 
                       class="form-control form-control-sm qty-sold" 
                       data-index="${index}"
                       step="0.01" 
                       min="0"
                       max="${product.deployed_qty}"
                       placeholder="0">
            </td>
            <td>
                <input type="number" 
                       name="items[${index}][quantity_unsold]" 
                       class="form-control form-control-sm" 
                       step="0.01" 
                       min="0"
                       placeholder="0">
            </td>
            <td>
                <input type="number" 
                       name="items[${index}][quantity_bo]" 
                       class="form-control form-control-sm" 
                       step="0.01" 
                       min="0"
                       placeholder="0">
            </td>
            <td>
                <input type="number" 
                       name="items[${index}][unit_price]" 
                       class="form-control form-control-sm unit-price" 
                       data-index="${index}"
                       step="0.01" 
                       min="0"
                       required
                       value="${product.selling_price || ''}"
                       placeholder="0.00">
            </td>
            <td class="text-end">
                <strong class="item-total" data-index="${index}">₱0.00</strong>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Add event listeners for calculations
    attachCalculationListeners();
    
    // Trigger initial calculation if price is pre-filled
    calculateTotals();
}

function attachCalculationListeners() {
    document.querySelectorAll('.qty-sold, .unit-price').forEach(input => {
        input.addEventListener('input', calculateTotals);
    });
}

function calculateTotals() {
    let grandTotal = 0;
    
    document.querySelectorAll('.qty-sold').forEach(input => {
        const index = input.dataset.index;
        const qtySold = parseFloat(input.value) || 0;
        const unitPrice = parseFloat(document.querySelector(`.unit-price[data-index="${index}"]`).value) || 0;
        const itemTotal = qtySold * unitPrice;
        
        document.querySelector(`.item-total[data-index="${index}"]`).textContent = 
            '₱' + itemTotal.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        grandTotal += itemTotal;
    });
    
    document.getElementById('grandTotal').textContent = 
        '₱' + grandTotal.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Payment Status Change
document.getElementById('paymentStatus').addEventListener('change', function() {
    if (this.value === 'paid') {
        document.getElementById('paymentModeSection').style.display = 'block';
        document.getElementById('paymentDetailsSection').style.display = 'block';
        document.getElementById('paymentMode').required = true;
    } else {
        document.getElementById('paymentModeSection').style.display = 'none';
        document.getElementById('paymentDetailsSection').style.display = 'none';
        document.getElementById('paymentMode').required = false;
    }
});

// Form Submission
document.getElementById('saleForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
});

// Reset functions
function resetCustomerSelection() {
    $('#customerSelect').empty().append('<option value="">-- Select Customer --</option>').trigger('change');
    selectedCustomer = null;
    document.getElementById('drSection').style.display = 'none';
    document.getElementById('dateSection').style.display = 'none';
}

function resetDRSelection() {
    $('#drNumberSelect').empty().append('<option value="">-- Select DR --</option>').trigger('change');
    selectedDR = null;
    document.getElementById('drWarning').style.display = 'none';
    document.getElementById('selectionSummary').style.display = 'none';
}

function resetProductsForm() {
    document.getElementById('productsPlaceholder').innerHTML = `
        <i class="bi bi-box display-1 opacity-25 d-block mb-3"></i>
        <h5>Select area, customer, and DR number</h5>
        <p class="mb-0">Products will load automatically</p>
    `;
    document.getElementById('productsPlaceholder').style.display = 'block';
    document.getElementById('saleForm').style.display = 'none';
}
</script>
@endsection