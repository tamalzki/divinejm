@extends('layouts.sidebar')
@section('page-title', 'Deliver to Customer')
@section('content')

<style>
    /* ── Top bar ── */
    .dj-back { display:inline-flex; align-items:center; gap:.3rem; font-size:.73rem; color:var(--text-secondary); text-decoration:none; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-card); transition:background .12s; }
    .dj-back:hover { background:var(--bg-page); color:var(--text-primary); }

    /* ── Compact header card ── */
    .header-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.75rem 1rem; margin-bottom:.75rem; }
    .header-grid { display:grid; grid-template-columns: 2fr 2fr 1.2fr 1.5fr 2fr; gap:.6rem; align-items:end; }
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
        <span style="font-size:.65rem;color:var(--text-muted)">Quantity defaults to 0. Only lines with quantity &gt; 0 are delivered after you confirm in the review step.</span>
    </div>
</div>

<form action="{{ route('branch-inventory.store-delivery') }}" method="POST" id="deliverForm">
@csrf

{{-- Compact Header Fields --}}
<div class="header-card">
    <div class="header-grid">
        {{-- Area --}}
        <div>
            <label class="field-label">Area <span class="field-req">*</span></label>
            <select name="branch_id" id="branchSelect" class="form-select" required>
                <option value="">— Select area —</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}"
                        data-customers="{{ json_encode($b->customers_list ?? []) }}"
                        {{ (string) old('branch_id', isset($branch) ? $branch->id : '') === (string) $b->id ? 'selected' : '' }}>
                        {{ $b->name }}
                    </option>
                @endforeach
            </select>
        </div>
        {{-- Customer --}}
        <div>
            <label class="field-label">Customer <span class="field-req">*</span></label>
            <select name="customer_name" id="customerSelect" class="form-select" required>
                <option value="">— Select area first —</option>
            </select>
        </div>
        {{-- DR Number --}}
        <div>
            <label class="field-label">DR # <span class="field-req">*</span></label>
            <input type="text" name="dr_number" class="form-control" placeholder="e.g. 2740" value="{{ old('dr_number') }}" required>
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
        <span id="activeCount" style="font-size:.68rem;color:var(--text-muted)">0 products with qty</span>
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
            </div>
            <div class="modal-footer border-top" style="background:var(--bg-page)">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="bi bi-pencil-square me-1"></i>Back to edit
                </button>
                <button type="button" class="btn btn-success btn-sm" id="btnSaveDelivery">
                    <i class="bi bi-check-lg me-1"></i>Save delivery
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
        'cost_price'    => $p->cost_price,
        'minimum_stock' => $p->minimum_stock,
    ];
})->values()) !!}
</script>

<script>
var allProducts = [];
var rowIndex    = 0;
var activeRows  = {};

/** Parse numeric input; strips thousands commas and avoids NaN from bad input. */
function parseMoney(el) {
    if (!el) return 0;
    var raw = el.value != null ? el.value : '';
    var s = String(raw).replace(/,/g, '').replace(/\s/g, '').trim();
    if (s === '') return 0;
    var n = parseFloat(s);
    return isFinite(n) ? n : 0;
}

document.addEventListener('DOMContentLoaded', function() {
    allProducts = JSON.parse(document.getElementById('productData').textContent);

    // Render one row per product, alphabetical (already sorted from PHP)
    allProducts.forEach(function(p, i) {
        renderRow(p, i);
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
        onChange: function(id) { loadCustomers(id, null); }
    });

    var oldBranch   = '{{ old("branch_id") }}';
    var oldCustomer = '{{ old("customer_name") }}';
    var prefBranch  = '{{ isset($branch) ? $branch->id : '' }}';
    if (oldBranch) {
        branchTs.setValue(oldBranch, true);
        loadCustomers(oldBranch, oldCustomer || null);
    } else if (prefBranch) {
        branchTs.setValue(prefBranch, true);
        loadCustomers(prefBranch, null);
    }

    var reviewModalEl = document.getElementById('reviewDeliveryModal');
    var reviewModal   = reviewModalEl ? new bootstrap.Modal(reviewModalEl) : null;

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
        var dr = document.querySelector('input[name="dr_number"]');
        if (!dr || !String(dr.value).trim()) {
            showToast('DR number is required.', 'danger');
            return false;
        }
        var md = document.querySelector('input[name="movement_date"]');
        if (!md || !String(md.value).trim()) {
            showToast('Delivery date is required.', 'danger');
            return false;
        }

        var hasAny  = false;
        var errRows = [];
        document.querySelectorAll('.qty-input').forEach(function(inp) {
            var idx   = inp.dataset.idx;
            var val   = parseMoney(inp);
            var avail = parseFloat(String(document.getElementById('avail-' + idx).textContent || '0').replace(/,/g, '')) || 0;
            var extra = parseMoney(document.getElementById('extra-' + idx));
            var row   = document.getElementById('prod-row-' + idx);
            if (val > 0) {
                hasAny = true;
                if (avail > 0 && (val + extra) > avail) {
                    errRows.push(row.querySelector('.prod-name').textContent.trim());
                }
            }
        });

        if (!hasAny) {
            showToast('Enter a quantity greater than zero for at least one product.', 'danger');
            return false;
        }
        if (errRows.length > 0) {
            showToast('Quantity exceeds warehouse stock for: ' + errRows.join(', '), 'danger');
            return false;
        }
        return true;
    }

    function openReviewModal() {
        if (!validateDeliveryFormForReview()) return;

        document.getElementById('reviewAreaName').textContent     = readBranchName() || '—';
        document.getElementById('reviewCustomerName').textContent = customerTs.getValue() || '—';
        document.getElementById('reviewDr').textContent           = document.querySelector('input[name="dr_number"]').value.trim();
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
        disableZeroQtyRowsBeforeSubmit();
        if (reviewModal) reviewModal.hide();
        document.getElementById('deliverForm').submit();
    });
});

function renderRow(product, idx) {
    var tbody = document.getElementById('productsTableBody');
    var tr    = document.createElement('tr');
    tr.id     = 'prod-row-' + idx;

    var avail    = parseFloat(product.stock_on_hand) || 0;
    var minStock = parseFloat(product.minimum_stock) || 0;
    var badgeCls = avail === 0 ? 'zero' : (avail <= minStock ? 'warn' : '');
    var price    = parseFloat(product.selling_price) || 0;

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
                'name="items[' + idx + '][quantity]" step="1" min="0" value="0" oninput="onQtyInput(' + idx + ',' + avail + ')">' +
            '<input type="hidden" name="items[' + idx + '][product_id]" value="' + product.id + '">' +
        '</td>' +
        '<td>' +
            '<input type="number" class="td-input extra-input" id="extra-' + idx + '" ' +
                'name="items[' + idx + '][extra_quantity]" step="1" min="0" value="0" oninput="calcRow(' + idx + ')">' +
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

    badge.className = 'avail-badge' + ((val + extra) > avail && avail > 0 ? ' warn' : (avail === 0 ? ' zero' : ''));

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
    document.getElementById('qty-' + idx).value   = '0';
    document.getElementById('extra-' + idx).value  = '0';
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