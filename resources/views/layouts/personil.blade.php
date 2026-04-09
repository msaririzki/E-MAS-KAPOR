<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Personil') — E-MAS KAPOR Polda NTB</title>
    <link rel="icon" href="{{ asset('e-mas-kapor.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            --brand-soft: #ffebee;
            --accent: #d4af37;
            --success: #059669;
            --success-soft: #ecfdf5;
            --warning: #d97706;
            --warning-soft: #fff7ed;
            --info: #2563eb;
            --info-soft: #eff6ff;
            --danger: #dc2626;
            --danger-soft: #fef2f2;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --shadow-sm: 0 8px 24px rgba(15, 23, 42, 0.05);
            --shadow-lg: 0 12px 28px rgba(15, 23, 42, 0.08);
            --radius: 16px;
            --radius-sm: 10px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .personil-topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
        }

        .personil-topbar-inner {
            max-width: 1120px;
            margin: 0 auto;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .personil-topbar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex: 1;
        }

        .hamburger-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-main);
            cursor: pointer;
            padding: 4px;
        }

        .personil-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .personil-brand img {
            width: 40px;
            height: 40px;
            border-radius: 10px;
        }

        .personil-brand-copy {
            min-width: 0;
        }

        .personil-brand-copy strong {
            display: block;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .personil-brand-copy span {
            display: block;
            margin-top: 2px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .personil-nav {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
        }

        .personil-nav-link,
        .personil-nav-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid var(--border-color);
            background: #ffffff;
            color: var(--text-main);
            font-size: 13px;
            font-weight: 700;
            transition: 0.18s ease;
        }

        .personil-nav-link.active {
            border-color: #fecaca;
            background: #fef2f2;
            color: var(--brand);
        }

        .personil-nav-button {
            cursor: pointer;
        }

        .personil-main {
            padding: 20px 16px 40px;
        }

        .personil-container {
            max-width: 1120px;
            margin: 0 auto;
        }

        .page {
            display: grid;
            gap: 16px;
            max-width: 760px;
            margin: 0 auto;
        }

        .panel {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .panel-header {
            background: var(--slate-50);
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .panel-header h1,
        .panel-header h2 {
            margin: 0;
            font-size: 18px;
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
            padding: 14px 20px 20px;
        }

        @media (min-width: 768px) {
            .personil-topbar-inner {
                padding: 14px 20px;
            }

            .personil-main {
                padding: 28px 20px 56px;
            }
        }

        @media (max-width: 640px) {
            .personil-topbar-inner {
                align-items: stretch;
                flex-direction: column;
                gap: 0;
            }

            .personil-topbar-header {
                width: 100%;
            }

            .hamburger-btn {
                display: block;
            }

            .personil-nav {
                display: none;
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
                margin-top: 12px;
                gap: 4px;
            }

            .personil-nav.show {
                display: flex;
            }

            .personil-nav-link,
            .personil-nav-button {
                width: auto;
                justify-content: flex-start;
                padding: 10px 0;
                background: transparent;
                border: none;
                font-size: 14px;
            }

            .personil-nav-link.active {
                background: transparent;
                border: none;
                color: var(--brand);
            }

            .personil-nav form {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px solid var(--border-color);
                width: 100%;
                display: flex;
                justify-content: flex-start;
            }
        }
    </style>

    @yield('styles')
</head>

<body>
    <header class="personil-topbar">
        <div class="personil-topbar-inner">
            <div class="personil-topbar-header">
                <a href="{{ route('dashboard') }}" class="personil-brand">
                    <img src="{{ asset('e-mas-kapor.png') }}" alt="E-MAS KAPOR">
                    <div class="personil-brand-copy">
                        <strong>E-MAS KAPOR</strong>
                        <span>Portal personil</span>
                    </div>
                </a>
                <button class="hamburger-btn" aria-label="Toggle Navigation"
                    onclick="document.getElementById('personilNav').classList.toggle('show')">
                    <i class="ri-menu-line"></i>
                </button>
            </div>

            <nav class="personil-nav" id="personilNav">
                <a href="{{ route('dashboard') }}"
                    class="personil-nav-link {{ request()->routeIs('dashboard') || request()->routeIs('personil.kapor.index') ? 'active' : '' }}">
                    <i class="ri-edit-box-line"></i>
                    Data
                </a>
                <a href="{{ route('personil.kapor.history') }}"
                    class="personil-nav-link {{ request()->routeIs('personil.kapor.history') ? 'active' : '' }}">
                    <i class="ri-history-line"></i>
                    Riwayat
                </a>
                <a href="{{ route('personil.testimoni.index') }}"
                    class="personil-nav-link {{ request()->routeIs('personil.testimoni.*') ? 'active' : '' }}">
                    <i class="ri-feedback-line"></i>
                    Testimoni
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="personil-nav-button">
                        <i class="ri-logout-box-r-line"></i>
                        Keluar
                    </button>
                </form>
            </nav>
        </div>
    </header>

    <main class="personil-main">
        <div class="personil-container">
            @yield('content')
        </div>
    </main>

    @yield('scripts')
</body>

</html>