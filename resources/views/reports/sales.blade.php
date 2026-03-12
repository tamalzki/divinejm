@extends('layouts.sidebar')
@section('page-title', 'Sales Report')
@section('content')

<style>
    /* ── Filter bar ── */
    .rpt-bar { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.6rem .9rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .period-btn { font-size:.72rem; padding:.2rem .65rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-page); color:var(--text-secondary); cursor:pointer; text-decoration:none; white-space:nowrap; font-weight:600; }
    .period-btn:hover { background:var(--accent-faint); color:var(--accent); border-color:var(--accent); }
    .period-btn.active { background:var(--accent); color:#fff; border-color:var(--accent); }
    .rpt-sep  { width:1px; height:18px; background:var(--border); flex-shrink:0; }
    .date-input { padding:.22rem .48rem; font-size:.76rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .rpt-select { padding:.22rem .48rem; font-size:.75rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .btn-apply { font-size:.74rem; font-weight:600; padding:.24rem .75rem; border-radius:4px; background:var(--accent); color:#fff; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:.3rem; }
    .btn-apply:hover { background:var(--accent-hover); }
    .btn-dl { font-size:.74rem; font-weight:600; padding:.24rem .75rem; border-radius:4px; background:#16a34a; color:#fff; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:.3rem; }
    .btn-dl:hover { background:#15803d; }
    .lbl { font-size:.70rem; color:var(--text-muted); white-space:nowrap; }

    /* ── Summary tiles ── */
    .tile-row { display:grid; grid-template-columns:repeat(4,1fr); gap:.65rem; margin-bottom:.9rem; }
    .sum-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.65rem .9rem; }
    .sum-tile-label { font-size:.60rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:.15rem; }
    .sum-tile-value { font-size:.93rem; font-weight:700; }
    .c-accent { color:var(--accent); } .c-green { color:var(--s-success-text); }
    .c-amber  { color:var(--s-warning-text); } .c-red { color:var(--s-danger-text); }

    /* ── Table wrapper ── */
    .rpt-wrap { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; }
    .rpt-scroll { overflow-x:auto; width:100%; }

    /* ── Spreadsheet table ── */
    .sr-table { width:max-content; min-width:100%; border-collapse:collapse; font-size:.74rem; }

    .sr-table thead th {
        background:var(--brand-deep); color:rgba(255,255,255,.9);
        font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px;
        padding:.46rem .65rem; border-right:1px solid rgba(255,255,255,.07);
        white-space:nowrap; position:sticky; top:0; z-index:2;
    }
    .sr-table thead th.th-product { background:#1b3a5c; text-align:center; min-width:42px; }
    .sr-table thead th.th-group   { background:#223044; text-align:center; font-size:.58rem; letter-spacing:.6px; }

    /* Sticky left columns */
    .sr-table thead th.col-date,
    .sr-table tbody td.col-date   { position:sticky; left:0; z-index:3; min-width:82px; }
    .sr-table thead th.col-dr,
    .sr-table tbody td.col-dr     { position:sticky; left:82px; z-index:3; min-width:60px; }
    .sr-table thead th.col-cust,
    .sr-table tbody td.col-cust   { position:sticky; left:142px; z-index:3; min-width:130px; }

    .sr-table thead th.col-date,
    .sr-table thead th.col-dr,
    .sr-table thead th.col-cust   { z-index:5; }

    .sr-table tbody td.col-date,
    .sr-table tbody td.col-dr,
    .sr-table tbody td.col-cust   { background:var(--bg-card); border-right:2px solid var(--border); }

    .sr-table tbody tr:hover td.col-date,
    .sr-table tbody tr:hover td.col-dr,
    .sr-table tbody tr:hover td.col-cust { background:var(--accent-faint); }

    .sr-table tbody td {
        padding:.40rem .65rem; border-bottom:1px solid var(--border);
        border-right:1px solid var(--border); vertical-align:middle; white-space:nowrap;
    }
    .sr-table tbody tr:last-child td { border-bottom:none; }
    .sr-table tbody tr:hover td { background:var(--accent-faint); }

    /* Grand total */
    .row-grand td { background:var(--brand-deep) !important; color:rgba(255,255,255,.92) !important; font-weight:700; font-size:.73rem; }
    .row-grand td.col-date,
    .row-grand td.col-dr,
    .row-grand td.col-cust { background:var(--brand-deep) !important; color:rgba(255,255,255,.92) !important; }

    /* Pills */
    .pill { display:inline-flex; align-items:center; padding:.07rem .36rem; border-radius:3px; font-size:.62rem; font-weight:700; }
    .pill-paid    { background:var(--s-success-bg); color:var(--s-success-text); }
    .pill-collect { background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .pill-partial { background:var(--s-warning-bg); color:var(--s-warning-text); }

    .qty-cell { text-align:center; }
    .qty-val  { font-weight:600; }
    .qty-null { color:#d1d5db; font-size:.65rem; }

    .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); font-size:.82rem; }

</style>

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-2 no-print">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-graph-up me-1" style="color:var(--accent)"></i>Sales Report
        </h5>
        <span style="font-size:.67rem;color:var(--text-muted)">
            {{ \Carbon\Carbon::parse($fromStr)->format('M d, Y') }}
            @if($fromStr !== $toStr) — {{ \Carbon\Carbon::parse($toStr)->format('M d, Y') }} @endif
            &nbsp;·&nbsp; {{ ucfirst($period) }}
        </span>
    </div>
    <div class="d-flex gap-2">
        <button class="btn-dl" onclick="downloadCSV()">
            <i class="bi bi-file-earmark-spreadsheet"></i> Download CSV
        </button>
    </div>
</div>

{{-- Filter bar --}}
<form method="GET" action="{{ route('reports.sales') }}" id="rptForm" class="no-print">
<div class="rpt-bar">
    <a href="{{ route('reports.sales', array_merge(request()->except('period','from','to'), ['period'=>'daily'])) }}"
       class="period-btn {{ $period==='daily'     ? 'active':'' }}">Daily</a>
    <a href="{{ route('reports.sales', array_merge(request()->except('period','from','to'), ['period'=>'weekly'])) }}"
       class="period-btn {{ $period==='weekly'    ? 'active':'' }}">Weekly</a>
    <a href="{{ route('reports.sales', array_merge(request()->except('period','from','to'), ['period'=>'quarterly'])) }}"
       class="period-btn {{ $period==='quarterly' ? 'active':'' }}">Quarterly</a>
    <div class="rpt-sep"></div>
    <span class="lbl">From</span>
    <input type="date" name="from" class="date-input" value="{{ $fromStr }}">
    <span class="lbl">To</span>
    <input type="date" name="to"   class="date-input" value="{{ $toStr }}">
    <input type="hidden" name="period" value="{{ $period }}">
    <div class="rpt-sep"></div>
    <span class="lbl">Area</span>
    <select name="area" class="rpt-select" onchange="document.getElementById('rptForm').submit()">
        <option value="">All Areas</option>
        @foreach($areas as $area)
            <option value="{{ $area }}" {{ $areaFilter===$area ? 'selected':'' }}>{{ $area }}</option>
        @endforeach
    </select>
    <span class="lbl">Status</span>
    <select name="status" class="rpt-select" onchange="document.getElementById('rptForm').submit()">
        <option value="">All Status</option>
        <option value="paid"            {{ $statusFilter==='paid'            ? 'selected':'' }}>Paid</option>
        <option value="to_be_collected" {{ $statusFilter==='to_be_collected' ? 'selected':'' }}>To Collect</option>
        <option value="partial"         {{ $statusFilter==='partial'         ? 'selected':'' }}>Partial</option>
    </select>
    <button type="submit" class="btn-apply"><i class="bi bi-search"></i> Apply</button>
</div>
</form>

{{-- Summary tiles --}}
@php
    $paidCount    = collect($rows)->where('status','paid')->count();
    $collectCount = collect($rows)->where('status','to_be_collected')->count();
    $partialCount = collect($rows)->where('status','partial')->count();
@endphp
<div class="tile-row no-print">
    <div class="sum-tile">
        <span class="sum-tile-label">Total Transactions</span>
        <span class="sum-tile-value c-accent">{{ $rows->count() }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Total Sales</span>
        <span class="sum-tile-value c-green">&#8369;{{ number_format($grandTotal, 2) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Total Items Sold</span>
        <span class="sum-tile-value c-accent">{{ number_format($grandTotalItems, 0) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Paid / Partial / Collect</span>
        <span class="sum-tile-value" style="font-size:.78rem">
            <span class="c-green">{{ $paidCount }}</span>
            <span style="color:var(--text-muted);font-weight:400"> / </span>
            <span class="c-amber">{{ $partialCount }}</span>
            <span style="color:var(--text-muted);font-weight:400"> / </span>
            <span class="c-red">{{ $collectCount }}</span>
        </span>
    </div>
</div>

{{-- Main table --}}
<div class="rpt-wrap print-area">

    <div class="rpt-scroll" id="tableWrapper">
        <table class="sr-table" id="salesTable">
            <thead>
                <tr>
                    <th class="col-date" rowspan="2">Date</th>
                    <th class="col-dr"   rowspan="2">DR#</th>
                    <th class="col-cust" rowspan="2">Customer</th>
                    <th rowspan="2" class="text-end" style="min-width:90px">Total Amount</th>
                    <th rowspan="2" class="text-end" style="min-width:90px">Sub Total/Day</th>
                    <th rowspan="2" style="min-width:70px">Area</th>
                    <th rowspan="2" style="min-width:70px">Status</th>
                    <th rowspan="2" style="min-width:65px">Payment</th>
                    <th rowspan="2" style="min-width:70px">GCash Ref</th>
                    <th rowspan="2" style="min-width:100px">Note</th>
                    @if($products->count())
                    <th colspan="{{ $products->count() }}" class="th-group">PRODUCTS</th>
                    @endif
                    <th rowspan="2" class="text-center" style="min-width:46px">Total#</th>
                </tr>
                <tr>
                    @foreach($products as $fp)
                    <th class="th-product" title="{{ $fp->name }}">
                        {{ strtoupper(implode('', array_map(fn($w) => $w[0], array_filter(explode(' ', $fp->name))))) }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $i => $row)
                @php $isFirstOfDate = ($i === 0) || ($rows[$i-1]['date'] !== $row['date']); @endphp
                <tr>
                    <td class="col-date" style="color:var(--text-muted);font-size:.70rem">{{ $row['date'] }}</td>
                    <td class="col-dr"   style="font-weight:700;color:var(--accent)">{{ $row['dr_number'] }}</td>
                    <td class="col-cust" style="font-weight:600">{{ $row['customer_name'] }}</td>
                    <td class="text-end" style="font-weight:600">&#8369;{{ number_format($row['total_amount'], 0) }}</td>
                    <td class="text-end" style="font-weight:700;color:var(--accent)">
                        @if($isFirstOfDate && ($subTotals[$row['date']] ?? 0) > 0)
                            &#8369;{{ number_format($subTotals[$row['date']], 0) }}
                        @endif
                    </td>
                    <td style="font-size:.70rem">{{ $row['area'] }}</td>
                    <td>
                        @if($row['status']==='paid')
                            <span class="pill pill-paid">PAID</span>
                        @elseif($row['status']==='to_be_collected')
                            <span class="pill pill-collect">COLLECT</span>
                        @else
                            <span class="pill pill-partial">PARTIAL</span>
                        @endif
                    </td>
                    <td style="font-size:.70rem">{{ strtoupper($row['payment_mode'] ?? '') }}</td>
                    <td style="font-size:.70rem;color:var(--text-muted)">{{ $row['gcash_ref'] ?: '—' }}</td>
                    <td style="font-size:.70rem;color:var(--text-secondary);max-width:120px;overflow:hidden;text-overflow:ellipsis">{{ $row['notes'] ?: '' }}</td>
                    @foreach($products as $fp)
                    <td class="qty-cell">
                        @if(!is_null($row['products'][$fp->id]) && $row['products'][$fp->id] > 0)
                            <span class="qty-val">{{ $row['products'][$fp->id] }}</span>
                        @else
                            <span class="qty-null">·</span>
                        @endif
                    </td>
                    @endforeach
                    <td class="text-center" style="font-weight:700">{{ $row['total_items'] ?: '' }}</td>
                </tr>

            @empty
                <tr>
                    <td colspan="{{ 12 + $products->count() }}" class="empty-state">
                        <i class="bi bi-inbox" style="font-size:1.8rem;display:block;opacity:.3;margin-bottom:.5rem"></i>
                        No sales data for this period.
                    </td>
                </tr>
            @endforelse

            @if($rows->count())
            <tr class="row-grand">
                <td class="col-date"></td>
                <td class="col-dr"></td>
                <td class="col-cust" style="text-align:right;font-size:.68rem;text-transform:uppercase;letter-spacing:.4px">Grand Total</td>
                <td class="text-end">&#8369;{{ number_format($grandTotal, 0) }}</td>
                <td></td>
                <td colspan="5"></td>
                @foreach($products as $fp)
                    <td class="text-center">{{ $productTotals[$fp->id] > 0 ? $productTotals[$fp->id] : '' }}</td>
                @endforeach
                <td class="text-center">{{ number_format($grandTotalItems, 0) }}</td>
            </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

{{-- Hidden data for CSV export --}}
<script id="csvProducts" type="application/json">{!! json_encode($products->map(function($p){ return strtoupper(implode('', array_map(fn($w) => $w[0], array_filter(explode(' ', $p->name))))); })->values()) !!}</script>
<script id="csvRows" type="application/json">{!! json_encode($rows->map(function($r) use ($products) {
    $base = [
        $r['date'], $r['dr_number'], $r['customer_name'],
        $r['total_amount'], '', $r['area'],
        $r['status'], $r['payment_mode'],
        $r['gcash_ref'], $r['notes']
    ];
    foreach($products as $fp) {
        $base[] = $r['products'][$fp->id] ?? '';
    }
    $base[] = $r['total_items'];
    return $base;
})->values()) !!}</script>
<script id="csvProductTotals" type="application/json">{!! json_encode(array_values($productTotals)) !!}</script>

<script>
function downloadCSV() {
    var products   = JSON.parse(document.getElementById('csvProducts').textContent);
    var rows       = JSON.parse(document.getElementById('csvRows').textContent);
    var prodTotals = JSON.parse(document.getElementById('csvProductTotals').textContent);

    var fixedHeaders = ['DATE','DR#','CUSTOMER','TOTAL AMOUNT','SUB TOTAL/DAY','AREA','STATUS','PAYMENT','GCASH REF','NOTE'];
    var headers = fixedHeaders.concat(products).concat(['TOTAL#']);

    var lines = [];
    lines.push('DIVINE JM FOODS - SALES REPORT');
    lines.push(document.querySelector('h5').textContent.trim() + ' - ' + document.querySelector('.sum-tile-label + .sum-tile-value').textContent.trim());
    lines.push('');
    lines.push(headers.map(function(h){ return '"' + h + '"'; }).join(','));

    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        lines.push(row.map(function(v){ return '"' + String(v).replace(/"/g,'""') + '"'; }).join(','));


    }

    // grand total
    var grandRow = ['','','GRAND TOTAL',
        rows.reduce(function(s,r){ return s + (parseFloat(r[3])||0); }, 0),
        '','','','','','',''];
    for (var p = 0; p < prodTotals.length; p++) grandRow.push(prodTotals[p] || '');
    grandRow.push(rows.reduce(function(s,r){ return s + (parseFloat(r[r.length-1])||0); }, 0));
    lines.push(grandRow.map(function(v){ return '"' + String(v).replace(/"/g,'""') + '"'; }).join(','));

    var blob = new Blob([lines.join('\n')], { type:'text/csv;charset=utf-8;' });
    var url  = URL.createObjectURL(blob);
    var a    = document.createElement('a');
    a.href     = url;
    a.download = 'sales-report-' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

@endsection