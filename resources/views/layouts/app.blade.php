<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'E-MAS KAPOR') — E-MAS KAPOR Polda NTB</title>
    <link rel="icon" href="{{ asset('e-mas-kapor.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">

    {{-- Select2 & jQuery --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* ═══════════════════════════════════════════════════════
           E-MAS KAPOR Design System — Stripe/Linear Inspired
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
            --brand: #C62828;
            --brand-light: #E53935;
            --brand-lighter: #EF5350;
            --brand-bg: #FFEBEE;
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
            --sidebar-collapsed: 80px;
            --header-h: 56px;

            /* Shadows (Stripe-style) */
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, .04);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, .06), 0 1px 2px rgba(0, 0, 0, .04);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, .06), 0 2px 4px -2px rgba(0, 0, 0, .04);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, .06), 0 4px 6px -4px rgba(0, 0, 0, .04);

            --radius: 10px;
            --radius-sm: 6px;
            --radius-lg: 14px;
            
            /* Global Layout Variables (Default Light) */
            --bg-body: var(--slate-50);
            --bg-card: #ffffff;
            --bg-header: rgba(255, 255, 255, 0.85);
            --text-main: var(--slate-800);
            --text-muted: var(--slate-500);
            --border-color: var(--slate-200);
            --input-bg: var(--slate-50);
            --hover-bg: var(--slate-50);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            font-size: clamp(14px, 0.9vw, 16px);
            line-height: 1.5;
        }

        /* ── Global Theme Overrides (Gen Z / Modern Aesthetics) ──────────────────── */

        /* Theme: Midnight Navy (Classic & Safe) - DEFAULT */
        body.theme-default {
            --brand: #C62828;
            --brand-light: #E53935;
            --brand-lighter: #EF5350;
            --brand-bg: #FFEBEE;
            --accent: #D4AF37;
            --sidebar-bg: #111827;      /* Midnight Navy */
            --sidebar-border: rgba(255, 255, 255, 0.05);
            --sidebar-active-bg: rgba(255, 255, 255, 0.1);
            --sidebar-active-text: #ffffff;
            --sidebar-badge-bg: var(--brand);
            --sidebar-badge-text: #ffffff;
        }
        
        /* Theme: Matcha Minimalist (Organic & Soft) */
        body.theme-matcha {
            --brand: #65a30d;           /* Lime/Matcha Green */
            --brand-light: #84cc16;
            --brand-lighter: #a3e635;
            --brand-bg: #ecfccb;
            --accent: #14b8a6;          /* Teal */
            --sidebar-bg: #FAFAF9;      /* Warm white */
            --sidebar-border: rgba(0, 0, 0, 0.05);
            --sidebar-active-bg: rgba(101, 163, 13, 0.1);
            --sidebar-active-text: #65a30d;
            --sidebar-badge-bg: var(--brand);
            --sidebar-badge-text: #ffffff;
        }
        /* Specific override for Matcha since sidebar is light */
        body.theme-matcha .sidebar { color: var(--slate-700); }
        body.theme-matcha .sidebar-brand { border-bottom: 1px solid rgba(0,0,0,0.05); }
        body.theme-matcha .sidebar-brand-text { color: var(--slate-800); }
        body.theme-matcha .nav-link { color: var(--slate-600); border-color: rgba(0,0,0,0.03); background: rgba(0,0,0,0.01); }
        body.theme-matcha .nav-link:hover { background: rgba(0,0,0,0.03); color: var(--slate-800); }
        body.theme-matcha .nav-link.active { color: var(--brand); background: rgba(101, 163, 13, 0.1); border-color: rgba(101, 163, 13, 0.2); }
        body.theme-matcha .nav-link i { color: var(--slate-400); }
        body.theme-matcha .nav-link:hover i { color: var(--slate-600); }
        body.theme-matcha .nav-link.active i { color: var(--brand); }
        body.theme-matcha .sidebar-footer { border-top: 1px solid rgba(0,0,0,0.05); }
        body.theme-matcha .nav-section-label { color: var(--slate-500); }
        body.theme-matcha .nav-group { border-color: rgba(0,0,0,0.05); background: #ffffff; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
        body.theme-matcha .nav-group-toggle { color: var(--slate-700); }
        body.theme-matcha .nav-group-toggle:hover { background: rgba(0,0,0,0.02); color: var(--brand); }
        body.theme-matcha .nav-group-toggle i.group-icon { color: var(--slate-400); }
        body.theme-matcha .nav-group-toggle:hover i.group-icon { color: var(--brand); }
        body.theme-matcha .nav-group-toggle .group-chevron { color: var(--slate-300); }
        body.theme-matcha .nav-group.open { background: rgba(101, 163, 13, 0.02); border-color: rgba(101, 163, 13, 0.1); }
        body.theme-matcha .nav-group.open > .nav-group-toggle { color: var(--brand); border-bottom-color: rgba(101, 163, 13, 0.05); background: transparent; }
        body.theme-matcha .nav-group.open > .nav-group-toggle i.group-icon { color: var(--brand); }
        body.theme-matcha .nav-group-children .nav-link { background: transparent; border: none; font-weight: 500; }
        body.theme-matcha .nav-group-children .nav-link::after { background: var(--slate-300); }
        body.theme-matcha .nav-group-children .nav-link.active { background: rgba(101, 163, 13, 0.15) !important; color: var(--brand) !important; font-weight: 700; }
        body.theme-matcha .nav-group-children .nav-link.active::after { background: var(--brand); box-shadow: 0 0 6px rgba(101, 163, 13, 0.4); }
        body.theme-matcha .sidebar-user:hover { background: rgba(0,0,0,0.04); }
        body.theme-matcha .user-name { color: var(--slate-800); }
        body.theme-matcha .user-role { color: var(--slate-500); }

        /* Theme: Cyber Neon (Dark Mode & Vibrant) */
        body.theme-cyber {
            --brand: #a855f7;           /* Neon Purple */
            --brand-light: #c084fc;
            --brand-lighter: #d8b4fe;
            --brand-bg: rgba(168, 85, 247, 0.15);
            --accent: #06b6d4;          /* Cyan */
            --sidebar-bg: #020617;      /* Extremely dark slate */
            --sidebar-border: rgba(168, 85, 247, 0.2);
            --sidebar-active-bg: rgba(6, 182, 212, 0.15); /* Cyan Bg */
            --sidebar-active-text: #22d3ee; /* Neon Cyan text */
            --sidebar-badge-bg: linear-gradient(135deg, #a855f7, #06b6d4);
            --sidebar-badge-text: #ffffff;
            
            /* Dark Mode Layout Adjustments */
            --bg-body: #020617;
            --bg-card: #0f172a;
            --bg-header: rgba(15, 23, 42, 0.85);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #1e293b;
            --input-bg: #020617;
            --hover-bg: #1e293b;
            
            /* Re-map some semantic colors for better dark mode visibility */
            --slate-50: #1e293b;
            --slate-100: #334155;
            --slate-200: #475569;
        }

        /* Theme: Monochrome Brutalist (Strict B&W + Orange Accent) */
        body.theme-monochrome {
            --brand: #000000;           /* Pure black buttons */
            --brand-light: #262626;
            --brand-lighter: #525252;
            --brand-bg: #f5f5f5;
            --accent: #ea580c;          /* Safety Orange */
            --sidebar-bg: #000000;      /* Pure black sidebar */
            --sidebar-border: rgba(255, 255, 255, 0.1);
            --sidebar-active-bg: #ffffff;
            --sidebar-active-text: #000000;
            --sidebar-badge-bg: #ea580c; /* Orange badges */
            --sidebar-badge-text: #ffffff;
        }

        /* Theme: Twilight Lavender (Soft Dark Mode) */
        body.theme-twilight {
            --brand: #8b5cf6;           /* Violet */
            --brand-light: #a78bfa;
            --brand-lighter: #c4b5fd;
            --brand-bg: #f5f3ff;
            --accent: #f472b6;          /* Pink */
            --sidebar-bg: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --sidebar-active-bg: rgba(244, 114, 182, 0.15);
            --sidebar-active-text: #f472b6;
            --sidebar-badge-bg: linear-gradient(135deg, #8b5cf6, #f472b6);
            --sidebar-badge-text: #ffffff;
        }

        /* ── NEW MODERN THEMES ── */
        
        /* Theme: Ocean Breeze (Clean Light Mode) */
        body.theme-ocean {
            --brand: #0ea5e9;           /* Sky Blue */
            --brand-light: #38bdf8;
            --brand-lighter: #7dd3fc;
            --brand-bg: #e0f2fe;
            --accent: #14b8a6;          /* Teal */
            --sidebar-bg: #ffffff;      
            --sidebar-border: rgba(0, 0, 0, 0.05);
            --sidebar-active-bg: rgba(14, 165, 233, 0.1);
            --sidebar-active-text: #0ea5e9;
            --sidebar-badge-bg: var(--brand);
            --sidebar-badge-text: #ffffff;
        }
        body.theme-ocean .sidebar { color: var(--slate-700); }
        body.theme-ocean .sidebar-brand { border-bottom: 1px solid rgba(0,0,0,0.05); }
        body.theme-ocean .nav-link { color: var(--slate-500); border-color: rgba(0,0,0,0.03); background: rgba(0,0,0,0.01); }
        body.theme-ocean .nav-link:hover { background: rgba(0,0,0,0.03); color: var(--slate-800); }
        body.theme-ocean .nav-link.active { color: #fff; background: var(--brand); border-color: var(--brand); }
        body.theme-ocean .nav-link i { color: var(--slate-400); }
        body.theme-ocean .nav-link:hover i { color: var(--slate-700); }
        body.theme-ocean .nav-link.active i { color: #fff; }
        body.theme-ocean .sidebar-footer { border-top: 1px solid rgba(0,0,0,0.05); }
        body.theme-ocean .nav-section-label { color: var(--slate-400); }
        body.theme-ocean .nav-group { border-color: rgba(0,0,0,0.05); background: #ffffff; }
        body.theme-ocean .nav-group-toggle { color: var(--slate-700); }
        body.theme-ocean .nav-group-toggle:hover { background: rgba(0,0,0,0.02); color: var(--brand); }
        body.theme-ocean .nav-group-toggle i.group-icon { color: var(--slate-400); }
        body.theme-ocean .nav-group-toggle:hover i.group-icon { color: var(--brand); }
        body.theme-ocean .nav-group-toggle .group-chevron { color: var(--slate-300); }
        body.theme-ocean .nav-group.open { background: rgba(14, 165, 233, 0.02); border-color: rgba(14, 165, 233, 0.1); }
        body.theme-ocean .nav-group.open > .nav-group-toggle { color: var(--brand); border-bottom-color: rgba(14, 165, 233, 0.05); }
        body.theme-ocean .nav-group.open > .nav-group-toggle i.group-icon { color: var(--brand); }
        body.theme-ocean .nav-group-children .nav-link::after { background: var(--slate-300); }
        body.theme-ocean .nav-group-children .nav-link.active { background: rgba(14, 165, 233, 0.1) !important; color: var(--brand) !important; }
        body.theme-ocean .nav-group-children .nav-link.active::after { background: var(--brand); box-shadow: 0 0 6px rgba(14, 165, 233, 0.4); }

        /* Theme: Sunset Glow (Warm Dark Mode) */
        body.theme-sunset {
            --brand: #f43f5e;           /* Rose/Pink */
            --brand-light: #fb7185;
            --brand-lighter: #fda4af;
            --brand-bg: rgba(244, 63, 94, 0.15);
            --accent: #f59e0b;          /* Amber/Orange */
            --sidebar-bg: linear-gradient(160deg, #2a0a18 0%, #17050f 100%);
            --sidebar-border: rgba(244, 63, 94, 0.2);
            --sidebar-active-bg: rgba(245, 158, 11, 0.15); /* Orange bg */
            --sidebar-active-text: #fcd34d;
            --sidebar-badge-bg: linear-gradient(135deg, #f43f5e, #f59e0b);
            --sidebar-badge-text: #ffffff;
        }

        /* Theme: Forest Pine (Elegant Dark Emerald) */
        body.theme-pine {
            --brand: #10b981;           /* Emerald */
            --brand-light: #34d399;
            --brand-lighter: #6ee7b7;
            --brand-bg: rgba(16, 185, 129, 0.15);
            --accent: #fbbf24;          /* Gold/Amber */
            --sidebar-bg: #064e3b;      /* Very dark emerald */
            --sidebar-border: rgba(52, 211, 153, 0.15);
            --sidebar-active-bg: rgba(251, 191, 36, 0.15); /* Gold bg */
            --sidebar-active-text: #fde68a;
            --sidebar-badge-bg: #fbbf24;
            --sidebar-badge-text: #064e3b; /* Dark text for contrast */
        }

        /* Theme: Sakura Pink (Soft Pastel Light Mode) */
        body.theme-sakura {
            --brand: #ec4899;           /* Pink */
            --brand-light: #f472b6;
            --brand-lighter: #fbcfe8;
            --brand-bg: #fdf2f8;
            --accent: #8b5cf6;          /* Violet */
            --sidebar-bg: #fff1f2;      /* Rose 50 */
            --sidebar-border: rgba(244, 114, 182, 0.2);
            --sidebar-active-bg: rgba(236, 72, 153, 0.1);
            --sidebar-active-text: #ec4899;
            --sidebar-badge-bg: var(--brand);
            --sidebar-badge-text: #ffffff;
        }
        body.theme-sakura .sidebar { color: #831843; /* Rose 900 */ }
        body.theme-sakura .sidebar-brand { border-bottom: 1px solid rgba(244,114,182,0.2); }
        body.theme-sakura .nav-link { color: #9f1239; /* Rose 800 */ border-color: rgba(244,114,182,0.1); background: rgba(255,255,255,0.4); }
        body.theme-sakura .nav-link:hover { background: rgba(255,255,255,0.8); color: #831843; }
        body.theme-sakura .nav-link.active { color: #fff; background: var(--brand); border-color: var(--brand); }
        body.theme-sakura .nav-link i { color: #be123c; /* Rose 700 */ }
        body.theme-sakura .nav-link:hover i { color: #831843; }
        body.theme-sakura .nav-link.active i { color: #fff; }
        body.theme-sakura .sidebar-footer { border-top: 1px solid rgba(244,114,182,0.2); }
        body.theme-sakura .nav-section-label { color: #f43f5e; /* Rose 500 */ }
        body.theme-sakura .nav-group { border-color: rgba(244,114,182,0.2); background: rgba(255,255,255,0.3); }
        body.theme-sakura .nav-group-toggle { color: #831843; }
        body.theme-sakura .nav-group-toggle:hover { background: rgba(255,255,255,0.5); color: var(--brand); }
        body.theme-sakura .nav-group-toggle i.group-icon { color: #be123c; }
        body.theme-sakura .nav-group-toggle:hover i.group-icon { color: var(--brand); }
        body.theme-sakura .nav-group-toggle .group-chevron { color: #f472b6; }
        body.theme-sakura .nav-group.open { background: #ffffff; border-color: rgba(236, 72, 153, 0.3); }
        body.theme-sakura .nav-group.open > .nav-group-toggle { color: var(--brand); border-bottom-color: rgba(236, 72, 153, 0.1); }
        body.theme-sakura .nav-group.open > .nav-group-toggle i.group-icon { color: var(--brand); }
        body.theme-sakura .nav-group-children .nav-link::after { background: #fda4af; }
        body.theme-sakura .nav-group-children .nav-link.active { background: rgba(236, 72, 153, 0.15) !important; color: var(--brand) !important; }
        body.theme-sakura .nav-group-children .nav-link.active::after { background: var(--brand); box-shadow: 0 0 6px rgba(236, 72, 153, 0.4); }

        /* ── Main Layout ── */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ── Sidebar ─────────────────────────────────────────── */
        .sidebar-brand {
            height: var(--header-h);
            padding: 0 16px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
            flex-shrink: 0;
            white-space: nowrap;
            position: relative;
        }

        .sidebar-brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: inherit;
            text-decoration: none;
            overflow: hidden;
        }

        .brand-logo {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: .5px;
            transition: opacity 0.2s;
        }

        .sidebar-toggle-float {
            position: absolute;
            right: -12px;
            top: 16px;
            width: 24px;
            height: 24px;
            background: var(--brand);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            z-index: 101;
            transition: transform 0.3s;
            font-size: 16px;
        }
        .sidebar-toggle-float:hover {
            transform: scale(1.1);
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 16px 12px;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 3px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .1);
            border-radius: 3px;
        }

        /* Collapsed Sidebar State Modifier */
        body.collapsed-sidebar .sidebar {
            width: var(--sidebar-collapsed);
        }
        body.collapsed-sidebar .main {
            margin-left: var(--sidebar-collapsed);
        }
        body.collapsed-sidebar .sidebar-brand-text,
        body.collapsed-sidebar .nav-section-label,
        body.collapsed-sidebar .nav-badge,
        body.collapsed-sidebar .group-chevron,
        body.collapsed-sidebar .user-info {
            display: none;
            opacity: 0;
            pointer-events: none;
        }
        
        /* Font-size 0 to hide text nodes effectively */
        body.collapsed-sidebar .nav-link,
        body.collapsed-sidebar .nav-group-toggle {
            font-size: 0;
            white-space: nowrap;
        }
        body.collapsed-sidebar .nav-link i,
        body.collapsed-sidebar .nav-group-toggle i {
            font-size: 18px;
        }
        
        body.collapsed-sidebar .sidebar-toggle-float {
            transform: rotate(180deg);
        }
        body.collapsed-sidebar .sidebar-toggle-float:hover {
            transform: rotate(180deg) scale(1.1);
        }
        body.collapsed-sidebar .sidebar-brand {
            padding: 0;
            justify-content: center;
        }
        body.collapsed-sidebar .brand-logo { margin: 0 auto; width: 32px; height: 32px; }
        
        body.collapsed-sidebar .sidebar-nav {
            padding: 20px 8px;
        }
        body.collapsed-sidebar .nav-link,
        body.collapsed-sidebar .nav-group-toggle {
            justify-content: center;
            padding: 12px 0;
            gap: 0;
        }
        body.collapsed-sidebar .nav-group-children .nav-link {
            padding: 10px 0;
            justify-content: center;
        }
        body.collapsed-sidebar .nav-group-children .nav-link::after { display: none; }
        
        body.collapsed-sidebar .sidebar-footer { padding: 16px 8px; justify-content: center; }
        body.collapsed-sidebar .user-avatar { margin: 0 auto; }

        /* ── Navigation Label ── */
        .nav-section {
            margin-bottom: 2px;
        }

        .nav-section-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            padding: 0 16px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        /* ── Collapsible Group ── */
        .nav-group {
            margin-bottom: 8px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.03);
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .nav-group-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            color: rgba(255, 255, 255, 0.85);
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .nav-group-toggle:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .nav-group-toggle i.group-icon {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.5);
            transition: color 0.2s ease;
        }

        .nav-group-toggle:hover i.group-icon {
            color: rgba(255, 255, 255, 0.9);
        }

        .nav-group-toggle .group-chevron {
            margin-left: auto;
            font-size: 15px;
            transition: transform .25s cubic-bezier(.4, 0, .2, 1);
            color: rgba(255, 255, 255, 0.3);
        }

        .nav-group.open {
            background: rgba(255, 255, 255, 0.08); /* Lighter bg when open */
            border-color: rgba(255, 255, 255, 0.15);
        }

        .nav-group.open > .nav-group-toggle {
            color: #fff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05); /* Separator line */
        }

        .nav-group.open > .nav-group-toggle i.group-icon {
            color: var(--brand-lighter);
        }

        .nav-group.open > .nav-group-toggle .group-chevron {
            transform: rotate(180deg);
            opacity: 1;
        }

        /* ── Group Children ── */
        .nav-group-children {
            max-height: 0;
            overflow: hidden;
            transition: max-height .3s cubic-bezier(.4,0,.2,1);
        }

        .nav-group.open > .nav-group-children {
            max-height: 500px;
        }

        .nav-group-children .nav-link {
            padding: 10px 14px 10px 42px;
            position: relative;
            font-size: 13px;
        }

        .nav-group-children .nav-link::before {
            display: none; /* Hide the vertical active line for children */
        }

        .nav-group-children .nav-link::after {
            content: '';
            position: absolute;
            left: 24px;
            top: 50%;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-50%);
            transition: all .2s;
        }

        .nav-group-children .nav-link:hover::after {
            background: rgba(255, 255, 255, 0.6);
            transform: translateY(-50%) scale(1.5);
        }

        .nav-group-children .nav-link.active {
            background: rgba(255, 255, 255, 0.08) !important; /* Solid block background */
            color: #fff !important;
            font-weight: 600;
            border-radius: 6px; /* Rounded corners */
            margin: 2px 8px; /* Slight margin so the block doesn't touch the edges */
            padding: 8px 6px 8px 34px; /* Adjust padding to compensate for margins */
        }

        .nav-group-children .nav-link.active::after {
            background: #fff; /* Dot is white */
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.8);
            transform: translateY(-50%) scale(1.5);
            left: 16px; /* Adjust left position due to new padding and margins */
        }

        /* ── Single nav link (no group) ── */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.03);
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            transition: all .15s ease;
            margin-bottom: 8px;
            position: relative;
        }

        /* Override for children inside the group so they don't look like boxes */
        .nav-group-children .nav-link {
            border: none;
            background: transparent;
            border-radius: 0;
            margin-bottom: 0;
            color: rgba(255, 255, 255, 0.65);
            font-weight: 500;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            font-weight: 700;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .nav-link i {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.5);
            transition: color 0.15s ease;
        }

        .nav-link:hover i {
            color: rgba(255, 255, 255, 0.9);
        }

        .nav-link.active i {
            color: #fff;
        }

        /* Removed the left border active indicator to match the boxed style */
        .nav-link.active::before {
            display: none;
        }

        .nav-badge {
            margin-left: auto;
            font-size: 10px;
            font-weight: 700;
            background: var(--sidebar-badge-bg, var(--brand));
            color: var(--sidebar-badge-text, #fff);
            padding: 1px 7px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }

        /* (Matcha theme overrides moved to the top specific block) */

        .sidebar-footer {
            padding: 12px 14px;
            border-top: 1px solid rgba(255, 255, 255, .06);
            display: none;
            /* Hidden: use top-right profile dropdown instead */
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: var(--radius-sm);
            transition: background .15s;
        }

        .sidebar-user:hover {
            background: rgba(255, 255, 255, .06);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--sidebar-badge-bg, linear-gradient(135deg, var(--brand), var(--brand-light)));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: var(--sidebar-badge-text, #fff);
            flex-shrink: 0;
        }

        .user-info .user-name {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--slate-200);
        }

        .user-info .user-role {
            font-size: 11px;
            color: var(--slate-500);
        }

        /* ── Header ────────────────────────────────────────── */
        .main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
        }

        .header {
            height: var(--header-h);
            background: var(--bg-header);
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-muted);
            padding: 4px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .breadcrumb a {
            color: var(--text-muted);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: var(--brand);
        }

        .breadcrumb .sep {
            color: var(--slate-300);
        }

        .breadcrumb .current {
            color: var(--text-main);
            font-weight: 600;
        }

        .header-center {
            flex: 1;
            max-width: 420px;
            margin: 0 32px;
        }

        .search-wrap {
            position: relative;
            width: 100%;
        }

        .search-wrap i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: var(--slate-400);
        }

        .search-input {
            width: 100%;
            padding: 7px 12px 7px 36px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-family: inherit;
            background: var(--input-bg);
            color: var(--text-main);
            outline: none;
            transition: all .15s;
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        .search-input:focus {
            border-color: var(--brand-lighter);
            background: var(--bg-card);
            box-shadow: 0 0 0 3px rgba(198, 40, 40, .08);
        }

        .search-kbd {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
            color: var(--slate-400);
            background: var(--slate-100);
            border: 1px solid var(--slate-200);
            padding: 1px 5px;
            border-radius: 3px;
            font-family: inherit;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-btn {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-size: 18px;
            cursor: pointer;
            transition: all .15s;
            position: relative;
        }

        .header-btn:hover {
            background: var(--hover-bg);
            color: var(--text-main);
        }

        .header-btn .dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--danger);
            border: 2px solid #fff;
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 8px 4px 4px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background .15s;
            border: none;
            background: transparent;
            font-family: inherit;
        }

        .user-dropdown:hover {
            background: var(--slate-100);
        }

        .user-dropdown .dd-avatar {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: linear-gradient(135deg, var(--brand), var(--brand-light));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
        }

        .user-dropdown .dd-name {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--slate-700);
        }

        .user-dropdown .dd-chevron {
            font-size: 14px;
            color: var(--slate-400);
        }

        /* Dropdown menu */
        .dropdown-container {
            position: relative;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            min-width: 200px;
            padding: 4px;
            z-index: 200;
        }

        .dropdown-container.open .dropdown-menu {
            display: block;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            color: var(--text-main);
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
            font-family: inherit;
            transition: background .1s;
        }

        .dropdown-item:hover {
            background: var(--hover-bg);
            color: var(--brand); /* highlight */
        }

        .dropdown-item i {
            font-size: 16px;
            width: 18px;
            color: var(--slate-400);
        }

        .dropdown-sep {
            height: 1px;
            background: var(--slate-100);
            margin: 4px 0;
        }

        .dropdown-item.danger {
            color: var(--danger);
        }

        .dropdown-item.danger i {
            color: var(--danger);
        }

        /* ── Content Area ─────────────────────────────────── */
        .content {
            padding: 24px 28px;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -.3px;
            color: var(--text-main);
        }

        .page-header p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-header-actions {
            display: flex;
            gap: 8px;
        }

        /* ── Stat Cards ───────────────────────────────────── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stats-row-5 {
            grid-template-columns: repeat(5, 1fr);
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1px solid var(--border-color);
            transition: all .3s cubic-bezier(.4, 0, .2, 1);
            position: relative;
            overflow: hidden;
            color: var(--text-main);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .stat-top .stat-label {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--slate-500);
        }

        .stat-top .stat-icon-sm {
            width: 30px;
            height: 30px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -.5px;
            line-height: 1;
        }

        .stat-footer {
            font-size: 11.5px;
            color: var(--slate-400);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stat-footer .up {
            color: var(--success);
        }

        .stat-footer .down {
            color: var(--danger);
        }

        /* ── Card ─────────────────────────────────────────── */
        .card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: box-shadow 0.3s ease;
            color: var(--text-main);
        }
        .card:hover {
            box-shadow: 0 8px 16px -4px rgba(0,0,0,0.05);
        }

        .card-head {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-head h3 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
        }

        .card-head .card-actions {
            display: flex;
            gap: 6px;
        }

        .card-body {
            padding: 20px;
        }

        .card-body.flush {
            padding: 0;
        }

        /* ── Table ─────────────────────────────────────────── */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: var(--bg-body);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .7px;
            color: var(--text-muted);
            font-weight: 600;
            text-align: left;
            padding: 6px 12px;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        tbody td {
            padding: 5px 12px;
            font-size: 13px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            color: var(--text-main);
        }

        tfoot td {
            padding: 6px 12px;
            font-size: 13px;
            vertical-align: middle;
            color: var(--text-main);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover td {
            background: var(--hover-bg);
        }

        .cell-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cell-avatar {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .cell-name {
            font-weight: 600;
            color: var(--text-main);
            font-size: 13px;
        }

        .cell-sub {
            font-size: 11.5px;
            color: var(--text-muted);
        }

        /* ── Badge ─────────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-success {
            background: var(--success-bg);
            color: var(--success);
        }

        .badge-warning {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .badge-danger {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .badge-info {
            background: var(--info-bg);
            color: var(--info);
        }

        .badge-neutral {
            background: var(--slate-100);
            color: var(--slate-600);
        }

        .badge-brand {
            background: var(--brand-bg);
            color: var(--brand);
        }

        /* ── Progress ──────────────────────────────────────── */
        .progress {
            height: 6px;
            background: var(--slate-100);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 3px;
            transition: width .6s ease;
        }

        .progress-bar.green {
            background: var(--success);
        }

        .progress-bar.yellow {
            background: var(--warning);
        }

        .progress-bar.red {
            background: var(--danger);
        }

        .progress-bar.brand {
            background: var(--brand);
        }

        /* ── Buttons ───────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--brand);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--brand-light);
            box-shadow: var(--shadow-sm);
        }

        .btn-ghost {
            background: transparent;
            color: var(--slate-600);
        }

        .btn-ghost:hover {
            background: var(--slate-100);
        }

        .btn-outline {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }

        .btn-outline:hover {
            border-color: var(--slate-400);
            background: var(--hover-bg);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-xs {
            padding: 3px 8px;
            font-size: 11px;
        }

        .btn i {
            font-size: 15px;
        }

        .btn-success {
            background: var(--success);
            color: #fff;
        }

        .btn-success:hover {
            background: #059669;
            box-shadow: var(--shadow-sm);
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .btn-danger:hover {
            background: #DC2626;
            box-shadow: var(--shadow-sm);
        }

        /* ── Status Dot ────────────────────────────────────── */
        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-dot.green {
            background: var(--success);
        }

        .status-dot.red {
            background: var(--danger);
        }

        .status-dot.yellow {
            background: var(--warning);
        }

        /* ── Grid layouts ──────────────────────────────────── */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .grid-3-1 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        /* ── Overlay for mobile ────────────────────────────── */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .3);
            z-index: 90;
        }

        /* ═══════════════════════════════════════════════════════
           Responsive Breakpoints
           ═══════════════════════════════════════════════════════ */

        /* ── Layar besar / 100% scaling (≥1600px) ── */
        @media (min-width: 1600px) {
            body {
                font-size: 15px;
            }
            .content {
                padding: 28px 36px;
            }
            .page-header h1 {
                font-size: 24px;
            }
            .stat-value {
                font-size: 28px;
            }
            .stat-card {
                padding: 24px;
            }
            .card-body {
                padding: 22px;
            }
            .card-head {
                padding: 18px 22px;
            }
            .card-head h3 {
                font-size: 15px;
            }
            tbody td {
                padding: 8px 14px;
                font-size: 14px;
            }
            thead th {
                padding: 8px 14px;
                font-size: 12px;
            }
        }

        /* ── Layar sangat besar / Monitor 4K (≥1920px) ── */
        @media (min-width: 1920px) {
            body {
                font-size: 16px;
            }

            .page-header h1 {
                font-size: 26px;
            }
            .stat-value {
                font-size: 30px;
            }
        }

        /* ── Laptop 14" / Monitor kecil (≤1440px) ── */
        @media (max-width: 1440px) {
            .content {
                padding: 20px 24px;
            }
            .page-header h1 {
                font-size: 20px;
            }
            .stat-value {
                font-size: 22px;
            }
            .stat-card {
                padding: 18px;
            }
        }

        /* ── Laptop 14" + 125% scale (≤1280px) ── */
        @media (max-width: 1280px) {
            :root {
                --sidebar-w: 230px;
            }
            .content {
                padding: 18px 20px;
            }
            .header {
                padding: 0 20px;
            }
            .card-body {
                padding: 16px;
            }
            .card-head {
                padding: 12px 16px;
            }
            .stats-row {
                gap: 12px;
            }
            .stats-row-5 {
                grid-template-columns: repeat(3, 1fr);
            }
            .stat-card {
                padding: 16px;
            }
            .stat-value {
                font-size: 20px;
            }
            .page-header h1 {
                font-size: 19px;
            }
        }

        /* ── Tablet / Layar ≤1024px — Sidebar hidden ── */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .overlay.open {
                display: block;
            }
            .main {
                margin-left: 0;
            }
            .btn-menu-toggle {
                display: block;
            }
            .header-center {
                display: none;
            }
            .content {
                padding: 16px;
            }
            .grid-2,
            .grid-3-1 {
                grid-template-columns: 1fr;
            }
            .stats-row,
            .stats-row-5 {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            .stat-value {
                font-size: 20px;
            }
            .stat-card {
                padding: 16px;
            }
            /* Reset sidebar width for mobile overlay */
            .sidebar {
                width: 280px;
            }
        }

        /* ── Mobile (≤768px) ── */
        @media (max-width: 768px) {
            .content {
                padding: 14px;
            }
            .header {
                padding: 0 14px;
                height: 50px;
            }
            .page-header h1 {
                font-size: 18px;
            }
            .card-head h3 {
                font-size: 13px;
            }
            tbody td, thead th {
                padding: 5px 8px;
                font-size: 12px;
            }
            .btn {
                padding: 6px 10px;
                font-size: 12px;
            }
            .page-header-row {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* ── Mobile kecil (≤480px) ── */
        @media (max-width: 480px) {
            .content {
                padding: 12px;
            }
            .header {
                padding: 0 12px;
            }
            .stats-row,
            .stats-row-5 {
                grid-template-columns: 1fr;
            }
            .stat-card {
                padding: 14px;
            }
            .stat-value {
                font-size: 18px;
            }
            .page-header h1 {
                font-size: 17px;
            }
            .breadcrumb {
                font-size: 11px;
            }
            .dd-name {
                display: none;
            }
            .sidebar {
                width: 260px;
            }
        }

        @yield('styles')
    </style>
</head>

<body class="{{ (auth()->check() && auth()->user()->hasRole('personil')) ? 'theme-default' : (empty(auth()->user()->theme) ? 'theme-default' : auth()->user()->theme) }}">
    {{-- ═══ Sidebar ═══ --}}
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="sidebar-brand-link">
                <img src="{{ asset('e-mas-kapor.png') }}" alt="Logo" class="brand-logo">
                <span class="sidebar-brand-text">E-MAS KAPOR</span>
            </a>
            <div class="sidebar-toggle-float" onclick="toggleMinimize()" title="Perbesar/Perkecil Sidebar">
                <i class="ri-arrow-left-s-line"></i>
            </div>
        </div>

        <nav class="sidebar-nav">
            {{-- ── Navigation Label ── --}}
            <div class="nav-section">
                <div class="nav-section-label">Navigation</div>
            </div>

            {{-- Dashboard (standalone) --}}
            <a href="{{ route('dashboard') }}"
                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="ri-dashboard-3-line"></i> Dashboard
            </a>

            {{-- ══ Personil Role ══ --}}
            @if(auth()->user()->hasRole('personil'))
                <a href="{{ route('personil.kapor.index') }}"
                    class="nav-link {{ request()->routeIs('personil.kapor.index') ? 'active' : '' }}">
                    <i class="ri-edit-line"></i> Input Ukuran
                </a>
                <a href="{{ route('personil.kapor.history') }}"
                    class="nav-link {{ request()->routeIs('personil.kapor.history') ? 'active' : '' }}">
                    <i class="ri-history-line"></i> Riwayat
                </a>
            @endif

            {{-- ══ Admin Satker Role ══ --}}
            @if(auth()->user()->hasRole('admin_satker'))
                <div class="nav-group {{ request()->routeIs('admin.personnel.*') || request()->routeIs('admin-satker.*') ? 'open' : '' }}">
                    <button class="nav-group-toggle" onclick="toggleNavGroup(this)">
                        <i class="ri-building-2-line group-icon"></i> Satker Saya
                        <i class="ri-arrow-down-s-line group-chevron"></i>
                    </button>
                    <div class="nav-group-children">
                        <a href="{{ route('admin.personnel.index') }}"
                            class="nav-link {{ request()->routeIs('admin.personnel.*') ? 'active' : '' }}">
                            Data Personel
                        </a>
                        <a href="{{ route('admin-satker.monitor') }}"
                            class="nav-link {{ request()->routeIs('admin-satker.monitor') ? 'active' : '' }}">
                            Monitoring
                        </a>
                        <a href="{{ route('admin-satker.reports') }}"
                            class="nav-link {{ request()->routeIs('admin-satker.reports') ? 'active' : '' }}">
                            Laporan
                        </a>
                        <a href="{{ route('admin-satker.settings') }}"
                            class="nav-link {{ request()->routeIs('admin-satker.settings') ? 'active' : '' }}">
                            Pengaturan
                        </a>
                    </div>
                </div>
            @endif

            {{-- ══ Admin / Superadmin Roles ══ --}}
            @if(auth()->user()->hasAnyRole(['admin', 'superadmin']))
                <div class="nav-group {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.satkers.*') ? 'open' : '' }}">
                    <button class="nav-group-toggle" onclick="toggleNavGroup(this)">
                        <i class="ri-shield-user-line group-icon"></i> Administrasi
                        <i class="ri-arrow-down-s-line group-chevron"></i>
                    </button>
                    <div class="nav-group-children">
                        <a href="{{ route('admin.users.index') }}"
                            class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            Manajemen User
                        </a>
                        <a href="{{ route('admin.satkers.index') }}"
                            class="nav-link {{ request()->routeIs('admin.satkers.*') ? 'active' : '' }}">
                            Data Satker
                        </a>
                    </div>
                </div>

                <div class="nav-group {{ request()->routeIs('admin.personnel.*') || request()->routeIs('admin.kapor-items.*') ? 'open' : '' }}">
                    <button class="nav-group-toggle" onclick="toggleNavGroup(this)">
                        <i class="ri-t-shirt-2-line group-icon"></i> Data Master
                        <i class="ri-arrow-down-s-line group-chevron"></i>
                    </button>
                    <div class="nav-group-children">
                        <a href="{{ route('admin.personnel.index') }}"
                            class="nav-link {{ request()->routeIs('admin.personnel.*') ? 'active' : '' }}">
                            Data Personel
                        </a>
                        <a href="{{ route('admin.kapor-items.index') }}"
                            class="nav-link {{ request()->routeIs('admin.kapor-items.*') ? 'active' : '' }}">
                            Item Kapor
                        </a>
                    </div>
                </div>

                <div class="nav-group {{ request()->routeIs('admin.budget.*') || request()->routeIs('admin.reports*') ? 'open' : '' }}">
                    <button class="nav-group-toggle" onclick="toggleNavGroup(this)">
                        <i class="ri-bar-chart-grouped-line group-icon"></i> Keuangan & Laporan
                        <i class="ri-arrow-down-s-line group-chevron"></i>
                    </button>
                    <div class="nav-group-children">
                        <a href="{{ route('admin.budget.index') }}"
                            class="nav-link {{ request()->routeIs('admin.budget.*') ? 'active' : '' }}">
                            Rencana Anggaran
                        </a>
                        <a href="{{ route('admin.reports') }}"
                            class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                            Laporan
                        </a>
                        <a href="{{ route('admin.audit-logs.index') }}"
                            class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                            Log Audit
                        </a>
                    </div>
                </div>
            @endif

            {{-- ══ Superadmin Only ══ --}}
            @if(auth()->user()->hasRole('superadmin'))
                <div class="nav-group {{ request()->routeIs('superadmin.*') ? 'open' : '' }}">
                    <button class="nav-group-toggle" onclick="toggleNavGroup(this)">
                        <i class="ri-settings-4-line group-icon"></i> Sistem
                        <i class="ri-arrow-down-s-line group-chevron"></i>
                    </button>
                    <div class="nav-group-children">
                        <a href="{{ route('superadmin.settings.index') }}"
                            class="nav-link {{ request()->routeIs('superadmin.settings.*') ? 'active' : '' }}">
                            Pengaturan
                        </a>
                        <a href="{{ route('superadmin.statistics') }}"
                            class="nav-link {{ request()->routeIs('superadmin.statistics') ? 'active' : '' }}">
                            Statistik
                        </a>
                    </div>
                </div>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                <div class="user-info">
                    <div class="user-name">{{ Str::limit(auth()->user()->name, 18) }}</div>
                    <div class="user-role">
                        {{ ucfirst(str_replace('_', ' ', auth()->user()->getRoleNames()->first() ?? 'User')) }}</div>
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
                    <a href="{{ route('dashboard') }}">E-MAS KAPOR</a>
                    <span class="sep">/</span>
                    <span class="current">@yield('breadcrumb', 'Dashboard')</span>
                </div>
            </div>



            <div class="header-right">
                <button class="header-btn" title="Notifikasi">
                    <i class="ri-notification-3-line"></i>
                </button>

                <div class="dropdown-container" id="userDropdown">
                    <button class="user-dropdown"
                        onclick="document.getElementById('userDropdown').classList.toggle('open')">
                        <div class="dd-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                        <span class="dd-name">{{ Str::limit(auth()->user()->name, 14) }}</span>
                        <i class="ri-arrow-down-s-line dd-chevron"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="{{ route('profile') }}" class="dropdown-item"><i class="ri-user-line"></i> Profil
                            Saya</a>
                        <div class="dropdown-sep"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item danger"><i class="ri-logout-box-r-line"></i>
                                Logout</button>
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
            // For Mobile: Slide in/out
            if(window.innerWidth <= 1024) {
                document.getElementById('sidebar').classList.toggle('open');
                document.getElementById('overlay').classList.toggle('open');
            } else {
                // Feature if clicked on desktop
                toggleMinimize();
            }
        }
        
        function toggleMinimize() {
            const body = document.body;
            body.classList.toggle('collapsed-sidebar');
            
            // Simpan preferensi di localStorage
            const isCollapsed = body.classList.contains('collapsed-sidebar');
            localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
            
            if(isCollapsed) {
                // Optional: Close all open nav groups when minifying
                document.querySelectorAll('.nav-group.open').forEach(g => g.classList.remove('open'));
            }
        }

        // Apply saved preference on load
        document.addEventListener('DOMContentLoaded', () => {
            const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
            if (isCollapsed && window.innerWidth > 1024) {
                document.body.classList.add('collapsed-sidebar');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            var dd = document.getElementById('userDropdown');
            if (dd && !dd.contains(e.target)) dd.classList.remove('open');
        });

        // Accordion toggle for sidebar nav groups
        function toggleNavGroup(btn) {
            // If sidebar is collapsed, force expand first before opening accordion
            if (document.body.classList.contains('collapsed-sidebar')) {
                toggleMinimize();
            }
            var group = btn.closest('.nav-group');
            group.classList.toggle('open');
        }
    </script>
    @yield('scripts')
</body>

</html>