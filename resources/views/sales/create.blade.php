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

<!-- Step 1: Sale Information Card - Top Section -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-cart-plus me-2"></i>Sale Information</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <!-- Branch Selection -->
            <div class="col-md-3">
                <label class="form-label fw-bold">Area/Branch <span class="text-danger">*</span></label>
                <select id="branchSelect" class="form-select select2" required>
                    <option value="">-- Select Area --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" data-name="{{ $branch->name }}">
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Customer Selection -->
            <div class="col-md-3" id="customerSection" style="display: none;">
                <label class="form-label fw-bold">Customer <span class="text-danger">*</span></label>
                <select id="customerSelect" class="form-select select2">
                    <option value="">-- Select Customer --</option>
                </select>
                <small class="text-muted" id="customerHint">Select area first</small>
            </div>

            <!-- DR Number Selection -->
            <div class="col-md-3" id="drSection" style="display: none;">
                <label class="form-label fw-bold">DR Number <span class="text-danger">*</span></label>
                <select id="drNumberSelect" class="form-select select2">
                    <option value="">-- Select DR --</option>
                </select>
                <small class="text-muted" id="drHint">Select customer first</small>
            </div>

            <!-- Sale Date -->
            <div class="col-md-3" id="dateSection" style="display: none;">
                <label class="form-label fw-bold">Sale Date <span class="text-danger">*</span></label>
                <input type="date" 
                       id="saleDateInput" 
                       class="form-control" 
                       value="{{ date('Y-m-d') }}">
            </div>
        </div>

        <!-- Warning for existing sales -->
        <div id="drWarning" class="alert alert-info mt-3" style="display: none;">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Previous Sales Found:</strong> This DR has existing sales. You can add another sale with remaining quantities.
        </div>

        <!-- Selection Summary -->
        <div id="selectionSummary" class="alert alert-success mt-3" style="display: none;">
            <div class="row">
                <div class="col-md-3">
                    <strong>Area:</strong> <span id="summaryBranch">-</span>
                </div>
                <div class="col-md-3">
                    <strong>Customer:</strong> <span id="summaryCustomer">-</span>
                </div>
                <div class="col-md-3">
                    <strong>DR#:</strong> <span id="summaryDR">-</span>
                </div>
                <div class="col-md-3">
                    <strong>Date:</strong> <span id="summaryDate">-</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Step 2: Products Entry - MAIN FOCUS AREA -->
