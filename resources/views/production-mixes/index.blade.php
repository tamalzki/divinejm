@extends('layouts.sidebar')
@section('page-title', 'Production Batches')
@section('content')

<style>
    .pm-toolbar { display: flex; align-items: center; gap: .5rem; margin-bottom: .6rem; flex-wrap: wrap; }

    .pm-search-wrap { position: relative; display: flex; align-items: center; }
    .pm-search-icon { position: absolute; left: .6rem; color: var(--text-muted); font-size: .78rem; pointer-events: none; }
    .pm-search-input {
        height: 30px; padding: 0 2rem 0 1.85rem;
        border: 1px solid var(--border); border-radius: 6px;
        font-size: .78rem; color: var(--text-primary); background: var(--bg-card);
        width: 220px; outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .pm-search-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(30,77,123,.12); }
    .pm-search-input::placeholder { color: var(--text-muted); }
    .pm-search-clear { position: absolute; right: .5rem; color: var(--text-muted); font-size: .7rem; text-decoration: none !important; }
    .pm-search-clear:hover { color: var(--s-danger-text); }

    .filter-pill {
        display: inline-flex; align-items: center; gap: .25rem;
        padding: .18rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 600;
        text-decoration: none !important; border: 1.5px solid var(--border);
        color: var(--text-secondary); background: var(--bg-card); transition: all .15s;
    }
    .filter-pill:hover, .filter-pill.active {
        border-color: var(--accent); color: var(--accent);
        background: var(--accent-light); box-shadow: 0 0 0 1.5px var(--accent) inset;
    }
    .toolbar-label { font-size: .68rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; }
    .toolbar-div { color: var(--border); }

    .pm-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: 0 1px 4px rgba(0,0,0,.04); overflow: hidden; }

    .pm-table { width: 100%; border-collapse: collapse; font-size: .79rem; }
    .pm-table thead th {
        background: var(--brand-deep); color: rgba(255,255,255,.88);
        font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        padding: .55rem .8rem; white-space: nowrap; border: none;
    }
    .pm-table tbody td {
        padding: .45rem .8rem; border-bottom: 1px solid var(--border); vertical-align: middle;
    }
    .pm-table tbody tr:last-child td { border-bottom: none; }
    .pm-table tbody tr:hover td { background: var(--accent-light); }

    .batch-code { font-size: .72rem; background: var(--accent-light); color: var(--accent); padding: .1rem .4rem; border-radius: 4px; font-family: monospace; font-weight: 700; }

    .expiry-pill { display: inline-flex; align-items: center; gap: .25rem; border-radius: 4px; padding: .1rem .45rem; font-size: .69rem; font-weight: 700; }
    .expiry-ok      { background: var(--s-success-bg); color: var(--s-success-text); }
    .expiry-soon    { background: var(--s-warning-bg); color: var(--s-warning-text); }
    .expiry-expired { background: var(--s-danger-bg);  color: var(--s-danger-text); }

    .rate-pill { display: inline-block; border-radius: 4px; padding: .1rem .42rem; font-size: .69rem; font-weight: 700; }
    .rate-ok   { background: var(--s-success-bg); color: var(--s-success-text); }
    .rate-mid  { background: var(--s-warning-bg); color: var(--s-warning-text); }
    .rate-bad  { background: var(--s-danger-bg);  color: var(--s-danger-text); }

    .btn-update {
        display: inline-flex; align-items: center; gap: .2rem;
        padding: .2rem .5rem; border-radius: 5px;
        background: var(--s-warning-bg); color: var(--s-warning-text);
        font-size: .7rem; font-weight: 600; border: 1px solid #fde68a;
        cursor: pointer; transition: background .15s; white-space:nowrap;
    }
    .btn-update:hover { background: #fef08a; }

    /* Inline edit row */

    .btn-view {
        display: inline-flex; align-items: center; gap: .2rem;
        padding: .2rem .5rem; border-radius: 5px;
        background: var(--accent); color: #fff;
        font-size: .7rem; font-weight: 600; border: none;
        text-decoration: none !important; transition: filter .15s;
    }
    .btn-view:hover { filter: brightness(.88); color: #fff; }

    .btn-del {
        display: inline-flex; align-items: center; gap: .2rem;
        padding: .2rem .38rem; border-radius: 5px;
        background: var(--s-danger-bg); color: var(--s-danger-text);
        font-size: .7rem; font-weight: 600; border: 1px solid #fca5a5;
        cursor: pointer; transition: background .15s;
    }
    .btn-del:hover { background: #fecaca; }

    /* Delete confirm modal */
    .dj-modal-overlay {
        display: none; position: fixed; inset: 0; z-index: 9998;
        background: rgba(0,0,0,.45); align-items: center; justify-content: center;
    }
    .dj-modal-overlay.show { display: flex; }
    .dj-modal {
        background: var(--bg-card); border-radius: 10px; padding: 1.5rem 1.75rem;
        width: 420px; max-width: 95vw; box-shadow: 0 8px 32px rgba(0,0,0,.22);
        animation: modalIn .15s ease;
    }
    @keyframes modalIn { from { transform: scale(.96); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .dj-modal-title { font-size: .9rem; font-weight: 700; color: var(--text-primary); margin-bottom: .35rem; }
    .dj-modal-body  { font-size: .78rem; color: var(--text-secondary); margin-bottom: 1.1rem; line-height: 1.55; }
    .dj-modal-body strong { color: var(--s-danger-text); }
    .dj-modal-actions { display: flex; gap: .5rem; justify-content: flex-end; }
    .btn-modal-cancel {
        padding: .35rem .85rem; border-radius: 5px; font-size: .78rem; font-weight: 600;
        border: 1px solid var(--border); background: var(--bg-page); color: var(--text-secondary); cursor: pointer;
    }
    .btn-modal-cancel:hover { background: var(--border); }
    .btn-modal-confirm.accent { background: var(--accent) !important; }
    .btn-modal-confirm {
        padding: .35rem .85rem; border-radius: 5px; font-size: .78rem; font-weight: 700;
        border: none; background: #dc2626; color: #fff; cursor: pointer;
        display: inline-flex; align-items: center; gap: .3rem;
    }
    .btn-modal-confirm:hover { background: #b91c1c; }

    .btn-new-batch {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .38rem .85rem; background: var(--accent); color: #fff;
        border-radius: 6px; font-size: .78rem; font-weight: 700;
        text-decoration: none !important; transition: background .15s;
    }
    .btn-new-batch:hover { background: var(--accent-hover); color: #fff; }

    .pm-footer {
        display: flex; align-items: center; justify-content: space-between;
        padding: .55rem .9rem; border-top: 1px solid var(--border);
        background: var(--bg-page); flex-wrap: wrap; gap: .4rem;
    }
    .pm-footer .page-info { font-size: .72rem; color: var(--text-muted); }

    .empty-state { text-align: center; padding: 2.5rem 1rem; color: var(--text-muted); }
    .empty-state i { font-size: 2rem; display: block; margin-bottom: .5rem; opacity: .35; }
    .empty-state p { font-size: .8rem; margin: 0; }
</style>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.95rem">
            <i class="bi bi-gear-wide-connected me-2" style="color:var(--accent)"></i>Production Batches
        </h5>
        <p class="mb-0" style="font-size:.72rem;color:var(--text-muted)">Track and manage all production runs</p>
    </div>
    <a href="{{ route('production-mixes.create') }}" class="btn-new-batch">
        <i class="bi bi-plus-lg"></i> New Batch
    </a>
</div>

{{-- Toolbar --}}
<div class="pm-toolbar">
    <form method="GET" action="{{ route('production-mixes.index') }}" id="searchForm">
        <input type="hidden" name="filter" value="{{ request('filter') }}">
        <div class="pm-search-wrap">
            <i class="bi bi-search pm-search-icon"></i>
            <input type="text" name="search" id="searchInput" class="pm-search-input"
                   placeholder="Search batch, product…" value="{{ request('search') }}" autocomplete="off">
            @if(request('search'))
            <a href="{{ route('production-mixes.index', array_filter(['filter' => request('filter')])) }}" class="pm-search-clear">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </div>
    </form>

    <span class="toolbar-div">|</span>
    <span class="toolbar-label">Filter:</span>

    <a href="{{ route('production-mixes.index', array_filter(['search' => request('search')])) }}"
       class="filter-pill {{ !request()->filled('filter') ? 'active' : '' }}">All</a>
    <a href="{{ route('production-mixes.index', array_filter(['filter' => 'expiring', 'search' => request('search')])) }}"
       class="filter-pill {{ request('filter') === 'expiring' ? 'active' : '' }}">
        <i class="bi bi-exclamation-triangle" style="font-size:.6rem"></i> Expiring Soon
    </a>
    <a href="{{ route('production-mixes.index', array_filter(['filter' => 'expired', 'search' => request('search')])) }}"
       class="filter-pill {{ request('filter') === 'expired' ? 'active' : '' }}">
        <i class="bi bi-x-circle" style="font-size:.6rem"></i> Expired
    </a>
    <a href="{{ route('production-mixes.index', array_filter(['filter' => 'high_reject', 'search' => request('search')])) }}"
       class="filter-pill {{ request('filter') === 'high_reject' ? 'active' : '' }}">
        <i class="bi bi-graph-down" style="font-size:.6rem"></i> High Rejects
    </a>
</div>

@if(request('search'))
<div class="mb-2" style="font-size:.73rem;color:var(--text-muted)">
    <i class="bi bi-search me-1"></i>
    Results for <strong>"{{ request('search') }}"</strong> — {{ $mixes->total() }} found
    <a href="{{ route('production-mixes.index', array_filter(['filter' => request('filter')])) }}"
       style="color:var(--text-muted);margin-left:.5rem"><i class="bi bi-x"></i> Clear</a>
</div>
@endif

{{-- Table --}}
<div class="pm-card">
    <div style="overflow-x:auto">
        <table class="pm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Mix Date</th>
                    <th class="text-end">Standard</th>
                    <th class="text-end">Actual</th>
                    <th class="text-end">Rejects</th>
                    <th class="text-center">Reject Rate</th>
                    <th class="text-end">Cost/Unit</th>
                    <th>Expiry</th>
                    <th class="text-center" style="min-width:260px">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($mixes as $mix)
                @php
                    $rate = round($mix->rejection_rate ?? 0, 2);
                    $expClass = '';
                    $expLabel = '—';
                    if ($mix->expiration_date) {
                        if ($mix->expiration_date->isPast()) {
                            $expClass = 'expiry-expired'; $expLabel = 'Expired ' . $mix->expiration_date->format('M d, Y');
                        } elseif ($mix->expiration_date->diffInDays(now()) <= 7) {
                            $expClass = 'expiry-soon'; $expLabel = $mix->expiration_date->format('M d, Y');
                        } else {
                            $expClass = 'expiry-ok'; $expLabel = $mix->expiration_date->format('M d, Y');
                        }
                    }
                @endphp
                <tr>
                    <td style="color:var(--text-muted);font-size:.72rem">{{ $mix->id }}</td>
                    <td style="font-weight:600">{{ $mix->finishedProduct->name }}</td>
                    <td style="font-size:.78rem">{{ $mix->mix_date->format('M d, Y') }}</td>
                    <td class="text-end" style="color:var(--text-muted)">{{ number_format($mix->expected_output, 0) }}</td>
                    <td class="text-end" style="font-weight:700;color:var(--s-success-text)">{{ number_format($mix->actual_output, 0) }}</td>
                    <td class="text-end" style="font-weight:600;color:{{ $mix->rejected_quantity > 0 ? 'var(--s-danger-text)' : 'var(--text-muted)' }}">
                        {{ number_format($mix->rejected_quantity, 0) }}
                    </td>
                    <td class="text-center">
                        <span class="rate-pill {{ $rate == 0 ? 'rate-ok' : ($rate <= 5 ? 'rate-mid' : 'rate-bad') }}">
                            {{ $rate }}%
                        </span>
                    </td>
                    <td class="text-end" style="font-size:.78rem;font-variant-numeric:tabular-nums">
                        ₱{{ number_format($mix->cost_per_unit, 4) }}
                    </td>
                    <td>
                        @if($expClass)
                            <span class="expiry-pill {{ $expClass }}">
                                @if($expClass === 'expiry-expired')<i class="bi bi-x-circle"></i>
                                @elseif($expClass === 'expiry-soon')<i class="bi bi-exclamation-triangle"></i>
                                @else<i class="bi bi-check-circle"></i>@endif
                                {{ $expLabel }}
                            </span>
                        @else
                            <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td class="text-center" style="white-space:nowrap">
                        <a href="{{ route('production-mixes.show', $mix) }}" class="btn-view">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <button type="button" class="btn-update ms-1"
                            onclick="openUpdateModal({{ $mix->id }}, '{{ addslashes($mix->finishedProduct->name) }}', {{ $mix->actual_output }}, {{ $mix->rejected_quantity }}, '{{ $mix->expiration_date ? $mix->expiration_date->format('Y-m-d') : '' }}', '{{ addslashes($mix->notes ?? '') }}')">
                            <i class="bi bi-pencil-square"></i> Update Actual
                        </button>
                        <button type="button" class="btn-del ms-1"
                            onclick="openDeleteModal({{ $mix->id }}, '{{ addslashes($mix->finishedProduct->name) }}', {{ $mix->actual_output }})">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">
                        <div class="empty-state">
                            <i class="bi bi-{{ request('search') ? 'search' : 'inbox' }}"></i>
                            <p>
                                @if(request('search'))
                                    No batches match <strong>"{{ request('search') }}"</strong>.
                                    <a href="{{ route('production-mixes.index') }}" style="color:var(--accent)">Clear</a>
                                @else
                                    No production batches yet.
                                    <a href="{{ route('production-mixes.create') }}" style="color:var(--accent)">Create one now</a>
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($mixes->total() > 0)
    <div class="pm-footer">
        <span class="page-info">
            Showing {{ $mixes->firstItem() }}–{{ $mixes->lastItem() }} of {{ $mixes->total() }} batches
            @if(request('search'))<span style="color:var(--accent)"> · filtered</span>@endif
        </span>
        {{ $mixes->links() }}
    </div>
    @endif
</div>


<script>
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    let t;
    searchInput.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => document.getElementById('searchForm').submit(), 400);
    });
}

</script>

{{-- Delete Confirm Modal --}}
<div class="dj-modal-overlay" id="deleteModal">
    <div class="dj-modal">
        <div class="dj-modal-title">
            <i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;margin-right:.35rem"></i>
            Delete Production Batch?
        </div>
        <div class="dj-modal-body" id="deleteModalBody"></div>
        <div class="dj-modal-actions">
            <button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <form id="deleteForm" method="POST" style="margin:0">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-modal-confirm">
                    <i class="bi bi-trash3"></i> Yes, Delete & Revert
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openDeleteModal(id, productName, actualOutput) {
    var body = document.getElementById('deleteModalBody');
    body.innerHTML =
        'You are about to delete the production batch for <strong>' + productName + '</strong>.<br><br>' +
        'This will:<br>' +
        '<ul style="margin:.4rem 0 0 1rem;padding:0;font-size:.76rem">' +
        '<li>Remove <strong>' + actualOutput + ' units</strong> from warehouse stock</li>' +
        '<li>Restore all raw materials that were consumed</li>' +
        '</ul>' +
        '<br><strong>This cannot be undone.</strong>';

    document.getElementById('deleteForm').action = '/production-mixes/' + id;
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
}

// Close on overlay click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>


{{-- ── Update Actual Modal ─────────────────────────────────────────── --}}
<div class="dj-modal-overlay" id="updateModal">
    <div class="dj-modal" style="max-width:420px">
        <div class="dj-modal-title">
            <i class="bi bi-pencil-square" style="color:var(--accent);margin-right:.35rem"></i>
            Update Actual Output
        </div>
        <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:.9rem" id="updateModalSub"></div>

        <form method="POST" id="updateActualForm">
            @csrf
            @method('PATCH')

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem;margin-bottom:.65rem">
                <div>
                    <label style="font-size:.70rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:.2rem">
                        Actual Output <span style="color:var(--s-danger-text)">*</span>
                    </label>
                    <input type="number" name="actual_output" id="modalActualInput"
                        step="0.01" min="0.01" required
                        style="width:100%;font-size:.82rem;font-weight:700;border:1px solid var(--border);border-radius:5px;padding:.35rem .6rem;color:var(--text-primary);background:var(--bg-card)">
                    <div style="font-size:.63rem;color:var(--accent);margin-top:.15rem">↑ Goes to warehouse stock</div>
                </div>
                <div>
                    <label style="font-size:.70rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:.2rem">Rejects</label>
                    <input type="number" name="rejected_quantity" id="modalRejectsInput"
                        step="0.01" min="0" value="0"
                        style="width:100%;font-size:.82rem;border:1px solid var(--border);border-radius:5px;padding:.35rem .6rem;color:var(--text-primary);background:var(--bg-card)">
                    <div style="font-size:.63rem;color:var(--s-danger-text);margin-top:.15rem">Documentation only</div>
                </div>
            </div>

            <div style="margin-bottom:.65rem">
                <label style="font-size:.70rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:.2rem">Expiry Date</label>
                <input type="date" name="expiration_date" id="modalExpiryInput"
                    style="width:100%;font-size:.8rem;border:1px solid var(--border);border-radius:5px;padding:.32rem .6rem;color:var(--text-primary);background:var(--bg-card)">
            </div>

            <div style="margin-bottom:.9rem">
                <label style="font-size:.70rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:.2rem">Notes</label>
                <input type="text" name="notes" id="modalNotesInput"
                    placeholder="Optional…"
                    style="width:100%;font-size:.8rem;border:1px solid var(--border);border-radius:5px;padding:.32rem .6rem;color:var(--text-primary);background:var(--bg-card)">
            </div>

            <div class="dj-modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeUpdateModal()">Cancel</button>
                <button type="submit" class="btn-modal-confirm" style="background:var(--accent)" id="updateModalSubmit">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openUpdateModal(mixId, productName, currentActual, currentRejects, currentExpiry, currentNotes) {
    // Set form action dynamically
    var base = '{{ url('production-mixes') }}/' + mixId + '/actual-output';
    document.getElementById('updateActualForm').action = base;

    // Populate fields
    document.getElementById('modalActualInput').value  = currentActual;
    document.getElementById('modalRejectsInput').value = currentRejects;
    document.getElementById('modalExpiryInput').value  = currentExpiry;
    document.getElementById('modalNotesInput').value   = currentNotes;

    // Subtitle
    document.getElementById('updateModalSub').innerHTML =
        '<strong>' + productName + '</strong> &nbsp;·&nbsp; Current actual: <strong>' + currentActual + '</strong>';

    // Show modal
    document.getElementById('updateModal').classList.add('show');

    // Focus actual input
    setTimeout(function() {
        var inp = document.getElementById('modalActualInput');
        inp.focus(); inp.select();
    }, 80);
}

function closeUpdateModal() {
    document.getElementById('updateModal').classList.remove('show');
}

// Close on overlay click
document.getElementById('updateModal').addEventListener('click', function(e) {
    if (e.target === this) closeUpdateModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeUpdateModal();
});
</script>

@endsection