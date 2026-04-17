@extends('layouts.sidebar')
@section('page-title')
    {{ $report === null ? 'New Daily Production' : 'Update Daily Production' }}
@endsection

@section('content')

@php
    $isNew = $report === null;
    $formAction = $isNew ? route('daily-production.store') : route('daily-production.save-sheet', $report);
    $productionDateVal = old('production_date', $report?->production_date?->format('Y-m-d') ?? $defaultProductionDate);
    $submitLabel = $isNew ? 'Save report' : 'Update report';
@endphp

<style>
    .sheet-head { margin-bottom: 1rem; }
    .sheet-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
    .sheet-scroll { overflow-x: auto; margin: 0 -1rem; padding: 0 1rem; }
    .mix-table { width: 100%; border-collapse: collapse; font-size: .72rem; min-width: 920px; }
    .mix-table thead th {
        background: var(--brand-deep); color: rgba(255,255,255,.9);
        font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
        padding: .45rem .4rem; border: 1px solid rgba(255,255,255,.12); white-space: nowrap;
    }
    .mix-table tbody td { padding: .25rem .35rem; border: 1px solid var(--border); vertical-align: middle; }
    .mix-table tbody tr:nth-child(even) td { background: rgba(0,0,0,.015); }
    .mix-table .pname { font-weight: 600; color: var(--text-primary); white-space: nowrap; position: sticky; left: 0; background: var(--bg-card); z-index: 1; box-shadow: 2px 0 4px rgba(0,0,0,.04); min-width: 9.5rem; }
    .mix-table tbody tr:nth-child(even) .pname { background: var(--bg-card); }
    .mix-table input.form-control, .mix-table input { font-size: .72rem; padding: .2rem .35rem; min-width: 3.2rem; }
    .mix-table input.txt-wide { min-width: 6rem; }
    .btn-save-sheet { padding: .45rem 1.2rem; font-weight: 700; font-size: .82rem; }
    .btn-update-sheet { padding: .45rem 1.2rem; font-weight: 700; font-size: .82rem; }
    .btn-outline-dang { border-color: #fca5a5; color: var(--s-danger-text); font-size: .78rem; }
    .hint { font-size: .68rem; color: var(--text-muted); max-width: 42rem; line-height: 1.45; }
    .no-recipe { color: var(--s-danger-text); font-size: .65rem; }
    .grid-edit-banner {
        display: inline-flex; align-items: center; gap: .35rem; flex-wrap: wrap;
        max-width: 100%;
        padding: .28rem .5rem; margin-bottom: .5rem;
        background: linear-gradient(90deg, var(--accent-light), rgba(30,77,123,.06));
        border: 1px dashed color-mix(in srgb, var(--accent) 35%, var(--border));
        border-radius: 6px; font-size: .65rem; line-height: 1.35; color: var(--text-secondary);
    }
    .grid-edit-banner > i.bi { flex-shrink: 0; color: var(--accent); font-size: .85rem; line-height: 1; }
    .mix-variance-cell {
        text-align: right; white-space: nowrap; min-width: 4.5rem;
        background: color-mix(in srgb, var(--accent-light) 35%, transparent);
        font-variant-numeric: tabular-nums;
    }
    .mix-table tbody tr:nth-child(even) .mix-variance-cell { background: color-mix(in srgb, var(--accent-light) 22%, var(--bg-card)); }
    .mix-variance-val { font-weight: 700; font-size: .74rem; }
    .mix-variance-val.pos { color: var(--s-success-text); }
    .mix-variance-val.neg { color: var(--s-danger-text); }
    .mix-variance-val.zero { color: var(--text-secondary); }
    .mix-variance-val.empty { color: var(--text-muted); font-weight: 600; }
    .mix-variance-pct { display: block; font-size: .62rem; font-weight: 600; color: var(--text-muted); margin-top: .06rem; }
    .mix-table tbody tr.mix-row-no-recipe td { background: rgba(0,0,0,.04); color: var(--text-muted); }
    .mix-table tbody tr.mix-row-no-recipe td.pname { opacity: .92; }
    .mix-table tbody tr.mix-row-no-recipe .mix-variance-cell { background: rgba(0,0,0,.03); }
    .mix-table tbody tr.mix-row-no-recipe input:disabled {
        background: rgba(0,0,0,.06); border-color: var(--border); color: var(--text-muted); cursor: not-allowed; opacity: 1;
    }
</style>

<div class="mb-2">
    <a href="{{ route('daily-production.index') }}" style="font-size:.78rem;color:var(--text-muted);text-decoration:none">
        <i class="bi bi-arrow-left me-1"></i>Back to index
    </a>
</div>

<div class="sheet-head">
    <h5 class="fw-bold mb-1" style="font-size:.95rem">
        <i class="bi bi-table me-2" style="color:var(--accent)"></i>
        @if($isNew)
            New daily production
        @else
            Update daily production <span class="text-muted fw-normal" style="font-size:.82rem">#{{ $report->id }}</span>
        @endif
    </h5>
    <p class="hint mb-0">Raw materials deduct as <strong>recipe × # of mix</strong> per filled row. Empty numeric fields are saved as <strong>0</strong>. Deductions apply even if on-hand goes <strong>negative</strong> (e.g. production ahead of recorded stock). You will confirm product lines and quantities before the report is saved. Saving replaces all lines on this report and returns to the index.</p>
</div>

{{-- session / validation: partials.flash in layout --}}

<form method="POST" action="{{ $formAction }}" class="sheet-card" id="dailyProductionForm">
    @csrf
    <div class="row g-2 align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label mb-0" style="font-size:.72rem;font-weight:600">Production date <span class="text-danger">*</span></label>
            <input type="date" name="production_date" class="form-control form-control-sm" required value="{{ $productionDateVal }}">
        </div>
        <div class="col-auto">
            <label class="form-label mb-0" style="font-size:.72rem;font-weight:600">Notes <span class="text-muted" style="font-weight:400">optional</span></label>
            <input type="text" name="notes" class="form-control form-control-sm" maxlength="500" value="{{ old('notes', $report?->notes) }}" style="min-width:14rem">
        </div>
        <div class="col-auto ms-auto">
            <button type="submit" class="btn btn-primary {{ $isNew ? 'btn-save-sheet' : 'btn-update-sheet' }}">
                <i class="bi {{ $isNew ? 'bi-check2-circle' : 'bi-arrow-repeat' }} me-1"></i>{{ $submitLabel }}
            </button>
        </div>
    </div>

    <div class="grid-edit-banner">
        <i class="bi bi-boxes" aria-hidden="true"></i>
        <span>Mix grid — deducts raw materials (recipe × mix) only. Rows without a recipe are disabled. Blanks = 0; rows with no data are skipped. Variance = actual − standard. Enter next row · Tab next cell.</span>
    </div>

    <div class="sheet-scroll">
        <table class="mix-table" id="dailyMixTable">
            <thead>
                <tr>
                    <th style="text-align:left">Product</th>
                    <th class="text-center"># of mix</th>
                    <th class="text-end">Std yield</th>
                    <th class="text-end">Actual yield</th>
                    <th class="text-end" title="Actual minus standard">Variance</th>
                    <th class="text-end">Rejects</th>
                    <th style="text-align:left">Unfinished</th>
                    <th class="text-end">Unpacked</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $rowIdx => $p)
                    @php
                        $e = $entriesByProduct->get($p->id);
                        $pfx = "lines.{$p->id}";
                        $stdOld = old($pfx.'.standard_yield', $e?->standard_yield);
                        $actOld = old($pfx.'.actual_yield', $e?->actual_yield);
                        $hasVar = $stdOld !== null && $stdOld !== '' && is_numeric($stdOld) && $actOld !== null && $actOld !== '' && is_numeric($actOld);
                        $varNum = $hasVar ? (float) $actOld - (float) $stdOld : null;
                    @endphp
                    <tr data-product-name="{{ $p->name }}" data-has-recipe="{{ $p->recipes->isEmpty() ? '0' : '1' }}" @class(['mix-row-no-recipe' => $p->recipes->isEmpty()]) title="{{ $p->recipes->isEmpty() ? 'Add a recipe for this product to enter production.' : '' }}">
                        <td class="pname">
                            {{ $p->name }}
                            @if($p->recipes->isEmpty())
                                <span class="no-recipe d-block">No recipe — row disabled</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <input type="text" inputmode="numeric" pattern="[0-9]*" name="lines[{{ $p->id }}][number_of_mix]" class="form-control form-control-sm text-center mix-grid-cell mix-num-int"
                                   placeholder="—" maxlength="3"
                                   data-row="{{ $rowIdx }}" data-col="0"
                                   autocomplete="off"
                                   @disabled($p->recipes->isEmpty())
                                   value="{{ old($pfx.'.number_of_mix', $e?->number_of_mix) }}">
                        </td>
                        <td>
                            <input type="text" inputmode="decimal" name="lines[{{ $p->id }}][standard_yield]" class="form-control form-control-sm text-end mix-grid-cell mix-yield-std mix-num-dec"
                                   placeholder="—"
                                   data-row="{{ $rowIdx }}" data-col="1"
                                   autocomplete="off"
                                   @disabled($p->recipes->isEmpty())
                                   value="{{ old($pfx.'.standard_yield', $e?->standard_yield) }}">
                        </td>
                        <td>
                            <input type="text" inputmode="decimal" name="lines[{{ $p->id }}][actual_yield]" class="form-control form-control-sm text-end mix-grid-cell mix-yield-actual mix-num-dec"
                                   placeholder="—"
                                   data-row="{{ $rowIdx }}" data-col="2"
                                   autocomplete="off"
                                   @disabled($p->recipes->isEmpty())
                                   value="{{ old($pfx.'.actual_yield', $e?->actual_yield) }}">
                        </td>
                        <td class="mix-variance-cell">
                            <span class="mix-variance-val {{ $varNum === null ? 'empty' : ($varNum > 0 ? 'pos' : ($varNum < 0 ? 'neg' : 'zero')) }}" data-var-row="{{ $rowIdx }}">
                                @if($varNum !== null)
                                    {{ $varNum > 0 ? '+' : '' }}{{ number_format($varNum, 2) }}
                                @else
                                    —
                                @endif
                            </span>
                            <span class="mix-variance-pct" data-var-pct-row="{{ $rowIdx }}">@if($varNum !== null && (float) $stdOld != 0.0){{ ($varNum >= 0 ? '+' : '') . number_format(((float) $actOld - (float) $stdOld) / (float) $stdOld * 100, 1) }}%@endif</span>
                        </td>
                        <td>
                            <input type="text" inputmode="decimal" name="lines[{{ $p->id }}][rejects]" class="form-control form-control-sm text-end mix-grid-cell mix-num-dec"
                                   placeholder="0"
                                   data-row="{{ $rowIdx }}" data-col="3"
                                   autocomplete="off"
                                   @disabled($p->recipes->isEmpty())
                                   value="{{ old($pfx.'.rejects', $e?->rejects) }}">
                        </td>
                        <td>
                            <input type="text" name="lines[{{ $p->id }}][unfinished]" class="form-control form-control-sm txt-wide mix-grid-cell"
                                   maxlength="500" placeholder="—"
                                   data-row="{{ $rowIdx }}" data-col="4"
                                   autocomplete="off"
                                   @disabled($p->recipes->isEmpty())
                                   value="{{ old($pfx.'.unfinished', $e?->unfinished) }}">
                        </td>
                        <td>
                            <input type="text" inputmode="decimal" name="lines[{{ $p->id }}][unpacked]" class="form-control form-control-sm text-end mix-grid-cell mix-num-dec"
                                   placeholder="—"
                                   data-row="{{ $rowIdx }}" data-col="5"
                                   autocomplete="off"
                                   @disabled($p->recipes->isEmpty())
                                   value="{{ old($pfx.'.unpacked', $e?->unpacked) }}">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
        <button type="submit" class="btn btn-primary {{ $isNew ? 'btn-save-sheet' : 'btn-update-sheet' }}">
            <i class="bi {{ $isNew ? 'bi-check2-circle' : 'bi-arrow-repeat' }} me-1"></i>{{ $submitLabel }}
        </button>
        @if(!$isNew)
            <button type="button" class="btn btn-outline-danger btn-outline-dang" data-bs-toggle="modal" data-bs-target="#delReportModal">Delete report</button>
        @endif
    </div>
