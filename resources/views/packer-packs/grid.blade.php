@extends('layouts.sidebar')
@section('page-title')
    {{ $report === null ? 'New Packers Report' : 'Update Packers Report' }}
@endsection

@section('content')

@php
    $isNew = $report === null;
    $formAction = $isNew ? route('packer-packs.store') : route('packer-packs.save-sheet', $report);
    $packDateVal = old('pack_date', $report?->pack_date?->format('Y-m-d') ?? $defaultPackDate);
    $expDateVal = old('expiration_date', $report?->expiration_date?->format('Y-m-d') ?? $defaultExpirationDate);
    $submitLabel = $isNew ? 'Save report' : 'Update report';
@endphp

<style>
    .sheet-head { margin-bottom: 1rem; }
    .sheet-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
    .sheet-scroll { overflow-x: auto; margin: 0 -1rem; padding: 0 1rem; }
    .pk-edit { width: 100%; border-collapse: collapse; font-size: .72rem; }
    .pk-edit thead th {
        background: var(--brand-deep); color: rgba(255,255,255,.9);
        font-size: .6rem; font-weight: 700; text-transform: uppercase;
        padding: .4rem .35rem; border: 1px solid rgba(255,255,255,.12); white-space: nowrap;
    }
    .pk-edit tbody td { padding: .2rem .3rem; border: 1px solid var(--border); vertical-align: middle; }
    .pk-edit tbody tr:nth-child(even) td { background: rgba(0,0,0,.015); }
    .pk-edit .pname { font-weight: 600; white-space: nowrap; position: sticky; left: 0; background: var(--bg-card); z-index: 1; min-width: 9rem; box-shadow: 2px 0 4px rgba(0,0,0,.04); }
    .pk-edit tbody tr:nth-child(even) .pname { background: var(--bg-card); }
    .grid-edit-banner {
        display: inline-flex; align-items: center; gap: .35rem; flex-wrap: wrap;
        max-width: 100%;
        padding: .28rem .5rem; margin-bottom: .5rem;
        background: linear-gradient(90deg, var(--accent-light), rgba(30,77,123,.06));
        border: 1px dashed color-mix(in srgb, var(--accent) 35%, var(--border));
        border-radius: 6px; font-size: .65rem; line-height: 1.35; color: var(--text-secondary);
    }
    .grid-edit-banner > i.bi { flex-shrink: 0; color: var(--accent); font-size: .85rem; line-height: 1; }
    .pk-edit .qty-cell {
        background: color-mix(in srgb, var(--accent-light) 65%, transparent);
        cursor: text;
        transition: background .12s, box-shadow .12s;
    }
    .pk-edit tbody tr:nth-child(even) .qty-cell { background: color-mix(in srgb, var(--accent-light) 45%, var(--bg-card)); }
    .pk-edit .qty-cell:hover {
        background: color-mix(in srgb, var(--accent-light) 90%, transparent);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--accent) 25%, transparent);
    }
    .pk-edit .qty-cell input {
        font-size: .72rem; padding: .28rem .35rem; width: 100%; min-width: 3.5rem; text-align: right;
        border: 1px solid color-mix(in srgb, var(--accent) 22%, var(--border));
        border-radius: 5px; background: var(--bg-card);
        cursor: text; transition: border-color .15s, box-shadow .15s;
        -moz-appearance: textfield;
    }
    .pk-edit .qty-cell input:hover { border-color: color-mix(in srgb, var(--accent) 45%, var(--border)); }
    .pk-edit .qty-cell input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent);
    }
    .pk-edit .qty-cell input::-webkit-outer-spin-button,
    .pk-edit .qty-cell input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .pk-edit .row-total-cell {
        text-align: right; font-weight: 700; font-variant-numeric: tabular-nums;
        background: var(--bg-page); color: var(--accent);
        border-left: 2px solid color-mix(in srgb, var(--accent) 28%, var(--border));
        white-space: nowrap; min-width: 3.25rem;
    }
    .pk-edit thead th.row-total-th {
        background: color-mix(in srgb, var(--brand-deep) 92%, var(--accent));
        border-left: 2px solid rgba(255,255,255,.2);
    }
    .btn-save-sheet { padding: .45rem 1.2rem; font-weight: 700; font-size: .82rem; }
    .btn-update-sheet { padding: .45rem 1.2rem; font-weight: 700; font-size: .82rem; }
    .hint { font-size: .68rem; color: var(--text-muted); max-width: 44rem; line-height: 1.45; }
    .meta-row input[type="date"] { max-width: 11rem; }
