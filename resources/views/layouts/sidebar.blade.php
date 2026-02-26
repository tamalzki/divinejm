<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Divine JM Foods') }}</title>
    
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-blue: #3e7487;
            --dark-blue: #2f5966;
            --light-blue: #5a95ab;
            --primary-pink: #F08080;
            --dark-pink: #D66D6D;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--primary-blue);
            padding: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.15);
            z-index: 1000;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 2rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            flex-shrink: 0;
        }

        .sidebar-brand {
            color: white;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-direction: column;
        }

        .sidebar-brand .brand-cloud {
            width: 50px;
            height: 32px;
            background: var(--primary-pink);
            border-radius: 20px;
            position: relative;
        }

        .sidebar-brand .brand-cloud::before {
            content: '';
            position: absolute;
            width: 28px;
            height: 28px;
            background: var(--primary-pink);
            border-radius: 50%;
            top: -12px;
            left: 10px;
        }

        .sidebar-brand .brand-cloud::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: var(--primary-pink);
            border-radius: 50%;
            top: -8px;
            right: 8px;
        }

        .sidebar-brand .brand-name {
            font-size: 1.6rem;
            letter-spacing: 1px;
            margin-top: 0.5rem;
            color: white;
        }

        .sidebar-brand .brand-tagline {
            font-size: 0.8rem;
            color: white;
            font-weight: 500;
            letter-spacing: 2px;
        }

        .sidebar-menu {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }

        .menu-section-title {
            color: white;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 1rem 1.25rem 0.5rem;
            font-weight: 700;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.25rem;
            color: white;
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            gap: 0.875rem;
            font-weight: 500;
        }

        .sidebar-link i {
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-link:hover {
            background: var(--light-blue);
            color: white;
            border-left-color: var(--primary-pink);
        }

        .sidebar-link.active {
            background: var(--light-blue);
            color: white;
            border-left-color: var(--primary-pink);
            font-weight: 700;
        }

        .sidebar-footer {
            flex-shrink: 0;
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.2);
            background: var(--dark-blue);
            margin-top: auto;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-pink);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 0.75rem;
        }

        .logout-btn {
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            transition: color 0.2s;
            flex-shrink: 0;
        }

        .logout-btn:hover {
            color: var(--primary-pink);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .topbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-area {
            padding: 2rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
        }

        .card-header.bg-primary {
            background: var(--primary-blue) !important;
            color: white;
        }

        .card-header.bg-success {
            background: var(--primary-pink) !important;
            color: white;
        }

        .card-header.bg-info {
            background: var(--light-blue) !important;
            color: white;
        }

        .card-header.bg-warning {
            background: var(--dark-pink) !important;
            color: white;
        }

        .btn-primary {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            color: white;
        }

        .btn-primary:hover {
            background: var(--dark-blue);
            border-color: var(--dark-blue);
            color: white;
        }

        .btn-success {
            background: var(--primary-pink);
            border-color: var(--primary-pink);
            color: white;
        }

        .btn-success:hover {
            background: var(--dark-pink);
            border-color: var(--dark-pink);
            color: white;
        }

        .btn-info {
            background: var(--light-blue);
            border-color: var(--light-blue);
            color: white;
        }

        .btn-info:hover {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
        }

        .btn-outline-primary {
            color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .btn-outline-primary:hover {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
        }

        .badge {
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
        }

        .badge.bg-primary {
            background-color: var(--primary-blue) !important;
        }

        .badge.bg-success {
            background-color: var(--primary-pink) !important;
        }

        .badge.bg-info {
            background-color: var(--light-blue) !important;
            color: white;
        }

        .stats-card {
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
        }

        .stats-card.blue {
            background: var(--primary-blue);
        }

        .stats-card.pink {
            background: var(--primary-pink);
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-card p {
            margin: 0;
            font-size: 0.95rem;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.25rem;
        }

        .alert-success {
            background-color: #FFE5E5;
            color: var(--dark-pink);
        }

        .alert-info {
            background-color: #E1EEF2;
            color: var(--dark-blue);
        }

        .alert-primary {
            background-color: #D4E4E9;
            color: var(--dark-blue);
        }

        /* Table styling */
        .table-primary {
            --bs-table-bg: #D4E4E9;
            --bs-table-color: var(--dark-blue);
        }

        .table-success {
            --bs-table-bg: #FFE5E5;
            --bs-table-color: var(--dark-pink);
        }

        .text-primary {
            color: var(--primary-blue) !important;
        }

        .text-success {
            color: var(--primary-pink) !important;
        }

        .bg-primary {
            background-color: var(--primary-blue) !important;
        }

        .bg-success {
            background-color: var(--primary-pink) !important;
        }

        .border-primary {
            border-color: var(--primary-blue) !important;
        }

        .border-success {
            border-color: var(--primary-pink) !important;
        }

        /* Scrollbar styling */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="sidebar-brand">
                <div class="brand-cloud"></div>
                <div class="brand-name">DIVINE JM</div>
                <div class="brand-tagline">EST. 1980</div>
            </a>
        </div>

        <div class="sidebar-menu">
            <div class="menu-section-title">MAIN</div>
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-fill"></i>
                <span>Dashboard</span>
            </a>

            <div class="menu-section-title">INVENTORY</div>
            <a href="{{ route('raw-materials.index') }}" class="sidebar-link {{ request()->routeIs('raw-materials.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span>Raw Materials</span>
            </a>
            <a href="{{ route('finished-products.index') }}" class="sidebar-link {{ request()->routeIs('finished-products.*') ? 'active' : '' }}">
                <i class="bi bi-basket-fill"></i>
                <span>Finished Products</span>
            </a>

            <div class="menu-section-title">AREAS</div>
            <a href="{{ route('branch-inventory.index') }}" class="sidebar-link {{ request()->routeIs('branch-inventory.*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i>
                <span>Deliver Products</span>
            </a>
            <a href="{{ route('branches.index') }}" class="sidebar-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i>
                <span>Area & Customer</span>
            </a>

            <div class="menu-section-title">TRANSACTIONS</div>
            <a href="{{ route('sales.index') }}" class="sidebar-link {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check-fill"></i>
                <span>Sales</span>
            </a>
            <a href="{{ route('expenses.index') }}" class="sidebar-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i>
                <span>Expenses</span>
            </a>

            <a href="{{ route('bank-deposits.index') }}" 
   class="sidebar-link {{ request()->routeIs('bank-deposits.*') ? 'active' : '' }}">
    <i class="bi bi-bank"></i>
    <span>Bank Deposits</span>
</a>

            <div class="menu-section-title">REPORTS</div>
            <a href="{{ route('financial-reports.index') }}" class="sidebar-link {{ request()->routeIs('financial-reports.*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Reports</span>
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->name }}</div>
                    <div class="user-role">Administrator</div>
                </div>
                <a href="{{ route('logout') }}" 
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="logout-btn"
                   title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <h4 class="mb-0">@yield('page-title', 'Dashboard')</h4>
            <div>
                <span class="text-muted">{{ now()->format('l, F d, Y') }}</span>
            </div>
        </div>

        <div class="content-area">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>