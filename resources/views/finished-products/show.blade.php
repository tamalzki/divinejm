@extends('layouts.sidebar')

@section('page-title', $finishedProduct->name)

@section('content')

<style>
    .show-wrap { max-width: 860px; }

    .show-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        margin-bottom: 1rem;
    }
    .show-card-header {
        padding: .6rem 1rem;
        border-bottom: 1px solid var(--border);
        background: var(--bg-page);
        border-radius: var(--radius) var(--radius) 0 0;
        display: flex; align-items: center; gap: .5rem;
    }
    .show-card-header span {
        font-size: .78rem; font-weight: 700; color: var(--text-secondary);
    }
    .show-card-header i { color: var(--accent); font-size: .85rem; }
    .show-card-body { padding: 1rem; }

    /* Stat tiles */
    .stat-tiles { display: flex; gap: .6rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .stat-tile {
        flex: 1 1 100px;
        background: var(--bg-page);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: .55rem .75rem;
        text-align: center;
    }
    .stat-tile-label { font-size: .65rem; color: var(--text-muted); display: block; text-transform: uppercase; letter-spacing: .5px; }
    .stat-tile-value { font-size: 1.2rem; font-weight: 700; color: var(--text-primary); line-height: 1.3; }

    /* Detail rows */
    .detail-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: .45rem 0;
        border-bottom: 1px solid var(--border);
        font-size: .82rem;
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-key { color: var(--text-muted); }
    .detail-val { font-weight: 600; color: var(--text-primary); }

    .type-tag { display: inline-block; border-radius: 4px; padding: .1rem .45rem; font-size: .69rem; font-weight: 700; }
    .type-mfg { background: var(--s-success-bg); color: var(--s-success-text); }
    .type-con { background: var(--s-info-bg); color: var(--s-info-text); }

    /* Barcode */
    .bc-preview {
        text-align: center; padding: 1rem 1.25rem;
        border: 1px dashed var(--border); border-radius: var(--radius);
        background: #fff; display: block; width: 100%; overflow: hidden; box-sizing: border-box;
    }
    .bc-preview svg { max-width: 100%; height: auto; display: block; margin: 0 auto; }
    .bc-name { font-size: .7rem; font-weight: 700; color: var(--text-secondary); letter-spacing: .4px; text-transform: uppercase; margin-top: .4rem; }

    /* Production action */
    .mix-action {
        text-align: center; padding: 1.25rem 1rem;
    }
    .mix-action p { font-size: .8rem; color: var(--text-muted); margin-bottom: .75rem; }
    .btn-bc-download {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .42rem 1.1rem;
        background: var(--accent); color: #fff;
        border-radius: 6px; font-size: .82rem; font-weight: 700;
        border: none; cursor: pointer;
        text-decoration: none !important; transition: background .15s;
    }
    .btn-bc-download:hover { background: var(--accent-hover); color: #fff; }

    .ongoing-mix-info { padding: .85rem 1rem; }
    .ongoing-mix-info .batch-row { font-size: .8rem; color: var(--text-secondary); margin-bottom: .3rem; }
    .ongoing-mix-info .batch-row strong { color: var(--text-primary); }

    /* History table */
    .hist-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
    .hist-table thead th {
        background: var(--brand-deep); color: rgba(255,255,255,.88);
        font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        padding: .5rem .85rem; white-space: nowrap;
    }
    .hist-table tbody td {
        padding: .45rem .85rem; border-bottom: 1px solid var(--border); vertical-align: middle;
    }
    .hist-table tbody tr:last-child td { border-bottom: none; }
    .hist-table tbody tr:hover td { background: var(--accent-light); }

    .status-pill {
        display: inline-block; border-radius: 999px; padding: .1rem .5rem;
        font-size: .68rem; font-weight: 700;
    }
    .status-completed { background: var(--s-success-bg); color: var(--s-success-text); }
    .status-pending   { background: var(--s-warning-bg); color: var(--s-warning-text); }

    .btn-view-mix {
        display: inline-flex; align-items: center; gap: .2rem;
        padding: .18rem .5rem; border-radius: 5px;
        background: var(--accent); color: #fff;
        font-size: .7rem; font-weight: 600;
        text-decoration: none !important; transition: filter .15s;
    }
    .btn-view-mix:hover { filter: brightness(.88); color: #fff; }

    .back-link { font-size: .78rem; color: var(--text-muted); text-decoration: none; }
    .back-link:hover { color: var(--accent); }

    .btn-adjust-stock {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .28rem .7rem; border-radius: 6px;
        font-size: .75rem; font-weight: 700;
        background: var(--accent); color: #fff;
        border: none; cursor: pointer; text-decoration: none !important;
        transition: filter .15s;
    }
    .btn-adjust-stock:hover { filter: brightness(.88); color: #fff; }

    /* Adjust Modal */
    .dj-modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.45); z-index: 1055;
        align-items: center; justify-content: center;
    }
    .dj-modal-overlay.show { display: flex; }
    .dj-modal-box {
        background: var(--bg-card); border-radius: 10px;
        box-shadow: 0 8px 40px rgba(0,0,0,.18);
        width: 100%; max-width: 380px; padding: 1.5rem;
    }
    .dj-modal-title { font-size: .92rem; font-weight: 700; color: var(--text-primary); margin-bottom: .25rem; }
    .dj-modal-sub   { font-size: .75rem; color: var(--text-muted); margin-bottom: 1.1rem; }
    .adj-current {
        display: flex; align-items: center; justify-content: space-between;
        padding: .55rem .75rem; margin-bottom: 1rem;
        background: var(--bg-page); border: 1px solid var(--border); border-radius: 6px;
        font-size: .8rem; color: var(--text-secondary);
    }
    .adj-current strong { font-size: 1.1rem; color: var(--text-primary); }
    .adj-field-label { font-size: .75rem; font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: .3rem; }
    .adj-input {
        width: 100%; font-size: 1.1rem; font-weight: 700; text-align: center;
        padding: .5rem; border: 2px solid var(--border); border-radius: 6px;
        color: var(--accent); background: var(--bg-card); outline: none;
        transition: border-color .15s;
    }
    .adj-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-faint); }
    .adj-diff {
        margin-top: .5rem; text-align: center; font-size: .78rem; font-weight: 600;
        min-height: 1.2em;
    }
    .adj-actions { display: flex; gap: .5rem; margin-top: 1.1rem; }
    .btn-adj-save {
        flex: 1; padding: .42rem; border: none; border-radius: 6px;
        background: var(--accent); color: #fff; font-size: .82rem; font-weight: 700;
        cursor: pointer; transition: background .15s;
    }
    .btn-adj-save:hover { background: var(--accent-hover); }
    .btn-adj-cancel {
        padding: .42rem .9rem; border: 1px solid var(--border); border-radius: 6px;
        background: var(--bg-page); color: var(--text-secondary);
        font-size: .82rem; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-adj-cancel:hover { background: var(--border); }
</style>

<div class="mb-3">
    <a href="{{ route('finished-products.index') }}" class="back-link">
        <i class="bi bi-arrow-left me-1"></i>Back to Products
    </a>
    <button type="button" class="btn-adjust-stock ms-3" onclick="openAdjustModal()">
        <i class="bi bi-sliders"></i> Adjust Stock
    </button>
    <a href="{{ route('finished-products.edit', $finishedProduct) }}" class="btn-adjust-stock ms-2" style="background:var(--brand-deep)">
        <i class="bi bi-pencil-square"></i> Edit
    </a>
</div>

<div class="show-wrap">

    {{-- Top section: stat tiles + details in full width, then barcode + production side by side --}}

    {{-- Product Details — full width --}}
    <div class="show-card">
        <div class="show-card-header">
            <i class="bi bi-box-seam-fill"></i>
            <span>Product Details</span>
            <span class="ms-auto type-tag {{ $finishedProduct->product_type === 'manufactured' ? 'type-mfg' : 'type-con' }}">
                {{ ucfirst($finishedProduct->product_type) }}
            </span>
        </div>
        <div class="show-card-body">
            <h5 class="fw-bold mb-3" style="font-size:.95rem;color:var(--text-primary)">
                {{ $finishedProduct->name }}
            </h5>

            <div class="stat-tiles">
                <div class="stat-tile">
                    <span class="stat-tile-label">Stock on Hand</span>
                    <span class="stat-tile-value" style="color:var(--s-success-text)">
                        {{ number_format($finishedProduct->stock_on_hand, 0) }}
                    </span>
                </div>
                <div class="stat-tile">
                    <span class="stat-tile-label">Min Stock</span>
                    <span class="stat-tile-value" style="color:var(--s-warning-text)">
                        {{ number_format($finishedProduct->minimum_stock, 0) }}
                    </span>
                </div>
                <div class="stat-tile">
                    <span class="stat-tile-label">Cost Price</span>
                    <span class="stat-tile-value" style="font-size:.95rem">
                        ₱{{ number_format($finishedProduct->cost_price, 2) }}
                    </span>
                </div>
                <div class="stat-tile">
                    <span class="stat-tile-label">Selling Price</span>
                    <span class="stat-tile-value" style="font-size:.95rem;color:var(--accent)">
                        ₱{{ number_format($finishedProduct->selling_price, 2) }}
                    </span>
                </div>
                @if($finishedProduct->barcode)
                <div class="stat-tile" style="flex:2 1 180px;text-align:left">
                    <span class="stat-tile-label">Barcode</span>
                    <code style="font-size:.78rem;background:var(--accent-light);color:var(--accent);padding:.1rem .4rem;border-radius:4px;margin-top:.2rem;display:inline-block">
                        {{ $finishedProduct->barcode }}
                    </code>
                </div>
                @endif
            </div>

            @if($finishedProduct->description)
            <div style="margin-top:.75rem;padding:.6rem .75rem;background:var(--bg-page);border-radius:var(--radius);font-size:.8rem;color:var(--text-secondary)">
                {{ $finishedProduct->description }}
            </div>
            @endif
        </div>
    </div>

    {{-- Barcode + Production side by side --}}
    <div class="row g-3 mb-0">

        {{-- Barcode --}}
        @if($finishedProduct->barcode)
        <div class="col-md-6">
            <div class="show-card">
                <div class="show-card-header">
                    <i class="bi bi-upc-scan"></i>
                    <span>Product Barcode</span>
                </div>
                <div class="show-card-body text-center">
                    <div class="bc-preview mb-3">
                        <svg id="bc-svg"></svg>
                        <div class="bc-name">{{ $finishedProduct->name }}</div>
                    </div>
                    <button type="button" class="btn-bc-download" onclick="bcDownload()" style="background:var(--brand-deep)">
                        <i class="bi bi-download"></i> Download PNG
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Production entry points (Daily Production + Packers) --}}
        <div class="{{ $finishedProduct->barcode ? 'col-md-6' : 'col-md-12' }}">
            @if($finishedProduct->product_type === 'manufactured')
                @php $pendingMix = $finishedProduct->pendingMixes->first(); @endphp
                <div class="show-card">
                    <div class="show-card-header">
                        <i class="bi bi-clipboard2-data"></i>
                        <span>Production</span>
                        <span class="ms-auto status-pill status-completed">Daily + Packers</span>
                    </div>
                    <div class="mix-action">
                        <p style="font-size:.8rem;margin-bottom:.75rem;color:var(--text-secondary)">
                            Use <strong>Daily Production</strong> (raw materials) and <strong>Packers Report</strong> from the sidebar — grids include all products.
                        </p>
                        @if($pendingMix)
                        <div class="mt-3 pt-2 border-top" style="font-size:.74rem;color:var(--text-muted)">
                            <strong class="text-warning">Legacy batch open:</strong> {{ $pendingMix->batch_number }} —
                            <a href="{{ route('production-mixes.show', $pendingMix) }}" style="color:var(--accent)">Open legacy record</a>
                        </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="show-card">
                    <div class="show-card-body text-center" style="padding:1.5rem 1rem">
                        <i class="bi bi-shop" style="font-size:1.5rem;color:var(--accent);opacity:.5;display:block;margin-bottom:.5rem"></i>
                        <p style="font-size:.8rem;color:var(--text-muted);margin:0">Consigned product — no production needed.</p>
                    </div>
                </div>
            @endif
        </div>

    </div>

    {{-- Legacy Production Mix history (read-only archive) --}}
    @if($finishedProduct->product_type === 'manufactured')
    <div class="show-card mt-3">
        <div class="show-card-header">
            <i class="bi bi-clock-history"></i>
            <span>Legacy mix batch history</span>
            @if($finishedProduct->productionMixes?->count() > 0)
            <span class="ms-auto" style="font-size:.7rem;color:var(--text-muted)">
                {{ $finishedProduct->productionMixes->count() }} {{ Str::plural('batch', $finishedProduct->productionMixes->count()) }}
            </span>
            @endif
        </div>
        @if($finishedProduct->productionMixes?->count() > 0)
        <div style="overflow-x:auto">
            <table class="hist-table">
                <thead>
                    <tr>
                        <th>Batch No.</th>
                        <th>Mix Date</th>
                        <th>Expiry</th>
                        <th class="text-center">Expected</th>
                        <th class="text-center">Actual</th>
                        <th class="text-center">Rejects</th>
                        <th class="text-center">Good Output</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($finishedProduct->productionMixes->sortByDesc('created_at') as $mix)
                    @php
                        $rejects    = $mix->rejected_quantity ?? 0;
                        $actual     = $mix->actual_output ?? 0;
                        $goodOutput = $actual - $rejects;
                        $rejectRate = $actual > 0 ? round(($rejects / $actual) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td><strong style="font-size:.75rem;color:var(--accent)">{{ $mix->batch_number }}</strong></td>
                        <td style="font-size:.78rem">{{ $mix->mix_date->format('M d, Y') }}</td>
                        <td style="font-size:.78rem{{ $mix->expiration_date->isPast() ? ';color:var(--s-danger-text);font-weight:700' : '' }}">
                            {{ $mix->expiration_date->format('M d, Y') }}
                            @if($mix->expiration_date->isPast())
                                <span style="font-size:.65rem"> ⚠ Expired</span>
                            @endif
                        </td>
                        <td class="text-center" style="font-weight:700;color:var(--text-secondary)">
                            {{ number_format($mix->expected_output, 0) }}
                        </td>
                        <td class="text-center">
                            @if($mix->actual_output)
                                <span style="font-weight:700;color:var(--s-success-text)">{{ number_format($actual, 0) }}</span>
                            @else
                                <span style="color:var(--text-muted);font-size:.75rem">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($mix->actual_output)
                                <span style="font-weight:600;color:{{ $rejects > 0 ? 'var(--s-danger-text)' : 'var(--text-muted)' }}">
                                    {{ number_format($rejects, 0) }}
                                </span>
                            @else
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($mix->actual_output)
                                <span style="font-weight:700;color:var(--s-success-text)">{{ number_format($goodOutput, 0) }}</span>
                            @else
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="status-pill {{ $mix->status === 'completed' ? 'status-completed' : 'status-pending' }}">
                                {{ ucfirst($mix->status) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('production-mixes.show', $mix) }}" class="btn-view-mix">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div style="text-align:center;padding:2rem 1rem;color:var(--text-muted)">
            <i class="bi bi-inbox" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.35"></i>
            <p style="font-size:.8rem;margin:0">No legacy mix batches. Use
                <a href="{{ route('daily-production.create') }}" style="color:var(--accent)">Daily Production</a>
                and <a href="{{ route('packer-packs.create') }}" style="color:var(--accent)">Packers Report</a>.
            </p>
        </div>
        @endif
    </div>
    @endif

</div>

@if($finishedProduct->barcode)
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
(function() {
    const CODE = @json($finishedProduct->barcode);
    const OPTS = { format:'CODE128', lineColor:'#122e34', background:'#ffffff', width:1.8, height:60, displayValue:true, fontSize:12, font:'monospace', fontOptions:'700', textMargin:4, margin:10 };
    JsBarcode(document.getElementById('bc-svg'), CODE, OPTS);
    window.bcDownload = function() {
        const svg = document.getElementById('bc-svg');
        const blob = new Blob([new XMLSerializer().serializeToString(svg)], { type:'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const img = new Image();
        img.onload = function() {
            const s = 3, c = document.createElement('canvas');
            c.width = img.width * s; c.height = img.height * s;
            const ctx = c.getContext('2d'); ctx.scale(s, s); ctx.drawImage(img, 0, 0);
            URL.revokeObjectURL(url);
            const a = document.createElement('a'); a.href = c.toDataURL('image/png');
            a.download = `barcode-${CODE}.png`; a.click();
        };
        img.src = url;
    };
})();
</script>
@endif


{{-- Inventory Adjustment Modal --}}
<div class="dj-modal-overlay" id="adjustModal">
    <div class="dj-modal-box">
        <div class="dj-modal-title"><i class="bi bi-sliders me-1" style="color:var(--accent)"></i> Adjust Stock</div>
        <div class="dj-modal-sub">Set the exact physical count for <strong>{{ $finishedProduct->name }}</strong>.</div>

        <div class="adj-current">
            <span>Current Stock</span>
            <strong id="adjCurrentVal">{{ number_format($finishedProduct->stock_on_hand, 0) }} units</strong>
        </div>

        <form method="POST" action="{{ route('finished-products.adjust', $finishedProduct) }}" id="adjustForm">
            @csrf
            @method('PATCH')
            <label class="adj-field-label">New Physical Count</label>
            <input type="number" name="new_stock" id="adjInput"
                   class="adj-input" min="0" step="1"
                   value="{{ $finishedProduct->stock_on_hand }}"
                   oninput="updateAdjDiff()" placeholder="Enter actual count">
            <div class="adj-diff" id="adjDiff"></div>
            <div class="adj-actions">
                <button type="submit" class="btn-adj-save"><i class="bi bi-check-lg me-1"></i>Apply</button>
                <button type="button" class="btn-adj-cancel" onclick="closeAdjustModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
var _adjCurrent = {{ $finishedProduct->stock_on_hand }};

function openAdjustModal() {
    document.getElementById('adjInput').value = _adjCurrent;
    updateAdjDiff();
    document.getElementById('adjustModal').classList.add('show');
    setTimeout(function() {
        var inp = document.getElementById('adjInput');
        inp.focus(); inp.select();
    }, 80);
}

function closeAdjustModal() {
    document.getElementById('adjustModal').classList.remove('show');
}

function updateAdjDiff() {
    var newVal = parseInt(document.getElementById('adjInput').value) || 0;
    var diff   = newVal - _adjCurrent;
    var el     = document.getElementById('adjDiff');
    if (diff === 0) {
        el.textContent = 'No change';
        el.style.color = 'var(--text-muted)';
    } else if (diff > 0) {
        el.textContent = '+' + diff + ' units will be added';
        el.style.color = 'var(--s-success-text)';
    } else {
        el.textContent = Math.abs(diff) + ' units will be removed';
        el.style.color = 'var(--s-danger-text)';
    }
}

document.getElementById('adjustModal').addEventListener('click', function(e) {
    if (e.target === this) closeAdjustModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAdjustModal();
});
</script>

@endsection