</style>

<div class="mb-2">
    <a href="{{ route('packer-packs.index') }}" style="font-size:.78rem;color:var(--text-muted);text-decoration:none">
        <i class="bi bi-arrow-left me-1"></i>Back to index
    </a>
</div>

<div class="sheet-head">
    <h5 class="fw-bold mb-1" style="font-size:.95rem">
        <i class="bi bi-grid-3x3-gap me-2" style="color:var(--accent)"></i>
        @if($isNew)
            New packers report
        @else
            Update packers report <span class="text-muted fw-normal" style="font-size:.82rem">#{{ $report->id }}</span>
        @endif
    </h5>
    <p class="hint mb-0">Blank cells = <strong>0</strong>. Confirm product × packer quantities before save; amounts apply to finished-product stock. Shortcuts are in the note above the grid.</p>
</div>

{{-- session / validation messages: partials.flash in layout --}}

<form method="POST" action="{{ $formAction }}" class="sheet-card" id="packersGridForm" data-packers-mode="{{ $isNew ? 'create' : 'update' }}">
    @csrf
    <div class="row g-3 align-items-end meta-row mb-3">
        <div class="col-auto">
            <label class="form-label mb-0" style="font-size:.72rem;font-weight:600">Pack date <span class="text-danger">*</span></label>
            <input type="date" name="pack_date" class="form-control form-control-sm" required value="{{ $packDateVal }}">
        </div>
        <div class="col-auto">
            <label class="form-label mb-0" style="font-size:.72rem;font-weight:600">Expiration <span class="text-danger">*</span></label>
            <input type="date" name="expiration_date" class="form-control form-control-sm" required value="{{ $expDateVal }}">
        </div>
        <div class="col-auto">
            <label class="form-label mb-0" style="font-size:.72rem;font-weight:600">Notes <span class="text-muted" style="font-weight:400">optional</span></label>
            <input type="text" name="notes" class="form-control form-control-sm" maxlength="500" value="{{ old('notes', $report?->notes) }}" style="min-width:12rem">
        </div>
        <div class="col-auto ms-auto">
            <button type="submit" class="btn btn-primary {{ $isNew ? 'btn-save-sheet' : 'btn-update-sheet' }}">
                <i class="bi {{ $isNew ? 'bi-check2-circle' : 'bi-arrow-repeat' }} me-1"></i>{{ $submitLabel }}
            </button>
        </div>
    </div>

    <div class="grid-edit-banner">
        <i class="bi bi-pencil-square" aria-hidden="true"></i>
        <span>Pack grid — click cells; numbers only. Blanks = 0. Enter next row · Tab next packer. Row totals update as you type.</span>
    </div>

    <div class="sheet-scroll">
        <table class="pk-edit" id="packersQtyTable">
            <thead>
                <tr>
                    <th style="text-align:left">Product</th>
                    @foreach($packerNames as $name)
                        <th class="text-end">{{ strtoupper($name) }}</th>
                    @endforeach
                    <th class="text-end row-total-th">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $rowIdx => $p)
                    <tr data-product-name="{{ $p->name }}">
                        <td class="pname">{{ $p->name }}</td>
                        @foreach($packerNames as $colIdx => $name)
                            @php $v = $matrix[$p->id][$name] ?? ''; @endphp
                            <td class="qty-cell" title="Click to edit quantity">
                                <input type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                                       class="form-control form-control-sm packer-qty"
                                       name="cells[{{ $p->id }}][{{ $name }}]"
                                       data-row="{{ $rowIdx }}" data-col="{{ $colIdx }}"
                                       placeholder=""
                                       aria-label="Packs for {{ $p->name }}, {{ $name }}"
                                       value="{{ old('cells.'.$p->id.'.'.$name, $v === '' || $v === null ? '' : (int) $v) }}">
                            </td>
                        @endforeach
                        <td class="row-total-cell">
                            <span class="row-total" data-row="{{ $rowIdx }}" title="Sum of all packers for this product">0</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3 pt-3 border-top">
        <button type="submit" class="btn btn-primary {{ $isNew ? 'btn-save-sheet' : 'btn-update-sheet' }}">
            <i class="bi {{ $isNew ? 'bi-check2-circle' : 'bi-arrow-repeat' }} me-1"></i>{{ $submitLabel }}
        </button>
        <span class="text-muted ms-2" style="font-size:.7rem">Blank cells count as 0. Clear all and {{ strtolower($submitLabel) }} to remove quantities (stock is reversed).</span>
    </div>
