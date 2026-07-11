@extends('layouts.sidebar')
@section('page-title', 'Deliver to Customer')
@section('content')

<style>
    /* ── Top bar ── */
    .dj-back { display:inline-flex; align-items:center; gap:.3rem; font-size:.73rem; color:var(--text-secondary); text-decoration:none; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-card); transition:background .12s; }
    .dj-back:hover { background:var(--bg-page); color:var(--text-primary); }

    /* ── Compact header card ── */
    .header-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.75rem 1rem; margin-bottom:.75rem; }
    .header-grid { display:grid; grid-template-columns: 2fr 2fr 1.2fr 1.5fr 2fr; gap:.6rem; align-items:start; }
    .field-label { font-size:.68rem; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:.18rem; }
    .field-req   { color:var(--s-danger-text); }
    .form-control, .form-select { font-size:.78rem; border:1px solid var(--border); border-radius:5px; padding:.32rem .6rem; color:var(--text-primary); background:var(--bg-card); width:100%; transition:border-color .15s,box-shadow .15s; }
    .form-control:focus, .form-select:focus { border-color:var(--accent); outline:none; box-shadow:0 0 0 3px rgba(59,91,219,.12); }

    /* ── Products table ── */
    .table-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:.75rem; }
    .table-card-header { display:flex; align-items:center; justify-content:space-between; padding:.5rem .85rem; border-bottom:1px solid var(--border); background:var(--bg-page); }
    .table-card-title  { font-size:.75rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }

    .prod-table { width:100%; border-collapse:collapse; font-size:.77rem; }
    .prod-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.42rem .7rem; border:none; white-space:nowrap; }
    .prod-table tbody td { padding:.3rem .55rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .prod-table tbody tr:last-child td { border-bottom:none; }
    .prod-table tbody tr:hover td { background:var(--accent-faint); }
    .prod-table tfoot td { background:var(--brand-deep); color:rgba(255,255,255,.9); padding:.42rem .7rem; font-size:.78rem; font-weight:700; }

    /* Row states */
    .prod-table tbody tr.row-active td { background:#f0fdf4; }
    .prod-table tbody tr.row-active:hover td { background:#dcfce7; }

    /* Inputs inside table */
    .td-input { font-size:.76rem; border:1px solid var(--border); border-radius:4px; padding:.25rem .45rem; color:var(--text-primary); background:var(--bg-card); width:100%; transition:border-color .12s; }
    .td-input:focus { border-color:var(--accent); outline:none; box-shadow:0 0 0 2px rgba(59,91,219,.1); }
    .td-input:disabled { background:var(--bg-page); color:var(--text-muted); }

    .qty-input  { width:72px; }
    .extra-input{ width:60px; }
    .price-input{ width:80px; }

    /* Availability badge */
    .avail-badge { display:inline-block; padding:.05rem .32rem; border-radius:3px; font-size:.68rem; font-weight:700; background:var(--s-info-bg); color:var(--s-info-text); }
    .avail-badge.warn { background:var(--s-danger-bg); color:var(--s-danger-text); }
    .avail-badge.zero { background:var(--border); color:var(--text-muted); }

    /* Product name cell */
    .prod-name { font-weight:600; font-size:.76rem; }
    .prod-batch { font-size:.65rem; color:var(--text-muted); margin-top:.05rem; }

    /* Batch selector */
    .batch-select { font-size:.70rem; border:1px solid var(--border); border-radius:4px; padding:.22rem .4rem; color:var(--text-primary); background:var(--bg-card); max-width:180px; }
    .batch-select:focus { border-color:var(--accent); outline:none; }

    /* Row total */
    .row-total { font-weight:700; font-size:.76rem; min-width:80px; text-align:right; }
    .row-total.has-val { color:var(--accent); }

    /* Grand total row */
    .grand-row td { font-weight:700; }

    /* Action strip */
    .action-strip { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); display:flex; align-items:center; gap:.5rem; padding:.65rem 1rem; }
    .btn-submit { display:inline-flex; align-items:center; gap:.3rem; padding:.38rem 1.1rem; background:var(--accent); color:#fff; border:none; border-radius:5px; font-size:.8rem; font-weight:600; cursor:pointer; transition:background .15s; }
    .btn-submit:hover { background:var(--accent-hover); }
    .btn-cancel { display:inline-flex; align-items:center; gap:.3rem; padding:.36rem .9rem; background:var(--bg-card); color:var(--text-secondary); border:1px solid var(--border); border-radius:5px; font-size:.8rem; font-weight:500; text-decoration:none !important; transition:background .15s; }
    .btn-cancel:hover { background:var(--bg-page); }

    /* ── Toast notification ── */
    #dj-toast {
        position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%) translateY(8px);
        z-index: 9999; padding: .6rem 1.2rem; border-radius: 6px;
        font-size: .8rem; font-weight: 600; display: flex; align-items: center; gap: .5rem;
        opacity: 0; pointer-events: none;
        transition: opacity .2s, transform .2s;
        box-shadow: 0 4px 16px rgba(0,0,0,.18);
        white-space: nowrap;
    }
    #dj-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); pointer-events: auto; }
    #dj-toast.danger { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
    #dj-toast.success { background: #f0fdf4; color: #166534; border: 1px solid #86efac; }

    /* Review delivery modal */
    .review-modal .modal-header { border-bottom: 1px solid var(--border); }
    .review-modal .modal-title { font-size: 1rem; font-weight: 700; }
    .review-modal .review-hint { font-size: .78rem; color: var(--text-secondary); line-height: 1.45; }
    .review-modal .review-table { font-size: .78rem; width: 100%; }
    .review-modal .review-table th { font-size: .62rem; text-transform: uppercase; letter-spacing: .04em; background: var(--brand-deep); color: rgba(255,255,255,.9); padding: .45rem .65rem; border: none; }
    .review-modal .review-table td { padding: .4rem .65rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .review-modal .review-table tfoot td { font-weight: 700; background: var(--bg-page); }
    .review-modal .alert-zeros { font-size: .76rem; }

    /* TomSelect overrides */
    .ts-wrapper .ts-control { font-size:.76rem !important; border-color:var(--border) !important; border-radius:5px !important; min-height:29px !important; padding:2px 6px !important; }
    .ts-wrapper.focus .ts-control { border-color:var(--accent) !important; box-shadow:0 0 0 3px rgba(59,91,219,.12) !important; }
    .ts-dropdown { font-size:.76rem !important; border-color:var(--border) !important; }
    .ts-dropdown .option { padding:.3rem .55rem !important; }
    .ts-dropdown .option.active { background:var(--accent) !important; color:#fff !important; }

    /* ── Mobile (iPhone 13, Xiaomi 13 Pro, and similar phones) ── */
    @media (max-width: 640px) {
        .header-grid { grid-template-columns: 1fr; }

        .table-card-header { flex-direction:column; align-items:stretch; gap:.5rem; }
        .table-card-header .d-flex { justify-content:space-between; }

        .action-strip { flex-wrap:wrap; }
        .action-strip .btn-submit,
        .action-strip .btn-cancel { flex:1; justify-content:center; }
        .action-strip span { width:100%; order:3; margin-left:0 !important; }

        .modal-dialog { margin:.5rem; }
        .review-modal .modal-body,
        .bo-replace-modal .modal-body { padding:.85rem; }

        #reviewBoSection .table-responsive,
        #boPanelTable { font-size:.72rem; }
    }
</style>

{{-- Page Header --}}
<div class="d-flex align-items-center gap-2 mb-2">
    <a href="{{ route('branch-inventory.index') }}" class="dj-back">
        <i class="bi bi-arrow-left"></i> Deliveries
    </a>
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.9rem">
            <i class="bi bi-truck me-1" style="color:var(--accent)"></i>Deliver to Customer
        </h5>
        <span style="font-size:.65rem;color:var(--text-muted)">Quantity defaults to 0. Only lines with quantity &gt; 0 are delivered after you confirm in the review step. Deliveries may proceed even if warehouse stock goes negative (overage is highlighted).</span>
    </div>
</div>

<form action="{{ route('branch-inventory.store-delivery') }}" method="POST" id="deliverForm">
@csrf
<input type="hidden" name="print_after_save" id="printAfterSaveFlag" value="0">
<div id="boHiddenInputs"></div>

{{-- Compact Header Fields --}}
<div class="header-card">
    <div class="header-grid">
        @php
            $lockArea = !empty($prefBranchId);
            $lockCustomer = $lockArea && !empty($prefCustomer);
        @endphp
        {{-- Area --}}
        <div>
            <label class="field-label">Area <span class="field-req">*</span></label>
            <select {!! $lockArea ? '' : 'name="branch_id"' !!} id="branchSelect" class="form-select" required>
                <option value="">— Select area —</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}"
                        data-customers="{{ json_encode($b->customers_list ?? []) }}"
                        data-distributor="{{ $b->is_distributor ? '1' : '0' }}"
                        {{ (string) old('branch_id', (string) ($prefBranchId ?? '')) === (string) $b->id ? 'selected' : '' }}>
                        {{ $b->name }}{{ $b->is_distributor ? ' (Distributor)' : '' }}
                    </option>
                @endforeach
            </select>
            @if($lockArea)
                <input type="hidden" name="branch_id" value="{{ $prefBranchId }}">
                <span style="font-size:.62rem;color:var(--text-muted)">Preselected — go back to change area</span>
            @endif
        </div>
        {{-- Customer --}}
        <div>
            <label class="field-label">Customer <span class="field-req">*</span></label>
            <select {!! $lockCustomer ? '' : 'name="customer_name"' !!} id="customerSelect" class="form-select" required>
                <option value="">— Select area first —</option>
            </select>
            @if($lockCustomer)
                <input type="hidden" name="customer_name" value="{{ $prefCustomer }}">
                <span style="font-size:.62rem;color:var(--text-muted)">Preselected — go back to change customer</span>
            @endif
        </div>
        {{-- DR Number (auto-generated, read-only) --}}
        <div>
            <label class="field-label">DR #</label>
            <input type="text" id="drNumberDisplay" class="form-control" value="{{ $nextDrNumber }}" readonly disabled
                   style="background:var(--bg-page);font-weight:700;color:var(--accent)" title="Auto-generated on save">
            <span style="font-size:.62rem;color:var(--text-muted)">Auto-generated on save</span>
        </div>
        {{-- Date --}}
        <div>
            <label class="field-label">Delivery Date <span class="field-req">*</span></label>
            <input type="date" name="movement_date" class="form-control" value="{{ old('movement_date', date('Y-m-d')) }}" required>
        </div>
        {{-- Notes --}}
        <div>
            <label class="field-label">Notes</label>
            <input type="text" name="notes" class="form-control" placeholder="Optional..." value="{{ old('notes') }}">
        </div>
    </div>
</div>

{{-- Products Table --}}
<div class="table-card">
    <div class="table-card-header">
        <span class="table-card-title">
            <i class="bi bi-cart-check" style="color:var(--accent)"></i>
            Products to Deliver
            <span style="font-size:.65rem;color:var(--text-muted);font-weight:400">— All products pre-loaded. Enter qty to include in delivery.</span>
        </span>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn-cancel" id="btnReplaceBo" style="padding:.2rem .65rem;border-color:#dc2626;color:#dc2626" disabled>
                <i class="bi bi-arrow-repeat"></i> Replace BO
            </button>
            <span id="activeCount" style="font-size:.68rem;color:var(--text-muted)">0 products with qty</span>
        </div>
    </div>
    <div style="overflow-x:auto">
        <table class="prod-table" id="productsTable">
            <thead>
                <tr>
                    <th style="width:4%">#</th>
                    <th style="width:32%">Product</th>
                    <th class="text-center" style="width:10%">Available</th>
                    <th style="width:12%">Qty Deploy</th>
                    <th style="width:10%">Extra/Free</th>
                    <th style="width:11%">Unit Price</th>
                    <th class="text-end" style="width:13%">Amount</th>
                    <th style="width:8%"></th>
                </tr>
            </thead>
            <tbody id="productsTableBody">
                {{-- auto-populated by JS --}}
            </tbody>
            <tfoot>
                <tr class="grand-row">
                    {{-- 8 columns: #, Product, Avail, Qty, Extra, Unit price, Amount, action --}}
                    <td colspan="6" class="text-end" style="font-size:.72rem;letter-spacing:.4px">TOTAL DELIVERY VALUE</td>
                    <td class="text-end" id="grandTotal">&#8369;0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Actions --}}
<div class="action-strip">
    <button type="button" class="btn-submit" id="btnOpenReview">
        <i class="bi bi-clipboard-check"></i> Confirm Delivery
    </button>
    <a href="{{ route('branch-inventory.index') }}" class="btn-cancel">Cancel</a>
    <span style="font-size:.68rem;color:var(--text-muted);margin-left:.5rem">You will review area, customer, and line items before saving.</span>
</div>

</form>

{{-- Review delivery modal --}}
<div class="modal fade review-modal" id="reviewDeliveryModal" tabindex="-1" aria-labelledby="reviewDeliveryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border)">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewDeliveryModalLabel">
                    <i class="bi bi-clipboard-check me-2" style="color:var(--accent)"></i>Review delivery
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="review-hint mb-2">
                    Only products with a quantity <strong>greater than zero</strong> will be included on this delivery.
                    Lines with <strong>0</strong> or blank quantity are <strong>not</strong> transferred.
                </p>
                <div class="alert alert-warning py-2 px-3 alert-zeros mb-3 d-none" id="reviewZeroLinesAlert" role="alert">
                    <i class="bi bi-info-circle me-1"></i>
                    <span id="reviewZeroLinesText"></span>
                </div>
                <div class="mb-3 p-2 rounded" style="background:var(--bg-page);font-size:.76rem;border:1px solid var(--border)">
                    <div><strong>Area:</strong> <span id="reviewAreaName">—</span></div>
                    <div><strong>Customer:</strong> <span id="reviewCustomerName">—</span></div>
                    <div><strong>DR #:</strong> <span id="reviewDr">—</span></div>
                    <div><strong>Delivery date:</strong> <span id="reviewDate">—</span></div>
                </div>
                <div class="table-responsive" style="max-height:50vh">
                    <table class="review-table table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Warehouse stock</th>
                                <th class="text-center">Qty to deliver</th>
                                <th class="text-end">Line value</th>
                            </tr>
                        </thead>
                        <tbody id="reviewTableBody"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end">Total delivery value</td>
                                <td class="text-end" id="reviewTotalCost">₱0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div style="margin-top:1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
                    <div style="font-size:.78rem;font-weight:700;color:#dc2626">
                        <i class="bi bi-arrow-repeat me-1"></i>BO Replaced — free, not billed
                    </div>
                    <button type="button" class="btn-cancel" style="padding:.18rem .6rem;border-color:#dc2626;color:#dc2626" id="btnReplaceBoFromReview">
                        <i class="bi bi-arrow-repeat"></i> Replace BO
                    </button>
                </div>
                <div id="reviewBoSection" style="display:none;margin-top:.5rem">
                    <div class="table-responsive" style="max-height:30vh">
                        <table class="review-table table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Original DR#</th>
                                    <th class="text-center">Qty Replaced</th>
                                    <th class="text-end">Ref. Price</th>
                                    <th class="text-end">Ref. Amount</th>
                                </tr>
                            </thead>
                            <tbody id="reviewBoTableBody"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end">Reference total (not added to delivery value)</td>
                                    <td class="text-end" id="reviewBoTotalCost">₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div id="reviewBoEmptyHint" style="display:none;font-size:.72rem;color:var(--text-muted);margin-top:.35rem">
                    None selected yet.
                </div>
            </div>
            <div class="modal-footer border-top" style="background:var(--bg-page)">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="bi bi-pencil-square me-1"></i>Back to edit
                </button>
                <button type="button" class="btn btn-success btn-sm" id="btnSaveDelivery">
                    <i class="bi bi-check-lg me-1"></i>Save delivery
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveAndPrint">
                    <i class="bi bi-printer me-1"></i>Save Delivery &amp; Print
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Replace BO modal --}}
<div class="modal fade bo-replace-modal" id="boReplaceModal" tabindex="-1" aria-labelledby="boReplaceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border)">
            <div class="modal-header">
                <h5 class="modal-title" id="boReplaceModalLabel">
                    <i class="bi bi-arrow-repeat me-2" style="color:#dc2626"></i>Replace BO
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="review-hint mb-3">
                    Swap damaged/bad-order items from this customer's past deliveries with fresh stock.
                    Replacements are <strong>free</strong> — not billed — but deducted from current stock and added to this DR.
                </p>
                <div style="overflow-x:auto">
                    <table class="prod-table" id="boPanelTable">
                        <thead>
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:24%">Product</th>
                                <th style="width:14%">Original DR#</th>
                                <th class="text-center" style="width:14%">Outstanding BO</th>
                                <th style="width:16%">Qty to Replace</th>
                                <th class="text-end" style="width:12%">Ref. Price</th>
                                <th class="text-end" style="width:15%">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="boPanelTableBody">
                            {{-- populated by JS --}}
                        </tbody>
                    </table>
                </div>
                <div id="boPanelEmpty" style="display:none;padding:1rem;text-align:center;font-size:.75rem;color:var(--text-muted)">
                    No outstanding BO for this area/customer.
                </div>
            </div>
            <div class="modal-footer border-top" style="background:var(--bg-page)">
                <button type="button" class="btn btn-success btn-sm" data-bs-dismiss="modal">
                    <i class="bi bi-check2 me-1"></i>Done
                </button>
            </div>
        </div>
    </div>
</div>

{{-- TomSelect --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

{{-- JSON product data — one card per product, FIFO resolved on backend --}}
<script id="productData" type="application/json">
{!! json_encode($products->map(function($p) {
    return [
        'id'            => $p->id,
        'name'          => $p->name,
        'stock_on_hand' => $p->stock_on_hand,
        'selling_price' => $p->selling_price,
        'distributor_price' => $p->distributor_price,
        'cost_price'    => $p->cost_price,
        'minimum_stock' => $p->minimum_stock,
        'branch_prices' => $p->branchPrices->pluck('price', 'branch_id'),
    ];
})->values()) !!}
</script>

<script>
var allProducts = [];
var rowIndex    = 0;
var activeRows  = {};
var boOutstanding = [];   // outstanding BO rows fetched for the current area/customer
var boLoadedFor    = '';  // "branchId|customer" key of the last successful fetch

/** Parse numeric input; strips thousands commas and avoids NaN from bad input. */
function parseMoney(el) {
    if (!el) return 0;
    var raw = el.value != null ? el.value : '';
    var s = String(raw).replace(/,/g, '').replace(/\s/g, '').trim();
    if (s === '') return 0;
    var n = parseFloat(s);
    return isFinite(n) ? n : 0;
}

function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
}

function getSelectedBranchId() {
    var el = document.getElementById('branchSelect');
    return el ? el.value : '';
}

function getSelectedCustomerName() {
    var el = document.getElementById('customerSelect');
    return el ? el.value : '';
}

function updateBoButtonState() {
    var btn = document.getElementById('btnReplaceBo');
    if (!btn) return;
    btn.disabled = !(getSelectedBranchId() && getSelectedCustomerName());
}

/** Area/Customer changed — any previously loaded BO selections belong to
 *  the old customer and must be discarded. */
function onAreaOrCustomerChanged() {
    updateBoButtonState();
    var key = getSelectedBranchId() + '|' + getSelectedCustomerName();
    if (key !== boLoadedFor) {
        boOutstanding = [];
        boLoadedFor = '';
        var body = document.getElementById('boPanelTableBody');
        if (body) body.innerHTML = '';
        updateBoSummary();
    }
}

var reopenReviewAfterBo = false;

/** Open the Replace BO modal. When opened from within the Review Delivery
 *  modal, hide it first and reopen it once BO replacement is done. */
function openBoReplaceModal(fromReview) {
    var branchId = getSelectedBranchId();
    var customer = getSelectedCustomerName();
    if (!branchId || !customer) return;

    reopenReviewAfterBo = !!fromReview;
    if (fromReview) {
        var reviewInstance = bootstrap.Modal.getInstance(document.getElementById('reviewDeliveryModal'));
        if (reviewInstance) reviewInstance.hide();
    }

    var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('boReplaceModal'));
    var key = branchId + '|' + customer;

    if (key === boLoadedFor) {
        modal.show();
        return;
    }

    fetch('{{ route("branch-inventory.outstanding-bo") }}?branch_id=' + encodeURIComponent(branchId) + '&customer=' + encodeURIComponent(customer), {
        headers: { 'Accept': 'application/json' },
    })
        .then(function(r) { return r.json(); })
        .then(function(rows) {
            boOutstanding = rows.map(function(r) { r.selected_qty = 0; return r; });
            boLoadedFor = key;
            renderBoPanel();
            updateBoSummary();
            modal.show();
        })
        .catch(function() {
            showToast('Could not load outstanding BO. Try again.', 'danger');
        });
}

function renderBoPanel() {
    var tbody = document.getElementById('boPanelTableBody');
    var emptyEl = document.getElementById('boPanelEmpty');
    var table = document.getElementById('boPanelTable');
    tbody.innerHTML = '';

    if (!boOutstanding.length) {
        emptyEl.style.display = '';
        table.style.display = 'none';
        return;
    }
    emptyEl.style.display = 'none';
    table.style.display = '';

    boOutstanding.forEach(function(row, idx) {
        var tr = document.createElement('tr');
        tr.id = 'bo-row-' + idx;
        tr.innerHTML =
            '<td style="color:var(--text-muted);font-size:.68rem;text-align:center">' + (idx + 1) + '</td>' +
            '<td class="prod-name">' + escapeHtml(row.product_name) + '</td>' +
            '<td style="font-size:.72rem;color:var(--text-secondary)">DR# ' + escapeHtml(row.dr_number || '—') + '</td>' +
            '<td class="text-center"><span class="avail-badge warn">' + row.outstanding_qty + '</span></td>' +
            '<td><input type="number" class="td-input" id="bo-qty-' + idx + '" min="0" max="' + row.outstanding_qty + '" step="1" value="" placeholder="0" oninput="onBoQtyInput(' + idx + ')"></td>' +
            '<td class="text-end" style="font-size:.76rem">₱' + Number(row.unit_price).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>' +
            '<td class="row-total" id="bo-row-total-' + idx + '">₱0.00</td>';
        tbody.appendChild(tr);
    });
}

function onBoQtyInput(idx) {
    var inp = document.getElementById('bo-qty-' + idx);
    var row = boOutstanding[idx];
    if (!inp || !row) return;

    var val = parseMoney(inp);
    var max = parseFloat(row.outstanding_qty) || 0;
    if (val > max) { val = max; inp.value = max; }
    if (val < 0) { val = 0; inp.value = 0; }
    row.selected_qty = val;

    var total = val * (parseFloat(row.unit_price) || 0);
    var cell = document.getElementById('bo-row-total-' + idx);
    if (cell) {
        cell.textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
        cell.className = 'row-total' + (total > 0 ? ' has-val' : '');
    }
    updateBoSummary();
}

function updateBoSummary() {
    var count = boOutstanding.filter(function(r) { return (r.selected_qty || 0) > 0; }).length;
    var label = '<i class="bi bi-arrow-repeat"></i> Replace BO' + (count > 0 ? ' (' + count + ')' : '');

    var btn = document.getElementById('btnReplaceBo');
    if (btn) btn.innerHTML = label;

    var btnReview = document.getElementById('btnReplaceBoFromReview');
    if (btnReview) btnReview.innerHTML = label;
}

/** Serialize selected BO replacements into hidden inputs before submit. */
function appendBoReplacementInputs() {
    var container = document.getElementById('boHiddenInputs');
    if (!container) return;
    container.innerHTML = '';

    var i = 0;
    boOutstanding.forEach(function(row) {
        var qty = row.selected_qty || 0;
        if (qty <= 0) return;

        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'bo_replacements[' + i + '][sale_item_id]';
        idInput.value = row.sale_item_id;
        container.appendChild(idInput);

        var qtyInput = document.createElement('input');
        qtyInput.type = 'hidden';
        qtyInput.name = 'bo_replacements[' + i + '][quantity]';
        qtyInput.value = qty;
        container.appendChild(qtyInput);

        i++;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    allProducts = JSON.parse(document.getElementById('productData').textContent);

    var oldBranch  = '{{ old("branch_id") }}';
    var prefBranch = '{{ $prefBranchId ?? '' }}';
    var initialBranchId = oldBranch || prefBranch || '';

    // Render one row per product, alphabetical (already sorted from PHP)
    allProducts.forEach(function(p, i) {
        renderRow(p, i, initialBranchId);
    });
    rowIndex = allProducts.length;

    updateActiveCount();
    calcGrand();

    // Branch → Customer
    var customerTs = new TomSelect('#customerSelect', {
        create: true,
        dropdownParent: 'body',
        placeholder: '\u2014 Select area first \u2014',
        maxOptions: 100,
        onChange: function() { onAreaOrCustomerChanged(); },
    });

    function loadCustomers(branchId, selectVal) {
        customerTs.clear();
        customerTs.clearOptions();
        if (!branchId) return;
        var opt = document.querySelector('#branchSelect option[value="' + branchId + '"]');
        var customers = [];
        try { customers = JSON.parse(opt ? opt.dataset.customers : '[]'); } catch(e) {}
        customers.forEach(function(c) {
            customerTs.addOption({ value: c, text: c });
        });
        customerTs.settings.placeholder = '\u2014 Select or type customer \u2014';
        customerTs.refreshOptions(false);
        if (selectVal) {
            if (!customers.includes(selectVal)) customerTs.addOption({ value: selectVal, text: selectVal });
            customerTs.setValue(selectVal, true);
        }
    }

    var branchTs = new TomSelect('#branchSelect', {
        dropdownParent: 'body',
        create: false,
        onChange: function(id) { loadCustomers(id, null); applyBranchPricing(id); onAreaOrCustomerChanged(); }
    });

    var oldCustomer = '{{ old("customer_name", $prefCustomer ?? '') }}';
    if (initialBranchId) {
        branchTs.setValue(initialBranchId, true);
        loadCustomers(initialBranchId, oldCustomer || null);
    }

    if ({{ $lockArea ? 'true' : 'false' }}) {
        branchTs.disable();
    }
    if ({{ $lockCustomer ? 'true' : 'false' }}) {
        customerTs.disable();
    }

    onAreaOrCustomerChanged();

    var reviewModalEl = document.getElementById('reviewDeliveryModal');
    var reviewModal   = reviewModalEl ? new bootstrap.Modal(reviewModalEl) : null;

    var boReplaceModalEl = document.getElementById('boReplaceModal');
    if (boReplaceModalEl) {
        new bootstrap.Modal(boReplaceModalEl);
        boReplaceModalEl.addEventListener('hidden.bs.modal', function() {
            if (reopenReviewAfterBo) {
                reopenReviewAfterBo = false;
                openReviewModal();
            }
        });
    }

    // ── Enter key: jump to next qty input ──────────────────────────
    document.getElementById('productsTableBody').addEventListener('keydown', function(e) {
        if (e.key !== 'Enter') return;
        var target = e.target;
        if (!target.classList.contains('qty-input')) return;
        e.preventDefault();

        var inputs = Array.from(document.querySelectorAll('.qty-input:not([disabled])'));
        var curIdx = inputs.indexOf(target);
        if (curIdx >= 0 && curIdx < inputs.length - 1) {
            inputs[curIdx + 1].focus();
            inputs[curIdx + 1].select();
        }
    });

    function readBranchName() {
        var sel = document.getElementById('branchSelect');
        var opt = sel && sel.options[sel.selectedIndex];
        return opt ? opt.textContent.replace(/\s+/g, ' ').trim() : '';
    }

    function validateDeliveryFormForReview() {
        var branchId = document.getElementById('branchSelect').value;
        if (!branchId) {
            showToast('Area is required — please select an area.', 'danger');
            return false;
        }
        var cust = customerTs.getValue();
        if (!cust || !String(cust).trim()) {
            showToast('Customer is required — select an area, then choose or type a customer.', 'danger');
            return false;
        }
        var md = document.querySelector('input[name="movement_date"]');
        if (!md || !String(md.value).trim()) {
            showToast('Delivery date is required.', 'danger');
            return false;
        }

        var hasAny  = false;
        document.querySelectorAll('.qty-input').forEach(function(inp) {
            var val   = parseMoney(inp);
            if (val > 0) {
                hasAny = true;
            }
        });
        var hasBoQty = boOutstanding.some(function(r) { return (r.selected_qty || 0) > 0; });

        if (!hasAny && !hasBoQty) {
            showToast('Enter a quantity greater than zero for at least one product, or select a BO to replace.', 'danger');
            return false;
        }
        return true;
    }

    function openReviewModal() {
        if (!validateDeliveryFormForReview()) return;

        document.getElementById('reviewAreaName').textContent     = readBranchName() || '—';
        document.getElementById('reviewCustomerName').textContent = customerTs.getValue() || '—';
        document.getElementById('reviewDr').textContent           = document.getElementById('drNumberDisplay').value.trim();
        var d = document.querySelector('input[name="movement_date"]').value;
        document.getElementById('reviewDate').textContent       = d ? new Date(d + 'T12:00:00').toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' }) : '—';

        var tbody = document.getElementById('reviewTableBody');
        tbody.innerHTML = '';
        var totalCost = 0;
        var linesWithQty = 0;

        for (var i = 0; i < rowIndex; i++) {
            var qEl = document.getElementById('qty-' + i);
            if (!qEl) continue;
            var qty = parseMoney(qEl);
            if (qty <= 0) continue;

            linesWithQty++;
            var p = allProducts[i];
            if (!p) continue;
            var stock = parseFloat(p.stock_on_hand) || 0;
            var extraEl = document.getElementById('extra-' + i);
            var priceEl = document.getElementById('price-' + i);
            var extra = parseMoney(extraEl);
            var unit  = parseMoney(priceEl);
            var lineVal = qty * unit;
            totalCost += lineVal;

            var qtyDisp = String(qty) + (extra > 0
                ? ' <span style="font-size:.68rem;color:var(--text-muted)">(+' + extra + ' free, not billed)</span>'
                : '');

            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><strong>' + escapeHtml(p.name) + '</strong></td>' +
                '<td class="text-center">' + stock + '</td>' +
                '<td class="text-center">' + qtyDisp + '</td>' +
                '<td class="text-end">\u20B1' + lineVal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>';
            tbody.appendChild(tr);
        }

        document.getElementById('reviewTotalCost').textContent =
            '\u20B1' + totalCost.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        var zeroLines = rowIndex - linesWithQty;
        var zAlert = document.getElementById('reviewZeroLinesAlert');
        var zText  = document.getElementById('reviewZeroLinesText');
        if (zeroLines > 0) {
            zText.textContent = zeroLines + ' product line(s) have no quantity and will not be on this delivery.';
            zAlert.classList.remove('d-none');
        } else {
            zAlert.classList.add('d-none');
        }

        // ── BO Replaced section ──
        var boTbody = document.getElementById('reviewBoTableBody');
        boTbody.innerHTML = '';
        var boTotal = 0;
        var boLines = 0;

        boOutstanding.forEach(function(row) {
            var qty = row.selected_qty || 0;
            if (qty <= 0) return;
            boLines++;
            var amount = qty * (parseFloat(row.unit_price) || 0);
            boTotal += amount;

            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><strong>' + escapeHtml(row.product_name) + '</strong></td>' +
                '<td>DR# ' + escapeHtml(row.dr_number || '—') + '</td>' +
                '<td class="text-center">' + qty + '</td>' +
                '<td class="text-end">₱' + Number(row.unit_price).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>' +
                '<td class="text-end">₱' + amount.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>';
            boTbody.appendChild(tr);
        });

        document.getElementById('reviewBoTotalCost').textContent =
            '₱' + boTotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('reviewBoSection').style.display = boLines > 0 ? '' : 'none';
        document.getElementById('reviewBoEmptyHint').style.display = boLines > 0 ? 'none' : '';

        if (reviewModal) reviewModal.show();
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function disableZeroQtyRowsBeforeSubmit() {
        document.querySelectorAll('#productsTableBody input').forEach(function(el) { el.disabled = false; });
        document.querySelectorAll('.qty-input').forEach(function(inp) {
            var idx = inp.dataset.idx;
            var val = parseFloat(inp.value || 0);
            var row = document.getElementById('prod-row-' + idx);
            if (!row) return;
            if (!val || val <= 0) {
                row.querySelectorAll('input').forEach(function(el) { el.disabled = true; });
            }
        });
    }

    document.getElementById('btnOpenReview').addEventListener('click', openReviewModal);

    document.getElementById('btnSaveDelivery').addEventListener('click', function() {
        document.getElementById('printAfterSaveFlag').value = '0';
        disableZeroQtyRowsBeforeSubmit();
        appendBoReplacementInputs();
        if (reviewModal) reviewModal.hide();
        document.getElementById('deliverForm').submit();
    });

    document.getElementById('btnSaveAndPrint').addEventListener('click', function() {
        document.getElementById('printAfterSaveFlag').value = '1';
        disableZeroQtyRowsBeforeSubmit();
        appendBoReplacementInputs();
        if (reviewModal) reviewModal.hide();
        document.getElementById('deliverForm').submit();
    });

    document.getElementById('btnReplaceBo').addEventListener('click', function() { openBoReplaceModal(false); });
    document.getElementById('btnReplaceBoFromReview').addEventListener('click', function() { openBoReplaceModal(true); });
});

/** True if the given Area select value is flagged as a Distributor account. */
function isDistributorBranch(branchId) {
    if (!branchId) return false;
    var opt = document.querySelector('#branchSelect option[value="' + branchId + '"]');
    return !!opt && opt.dataset.distributor === '1';
}

/** Price for a product given the selected area: distributor price for
 *  Distributor accounts, else the area-specific price, else selling_price. */
function getPriceForBranch(product, branchId) {
    if (isDistributorBranch(branchId)) {
        return parseFloat(product.distributor_price) || 0;
    }
    if (branchId && product.branch_prices) {
        var v = product.branch_prices[String(branchId)];
        if (v !== null && v !== undefined && v !== '') return parseFloat(v) || 0;
    }
    return parseFloat(product.selling_price) || 0;
}

/** Re-populate every row's unit price when the selected area changes. */
function applyBranchPricing(branchId) {
    for (var i = 0; i < rowIndex; i++) {
        var priceEl = document.getElementById('price-' + i);
        var product = allProducts[i];
        if (!priceEl || !product) continue;
        priceEl.value = getPriceForBranch(product, branchId).toFixed(2);
        calcRow(i);
    }
}

function renderRow(product, idx, branchId) {
    var tbody = document.getElementById('productsTableBody');
    var tr    = document.createElement('tr');
    tr.id     = 'prod-row-' + idx;

    var avail    = parseFloat(product.stock_on_hand) || 0;
    var minStock = parseFloat(product.minimum_stock) || 0;
    var badgeCls = avail === 0 ? 'zero' : (avail <= minStock ? 'warn' : '');
    var price    = getPriceForBranch(product, branchId);

    tr.innerHTML =
        '<td style="color:var(--text-muted);font-size:.68rem;text-align:center">' + (idx + 1) + '</td>' +
        '<td>' +
            '<div class="prod-name">' + product.name + '</div>' +
            '<div style="font-size:.63rem;color:var(--text-muted)">FIFO — oldest batch first</div>' +
        '</td>' +
        '<td class="text-center">' +
            '<span class="avail-badge ' + badgeCls + '" id="avail-' + idx + '">' + avail + '</span>' +
        '</td>' +
        '<td>' +
            '<input type="number" class="td-input qty-input" id="qty-' + idx + '" data-idx="' + idx + '" ' +
                'name="items[' + idx + '][quantity]" step="1" min="0" value="" placeholder="0" oninput="onQtyInput(' + idx + ',' + avail + ')">' +
            '<input type="hidden" name="items[' + idx + '][product_id]" value="' + product.id + '">' +
        '</td>' +
        '<td>' +
            '<input type="number" class="td-input extra-input" id="extra-' + idx + '" ' +
                'name="items[' + idx + '][extra_quantity]" step="1" min="0" value="" placeholder="0" oninput="onQtyInput(' + idx + ',' + avail + '); calcRow(' + idx + ')">' +
        '</td>' +
        '<td>' +
            '<input type="number" class="td-input price-input" id="price-' + idx + '" ' +
                'name="items[' + idx + '][unit_price]" step="0.01" min="0" value="' + price.toFixed(2) + '" oninput="calcRow(' + idx + ')">' +
        '</td>' +
        '<td class="row-total" id="row-total-' + idx + '">\u20B10.00</td>' +
        '<td style="text-align:center">' +
            '<button type="button" onclick="clearRow(' + idx + ')" title="Clear" ' +
                'style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:.8rem;padding:.1rem .3rem;border-radius:3px">' +
                '<i class="bi bi-x-lg"></i>' +
            '</button>' +
        '</td>';

    tbody.appendChild(tr);
}

function onQtyInput(idx, avail) {
    var val   = parseMoney(document.getElementById('qty-' + idx));
    var extra = parseMoney(document.getElementById('extra-' + idx));
    var row   = document.getElementById('prod-row-' + idx);
    var badge = document.getElementById('avail-' + idx);

    if (val > 0) {
        row.style.background = '#f0fdf4';
        activeRows[idx] = true;
    } else {
        row.style.background = '';
        delete activeRows[idx];
    }

    var over = (val + extra) > avail;
    badge.className = 'avail-badge' + (over ? ' warn' : (avail === 0 ? ' zero' : ''));

    updateActiveCount();
    calcRow(idx);
}

function calcRow(idx) {
    var qty   = parseMoney(document.getElementById('qty-' + idx));
    var price = parseMoney(document.getElementById('price-' + idx));
    var total = qty > 0 ? qty * price : 0;
    var cell  = document.getElementById('row-total-' + idx);
    cell.textContent = '\u20B1' + total.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    cell.className   = 'row-total' + (total > 0 ? ' has-val' : '');
    calcGrand();
}

function calcGrand() {
    var grand = 0;
    for (var i = 0; i < rowIndex; i++) {
        var qEl = document.getElementById('qty-' + i);
        var pEl = document.getElementById('price-' + i);
        if (!qEl || !pEl) continue;
        var q = parseMoney(qEl);
        if (q > 0) grand += q * parseMoney(pEl);
    }
    document.getElementById('grandTotal').textContent =
        '\u20B1' + grand.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function clearRow(idx) {
    document.getElementById('qty-' + idx).value   = '';
    document.getElementById('extra-' + idx).value  = '';
    document.getElementById('prod-row-' + idx).style.background = '';
    delete activeRows[idx];
    updateActiveCount();
    calcRow(idx);
}

function updateActiveCount() {
    var count = Object.keys(activeRows).length;
    document.getElementById('activeCount').textContent =
        count > 0 ? count + ' product' + (count > 1 ? 's' : '') + ' with qty' : 'No qty entered yet';
}

// ── Toast helper ──────────────────────────────────────────────────
var toastTimer = null;
function showToast(msg, type) {
    var t = document.getElementById('dj-toast');
    t.textContent = msg;
    t.className   = 'show ' + (type || 'danger');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function() {
        t.className = type || 'danger';
    }, 4000);
}
</script>

{{-- Toast element --}}
<div id="dj-toast"></div>

@endsection