<div class="card shadow-sm border-primary" style="border-width: 2px;">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Products to Sell</h5>
    </div>
    <div class="card-body p-0">
        <div id="productsPlaceholder" class="text-center text-muted py-5">
            <i class="bi bi-box display-1 opacity-25 d-block mb-3"></i>
            <h4>Select Sale Information Above</h4>
            <p class="mb-0">Products will load automatically once you select Area, Customer, DR, and Date</p>
        </div>

        <!-- Products Form -->
        <form id="saleForm" action="{{ route('sales.store') }}" method="POST" style="display: none;">
            @csrf
            <input type="hidden" name="branch_id" id="formBranchId">
            <input type="hidden" name="customer_name" id="formCustomerName">
            <input type="hidden" name="dr_number" id="formDrNumber">
            <input type="hidden" name="sale_date" id="formSaleDate">

            <!-- Previous Sales Notification -->
            <div id="previousSalesNotification"></div>

            <!-- Products Table - CLEAN BLACK & WHITE -->
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width: 16%;">Product</th>
                            <th style="width: 7%;" class="text-center">Batch#</th>
                            <th style="width: 7%;" class="text-center">Delivered</th>
                            <th style="width: 7%;" class="text-center">Sold</th>
                            <th style="width: 7%;" class="text-center">Remaining</th>
                            <th style="width: 9%;" class="text-center">QTY</th>
                            <th style="width: 8%;" class="text-center">Unsold</th>
                            <th style="width: 7%;" class="text-center">BO</th>
                            <th style="width: 10%;">Unit Price</th>
                            <th style="width: 10%;">Discount ₱</th>
                            <th style="width: 12%;" class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <!-- Products will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Grand Total - Clean -->
            <div class="bg-light border-top p-3">
                <div class="row align-items-center">
                    <div class="col-md-8 text-end">
                        <h5 class="mb-0">GRAND TOTAL:</h5>
                    </div>
                    <div class="col-md-4 text-end">
                        <h4 class="mb-0" id="grandTotal">₱0.00</h4>
                    </div>
                </div>
            </div>

            <!-- Payment Information - Collapsible -->
            <div class="p-4 border-top">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Payment Information</h5>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Payment Status <span class="text-danger">*</span></label>
                        <select name="payment_status" id="paymentStatus" class="form-select" required>
                            <option value="">-- Select Status --</option>
                            <option value="to_be_collected">To Be Collected</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3" id="paymentModeSection" style="display: none;">
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

                    <div class="col-md-2" id="paymentDetailsSection" style="display: none;">
                        <label class="form-label fw-bold">Amount Paid</label>
                        <input type="number" 
                               name="amount_paid" 
                               id="amountPaid"
                               class="form-control" 
                               step="0.01" 
                               min="0"
                               placeholder="0.00">
                    </div>
                    
                    <div class="col-md-2" id="paymentRefSection" style="display: none;">
                        <label class="form-label fw-bold">Reference</label>
                        <input type="text" 
                               name="payment_reference" 
                               class="form-control" 
                               placeholder="Ref #">
                    </div>
                    
                    <div class="col-md-2" id="paymentDateSection" style="display: none;">
                        <label class="form-label fw-bold">Payment Date</label>
                        <input type="date" 
                               name="payment_date" 
                               class="form-control" 
                               value="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea name="notes" 
                                  class="form-control" 
                                  rows="2" 
                                  placeholder="Additional notes about this sale..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="p-4 bg-light border-top">
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                        <i class="bi bi-check-circle me-2"></i>Record Sale
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Clean table styling - Properly sized inputs */
.table input.form-control {
    font-size: 0.9rem;
    padding: 0.4rem 0.5rem;
    font-weight: 500;
    width: 100%;
    text-align: center;
}

/* Make unit price especially visible */
.table .unit-price {
    font-size: 0.9rem;
    font-weight: 600;
    background-color: #f8f9fa !important;
    border: 2px solid #dee2e6 !important;
    text-align: right !important;
}

/* QTY input - make it stand out */
.table .qty-sold {
    font-size: 0.95rem;
    font-weight: 600;
    border: 2px solid #0d6efd !important;
}

/* Discount input */
.table .discount {
    font-size: 0.9rem;
    text-align: right !important;
}

/* BO and Unsold inputs */
.table .qty-bo,
.table .qty-unsold {
    font-size: 0.9rem;
}

/* Normal sized badges */
.table .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Sticky header */
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #f8f9fa;
}

/* Select2 styling - FORCE selected value to show */
.select2-container {
    width: 100% !important;
}

.select2-container .select2-selection--single {
    height: 38px !important;
    border: 1px solid #ced4da !important;
    background-color: #fff !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important;
    padding-left: 12px !important;
    padding-right: 12px !important;
    color: #212529 !important;
    font-weight: 500 !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
    right: 1px !important;
}

.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #86b7fe !important;
    outline: 0 !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}

/* Selected value highlight */
.select2-container--default .select2-selection--single .select2-selection__rendered {
    background-color: #e7f3ff !important;
    color: #0d6efd !important;
}

.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #6c757d !important;
    background-color: #fff !important;
}

/* Simple hover */
.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

/* Card border */
.card.border-primary {
    border-width: 2px !important;
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
    $('#branchSelect').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select Area --',
        allowClear: true,
        width: '100%'
    });

    $('#branchSelect').on('change', function() {
        const branchId = $(this).val();
        const branchName = $(this).find('option:selected').data('name');
        
        if (branchId) {
            selectedBranch = { id: branchId, name: branchName };
            $('#customerSection').show();
            loadCustomers(branchId);
            resetDRSelection();
            resetProductsForm();
        } else {
            selectedBranch = null;
            $('#customerSection').hide();
            resetDRSelection();
            resetProductsForm();
        }
    });
});

