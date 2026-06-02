<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Personil') — E-MAS KAPOR Polda NTB</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon-48x48.png') }}" type="image/png" sizes="48x48">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300..900;1,14..32,300..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        :root {
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-700: #334155;
            --slate-900: #0f172a;
            --brand: #c62828;
            --brand-light: #e53935;
            --brand-soft: #fff0f0;
            --brand-gradient: linear-gradient(135deg, #c62828 0%, #e53935 100%);
            --accent: #d4af37;
            --success: #059669;
            --success-soft: #ecfdf5;
            --warning: #d97706;
            --warning-soft: #fff7ed;
            --info: #2563eb;
            --info-soft: #eff6ff;
            --danger: #dc2626;
            --danger-soft: #fef2f2;
            --border-color: #e8edf3;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-body: #f4f6fb;
            --bg-card: #ffffff;
            --shadow-sm: 0 2px 12px rgba(15, 23, 42, 0.06);
            --shadow-md: 0 8px 24px rgba(15, 23, 42, 0.08);
            --shadow-lg: 0 16px 40px rgba(15, 23, 42, 0.10);
            --radius: 16px;
            --radius-sm: 10px;
            --topbar-h: 64px;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }

        button, input, select, textarea { font: inherit; }

        /* ─── TOPBAR ─────────────────────────────────────────── */
        .personil-topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            height: var(--topbar-h);
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 1px 20px rgba(15, 23, 42, 0.06);
        }

        .personil-topbar-inner {
            max-width: 1120px;
            margin: 0 auto;
            padding: 0 20px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        /* ─── BRAND ──────────────────────────────────────────── */
        .personil-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .personil-brand-logo {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(198, 40, 40, 0.2);
        }

        .personil-brand-copy strong {
            display: block;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text-main);
            line-height: 1.2;
        }

        .personil-brand-copy span {
            display: block;
            font-size: 11px;
            font-weight: 500;
            color: var(--text-muted);
            margin-top: 1px;
        }

        /* ─── DESKTOP NAV ────────────────────────────────────── */
        .personil-nav {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .personil-nav-link,
        .personil-nav-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 36px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.18s ease;
            white-space: nowrap;
        }

        .personil-nav-link:hover {
            background: var(--slate-100);
            color: var(--text-main);
        }

        .personil-nav-link.active {
            background: var(--brand-soft);
            border-color: #fecaca;
            color: var(--brand);
            font-weight: 700;
        }

        .personil-nav-button {
            color: var(--text-muted);
        }
        .personil-nav-button:hover {
            background: #fef2f2;
            color: var(--brand);
            border-color: #fecaca;
        }

        .nav-divider {
            width: 1px;
            height: 20px;
            background: var(--border-color);
            margin: 0 4px;
        }

        /* ─── HAMBURGER ──────────────────────────────────────── */
        .hamburger-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: #fff;
            color: var(--text-main);
            font-size: 20px;
            cursor: pointer;
            transition: all 0.18s;
            flex-shrink: 0;
        }
        .hamburger-btn:hover {
            background: var(--slate-100);
        }

        /* ─── MOBILE DRAWER ──────────────────────────────────── */
        .mobile-drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            z-index: 200;
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        .mobile-drawer-overlay.open {
            display: block;
            opacity: 1;
        }

        .mobile-drawer {
            position: fixed;
            top: 0;
            right: -300px;
            width: 280px;
            height: 100%;
            background: #fff;
            z-index: 201;
            box-shadow: -8px 0 40px rgba(15, 23, 42, 0.15);
            transition: right 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .mobile-drawer.open {
            right: 0;
        }

        .mobile-drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .mobile-drawer-close {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.18s;
        }
        .mobile-drawer-close:hover {
            background: var(--slate-100);
            color: var(--text-main);
        }

        .mobile-drawer-nav {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }

        .mobile-drawer-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.18s;
        }
        .mobile-drawer-link:hover {
            background: var(--slate-100);
            color: var(--text-main);
        }
        .mobile-drawer-link.active {
            background: var(--brand-soft);
            color: var(--brand);
        }
        .mobile-drawer-link i {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--slate-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .mobile-drawer-link.active i {
            background: #fecaca;
            color: var(--brand);
        }

        .mobile-drawer-footer {
            padding: 16px;
            border-top: 1px solid var(--border-color);
        }

        .mobile-logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: var(--brand);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.18s;
            text-align: left;
        }
        .mobile-logout-btn:hover {
            background: #fee2e2;
        }
        .mobile-logout-btn i {
            font-size: 18px;
        }

        /* ─── MAIN CONTENT ───────────────────────────────────── */
        .personil-main {
            padding: 24px 16px 56px;
        }

        .personil-container {
            max-width: 1280px;
            margin: 0 auto;
        }

        /* ─── PAGE: single column mobile, 2-col desktop ── */
        .page {
            display: grid;
            gap: 20px;
        }

        /* Top-bar alerts & banners always full width */
        .page-full {
            grid-column: 1 / -1;
        }

        @media (min-width: 1024px) {
            .personil-main {
                padding: 32px 32px 64px;
            }

            .page {
                grid-template-columns: 320px 1fr;
                grid-template-rows: auto;
                align-items: start;
                gap: 24px;
            }

            .page-sidebar {
                grid-column: 1;
                position: sticky;
                top: calc(var(--topbar-h) + 20px);
                display: flex;
                flex-direction: column;
                gap: 16px;
            }

            .page-main {
                grid-column: 2;
                display: flex;
                flex-direction: column;
                gap: 20px;
            }
        }

        @media (max-width: 1023px) {
            .page-sidebar,
            .page-main {
                display: contents;
            }
        }

        /* ─── PANEL BASE (kept for compat, overridden in page) ─ */
        .panel {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .panel-header {
            background: var(--slate-50);
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .panel-header h1,
        .panel-header h2 {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .panel-header p {
            margin: 4px 0 0;
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .panel-body {
            padding: 16px 20px 20px;
        }

        @media (min-width: 768px) {
            .personil-topbar-inner { padding: 0 32px; }
        }

        @media (max-width: 640px) {
            .personil-nav { display: none; }
            .hamburger-btn { display: flex; }
        }
    </style>

    @yield('styles')
</head>

<body>
    <!-- Topbar -->
    <header class="personil-topbar">
        <div class="personil-topbar-inner">
            <a href="{{ route('dashboard') }}" class="personil-brand">
                <img src="{{ asset('e-mas-kapor.png') }}" alt="E-MAS KAPOR" class="personil-brand-logo">
                <div class="personil-brand-copy">
                    <strong>E-MAS KAPOR</strong>
                    <span>Portal Personil · Polda NTB</span>
                </div>
            </a>

            <!-- Desktop Nav -->
            <nav class="personil-nav">
                <a href="{{ route('dashboard') }}"
                   class="personil-nav-link {{ request()->routeIs('dashboard') || request()->routeIs('personil.kapor.index') ? 'active' : '' }}">
                    <i class="ri-edit-box-line"></i> Data
                </a>
                <a href="{{ route('personil.kapor.history') }}"
                   class="personil-nav-link {{ request()->routeIs('personil.kapor.history') ? 'active' : '' }}">
                    <i class="ri-history-line"></i> Riwayat
                </a>
                <a href="{{ route('personil.testimoni.index') }}"
                   class="personil-nav-link {{ request()->routeIs('personil.testimoni.*') ? 'active' : '' }}">
                    <i class="ri-feedback-line"></i> Review
                </a>
                <div class="nav-divider"></div>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="personil-nav-button">
                        <i class="ri-logout-box-r-line"></i> Keluar
                    </button>
                </form>
            </nav>

            <!-- Mobile Hamburger -->
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Buka menu">
                <i class="ri-menu-3-line"></i>
            </button>
        </div>
    </header>

    <!-- Mobile Drawer Overlay -->
    <div class="mobile-drawer-overlay" id="drawerOverlay"></div>

    <!-- Mobile Drawer -->
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="mobile-drawer-header">
            <div class="personil-brand" style="gap:10px;">
                <img src="{{ asset('e-mas-kapor.png') }}" alt="Logo" style="width:32px;height:32px;border-radius:8px;">
                <div class="personil-brand-copy">
                    <strong style="font-size:13px;">E-MAS KAPOR</strong>
                    <span>Portal Personil</span>
                </div>
            </div>
            <button class="mobile-drawer-close" id="drawerClose" aria-label="Tutup menu">
                <i class="ri-close-line"></i>
            </button>
        </div>

        <nav class="mobile-drawer-nav">
            <a href="{{ route('dashboard') }}"
               class="mobile-drawer-link {{ request()->routeIs('dashboard') || request()->routeIs('personil.kapor.index') ? 'active' : '' }}">
                <i class="ri-edit-box-line"></i>
                <span>Data Kaporlap</span>
            </a>
            <a href="{{ route('personil.kapor.history') }}"
               class="mobile-drawer-link {{ request()->routeIs('personil.kapor.history') ? 'active' : '' }}">
                <i class="ri-history-line"></i>
                <span>Riwayat Ukuran</span>
            </a>
            <a href="{{ route('personil.testimoni.index') }}"
               class="mobile-drawer-link {{ request()->routeIs('personil.testimoni.*') ? 'active' : '' }}">
                <i class="ri-feedback-line"></i>
                <span>Review Item</span>
            </a>
        </nav>

        <div class="mobile-drawer-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="mobile-logout-btn">
                    <i class="ri-logout-box-r-line"></i>
                    Keluar dari Akun
                </button>
            </form>
        </div>
    </div>

    <main class="personil-main">
        <div class="personil-container">
            @yield('content')
        </div>
    </main>

    <script>
        (function () {
            const btn = document.getElementById('hamburgerBtn');
            const overlay = document.getElementById('drawerOverlay');
            const drawer = document.getElementById('mobileDrawer');
            const closeBtn = document.getElementById('drawerClose');

            function openDrawer() {
                overlay.classList.add('open');
                drawer.classList.add('open');
                document.body.style.overflow = 'hidden';
            }
            function closeDrawer() {
                overlay.classList.remove('open');
                drawer.classList.remove('open');
                document.body.style.overflow = '';
            }

            btn?.addEventListener('click', openDrawer);
            closeBtn?.addEventListener('click', closeDrawer);
            overlay?.addEventListener('click', closeDrawer);
        })();
    </script>

    @yield('scripts')
</body>

</html>