</form>

<div class="modal fade" id="packersSaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
        <div class="modal-content" style="font-size:.85rem">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="packersSaveModalTitle">Confirm save</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-2" id="packersSaveModalBody"></div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" id="packersSaveModalDismiss">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="packersSaveModalConfirm" style="display:none">Confirm save</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var table = document.getElementById('packersQtyTable');
    if (!table) return;

    function digitsOnly(s) {
        return String(s || '').replace(/\D/g, '');
    }

       function sanitizeInput(el) {
        var d = digitsOnly(el.value);
        el.value = d === '' ? '' : String(parseInt(d, 10));
    }

    var navKeys = { Backspace: 1, Delete: 1, Tab: 1, Escape: 1, ArrowLeft: 1, ArrowRight: 1, ArrowUp: 1, ArrowDown: 1, Home: 1, End: 1 };

    function allowDigitsKeydown(e) {
        if (e.key === 'Enter') return;
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        if (navKeys[e.key]) return;
        if (/^\d$/.test(e.key)) return;
        e.preventDefault();
    }

    function pasteDigitsAtCursor(e, el) {
        e.preventDefault();
        var chunk = digitsOnly(e.clipboardData ? e.clipboardData.getData('text') : '');
        var start = el.selectionStart != null ? el.selectionStart : el.value.length;
        var end = el.selectionEnd != null ? el.selectionEnd : el.value.length;
        el.value = el.value.slice(0, start) + chunk + el.value.slice(end);
        sanitizeInput(el);
    }

    function parseQty(v) {
        var n = parseInt(v, 10);
        return (isNaN(n) || n < 0) ? 0 : n;
    }

    function updateRowTotal(row) {
        var sum = 0;
        table.querySelectorAll('.packer-qty[data-row="' + row + '"]').forEach(function (inp) {
            sum += parseQty(inp.value);
        });
        var out = table.querySelector('.row-total[data-row="' + row + '"]');
        if (out) {
            out.textContent = sum === 0 ? '0' : String(sum);
        }
    }

    function refreshAllRowTotals() {
        var rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (tr, idx) {
            updateRowTotal(idx);
        });
    }

    table.querySelectorAll('.packer-qty').forEach(function (el) {
        el.addEventListener('input', function () {
            sanitizeInput(el);
            updateRowTotal(parseInt(el.dataset.row, 10));
        });
        el.addEventListener('blur', function () {
            if (el.value === '' || el.value === '0') el.value = '';
            updateRowTotal(parseInt(el.dataset.row, 10));
        });
        el.addEventListener('keydown', function (e) {
            allowDigitsKeydown(e);
            if (e.key === 'Enter') {
                e.preventDefault();
                var row = parseInt(el.dataset.row, 10);
                var col = parseInt(el.dataset.col, 10);
                var next = table.querySelector('.packer-qty[data-row="' + (row + 1) + '"][data-col="' + col + '"]');
                if (next) next.focus();
                else {
                    var first = table.querySelector('.packer-qty[data-row="0"][data-col="' + (col + 1) + '"]');
                    if (first) first.focus();
                }
            }
        });
        el.addEventListener('paste', function (e) {
            pasteDigitsAtCursor(e, el);
            updateRowTotal(parseInt(el.dataset.row, 10));
        });
    });

    refreshAllRowTotals();

    /* Save confirmation runs on DOMContentLoaded so Bootstrap JS (loaded at end of layout) is available. */
    document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('packersGridForm');
    var saveModalEl = document.getElementById('packersSaveModal');
    var saveModalConfirm = document.getElementById('packersSaveModalConfirm');
    var saveModalBody = document.getElementById('packersSaveModalBody');
    var saveModalTitle = document.getElementById('packersSaveModalTitle');
    var saveModalDismiss = document.getElementById('packersSaveModalDismiss');
    if (form && saveModalEl && saveModalConfirm && saveModalBody && saveModalTitle) {
        var saveConfirmed = false;
        var saveModal = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
            ? bootstrap.Modal.getOrCreateInstance(saveModalEl)
            : null;

        function escapeHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function analyzePackerSheet() {
            var errors = [];
            var lines = [];
            var packInp = form.querySelector('input[name="pack_date"]');
            var expInp = form.querySelector('input[name="expiration_date"]');
            var packVal = packInp ? packInp.value : '';
            var expVal = expInp ? expInp.value : '';
            if (packVal && expVal && expVal < packVal) {
                errors.push('Expiration must be on or after the pack date.');
            }
            table.querySelectorAll('tbody tr').forEach(function (tr) {
                var productName = tr.getAttribute('data-product-name') || '';
                tr.querySelectorAll('.packer-qty').forEach(function (inp) {
                    var name = inp.getAttribute('name') || '';
                    var m = name.match(/^cells\[(\d+)]\[([^\]]+)]$/);
                    if (!m) return;
                    var packer = m[2];
                    var q = parseQty(inp.value);
                    if (q > 0) {
                        lines.push({ product: productName, packer: packer, qty: q });
                    }
                });
            });
            lines.sort(function (a, b) {
                var c = a.product.localeCompare(b.product);
                if (c !== 0) return c;
                return a.packer.localeCompare(b.packer);
            });
            return { errors: errors, lines: lines };
        }

        function openPackerModalErrors(errors) {
            saveModalTitle.textContent = 'Cannot save';
            saveModalBody.textContent = '';
            var p = document.createElement('p');
            p.className = 'mb-2';
            p.textContent = 'Fix these issues before saving:';
            saveModalBody.appendChild(p);
            var ul = document.createElement('ul');
            ul.className = 'mb-0 ps-3';
            errors.forEach(function (err) {
                var li = document.createElement('li');
                li.textContent = err;
                ul.appendChild(li);
            });
            saveModalBody.appendChild(ul);
            saveModalConfirm.style.display = 'none';
            saveModalDismiss.textContent = 'Close';
            if (saveModal) saveModal.show();
        }

        function openPackerModalConfirm(lines, packDate, expDate, notes) {
            var mode = form.getAttribute('data-packers-mode') || 'create';
            var isUpdate = mode === 'update';

            saveModalTitle.textContent = 'Confirm save';
            saveModalBody.textContent = '';
            var meta = document.createElement('div');
            meta.className = 'mb-2';
            meta.style.fontSize = '.8rem';
            meta.innerHTML = '<strong>Pack date:</strong> ' + escapeHtml(packDate || '—')
                + '<br><strong>Expiration:</strong> ' + escapeHtml(expDate || '—')
                + (notes ? '<br><strong>Notes:</strong> ' + escapeHtml(notes) : '');
            saveModalBody.appendChild(meta);

            if (isUpdate) {
                var rev = document.createElement('p');
                rev.className = 'mb-2';
                rev.style.fontSize = '.74rem';
                rev.style.color = 'var(--text-secondary)';
                rev.textContent = 'This update removes this report’s previous pack quantities from stock, then adds the amounts below.';
                saveModalBody.appendChild(rev);
            }

            var invNote = document.createElement('p');
            invNote.className = 'mb-2';
            invNote.style.fontSize = '.74rem';
            invNote.style.lineHeight = '1.45';
            invNote.innerHTML = 'Each row is <strong>total packs for that product</strong> (all packers combined). Those totals <strong>increase stock on hand</strong> (same as on product / inventory views).';
            saveModalBody.appendChild(invNote);

            if (lines.length === 0) {
                var emptyP = document.createElement('p');
                emptyP.className = 'mb-0 text-warning';
                emptyP.style.fontSize = '.8rem';
                emptyP.textContent = isUpdate
                    ? 'No quantities — all cells are 0. Saving clears this report’s packs and reverses their stock effect.'
                    : 'No quantities entered — every cell is 0. Saving will not change finished stock.';
                saveModalBody.appendChild(emptyP);
            } else {
                var byProduct = {};
                lines.forEach(function (L) {
                    byProduct[L.product] = (byProduct[L.product] || 0) + L.qty;
                });
                var summaryRows = Object.keys(byProduct).sort().map(function (name) {
                    return { product: name, total: byProduct[name] };
                });
                var grand = 0;
                summaryRows.forEach(function (R) { grand += R.total; });

                var cap = document.createElement('p');
                cap.className = 'mb-1 fw-semibold';
                cap.style.fontSize = '.78rem';
                cap.textContent = 'Summary — product and total packs (all packers)';
                saveModalBody.appendChild(cap);
                var wrap = document.createElement('div');
                wrap.className = 'table-responsive mb-2';
                var tbl = document.createElement('table');
                tbl.className = 'table table-sm table-bordered mb-0';
                tbl.style.fontSize = '.74rem';
                var thead = document.createElement('thead');
                thead.innerHTML = '<tr><th>Product</th><th class="text-end">Total packs</th></tr>';
                tbl.appendChild(thead);
                var tb = document.createElement('tbody');
                summaryRows.forEach(function (R) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + escapeHtml(R.product) + '</td>'
                        + '<td class="text-end">' + escapeHtml(String(R.total)) + '</td>';
                    tb.appendChild(tr);
                });
                tbl.appendChild(tb);
                wrap.appendChild(tbl);
                saveModalBody.appendChild(wrap);

                var grandEl = document.createElement('p');
                grandEl.className = 'mb-0 text-muted';
                grandEl.style.fontSize = '.72rem';
                grandEl.textContent = 'Total (all products): ' + String(grand) + ' pack' + (grand === 1 ? '' : 's') + '.';
                saveModalBody.appendChild(grandEl);
            }
            saveModalConfirm.style.display = '';
            saveModalDismiss.textContent = 'Cancel';
            if (saveModal) saveModal.show();
        }

        form.addEventListener('submit', function (e) {
            if (saveConfirmed) {
                saveConfirmed = false;
                return;
            }
            e.preventDefault();
            if (!saveModal) {
                window.alert('Could not open confirm dialog (Bootstrap not loaded). Check your connection or refresh the page.');
                return;
            }
            var out = analyzePackerSheet();
            if (out.errors.length) {
                openPackerModalErrors(out.errors);
                return;
            }
            var notesInp = form.querySelector('input[name="notes"]');
            openPackerModalConfirm(
                out.lines,
                form.querySelector('input[name="pack_date"]') ? form.querySelector('input[name="pack_date"]').value : '',
                form.querySelector('input[name="expiration_date"]') ? form.querySelector('input[name="expiration_date"]').value : '',
                notesInp ? String(notesInp.value || '').trim() : ''
            );
        });

        saveModalConfirm.addEventListener('click', function () {
            saveConfirmed = true;
            saveModal.hide();
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }
    });
})();
</script>
@endsection
