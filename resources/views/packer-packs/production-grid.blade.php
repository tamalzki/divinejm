@extends('layouts.sidebar')
@section('page-title')
    Packers Report — {{ $dailyReport?->production_date?->format('M d, Y') ?? 'Production' }}
@endsection

@section('content')

@php
    $formAction  = route('packer-packs.save-sheet', $report);
    $packDateVal = old('pack_date', $report->pack_date?->format('Y-m-d') ?? $defaultPackDate);
    $expDateVal  = old('expiration_date', $report->expiration_date?->format('Y-m-d') ?? $defaultExpirationDate);
@endphp

<style>
    /* ── layout ── */
    .pg-back { font-size:.78rem; color:var(--text-muted); text-decoration:none; }
    .pg-back:hover { color:var(--accent); }
    .pg-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:1rem; box-shadow:0 1px 4px rgba(0,0,0,.04); margin-bottom:1rem; }
    .pg-title { font-size:.95rem; font-weight:700; margin-bottom:.15rem; }
    .pg-sub   { font-size:.7rem; color:var(--text-muted); }
    .pg-badge { display:inline-block; padding:.15rem .55rem; border-radius:20px; font-size:.62rem; font-weight:700; letter-spacing:.3px; }
    .pg-badge.prod   { background:color-mix(in srgb,var(--accent) 12%,transparent); color:var(--accent); }
    .pg-badge.packer { background:color-mix(in srgb,#16a34a 12%,transparent); color:#16a34a; }
    .session-hint { font-size:.65rem; color:var(--text-muted); display:block; margin-top:.25rem; }

    /* ── table ── */
    .pg-scroll { overflow-x:auto; margin:0 -.75rem; padding:0 .75rem; }
    .pg-table { width:100%; border-collapse:collapse; font-size:.72rem; }
    .pg-table thead tr.pg-group-row th {
        padding:.22rem .3rem; font-size:.6rem; font-weight:700; text-transform:uppercase;
        letter-spacing:.4px; text-align:center; border:1px solid rgba(255,255,255,.12);
    }
    .pg-table thead tr.pg-group-row th.col-prod { background:var(--brand-deep); color:rgba(255,255,255,.9); }
    .pg-table thead tr.pg-group-row th.col-packer{ background:#16532d; color:rgba(255,255,255,.9); }
    .pg-table thead tr.pg-group-row th.col-summary{ background:#374151; color:rgba(255,255,255,.9); }
    .pg-table thead tr.pg-header-row th {
        padding:.3rem .3rem; font-size:.6rem; font-weight:700; text-transform:uppercase;
        letter-spacing:.3px; white-space:nowrap; border:1px solid rgba(255,255,255,.15);
    }
    .pg-table thead tr.pg-header-row th.h-prod  { background:color-mix(in srgb,var(--brand-deep) 85%,#000); color:rgba(255,255,255,.85); }
    .pg-table thead tr.pg-header-row th.h-packer{ background:color-mix(in srgb,#166534 85%,#000); color:rgba(255,255,255,.85); text-align:center; }
    .pg-table thead tr.pg-header-row th.h-summary{ background:color-mix(in srgb,#374151 85%,#000); color:rgba(255,255,255,.85); text-align:right; }
    .pg-table tbody td { padding:.22rem .3rem; border:1px solid var(--border); vertical-align:middle; }
    .pg-table tbody tr:nth-child(even) td { background:rgba(0,0,0,.012); }
    .pname { font-weight:600; white-space:nowrap; position:sticky; left:0; background:var(--bg-card); z-index:1; min-width:9rem; box-shadow:2px 0 4px rgba(0,0,0,.04); }
    .pg-table tbody tr:nth-child(even) .pname { background:var(--bg-card); }
    .prod-num { text-align:right; font-variant-numeric:tabular-nums; color:var(--text-secondary); min-width:3.5rem; }
    .prod-num.yield-act { color:var(--accent); font-weight:700; }

    /* packer cells */
    .qty-cell {
        background:color-mix(in srgb,#dcfce7 65%,transparent);
        cursor:text;
        transition:background .12s, box-shadow .12s;
    }
    .pg-table tbody tr:nth-child(even) .qty-cell { background:color-mix(in srgb,#dcfce7 45%,var(--bg-card)); }
    .qty-cell:hover { background:color-mix(in srgb,#dcfce7 90%,transparent); box-shadow:inset 0 0 0 1px #16a34a44; }
    .qty-cell input {
        font-size:.72rem; padding:.26rem .3rem; width:100%; min-width:3rem; text-align:right;
        border:1px solid #16a34a33; border-radius:5px; background:var(--bg-card);
        cursor:text; transition:border-color .15s,box-shadow .15s;
        -moz-appearance:textfield;
    }
    .qty-cell input:hover { border-color:#16a34a88; }
    .qty-cell input:focus { outline:none; border-color:#16a34a; box-shadow:0 0 0 3px #16a34a22; }
    .qty-cell input::-webkit-outer-spin-button,
    .qty-cell input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }

    /* summary cells */
    .cell-total        { text-align:right; font-weight:700; font-variant-numeric:tabular-nums; color:#16a34a; border-left:2px solid #16a34a44; min-width:3.5rem; }
    .cell-total-packed { text-align:right; font-weight:700; font-variant-numeric:tabular-nums; color:#16a34a; background:color-mix(in srgb,#dcfce7 30%,transparent); min-width:4rem; }
    .cell-yield  { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; color:var(--accent); min-width:3.5rem; }
    .cell-rejects{ text-align:right; font-variant-numeric:tabular-nums; color:#dc2626; min-width:3rem; }
    .cell-unpacked{ text-align:right; font-variant-numeric:tabular-nums; font-weight:700; border-left:2px solid #f59e0b44; min-width:3.5rem; }
    .cell-unpacked.ok  { color:#16a34a; }
    .cell-unpacked.warn{ color:#f59e0b; }
    .cell-unpacked.over{ color:#dc2626; }

    /* save button */
    .btn-save-pg { padding:.45rem 1.2rem; font-weight:700; font-size:.82rem; }

    /* ── history ── */
    .hist-section { margin-top:1.5rem; }
    .hist-title { font-size:.82rem; font-weight:700; margin-bottom:.6rem; display:flex; align-items:center; gap:.4rem; }
    .hist-title i { color:var(--accent); }
    .hist-entry { border:1px solid var(--border); border-radius:var(--radius); margin-bottom:.65rem; overflow:hidden; }
    .hist-header { background:color-mix(in srgb,var(--accent-light) 60%,transparent); padding:.38rem .7rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; font-size:.68rem; }
    .hist-ts { font-weight:700; color:var(--text-primary); }
    .hist-by { color:var(--text-muted); }
    .hist-count { margin-left:auto; font-size:.63rem; color:var(--text-muted); }
    .hist-body { overflow-x:auto; }
    .hist-table { width:100%; border-collapse:collapse; font-size:.7rem; }
    .hist-table thead th {
        background:var(--bg-page); padding:.22rem .5rem;
        font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.3px;
        color:var(--text-muted); border-bottom:1px solid var(--border); white-space:nowrap;
    }
    .hist-table tbody td { padding:.2rem .5rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .hist-table tbody tr:last-child td { border-bottom:0; }
    .hist-table .qty-badge { display:inline-block; padding:.08rem .4rem; border-radius:20px; font-weight:700; font-size:.68rem; background:color-mix(in srgb,#16a34a 12%,transparent); color:#16a34a; }
    .hist-empty { font-size:.72rem; color:var(--text-muted); padding:.5rem 0; }
</style>

<div class="mb-2">
    @if($dailyReport)
        <a href="{{ route('daily-production.index') }}" class="pg-back">
            <i class="bi bi-arrow-left me-1"></i>Back to Daily Production
        </a>
    @else
        <a href="{{ route('packer-packs.index') }}" class="pg-back">
            <i class="bi bi-arrow-left me-1"></i>Back to Packers
        </a>
    @endif
</div>

{{-- Header --}}
<div class="d-flex align-items-start gap-3 mb-3 flex-wrap">
    <div>
        <div class="pg-title">
            <i class="bi bi-box me-2" style="color:var(--accent)"></i>
            Daily Production &amp; Packers Report
            @if($dailyReport)
                &mdash; <span style="font-size:.88rem;color:var(--text-secondary)">{{ $dailyReport->production_date->format('F d, Y') }}</span>
            @endif
        </div>
        <div class="pg-sub mt-1">
            <span class="pg-badge prod me-1"><i class="bi bi-clipboard-data me-1"></i>Production #{{ $dailyReport?->id }}</span>
            <span class="pg-badge packer"><i class="bi bi-people me-1"></i>Packer Report #{{ $report->id }}</span>
            @if($dailyReport?->notes)
                <span class="ms-2 text-muted" style="font-size:.68rem">{{ $dailyReport->notes }}</span>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible py-2 px-3 mb-3" style="font-size:.8rem" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ $formAction }}" class="pg-card" id="prodPackersForm">
    @csrf

    <div class="row g-3 align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label mb-0" style="font-size:.72rem;font-weight:600">Pack date <span class="text-danger">*</span></label>
            <input type="date" name="pack_date" class="form-control form-control-sm" required value="{{ $packDateVal }}">
        </div>
        <div class="col-auto">
            <label class="form-label mb-0" style="font-size:.72rem;font-weight:600">Expiration <span class="text-danger">*</span></label>
            <input type="date" name="expiration_date" class="form-control form-control-sm" required value="{{ $expDateVal }}">
        </div>
        <div class="col-auto">
            <label class="form-label mb-0" style="font-size:.72rem;font-weight:600">Session notes <span class="text-muted fw-normal">optional</span></label>
            <input type="text" name="notes" class="form-control form-control-sm" maxlength="500" placeholder="e.g. morning shift" style="min-width:14rem">
        </div>
        <div class="col-auto ms-auto">
            <button type="submit" class="btn btn-success btn-save-pg">
                <i class="bi bi-arrow-repeat me-1"></i>Update Packing
            </button>
        </div>
    </div>

    @if($errors->has('sheet'))
        <div class="alert alert-danger py-2 px-3 mb-2" style="font-size:.78rem">
            @foreach($errors->get('sheet') as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <div class="pg-scroll">
        <table class="pg-table" id="pgTable">
            <thead>
                {{-- Group header --}}
                <tr class="pg-group-row">
                    <th class="col-prod" colspan="4" style="text-align:left">Production Team Report</th>
                    <th class="col-packer" colspan="{{ count($packerNames) }}">Packers Team Report</th>
                    <th class="col-summary" colspan="5" style="text-align:center">Summary</th>
                </tr>
                {{-- Column header --}}
                <tr class="pg-header-row">
                    <th class="h-prod" style="text-align:left;min-width:9rem">Product</th>
                    <th class="h-prod text-end"># of Mix</th>
                    <th class="h-prod text-end">Exp. Yield</th>
                    <th class="h-prod text-end">Actual Yield</th>
                    @foreach($packerNames as $pname)
                        <th class="h-packer">{{ strtoupper($pname) }}</th>
                    @endforeach
                    <th class="h-summary">This Session</th>
                    <th class="h-summary">Total Packed</th>
                    <th class="h-summary">Actual Yield</th>
                    <th class="h-summary">Rejects</th>
                    <th class="h-summary">Unpacked</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $rowIdx => $p)
                    @php
                        $entry        = $entriesByProduct[$p->id] ?? null;
                        $numMix       = $entry?->number_of_mix ?? 0;
                        $stdYield     = $entry?->standard_yield ?? 0;
                        $actYield     = $entry?->actual_yield ?? 0;
                        $rejects      = $entry?->rejects ?? 0;
                        $totalPacked  = (float) ($entry?->packed_quantity ?? 0);
                        $unpacked     = (float) ($entry?->unpacked ?? 0);
                        $availTopack  = max(0, $actYield - $rejects);

                        $unpackedClass = $unpacked > 0 ? 'warn' : 'ok';
                    @endphp
                    <tr data-row="{{ $rowIdx }}" data-avail="{{ $unpacked }}">
                        <td class="pname">{{ $p->name }}</td>
                        <td class="prod-num">{{ $numMix > 0 ? number_format((float)$numMix,2) : '—' }}</td>
                        <td class="prod-num">{{ $stdYield > 0 ? number_format($stdYield) : '—' }}</td>
                        <td class="prod-num yield-act">{{ $actYield > 0 ? number_format($actYield) : '—' }}</td>
                        @foreach($packerNames as $colIdx => $pname)
                            <td class="qty-cell text-center" title="{{ $pname }}">
                                <input type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                                       class="packer-qty"
                                       name="cells[{{ $p->id }}][{{ $pname }}]"
                                       data-row="{{ $rowIdx }}" data-col="{{ $colIdx }}"
                                       placeholder="0"
                                       aria-label="{{ $p->name }} — {{ $pname }}"
                                       value="">
                            </td>
                        @endforeach
                        {{-- This Session total (live from inputs) --}}
                        <td class="cell-total">
                            <span class="row-total" data-row="{{ $rowIdx }}">0</span>
                        </td>
                        {{-- Accumulated Total Packed (from daily production entry, synced) --}}
                        <td class="cell-total-packed">{{ $totalPacked > 0 ? number_format($totalPacked) : '0' }}</td>
                        <td class="cell-yield">{{ $actYield > 0 ? number_format($actYield) : '—' }}</td>
                        <td class="cell-rejects">{{ $rejects > 0 ? number_format($rejects) : '—' }}</td>
                        <td class="cell-unpacked {{ $unpackedClass }}">
                            <span class="unpacked-val" data-row="{{ $rowIdx }}">{{ number_format($unpacked) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 4 + count($packerNames) + 5 }}" class="text-center text-muted" style="padding:1rem;font-size:.78rem">
                            No products found for this daily production report.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3 pt-3 border-top d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-success btn-save-pg">
            <i class="bi bi-arrow-repeat me-1"></i>Update Packing
        </button>
        <span class="text-muted" style="font-size:.7rem">
            Enter quantities packed <strong>this session</strong>. Fields reset to 0 after saving — each save <em>adds</em> to the running total.
            Inventory stock and packed/unpacked balances are updated automatically.
        </span>
    </div>
</form>

{{-- ── Session History ── --}}
<div class="hist-section">
    <div class="hist-title">
        <i class="bi bi-clock-history"></i>
        Packing Update History
        <span style="font-size:.7rem;font-weight:400;color:var(--text-muted)">({{ $sessionLogs->count() }} session{{ $sessionLogs->count() !== 1 ? 's' : '' }})</span>
    </div>

    @forelse($sessionLogs as $log)
        @php
            // Group snapshot by product for cleaner display
            $byProduct = collect($log->snapshot)->groupBy('product_name');
        @endphp
        <div class="hist-entry">
            <div class="hist-header">
                <i class="bi bi-bookmark-check-fill" style="color:#16a34a;font-size:.82rem"></i>
                <span class="hist-ts">{{ $log->created_at->format('M d, Y  h:i A') }}</span>
                @if($log->savedBy)
                    <span class="hist-by">by {{ $log->savedBy->name }}</span>
                @endif
                @if($log->notes)
                    <span class="ms-1" style="font-size:.64rem;background:var(--bg-page);padding:.1rem .4rem;border-radius:20px;color:var(--text-secondary)">{{ $log->notes }}</span>
                @endif
                <span class="hist-count">{{ count($log->snapshot) }} cell(s)</span>
            </div>
            <div class="hist-body">
                <table class="hist-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            @foreach(collect($log->snapshot)->pluck('packer_name')->unique()->sort()->values() as $pn)
                                <th class="text-end">{{ strtoupper($pn) }}</th>
                            @endforeach
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $logPackerNames = collect($log->snapshot)->pluck('packer_name')->unique()->sort()->values()->all();
                        @endphp
                        @foreach($byProduct as $productName => $rows)
                            @php
                                $packerQtys = $rows->pluck('quantity','packer_name');
                                $rowTotal   = $packerQtys->sum();
                            @endphp
                            <tr>
                                <td style="font-weight:600;white-space:nowrap">{{ $productName }}</td>
                                @foreach($logPackerNames as $pn)
                                    <td class="text-end">
                                        @if(($q = $packerQtys[$pn] ?? 0) > 0)
                                            <span class="qty-badge">{{ number_format($q) }}</span>
                                        @else
                                            <span style="color:var(--text-muted)">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-end" style="font-weight:700;color:#16a34a">{{ number_format($rowTotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <p class="hist-empty"><i class="bi bi-info-circle me-1"></i>No packing sessions recorded yet. Update the packing grid above to start logging history.</p>
    @endforelse
</div>

<script>
(function () {
    var table = document.getElementById('pgTable');
    if (!table) return;

    function digitsOnly(s) { return String(s || '').replace(/\D/g, ''); }
    function sanitize(el) { var d = digitsOnly(el.value); el.value = d === '' ? '' : String(parseInt(d, 10)); }
    function parseQty(v) { var n = parseInt(v, 10); return (isNaN(n) || n < 0) ? 0 : n; }

    var navKeys = { Backspace:1, Delete:1, Tab:1, Escape:1, ArrowLeft:1, ArrowRight:1, ArrowUp:1, ArrowDown:1, Home:1, End:1 };

    function updateRow(rowIdx) {
        var sum = 0;
        table.querySelectorAll('.packer-qty[data-row="' + rowIdx + '"]').forEach(function (inp) {
            sum += parseQty(inp.value);
        });
        var totalEl = table.querySelector('.row-total[data-row="' + rowIdx + '"]');
        if (totalEl) totalEl.textContent = String(sum);

        // Live unpacked preview: current unpacked minus what's being entered this session
        var tr = table.querySelector('tbody tr[data-row="' + rowIdx + '"]');
        var unpackedEl = table.querySelector('.unpacked-val[data-row="' + rowIdx + '"]');
        if (tr && unpackedEl) {
            var currentUnpacked = parseFloat(tr.dataset.avail || 0);
            var previewUnpacked = Math.max(0, currentUnpacked - sum);
            unpackedEl.textContent = Math.round(previewUnpacked).toLocaleString();
            var cell = unpackedEl.closest('.cell-unpacked');
            if (cell) {
                cell.classList.remove('ok', 'warn', 'over');
                cell.classList.add(previewUnpacked > 0 ? 'warn' : 'ok');
            }
        }
    }

    function refreshAll() {
        var rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (tr, idx) { updateRow(idx); });
    }

    table.querySelectorAll('.packer-qty').forEach(function (el) {
        el.addEventListener('input', function () { sanitize(el); updateRow(parseInt(el.dataset.row, 10)); });
        el.addEventListener('blur', function () { if (el.value === '' || el.value === '0') el.value = ''; });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var row = parseInt(el.dataset.row, 10);
                var col = parseInt(el.dataset.col, 10);
                var next = table.querySelector('.packer-qty[data-row="' + (row + 1) + '"][data-col="' + col + '"]');
                if (next) next.focus();
            } else if (!e.ctrlKey && !e.metaKey && !e.altKey && !navKeys[e.key] && !/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        });
        el.addEventListener('paste', function (e) {
            e.preventDefault();
            var chunk = digitsOnly(e.clipboardData ? e.clipboardData.getData('text') : '');
            var start = el.selectionStart != null ? el.selectionStart : el.value.length;
            var end   = el.selectionEnd   != null ? el.selectionEnd   : el.value.length;
            el.value  = el.value.slice(0, start) + chunk + el.value.slice(end);
            sanitize(el);
            updateRow(parseInt(el.dataset.row, 10));
        });
    });

    refreshAll();
})();
</script>

@endsection