function loadCustomers(branchId) {
    $('#customerHint').html('<i class="spinner-border spinner-border-sm"></i> Loading...');
    
    $.get(`/api/sales/customers/${branchId}`)
        .done(function(data) {
            const $select = $('#customerSelect');
            
            // Completely destroy and rebuild
            if ($select.data('select2')) {
                $select.select2('destroy');
            }
            
            // Clear and add options
            $select.empty();
            $select.append('<option value="">-- Select Customer --</option>');
            
            if (data.customers && data.customers.length > 0) {
                data.customers.forEach(function(customer) {
                    $select.append($('<option></option>').attr('value', customer).text(customer));
                });
                $('#customerHint').text(`${data.customers.length} customer(s)`);
            } else {
                $('#customerHint').text('No customers found');
            }
            
            // Initialize Select2
            $select.select2({
                theme: 'bootstrap-5',
                placeholder: '-- Select Customer --',
                allowClear: true,
                width: '100%',
                dropdownParent: $select.parent()
            });
            
            // Attach change event
            $select.on('change', function() {
                const customerName = $(this).val();
                console.log('Customer selected:', customerName);
                if (customerName) {
                    selectedCustomer = customerName;
                    $('#drSection').show();
                    loadDRNumbers();
                } else {
                    selectedCustomer = null;
                    resetDRSelection();
                    resetProductsForm();
                }
            });
        })
        .fail(function() {
            $('#customerHint').text('Error loading customers');
        });
}

function loadDRNumbers() {
    $('#drHint').html('<i class="spinner-border spinner-border-sm"></i> Loading...');
    
    $.get(`/api/sales/dr-numbers/${selectedBranch.id}`)
        .done(function(data) {
            const $select = $('#drNumberSelect');
            
            // Completely destroy and rebuild
            if ($select.data('select2')) {
                $select.select2('destroy');
            }
            
            // Clear and add options
            $select.empty();
            $select.append('<option value="">-- Select DR --</option>');
            
            if (data.dr_numbers && data.dr_numbers.length > 0) {
                data.dr_numbers.forEach(function(dr) {
                    const optionText = `DR# ${dr.dr_number} (${dr.product_count} products)`;
                    $select.append($('<option></option>').attr('value', dr.dr_number).text(optionText));
                });
                $('#drHint').text(`${data.dr_numbers.length} DR(s)`);
            } else {
                $('#drHint').text('No DRs found');
            }
            
            $('#dateSection').show();
            
            // Initialize Select2
            $select.select2({
                theme: 'bootstrap-5',
                placeholder: '-- Select DR --',
                allowClear: true,
                width: '100%',
                dropdownParent: $select.parent()
            });
            
            // Attach change event
            $select.on('change', function() {
                const drNumber = $(this).val();
                console.log('DR selected:', drNumber);
                if (drNumber) {
                    selectedDR = drNumber;
                    checkForPreviousSales(drNumber);
                    
                    const saleDate = $('#saleDateInput').val();
                    if (selectedBranch && selectedCustomer && selectedDR && saleDate) {
                        autoLoadProducts();
                    }
                } else {
                    selectedDR = null;
                    $('#drWarning').hide();
                    $('#selectionSummary').hide();
                    resetProductsForm();
                }
            });
        })
        .fail(function() {
            $('#drHint').text('Error loading DRs');
        });
}

$('#saleDateInput').on('change', function() {
    if (selectedBranch && selectedCustomer && selectedDR && $(this).val()) {
        autoLoadProducts();
    }
});

function checkForPreviousSales(drNumber) {
    $.get(`/api/sales/check-dr/${selectedBranch.id}/${encodeURIComponent(selectedCustomer)}/${encodeURIComponent(drNumber)}`)
        .done(function(data) {
            if (data.exists && data.sales_count > 0) {
                $('#drWarning').html(`
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Previous Sales Found:</strong> This DR has ${data.sales_count} existing sale(s). 
                    You can add another sale with remaining quantities.
                `).show();
            } else {
                $('#drWarning').hide();
            }
        });
}

