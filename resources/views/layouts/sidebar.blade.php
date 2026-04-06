<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Divine JM — @yield('page-title', 'Dashboard')</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            /*
             * Divine JM — GitHub/Basecamp style
             * Sidebar : soft warm gray  (#f0f0ed)
             * Content : slightly warm white (#fafaf9)
             * Accent  : slate blue (#3b5bdb) — vivid, readable
             * Status  : saturated enough to distinguish clearly
             */

            /* ── Sidebar ── */
            --sidebar-bg:     #f0f0ed;
            --sidebar-hover:  #e4e4e0;
            --sidebar-active: #dcdcf0;
            --sidebar-border: #d8d8d4;
            --sidebar-text:   #2c2c2c;
            --sidebar-muted:  #7a7a74;
            --sidebar-label:  #a8a8a2;

            /* ── Single accent ── */
            --accent:         #3b5bdb;
            --accent-hover:   #2f4ac2;
            --accent-light:   #eef2ff;
            --accent-faint:   #e8edff;

            /* ── Content neutrals ── */
            --text-primary:   #1c1c1a;
            --text-secondary: #44443e;
            --text-muted:     #8a8a82;
            --border:         #e4e4e0;
            --bg-page:        #f5f5f2;
            --bg-card:        #ffffff;
            --radius:         6px;

            /* ── Layout ── */
            --topbar-h:       52px;
            --sidebar-width:  230px;

            /* ── Status — clearly distinct, not washed out ── */
            --s-success-bg:   #dcfce7; --s-success-text: #15622e;
            --s-danger-bg:    #fee2e2; --s-danger-text:  #9b1c1c;
            --s-warning-bg:   #fef3c7; --s-warning-text: #854d0e;
            --s-info-bg:      #dbeafe; --s-info-text:    #1e40af;

            /* ── Table headers ── */
            --brand-deep:     #2c2c38;

            /* ── Legacy aliases ── */
            --brand:        var(--accent);
            --brand-accent: var(--accent);
            --brand-mid:    var(--accent);
            --brand-active: var(--accent-hover);
            --brand-text:   var(--sidebar-text);
            --brand-muted:  var(--text-muted);
            --brand-label:  var(--text-muted);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.875rem;
            background: var(--bg-page);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ── Sidebar ──────────────────────────────────────────── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow-y: auto;
            overflow-x: hidden;
            border-right: 1px solid var(--sidebar-border);
            scrollbar-width: thin;
            scrollbar-color: var(--border) transparent;
        }
        .sidebar::-webkit-scrollbar { width: 3px; }
        .sidebar::-webkit-scrollbar-thumb { background: var(--border); }

        /* Brand block */
        .sidebar-brand {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid var(--sidebar-border);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }
        .sidebar-brand-icon {
            width: 34px; height: 34px;
            background: var(--accent);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-brand-icon svg {
            width: 20px; height: 20px;
            fill: #fff;
        }
        .sidebar-brand-text {}
        .sidebar-brand-name {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.2px;
            line-height: 1.2;
            display: block;
        }
        .sidebar-brand-sub {
            font-size: 0.6rem;
            font-weight: 500;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--sidebar-muted);
            display: block;
            margin-top: 1px;
        }

        /* Nav */
        .sidebar-nav { padding: 0.6rem 0 1rem; flex: 1; }

        .sidebar-section {
            padding: 0.85rem 1.1rem 0.25rem;
            font-size: 0.58rem;
            font-weight: 700;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: var(--sidebar-label);
            letter-spacing: 1.2px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.42rem 1.1rem;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 0.815rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: background 0.12s, color 0.12s, border-color 0.12s;
        }
        .sidebar-link i {
            font-size: 0.9rem;
            width: 15px;
            text-align: center;
            opacity: 0.65;
            flex-shrink: 0;
        }
        .sidebar-link:hover {
            background: var(--sidebar-hover);
            color: var(--accent);
            border-left-color: var(--accent);
        }
        .sidebar-link:hover i { opacity: 1; color: var(--accent); }
        .sidebar-link.active {
            background: var(--accent-light);
            color: var(--accent);
            font-weight: 700;
            border-left-color: var(--accent);
        }
        .sidebar-link.active i { opacity: 1; color: var(--accent); }

        /* Footer */
        .sidebar-footer {
            padding: 0.8rem 1.1rem;
            border-top: 1px solid var(--sidebar-border);
            background: var(--sidebar-bg);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-shrink: 0;
        }
        .sidebar-avatar {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.68rem; font-weight: 700;
            flex-shrink: 0;
            letter-spacing: 0;
        }
        .sidebar-user-name {
            font-size: 0.77rem;
            font-weight: 600;
            color: var(--sidebar-text);
            line-height: 1.2;
            display: block;
        }
        .sidebar-user-role {
            font-size: 0.64rem;
            color: var(--sidebar-muted);
            display: block;
        }
        .sidebar-logout-btn {
            margin-left: auto;
            background: none;
            border: none;
            color: var(--sidebar-muted);
            font-size: 0.95rem;
            cursor: pointer;
            padding: 0.2rem;
            transition: color 0.15s;
        }
        .sidebar-logout-btn:hover { color: #ff7b7b; }

        /* ── Top Bar ──────────────────────────────────────────── */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-h);
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 0 var(--border);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            z-index: 99;
            gap: 0.6rem;
        }
        .topbar-brand {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.2px;
        }
        .topbar-sep {
            width: 1px; height: 18px;
            background: var(--border);
            flex-shrink: 0;
        }
        .topbar-title {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .topbar-date {
            font-size: 0.73rem;
            color: var(--text-muted);
        }
        .topbar-user {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .topbar-badge {
            background: var(--accent);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.12rem 0.45rem;
            border-radius: 4px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        /* ── Main ─────────────────────────────────────────────── */
        .main-wrap {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-h);
            min-height: 100vh;
        }
        .main-inner {
            padding: 1.4rem 1.5rem;
        }

        /* ── Global overrides ─────────────────────────────────── */

        .card {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .card-header {
            background: var(--bg-page);
            border-bottom: 1px solid var(--border);
            font-size: 0.81rem;
            font-weight: 600;
            color: var(--text-primary);
            padding: 0.55rem 1rem;
        }

        /* Buttons */
        .btn { font-size: 0.8rem; font-weight: 500; border-radius: 6px; }
        .btn-primary { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; }
        .btn-primary:hover { background: var(--accent-hover) !important; border-color: var(--accent-hover) !important; }
        .btn-outline-primary { color: var(--accent) !important; border-color: var(--accent) !important; }
        .btn-outline-primary:hover { background: var(--accent) !important; color: #fff !important; }
        .btn-secondary { background: #eeeeed !important; border-color: #ddddd8 !important; color: var(--text-secondary) !important; }
        .btn-secondary:hover { background: #e4e4e0 !important; }

        /* Tables */
        .table { font-size: 0.82rem; }
        .table thead th {
            background: var(--brand-deep);
            color: rgba(255,255,255,0.9);
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border: none;
            padding: 0.55rem 0.85rem;
            white-space: nowrap;
        }
        .table tbody td {
            vertical-align: middle;
            border-color: var(--border);
            padding: 0.48rem 0.85rem;
            color: var(--text-primary);
        }
        .table-hover tbody tr:hover td { background: #f0f0ec; }
        .table-sm td, .table-sm th { padding: 0.35rem 0.75rem !important; }

        /* Alerts */
        .alert { border-radius: var(--radius); font-size: 0.81rem; border-width: 1px; }
        .alert-success { background: var(--s-success-bg); border-color: #b8ddc4; color: var(--s-success-text); }
        .alert-danger  { background: var(--s-danger-bg);  border-color: #f5c0c0; color: var(--s-danger-text); }
        .alert-warning { background: var(--s-warning-bg); border-color: #f5dcb0; color: var(--s-warning-text); }
        .alert-info    { background: var(--s-info-bg);    border-color: #b0d4dc; color: var(--s-info-text); }
        .alert-light   { background: #fafafa; border-color: var(--border); color: var(--text-secondary); }

        /* Badges */
        .badge { font-weight: 600; letter-spacing: 0.2px; }
        .badge.bg-success { background: var(--s-success-bg) !important; color: var(--s-success-text) !important; }
        .badge.bg-danger  { background: var(--s-danger-bg)  !important; color: var(--s-danger-text)  !important; }
        .badge.bg-warning { background: var(--s-warning-bg) !important; color: var(--s-warning-text) !important; }
        .badge.bg-primary { background: var(--s-info-bg)    !important; color: var(--s-info-text)    !important; }
        .badge.bg-info    { background: var(--s-info-bg) !important; color: var(--s-info-text) !important; }
        .badge.text-dark  { color: inherit !important; }

        /* Forms */
        .form-control, .form-select {
            border-color: var(--border);
            font-size: 0.81rem;
            border-radius: 6px;
            color: var(--text-primary);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,91,219,.15);
        }
        .form-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.3rem;
        }
        .form-text { font-size: 0.72rem; }
        .invalid-feedback { font-size: 0.72rem; }

        /* Pagination */
        .page-link { color: var(--accent); border-color: var(--border); font-size: 0.78rem; background: var(--bg-card); }
        .page-item.active .page-link { background: var(--accent); border-color: var(--accent); }

        /* Code */
        code {
            background: #eef2ff;
            color: var(--accent);
            padding: 0.1rem 0.35rem;
            border-radius: 4px;
            font-size: 0.78rem;
        }

        /* ── Simplified semantic colors — used in tables/data ─── */

        /* Status text — muted, not loud */
        .text-success { color: #15622e !important; }
        .text-danger  { color: #9b1c1c !important; }
        .text-warning { color: #854d0e !important; }
        .text-primary { color: var(--accent) !important; }
        .text-info    { color: var(--s-info-text) !important; }
        .text-muted   { color: #6b7280 !important; }

        /* Table row tints — very subtle */
        .table-success td, tr.table-success td { background: #dcfce7 !important; }
        .table-danger  td, tr.table-danger  td { background: #fee2e2 !important; }
        .table-warning td, tr.table-warning td { background: #fef3c7 !important; }

        /* Row danger/warning used in finished products */
        .row-danger  td { background: #fdf4f4 !important; }
        .row-warning td { background: #fdf8f0 !important; }
        .row-danger:hover  td { background: #fecaca !important; }
        .row-warning:hover td { background: #fde68a !important; }

        /* Simplify badge text colors that use text-dark override */
        .badge.bg-warning.text-dark { color: #7a4a00 !important; }

        /* Reject rate / expiry badges — keep readable but muted */
        .badge.bg-danger  { font-size: 0.7rem !important; }
        .badge.bg-warning { font-size: 0.7rem !important; }
        .badge.bg-success { font-size: 0.7rem !important; }

        /* Invalid/valid form feedback */
        .is-invalid { border-color: #c08888 !important; }
        .is-invalid:focus { box-shadow: 0 0 0 3px rgba(192,136,136,.12) !important; }
        .invalid-feedback { color: var(--s-danger-text); }

        /* Legacy per-page alerts: keep readable if any view still uses alert-bar */
        .alert-bar.success {
            background: #ecfdf5 !important;
            color: #065f46 !important;
            border: 1px solid #6ee7b7 !important;
            border-left: 4px solid #059669 !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06) !important;
        }
        .alert-bar.danger {
            background: #fef2f2 !important;
            color: #7f1d1d !important;
            border: 1px solid #fca5a5 !important;
            border-left: 4px solid #dc2626 !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06) !important;
        }
    </style>
</head>
<body>

{{-- Sidebar --}}
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            {{-- Cloud icon matching the logo --}}
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/>
            </svg>
        </div>
        <div class="sidebar-brand-text">
            <span class="sidebar-brand-name">Divine JM</span>
            <span class="sidebar-brand-sub">Foods · Est. 1980</span>
        </div>
    </div>

    <nav class="sidebar-nav">

        <div class="sidebar-section">Main</div>
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="sidebar-section">Master Data</div>
        <a href="{{ route('raw-materials.index') }}" class="sidebar-link {{ request()->routeIs('raw-materials.*') ? 'active' : '' }}">
            <i class="bi bi-layers"></i> Raw Materials
        </a>
        <a href="{{ route('finished-products.index') }}" class="sidebar-link {{ request()->routeIs('finished-products.*') ? 'active' : '' }}">
            <i class="bi bi-basket2"></i> Finished Products
        </a>
        <a href="{{ route('branches.index') }}" class="sidebar-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
            <i class="bi bi-geo-alt"></i> Area & Customer
        </a>

        <div class="sidebar-section">Production</div>
        <a href="{{ route('production-mixes.index') }}" class="sidebar-link {{ request()->routeIs('production-mixes.*') ? 'active' : '' }}">
            <i class="bi bi-gear-wide-connected"></i> Production Mix
        </a>

        <div class="sidebar-section">Distribution</div>
        <a href="{{ route('branch-inventory.index') }}" class="sidebar-link {{ request()->routeIs('branch-inventory.*') ? 'active' : '' }}">
            <i class="bi bi-send"></i> Deliver Products
        </a>
        <a href="{{ route('sales.index') }}" class="sidebar-link {{ request()->routeIs('sales.*') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i> Sales
        </a>

        <div class="sidebar-section">Inventory</div>
        
        

        <div class="sidebar-section">Finance</div>
        <a href="{{ route('ar.index') }}" class="sidebar-link {{ request()->routeIs('ar.*') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i> Receivables
        </a>
        <a href="{{ route('expenses.index') }}" class="sidebar-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
            <i class="bi bi-wallet2"></i> Expenses
        </a>
        <a href="{{ route('bank-deposits.index') }}" class="sidebar-link {{ request()->routeIs('bank-deposits.*') ? 'active' : '' }}">
            <i class="bi bi-bank"></i> Bank Accounts
        </a>

        <div class="sidebar-section">Reports</div>
        <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.*') || request()->routeIs('financial-reports.*') ? 'active' : '' }}">
            <i class="bi bi-graph-up-arrow"></i> Reports
        </a>

    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-avatar">
            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
        </div>
        <div>
            <span class="sidebar-user-name">{{ Auth::user()->name ?? 'User' }}</span>
            <span class="sidebar-user-role">{{ ucfirst(Auth::user()->role ?? 'Administrator') }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-logout-btn" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>
</aside>

{{-- Top Bar --}}
<header class="topbar">
    <span class="topbar-brand">Divine JM</span>
    <div class="topbar-sep"></div>
    <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
    <div class="topbar-right">
        <span class="topbar-date">{{ now()->format('l, F d, Y') }}</span>
        <div class="topbar-user">
            <i class="bi bi-person-circle" style="font-size:1rem;opacity:0.5"></i>
            {{ Auth::user()->name ?? 'User' }}
            <span class="topbar-badge">{{ Auth::user()->role ?? 'Admin' }}</span>
        </div>
    </div>
</header>

{{-- Content --}}
<main class="main-wrap">
    <div class="main-inner">
        @include('partials.flash')
        @yield('content')
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.dj-flash-dismiss').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = this.closest('.dj-flash');
                if (row) {
                    row.remove();
                }
            });
        });
    });
</script>
</body>
</html>