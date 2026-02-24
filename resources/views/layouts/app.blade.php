<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SI-KAPOR') — SI-KAPOR Polda NTB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════════════════════
           SI-KAPOR Design System — Stripe/Linear Inspired
           ═══════════════════════════════════════════════════════ */
        :root {
            /* Core palette */
            --slate-50: #F8FAFC;
            --slate-100: #F1F5F9;
            --slate-200: #E2E8F0;
            --slate-300: #CBD5E1;
            --slate-400: #94A3B8;
            --slate-500: #64748B;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1E293B;
            --slate-900: #0F172A;

            /* Brand */
            --brand: #4F46E5;
            --brand-light: #6366F1;
            --brand-lighter: #818CF8;
            --brand-bg: #EEF2FF;
            --accent: #D4AF37;
            --accent-light: #E8C94A;

            /* Semantic */
            --success: #10B981;
            --success-bg: #ECFDF5;
            --success-border: #A7F3D0;
            --warning: #F59E0B;
            --warning-bg: #FFFBEB;
            --warning-border: #FDE68A;
            --danger: #EF4444;
            --danger-bg: #FEF2F2;
            --danger-border: #FECACA;
            --info: #3B82F6;
            --info-bg: #EFF6FF;
            --info-border: #BFDBFE;

            /* Layout */
            --sidebar-w: 260px;
            --sidebar-collapsed: 72px;
            --header-h: 56px;

            /* Shadows (Stripe-style) */
            --shadow-xs: 0 1px 2px rgba(0,0,0,.04);
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,.06), 0 2px 4px -2px rgba(0,0,0,.04);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.06), 0 4px 6px -4px rgba(0,0,0,.04);

            --radius: 10px;
            --radius-sm: 6px;
            --radius-lg: 14px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--slate-50);
            color: var(--slate-800);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ── Sidebar ─────────────────────────────────────────── */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--slate-900);
            color: #fff;
            z-index: 100;
            display: flex; flex-direction: column;
            transition: all .25s cubic-bezier(.4,0,.2,1);
            border-right: 1px solid rgba(255,255,255,.06);
        }

        .sidebar-brand {
            height: var(--header-h);
            padding: 0 20px;
            display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,.06);
            flex-shrink: 0;
        }
        .brand-icon {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--brand), var(--brand-light));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 800; color: #fff;
            letter-spacing: -.5px; flex-shrink: 0;
        }
        .brand-text { font-size: 14px; font-weight: 700; letter-spacing: -.2px; }
        .brand-badge {
            font-size: 9px; font-weight: 600; background: rgba(255,255,255,.1);
            padding: 2px 6px; border-radius: 4px; margin-left: 4px; opacity: .6;
        }

        .sidebar-nav { flex: 1; overflow-y: auto; padding: 12px 10px; }
        .sidebar-nav::-webkit-scrollbar { width: 3px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }

        .nav-section { margin-bottom: 20px; }
        .nav-section-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.2px;
            color: var(--slate-500); padding: 0 10px; margin-bottom: 6px;
            font-weight: 600;
        }
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border-radius: var(--radius-sm);
            color: var(--slate-400); text-decoration: none;
            font-size: 13px; font-weight: 500;
            transition: all .15s ease; margin-bottom: 1px;
            position: relative;
        }
        .nav-link:hover { background: rgba(255,255,255,.06); color: var(--slate-200); }
        .nav-link.active {
            background: rgba(79,70,229,.15);
            color: var(--brand-lighter);
        }
        .nav-link.active::before {
            content: ''; position: absolute; left: 0; top: 6px; bottom: 6px; width: 3px;
            background: var(--brand-light); border-radius: 0 3px 3px 0;
        }
        .nav-link i { font-size: 17px; width: 20px; text-align: center; flex-shrink: 0; }
        .nav-badge {
            margin-left: auto; font-size: 10px; font-weight: 700;
            background: var(--brand); color: #fff;
            padding: 1px 7px; border-radius: 10px; min-width: 20px; text-align: center;
        }

        .sidebar-footer {
            padding: 12px 14px;
            border-top: 1px solid rgba(255,255,255,.06);
        }
        .sidebar-user {
            display: flex; align-items: center; gap: 10px;
            padding: 8px; border-radius: var(--radius-sm);
            transition: background .15s;
        }
        .sidebar-user:hover { background: rgba(255,255,255,.06); }
        .user-avatar {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg, var(--brand), var(--brand-light));
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
        }
        .user-info .user-name { font-size: 12.5px; font-weight: 600; color: var(--slate-200); }
        .user-info .user-role { font-size: 11px; color: var(--slate-500); }

        /* ── Header ────────────────────────────────────────── */
        .main { margin-left: var(--sidebar-w); min-height: 100vh; }

        .header {
            height: var(--header-h);
            background: rgba(255,255,255,.85);
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
            border-bottom: 1px solid var(--slate-200);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 28px;
            position: sticky; top: 0; z-index: 50;
        }
        .header-left { display: flex; align-items: center; gap: 16px; }
        .btn-menu-toggle {
            display: none; background: none; border: none;
            font-size: 20px; cursor: pointer; color: var(--slate-600);
            padding: 4px;
        }
        .breadcrumb {
            display: flex; align-items: center; gap: 6px;
            font-size: 13px; color: var(--slate-500);
        }
        .breadcrumb a { color: var(--slate-400); text-decoration: none; }
        .breadcrumb a:hover { color: var(--brand); }
        .breadcrumb .sep { color: var(--slate-300); }
        .breadcrumb .current { color: var(--slate-700); font-weight: 600; }

        .header-center { flex: 1; max-width: 420px; margin: 0 32px; }
        .search-wrap {
            position: relative; width: 100%;
        }
        .search-wrap i {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            font-size: 16px; color: var(--slate-400);
        }
        .search-input {
            width: 100%; padding: 7px 12px 7px 36px;
            border: 1px solid var(--slate-200); border-radius: var(--radius-sm);
            font-size: 13px; font-family: inherit;
            background: var(--slate-50); color: var(--slate-700);
            outline: none; transition: all .15s;
        }
        .search-input::placeholder { color: var(--slate-400); }
        .search-input:focus {
            border-color: var(--brand-lighter); background: #fff;
            box-shadow: 0 0 0 3px rgba(79,70,229,.08);
        }
        .search-kbd {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            font-size: 10px; color: var(--slate-400); background: var(--slate-100);
            border: 1px solid var(--slate-200); padding: 1px 5px; border-radius: 3px;
            font-family: inherit;
        }

        .header-right { display: flex; align-items: center; gap: 8px; }
        .header-btn {
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            border-radius: var(--radius-sm); border: none;
            background: transparent; color: var(--slate-500);
            font-size: 18px; cursor: pointer; transition: all .15s;
            position: relative;
        }
        .header-btn:hover { background: var(--slate-100); color: var(--slate-700); }
        .header-btn .dot {
            position: absolute; top: 6px; right: 6px;
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--danger); border: 2px solid #fff;
        }

        .user-dropdown {
            display: flex; align-items: center; gap: 8px;
            padding: 4px 8px 4px 4px; border-radius: var(--radius-sm);
            cursor: pointer; transition: background .15s;
            border: none; background: transparent; font-family: inherit;
        }
        .user-dropdown:hover { background: var(--slate-100); }
        .user-dropdown .dd-avatar {
            width: 28px; height: 28px; border-radius: 6px;
            background: linear-gradient(135deg, var(--brand), var(--brand-light));
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; color: #fff;
        }
        .user-dropdown .dd-name { font-size: 12.5px; font-weight: 600; color: var(--slate-700); }
        .user-dropdown .dd-chevron { font-size: 14px; color: var(--slate-400); }

        /* Dropdown menu */
        .dropdown-container { position: relative; }
        .dropdown-menu {
            display: none; position: absolute; top: calc(100% + 4px); right: 0;
            background: #fff; border: 1px solid var(--slate-200);
            border-radius: var(--radius); box-shadow: var(--shadow-lg);
            min-width: 200px; padding: 4px; z-index: 200;
        }
        .dropdown-container.open .dropdown-menu { display: block; }
        .dropdown-item {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 12px; border-radius: var(--radius-sm);
            font-size: 13px; color: var(--slate-600); text-decoration: none;
            border: none; background: none; width: 100%; cursor: pointer;
            font-family: inherit; transition: background .1s;
        }
        .dropdown-item:hover { background: var(--slate-50); color: var(--slate-800); }
        .dropdown-item i { font-size: 16px; width: 18px; color: var(--slate-400); }
        .dropdown-sep { height: 1px; background: var(--slate-100); margin: 4px 0; }
        .dropdown-item.danger { color: var(--danger); }
        .dropdown-item.danger i { color: var(--danger); }

        /* ── Content Area ─────────────────────────────────── */
        .content { padding: 24px 28px; max-width: 1360px; }

        .page-header {
            margin-bottom: 24px;
        }
        .page-header h1 {
            font-size: 22px; font-weight: 700; letter-spacing: -.3px;
            color: var(--slate-900);
        }
        .page-header p {
            font-size: 13px; color: var(--slate-500); margin-top: 2px;
        }
        .page-header-row {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px;
        }
        .page-header-actions { display: flex; gap: 8px; }

        /* ── Stat Cards ───────────────────────────────────── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 24px;
        }
        .stat-card {
            background: #fff; border-radius: var(--radius);
            padding: 20px 22px;
            border: 1px solid var(--slate-200);
            transition: box-shadow .2s;
        }
        .stat-card:hover { box-shadow: var(--shadow-md); }
        .stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .stat-top .stat-label { font-size: 12.5px; font-weight: 500; color: var(--slate-500); }
        .stat-top .stat-icon-sm {
            width: 30px; height: 30px; border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
        }
        .stat-value { font-size: 26px; font-weight: 800; letter-spacing: -.5px; line-height: 1; }
        .stat-footer { font-size: 11.5px; color: var(--slate-400); margin-top: 6px; display: flex; align-items: center; gap: 4px; }
        .stat-footer .up { color: var(--success); }
        .stat-footer .down { color: var(--danger); }

        /* ── Card ─────────────────────────────────────────── */
        .card {
            background: #fff; border-radius: var(--radius);
            border: 1px solid var(--slate-200);
            margin-bottom: 20px;
        }
        .card-head {
            padding: 16px 20px;
            border-bottom: 1px solid var(--slate-100);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-head h3 { font-size: 14px; font-weight: 700; color: var(--slate-800); }
        .card-head .card-actions { display: flex; gap: 6px; }
        .card-body { padding: 20px; }
        .card-body.flush { padding: 0; }

        /* ── Table ─────────────────────────────────────────── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: var(--slate-50);
            font-size: 11px; text-transform: uppercase; letter-spacing: .7px;
            color: var(--slate-500); font-weight: 600; text-align: left;
            padding: 6px 12px; border-bottom: 1px solid var(--slate-200);
            white-space: nowrap;
        }
        tbody td {
            padding: 5px 12px; font-size: 13px;
            border-bottom: 1px solid var(--slate-100);
            vertical-align: middle;
        }
        tfoot td {
            padding: 6px 12px; font-size: 13px;
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: var(--slate-50); }

        .cell-user { display: flex; align-items: center; gap: 10px; }
        .cell-avatar {
            width: 30px; height: 30px; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; flex-shrink: 0;
        }
        .cell-name { font-weight: 600; color: var(--slate-800); font-size: 13px; }
        .cell-sub { font-size: 11.5px; color: var(--slate-400); }

        /* ── Badge ─────────────────────────────────────────── */
        .badge {
            display: inline-flex; align-items: center; gap: 3px;
            padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .badge-success { background: var(--success-bg); color: var(--success); }
        .badge-warning { background: var(--warning-bg); color: var(--warning); }
        .badge-danger { background: var(--danger-bg); color: var(--danger); }
        .badge-info { background: var(--info-bg); color: var(--info); }
        .badge-neutral { background: var(--slate-100); color: var(--slate-600); }
        .badge-brand { background: var(--brand-bg); color: var(--brand); }

        /* ── Progress ──────────────────────────────────────── */
        .progress {
            height: 6px; background: var(--slate-100);
            border-radius: 3px; overflow: hidden;
        }
        .progress-bar {
            height: 100%; border-radius: 3px; transition: width .6s ease;
        }
        .progress-bar.green { background: var(--success); }
        .progress-bar.yellow { background: var(--warning); }
        .progress-bar.red { background: var(--danger); }
        .progress-bar.brand { background: var(--brand); }

        /* ── Buttons ───────────────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: var(--radius-sm);
            font-size: 13px; font-weight: 600; border: none;
            cursor: pointer; transition: all .15s; text-decoration: none;
            font-family: inherit;
        }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-light); box-shadow: var(--shadow-sm); }
        .btn-ghost { background: transparent; color: var(--slate-600); }
        .btn-ghost:hover { background: var(--slate-100); }
        .btn-outline { background: #fff; border: 1px solid var(--slate-200); color: var(--slate-700); }
        .btn-outline:hover { border-color: var(--slate-300); background: var(--slate-50); }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .btn-xs { padding: 3px 8px; font-size: 11px; }
        .btn i { font-size: 15px; }
        .btn-success { background: var(--success); color: #fff; }
        .btn-success:hover { background: #059669; box-shadow: var(--shadow-sm); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: #DC2626; box-shadow: var(--shadow-sm); }

        /* ── Status Dot ────────────────────────────────────── */
        .status-dot {
            width: 7px; height: 7px; border-radius: 50%; display: inline-block;
        }
        .status-dot.green { background: var(--success); }
        .status-dot.red { background: var(--danger); }
        .status-dot.yellow { background: var(--warning); }

        /* ── Grid layouts ──────────────────────────────────── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3-1 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }

        /* ── Overlay for mobile ────────────────────────────── */
        .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.3); z-index: 90; }

        @media (max-width: 1024px) {
            .grid-2, .grid-3-1 { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .overlay.open { display: block; }
            .main { margin-left: 0; }
            .btn-menu-toggle { display: block; }
            .header-center { display: none; }
            .content { padding: 16px; }
            .stats-row { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .stat-value { font-size: 20px; }
        }

        @yield('styles')
    </style>
</head>
<body>
    {{-- ═══ Sidebar ═══ --}}
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">SK</div>
            <span class="brand-text">SI-KAPOR</span>
            <span class="brand-badge">v1.0</span>
        </div>

        <nav class="sidebar-nav">
            {{-- Main --}}
            <div class="nav-section">
                <div class="nav-section-label">Overview</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="ri-dashboard-3-line"></i> Dashboard
                </a>
            </div>

            @if(auth()->user()->hasRole('personil'))
            <div class="nav-section">
                <div class="nav-section-label">Kapor</div>
                <a href="{{ route('personil.kapor.index') }}" class="nav-link {{ request()->routeIs('personil.kapor.index') ? 'active' : '' }}">
                    <i class="ri-shirt-line"></i> Input Ukuran
                </a>
                <a href="{{ route('personil.kapor.history') }}" class="nav-link {{ request()->routeIs('personil.kapor.history') ? 'active' : '' }}">
                    <i class="ri-history-line"></i> Riwayat
                </a>
            </div>
            @endif

            @if(auth()->user()->hasRole('admin_satker'))
            <div class="nav-section">
                <div class="nav-section-label">Satker Saya</div>
                <a href="{{ route('admin-satker.personnel.index') }}" class="nav-link {{ request()->routeIs('admin-satker.personnel.*') ? 'active' : '' }}">
                    <i class="ri-team-line"></i> Personil
                </a>
                <a href="{{ route('admin-satker.monitor') }}" class="nav-link {{ request()->routeIs('admin-satker.monitor') ? 'active' : '' }}">
                    <i class="ri-eye-line"></i> Monitoring
                </a>
            </div>
            @endif

            @if(auth()->user()->hasAnyRole(['admin', 'superadmin']))
            <div class="nav-section">
                <div class="nav-section-label">Administrasi</div>
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="ri-group-line"></i> Manajemen User
                </a>
                <a href="{{ route('admin.satkers.index') }}" class="nav-link {{ request()->routeIs('admin.satkers.*') ? 'active' : '' }}">
                    <i class="ri-building-2-line"></i> Data Satker
                </a>
                <a href="{{ route('admin.personnel.index') }}" class="nav-link {{ request()->routeIs('admin.personnel.*') ? 'active' : '' }}">
                    <i class="ri-team-line"></i> Data Personel
                </a>
                <a href="{{ route('admin.kapor-items.index') }}" class="nav-link {{ request()->routeIs('admin.kapor-items.*') ? 'active' : '' }}">
                    <i class="ri-t-shirt-2-line"></i> Data Item Kapor
                </a>
                <a href="{{ route('admin.reports') }}" class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                    <i class="ri-bar-chart-grouped-line"></i> Laporan
                </a>
                <a href="{{ route('admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                    <i class="ri-shield-user-line"></i> Log Audit
                </a>
            </div>
            @endif

            @if(auth()->user()->hasRole('superadmin'))
            <div class="nav-section">
                <div class="nav-section-label">System</div>
                <a href="{{ route('superadmin.satkers.index') }}" class="nav-link {{ request()->routeIs('superadmin.satkers.*') ? 'active' : '' }}">
                    <i class="ri-organization-chart"></i> Kelola Satker
                </a>
                <a href="{{ route('superadmin.kapor-items.index') }}" class="nav-link {{ request()->routeIs('superadmin.kapor-items.*') ? 'active' : '' }}">
                    <i class="ri-t-shirt-2-line"></i> Item Kapor
                </a>
                <a href="{{ route('superadmin.settings.index') }}" class="nav-link {{ request()->routeIs('superadmin.settings.*') ? 'active' : '' }}">
                    <i class="ri-settings-3-line"></i> Pengaturan
                </a>
                <a href="{{ route('superadmin.statistics') }}" class="nav-link {{ request()->routeIs('superadmin.statistics') ? 'active' : '' }}">
                    <i class="ri-pie-chart-2-line"></i> Statistik
                </a>
            </div>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                <div class="user-info">
                    <div class="user-name">{{ Str::limit(auth()->user()->name, 18) }}</div>
                    <div class="user-role">{{ ucfirst(str_replace('_', ' ', auth()->user()->getRoleNames()->first() ?? 'User')) }}</div>
                </div>
            </div>
        </div>
    </aside>

    {{-- Overlay --}}
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    {{-- ═══ Main ═══ --}}
    <div class="main">
        <header class="header">
            <div class="header-left">
                <button class="btn-menu-toggle" onclick="toggleSidebar()"><i class="ri-menu-line"></i></button>
                <div class="breadcrumb">
                    <a href="{{ route('dashboard') }}">SI-KAPOR</a>
                    <span class="sep">/</span>
                    <span class="current">@yield('breadcrumb', 'Dashboard')</span>
                </div>
            </div>



            <div class="header-right">
                <button class="header-btn" title="Notifikasi">
                    <i class="ri-notification-3-line"></i>
                </button>

                <div class="dropdown-container" id="userDropdown">
                    <button class="user-dropdown" onclick="document.getElementById('userDropdown').classList.toggle('open')">
                        <div class="dd-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                        <span class="dd-name">{{ Str::limit(auth()->user()->name, 14) }}</span>
                        <i class="ri-arrow-down-s-line dd-chevron"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="{{ route('profile') }}" class="dropdown-item"><i class="ri-user-line"></i> Profil Saya</a>
                        <div class="dropdown-sep"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item danger"><i class="ri-logout-box-r-line"></i> Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('open');
        }
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            var dd = document.getElementById('userDropdown');
            if (dd && !dd.contains(e.target)) dd.classList.remove('open');
        });
    </script>
    @yield('scripts')
</body>
</html>