</form>

@if(!$isNew)
<div class="modal fade" id="delReportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="font-size:.85rem">
            <div class="modal-header py-2">
                <h6 class="modal-title">Delete report #{{ $report->id }}?</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-2">Raw material deductions for this report will be restored.</div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('daily-production.destroy', $report) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="dailySaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
        <div class="modal-content" style="font-size:.85rem">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="dailySaveModalTitle">Confirm save</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-2" id="dailySaveModalBody"></div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" id="dailySaveModalDismiss">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="dailySaveModalConfirm" style="display:none">Confirm save</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var table = document.getElementById('dailyMixTable');
    if (!table) return;

    var navKeys = { Backspace: 1, Delete: 1, Tab: 1, Escape: 1, Enter: 1, ArrowLeft: 1, ArrowRight: 1, ArrowUp: 1, ArrowDown: 1, Home: 1, End: 1 };

    function allowIntKeydown(e) {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        if (navKeys[e.key]) return;
        if (/^\d$/.test(e.key)) return;
        e.preventDefault();
    }

    function allowDecKeydown(e) {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        if (navKeys[e.key]) return;
        if (/^\d$/.test(e.key)) return;
        if (e.key === '.' && String(e.target.value).indexOf('.') === -1) return;
        e.preventDefault();
    }

    function sanitizeIntField(el) {
        var d = String(el.value || '').replace(/\D/g, '');
        if (d.length > 3) d = d.slice(0, 3);
        el.value = d;
    }

    function sanitizeDecField(el) {
        var v = String(el.value || '').replace(/[^\d.]/g, '');
        var parts = v.split('.');
        if (parts.length <= 1) {
            el.value = v;

            return;
        }
        el.value = parts[0] + '.' + parts.slice(1).join('');
    }

    function pasteDigitsOnly(e, el, asDecimal) {
        e.preventDefault();
        var raw = e.clipboardData ? e.clipboardData.getData('text') : '';
        var chunk = asDecimal ? String(raw).replace(/[^\d.]/g, '') : String(raw).replace(/\D/g, '');
        if (!asDecimal && chunk.length > 3) chunk = chunk.slice(0, 3);
        var start = el.selectionStart != null ? el.selectionStart : el.value.length;
        var end = el.selectionEnd != null ? el.selectionEnd : el.value.length;
        el.value = el.value.slice(0, start) + chunk + el.value.slice(end);
        if (asDecimal) sanitizeDecField(el);
        else sanitizeIntField(el);
    }

    function fireVarianceForRowFromEl(el) {
        if (!el || el.dataset.row == null) return;
        updateVarianceRow(parseInt(el.dataset.row, 10));
    }

    table.querySelectorAll('.mix-num-int').forEach(function (el) {
        el.addEventListener('keydown', allowIntKeydown);
        el.addEventListener('input', function () {
            sanitizeIntField(el);
            fireVarianceForRowFromEl(el);
        });
        el.addEventListener('paste', function (e) { pasteDigitsOnly(e, el, false); });
    });

    table.querySelectorAll('.mix-num-dec').forEach(function (el) {
        el.addEventListener('keydown', allowDecKeydown);
        el.addEventListener('input', function () {
            sanitizeDecField(el);
            fireVarianceForRowFromEl(el);
        });
        el.addEventListener('paste', function (e) {
            pasteDigitsOnly(e, el, true);
            fireVarianceForRowFromEl(el);
        });
    });

    function parseInt0(v) {
        var n = parseInt(String(v || '').replace(/\D/g, ''), 10);
        return isNaN(n) ? 0 : n;
    }

    function parseFloat0(v) {
        if (v === '' || v === null || v === undefined) return 0;
        var n = parseFloat(String(v).replace(/,/g, ''));
        if (isNaN(n)) return 0;
        return n < 0 ? 0 : n;
    }

    function updateVarianceRow(rowIdx) {
        var mixInp = table.querySelector('.mix-grid-cell[data-row="' + rowIdx + '"][data-col="0"]');
        var stdInp = table.querySelector('.mix-yield-std[data-row="' + rowIdx + '"]');
        var actInp = table.querySelector('.mix-yield-actual[data-row="' + rowIdx + '"]');
        var span = table.querySelector('[data-var-row="' + rowIdx + '"]');
        var pctEl = table.querySelector('[data-var-pct-row="' + rowIdx + '"]');
        if (!span) return;

        var mixVal = mixInp ? parseInt0(mixInp.value) : 0;
        var stdRaw = stdInp ? stdInp.value : '';
        var actRaw = actInp ? actInp.value : '';
        var bothBlank = (stdRaw === '' || stdRaw == null) && (actRaw === '' || actRaw == null);

        span.classList.remove('pos', 'neg', 'zero', 'empty');
        if (pctEl) {
            pctEl.textContent = '';
            pctEl.style.display = 'none';
        }

        if (bothBlank && mixVal === 0) {
            span.textContent = '—';
            span.classList.add('empty');
            return;
        }

        var std = parseFloat0(stdRaw);
        var act = parseFloat0(actRaw);

        var diff = act - std;
        span.textContent = (diff > 0 ? '+' : '') + diff.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (diff > 0) span.classList.add('pos');
        else if (diff < 0) span.classList.add('neg');
        else span.classList.add('zero');

        if (pctEl) {
            if (std !== 0) {
                var pct = (diff / std) * 100;
                pctEl.textContent = (pct >= 0 ? '+' : '') + pct.toFixed(1) + '%';
                pctEl.style.display = 'block';
            } else {
                pctEl.textContent = '';
                pctEl.style.display = 'none';
            }
        }
    }

    table.querySelectorAll('.mix-yield-std').forEach(function (inp) {
        updateVarianceRow(parseInt(inp.dataset.row, 10));
    });

    function focusNextEnabledMixCell(fromRow, col) {
        var numRows = table.querySelectorAll('tbody tr').length;
        var r = fromRow + 1;
        while (r < numRows) {
            var el = table.querySelector('.mix-grid-cell[data-row="' + r + '"][data-col="' + col + '"]');
            if (el && !el.disabled) {
                el.focus();
                if (el.select) el.select();
                return;
            }
            r += 1;
        }
        var c = col + 1;
        if (c > 5) return;
        for (r = 0; r < numRows; r += 1) {
            var el2 = table.querySelector('.mix-grid-cell[data-row="' + r + '"][data-col="' + c + '"]');
            if (el2 && !el2.disabled) {
                el2.focus();
                if (el2.select) el2.select();
                return;
            }
        }
    }

    table.querySelectorAll('.mix-grid-cell').forEach(function (el) {
        el.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            var row = parseInt(el.dataset.row, 10);
            var col = parseInt(el.dataset.col, 10);
            focusNextEnabledMixCell(row, col);
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('dailyProductionForm');
    var saveModalEl = document.getElementById('dailySaveModal');
    var saveModalConfirm = document.getElementById('dailySaveModalConfirm');
    var saveModalBody = document.getElementById('dailySaveModalBody');
    var saveModalTitle = document.getElementById('dailySaveModalTitle');
    var saveModalDismiss = document.getElementById('dailySaveModalDismiss');
    if (form && saveModalEl && saveModalConfirm && saveModalBody && saveModalTitle) {
        var saveConfirmed = false;
        var saveModal = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
            ? bootstrap.Modal.getOrCreateInstance(saveModalEl)
            : null;

        function rowInputs(tr) {
            return {
                mix: tr.querySelector('.mix-grid-cell[data-col="0"]'),
                std: tr.querySelector('.mix-grid-cell[data-col="1"]'),
                act: tr.querySelector('.mix-grid-cell[data-col="2"]'),
                rej: tr.querySelector('.mix-grid-cell[data-col="3"]'),
                unfinished: tr.querySelector('.mix-grid-cell[data-col="4"]'),
                unp: tr.querySelector('.mix-grid-cell[data-col="5"]'),
            };
        }

        function analyzeSheet() {
            var errors = [];
            var lines = [];
            table.querySelectorAll('tbody tr').forEach(function (tr) {
                if (tr.getAttribute('data-has-recipe') !== '1') return;
                var name = tr.getAttribute('data-product-name') || '';
                var inp = rowInputs(tr);
                var mix = inp.mix ? parseInt0(inp.mix.value) : 0;
                var std = inp.std ? parseFloat0(inp.std.value) : 0;
                var act = inp.act ? parseFloat0(inp.act.value) : 0;
                var rej = inp.rej ? parseFloat0(inp.rej.value) : 0;
                var unp = inp.unp ? parseFloat0(inp.unp.value) : 0;
                var unfinished = inp.unfinished ? String(inp.unfinished.value || '').trim() : '';
                var hasNumbers = mix > 0 || std > 0 || act > 0 || rej > 0 || unp > 0 || unfinished !== '';
                if (!hasNumbers) return;
                if (mix < 1) {
                    errors.push(name + ': # of mix is required (at least 1) when entering a row.');
                    return;
                }
                lines.push({
                    name: name,
                    mix: mix,
                    std: std,
                    act: act,
                    rej: rej,
                    unfinished: unfinished,
                    unp: unp,
                });
            });
            return { errors: errors, lines: lines };
        }

        function formatQty(n) {
            return Number(n).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function openSaveModalErrors(errors) {
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

        function openSaveModalConfirm(lines, productionDate, notes) {
            saveModalTitle.textContent = 'Confirm save';
            saveModalBody.textContent = '';
            var meta = document.createElement('div');
            meta.className = 'mb-2';
            meta.style.fontSize = '.8rem';
            meta.innerHTML = '<strong>Production date:</strong> ' + escapeHtml(productionDate || '—')
                + (notes ? '<br><strong>Notes:</strong> ' + escapeHtml(notes) : '');
            saveModalBody.appendChild(meta);

            if (lines.length === 0) {
                var emptyP = document.createElement('p');
                emptyP.className = 'mb-0 text-warning';
                emptyP.style.fontSize = '.8rem';
                emptyP.textContent = 'No product lines will be saved. Raw materials will not change.';
                saveModalBody.appendChild(emptyP);
            } else {
                var cap = document.createElement('p');
                cap.className = 'mb-2';
                cap.style.fontSize = '.78rem';
                cap.textContent = 'The following lines will be saved (blanks counted as 0). Raw materials will deduct by recipe × # of mix.';
                saveModalBody.appendChild(cap);
                var wrap = document.createElement('div');
                wrap.className = 'table-responsive';
                var tbl = document.createElement('table');
                tbl.className = 'table table-sm table-bordered mb-0';
                tbl.style.fontSize = '.74rem';
                var thead = document.createElement('thead');
                thead.innerHTML = '<tr><th>Product</th><th class="text-end">Mix</th><th class="text-end">Std yield</th><th class="text-end">Actual</th><th class="text-end">Rejects</th><th>Unfinished</th><th class="text-end">Unpacked</th></tr>';
                tbl.appendChild(thead);
                var tb = document.createElement('tbody');
                lines.forEach(function (L) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + escapeHtml(L.name) + '</td>'
                        + '<td class="text-end">' + formatQty(L.mix) + '</td>'
                        + '<td class="text-end">' + formatQty(L.std) + '</td>'
                        + '<td class="text-end">' + formatQty(L.act) + '</td>'
                        + '<td class="text-end">' + formatQty(L.rej) + '</td>'
                        + '<td>' + escapeHtml(L.unfinished || '—') + '</td>'
                        + '<td class="text-end">' + formatQty(L.unp) + '</td>';
                    tb.appendChild(tr);
                });
                tbl.appendChild(tb);
                wrap.appendChild(tbl);
                saveModalBody.appendChild(wrap);
            }
            saveModalConfirm.style.display = '';
            saveModalDismiss.textContent = 'Cancel';
            if (saveModal) saveModal.show();
        }

        function escapeHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
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
            var out = analyzeSheet();
            if (out.errors.length) {
                openSaveModalErrors(out.errors);
                return;
            }
            var dateInp = form.querySelector('input[name="production_date"]');
            var notesInp = form.querySelector('input[name="notes"]');
            openSaveModalConfirm(out.lines, dateInp ? dateInp.value : '', notesInp ? String(notesInp.value || '').trim() : '');
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