function autoLoadProducts() {
    updateSummary();
    $('#selectionSummary').show();
    
    $('#formBranchId').val(selectedBranch.id);
    $('#formCustomerName').val(selectedCustomer);
    $('#formDrNumber').val(selectedDR);
    $('#formSaleDate').val($('#saleDateInput').val());
    
    loadProductsForDR();
}

function updateSummary() {
    $('#summaryBranch').text(selectedBranch.name);
    $('#summaryCustomer').text(selectedCustomer);
    $('#summaryDR').text(selectedDR);
    $('#summaryDate').text(new Date($('#saleDateInput').val()).toLocaleDateString());
}

function loadProductsForDR() {
    $('#productsPlaceholder').html('<div class="spinner-border text-primary my-5"></div>');
    
    $.get(`/api/sales/dr-products/${selectedBranch.id}/${encodeURIComponent(selectedDR)}`)
        .done(function(data) {
            if (data.products && data.products.length > 0) {
                // Show notification if previous sales
                if (data.has_previous_sales) {
                    $('#previousSalesNotification').html(`
                        <div class="alert alert-info alert-dismissible fade show m-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Progressive Sale:</strong> This DR has ${data.previous_sales_count} previous sale(s). 
                            Showing remaining quantities only.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                }
                
                renderProducts(data.products);
                $('#productsPlaceholder').hide();
                $('#saleForm').show();
            } else {
                $('#productsPlaceholder').html(`
                    <i class="bi bi-inbox display-1 opacity-25 d-block my-4"></i>
                    <h5>No Products Available</h5>
                    <p class="mb-0">All products from this DR have been sold</p>
                `);
            }
        });
}

function renderProducts(products) {
    const tbody = $('#productsTableBody');
    tbody.empty();
    drProducts = products;
    
    let hasAvailableProducts = false;
    
    products.forEach((product, index) => {
        // Check if product has remaining quantity
        const hasRemaining = product.remaining_qty > 0;
        if (hasRemaining) hasAvailableProducts = true;
        
        const rowClass = !hasRemaining ? 'table-secondary' : '';
        const disabledAttr = !hasRemaining ? 'disabled' : '';
        
        const row = `
            <tr class="${rowClass}">
                <td>
                    <input type="hidden" name="items[${index}][finished_product_id]" value="${product.finished_product_id}">
                    <input type="hidden" name="items[${index}][batch_number]" value="${product.batch_number || ''}">
                    <input type="hidden" name="items[${index}][quantity_deployed]" value="${product.deployed_qty}">
                    <input type="hidden" name="items[${index}][remaining_qty]" value="${product.remaining_qty}">
                    <strong>${product.product_name}</strong><br>
                    <small class="text-muted">SKU: ${product.sku || 'N/A'}</small>
                    ${!hasRemaining ? '<br><small class="text-danger fw-bold">⚠️ All Sold</small>' : ''}
                </td>
                <td class="text-center">
                    <span class="badge bg-secondary">${product.batch_number || 'N/A'}</span>
                </td>
                <td class="text-center">
                    <span class="badge bg-info">${product.deployed_qty}</span>
                </td>
                <td class="text-center">
                    ${product.already_sold > 0 ? `<span class="badge bg-warning">${product.already_sold}</span>` : '<span class="text-muted">-</span>'}
                </td>
                <td class="text-center">
                    ${hasRemaining 
                        ? `<span class="badge bg-success">${product.remaining_qty}</span>` 
                        : `<span class="badge bg-danger">0</span>`}
                </td>
                <td>
                    <input type="number" 
                           name="items[${index}][quantity_sold]" 
                           class="form-control qty-sold" 
                           data-index="${index}"
                           step="0.01" 
                           min="0"
                           max="${product.remaining_qty}"
                           placeholder="0"
                           ${disabledAttr}>
                </td>
                <td class="text-center">
                    <input type="number" 
                           name="items[${index}][quantity_unsold]" 
                           class="form-control text-center qty-unsold bg-light" 
                           data-index="${index}"
                           step="0.01" 
                           min="0"
                           placeholder="0"
                           readonly
                           ${disabledAttr}>
                </td>
                <td class="text-center">
                    <input type="number" 
                           name="items[${index}][quantity_bo]" 
                           class="form-control text-center qty-bo" 
                           data-index="${index}"
                           step="0.01" 
                           min="0"
                           placeholder="0"
                           ${disabledAttr}>
                </td>
                <td>
                    <input type="number" 
                           name="items[${index}][unit_price]" 
                           class="form-control unit-price bg-light fw-bold" 
                           data-index="${index}"
                           step="0.01" 
                           min="0"
                           required
                           value="${product.selling_price || ''}"
                           placeholder="0.00"
                           readonly>
                </td>
                <td>
                    <input type="number" 
                           name="items[${index}][discount]" 
                           class="form-control discount" 
                           data-index="${index}"
                           step="0.01" 
                           min="0"
                           placeholder="0.00"
                           ${disabledAttr}>
                </td>
                <td class="text-end">
                    <strong class="item-total" data-index="${index}">₱0.00</strong>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Show warning if no products available
    if (!hasAvailableProducts) {
        tbody.prepend(`
            <tr>
                <td colspan="11" class="text-center py-4">
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>All Products Sold!</strong> 
                        All products from this DR have been completely sold. No remaining quantities available.
                    </div>
                </td>
            </tr>
        `);
        $('#submitBtn').prop('disabled', true).text('No Products to Sell');
    } else {
        $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle me-2"></i>Record Sale');
    }
    
    attachCalculationListeners();
    calculateTotals();
}

function attachCalculationListeners() {
    $('.qty-sold, .qty-bo, .discount').on('input', calculateTotals);
}

function calculateTotals() {
    let grandTotal = 0;
    
    $('.qty-sold').each(function() {
        const index = $(this).data('index');
        const qtySold = parseFloat($(this).val()) || 0;
        const qtyBO = parseFloat($(`.qty-bo[data-index="${index}"]`).val()) || 0;
        const unitPrice = parseFloat($(`.unit-price[data-index="${index}"]`).val()) || 0;
        const discountAmount = parseFloat($(`.discount[data-index="${index}"]`).val()) || 0;
        const remaining = parseFloat($(`input[name="items[${index}][remaining_qty]"]`).val()) || 0;
        
        // Auto-calculate unsold: Remaining - Sold - BO
        const unsold = Math.max(0, remaining - qtySold - qtyBO);
        $(`.qty-unsold[data-index="${index}"]`).val(unsold.toFixed(2));
        
        // Calculate amount with discount (discount is in pesos now)
        const subtotal = qtySold * unitPrice;
        const itemTotal = subtotal - discountAmount;
        
        $(`.item-total[data-index="${index}"]`).text('₱' + Math.max(0, itemTotal).toLocaleString('en-PH', {minimumFractionDigits: 2}));
        grandTotal += Math.max(0, itemTotal);
    });
    
    $('#grandTotal').text('₱' + grandTotal.toLocaleString('en-PH', {minimumFractionDigits: 2}));
}

$('#paymentStatus').on('change', function() {
    if ($(this).val() === 'paid') {
        $('#paymentModeSection, #paymentDetailsSection, #paymentRefSection, #paymentDateSection').show();
        $('#paymentMode').prop('required', true);
    } else {
        $('#paymentModeSection, #paymentDetailsSection, #paymentRefSection, #paymentDateSection').hide();
        $('#paymentMode').prop('required', false);
    }
});

$('#saleForm').on('submit', function() {
    const submitBtn = $('#submitBtn');
    submitBtn.prop('disabled', true);
    submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Recording Sale...');
});

function resetDRSelection() {
    $('#drNumberSelect').empty().append('<option value="">-- Select DR --</option>').trigger('change');
    selectedDR = null;
    $('#drWarning').hide();
    $('#selectionSummary').hide();
    $('#drSection').hide();
    $('#dateSection').hide();
}

function resetProductsForm() {
    $('#productsPlaceholder').html(`
        <i class="bi bi-box display-1 opacity-25 d-block my-5"></i>
        <h4>Select Sale Information Above</h4>
        <p class="mb-0">Products will load automatically</p>
    `).show();
    $('#saleForm').hide();
    $('#previousSalesNotification').empty();
}
</script>
@endsection