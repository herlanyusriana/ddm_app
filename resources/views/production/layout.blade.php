<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="DDM Admin">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>{{ $title ?? 'DDM Production' }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/pwa-icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/pwa-icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ── TOKENS ── */
        :root {
            --bg: #f0f2f5;
            --panel: #ffffff;
            --ink: #0f172a;
            --ink2: #334155;
            --muted: #64748b;
            --line: #e2e8f0;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-soft: #eff6ff;
            --success: #16a34a;
            --success-soft: #f0fdf4;
            --warning: #d97706;
            --warning-soft: #fffbeb;
            --danger: #dc2626;
            --danger-soft: #fef2f2;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #2563eb;
            --sidebar-muted: #94a3b8;
            --sidebar-text: #cbd5e1;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 10px 30px rgba(0,0,0,.08);
        }

        /* ── RESET ── */
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: 'Inter', sans-serif; font-size: 14px; line-height: 1.5; }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }
        h1, h2, h3 { margin: 0; }
        p { margin: 0; }

        /* ── LAYOUT ── */
        .shell { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        .mobile-bar, .sidebar-backdrop { display: none; }

        /* ── SIDEBAR ── */
        .sidebar {
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            padding: 0;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 24px 20px 20px;
            border-bottom: 1px solid #1e293b;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .brand-text {
            font-size: 16px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.01em;
        }

        .brand-sub {
            color: var(--sidebar-muted);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-left: 46px;
        }

        .sidebar-nav {
            padding: 16px 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .nav-group {
            margin-bottom: 8px;
        }

        .nav-group-title {
            color: #475569;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 10px 10px 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: var(--radius-sm);
            color: var(--sidebar-text);
            font-weight: 500;
            font-size: 13.5px;
            transition: background .15s, color .15s;
        }

        .nav-link:hover { background: var(--sidebar-hover); color: #fff; }
        .nav-link.active { background: var(--sidebar-active); color: #fff; font-weight: 700; }

        .nav-link .icon {
            width: 20px;
            text-align: center;
            font-size: 15px;
            opacity: .85;
            flex-shrink: 0;
        }

        .nav-link .badge {
            margin-left: auto;
            background: rgba(255,255,255,.12);
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 7px;
        }

        .nav-link.active .badge { background: rgba(255,255,255,.25); }

        .nav-divider { height: 1px; background: #1e293b; margin: 8px 0; }

        /* ── MAIN ── */
        .main { display: flex; flex-direction: column; min-height: 100vh; }

        .topbar {
            background: var(--panel);
            border-bottom: 1px solid var(--line);
            padding: 20px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .topbar-left { display: flex; flex-direction: column; gap: 2px; }
        .topbar-left h1 { font-size: 20px; font-weight: 800; letter-spacing: -.02em; }
        .topbar-left p { color: var(--muted); font-size: 13px; font-weight: 500; }

        .topbar-right { display: flex; align-items: center; gap: 10px; }

        .page-content { padding: 24px 28px; flex: 1; }

        /* ── ALERTS ── */
        .alert { border-radius: var(--radius-sm); font-weight: 600; font-size: 13px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: var(--success-soft); border: 1px solid #bbf7d0; color: #15803d; }
        .alert-error { background: var(--danger-soft); border: 1px solid #fecaca; color: #b91c1c; }

        /* ── PANELS ── */
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius); padding: 0; box-shadow: var(--shadow); overflow: hidden; }
        .panel-header { padding: 18px 20px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .panel-header h2 { font-size: 15px; font-weight: 700; color: var(--ink); }
        .panel-body { padding: 20px; }
        .panel-body.no-pad { padding: 0; }

        /* ── GRID ── */
        .grid { display: grid; gap: 20px; }
        .grid-2 { grid-template-columns: 1fr 1fr; }
        .grid-3 { grid-template-columns: 1fr 1fr 1fr; }
        .grid-4 { grid-template-columns: repeat(4,1fr); }

        /* ── STAT CARDS ── */
        .stat { background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); }
        .stat-label { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; margin-bottom: 8px; }
        .stat-value { font-size: 32px; font-weight: 800; letter-spacing: -.03em; line-height: 1; margin-bottom: 4px; }
        .stat-sub { color: var(--muted); font-size: 12px; font-weight: 500; }

        /* ── FORM ── */
        .form-grid { display: grid; gap: 16px; }
        .form-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; }
        .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
        .col-span-2 { grid-column: span 2; }
        .col-span-3 { grid-column: span 3; }
        .col-span-4 { grid-column: span 4; }

        .field { display: flex; flex-direction: column; gap: 6px; }
        .field label { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
        .field input, .field select, .field textarea {
            background: var(--bg);
            border: 1.5px solid var(--line);
            border-radius: var(--radius-sm);
            color: var(--ink);
            font-size: 14px;
            font-weight: 500;
            min-height: 42px;
            padding: 10px 12px;
            transition: border-color .15s, box-shadow .15s;
            width: 100%;
        }
        .field input:focus, .field select:focus, .field textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
            outline: none;
            background: #fff;
        }

        /* ── PROCESS PICKER ── */
        .processes { display: grid; gap: 8px; grid-template-columns: repeat(5,1fr); }
        .process-label {
            border: 1.5px solid var(--line);
            border-radius: var(--radius-sm);
            cursor: pointer;
            display: grid;
            min-height: 52px;
            place-items: center;
            text-align: center;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ink2);
            padding: 8px;
            transition: all .15s;
            background: var(--bg);
        }
        .process-label:hover { border-color: var(--primary); background: var(--primary-soft); }
        .process-label input { display: none; }
        .process-label:has(input:checked) { background: var(--primary-soft); border-color: var(--primary); color: var(--primary-dark); font-weight: 700; }

        /* ── QTY INPUTS ── */
        .qty-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-top: 4px; }
        .qty-box { border-radius: var(--radius-sm); padding: 14px 16px; }
        .qty-box.good { background: var(--success-soft); border: 1.5px solid #bbf7d0; }
        .qty-box.rework { background: var(--warning-soft); border: 1.5px solid #fde68a; }
        .qty-box.scrap { background: var(--danger-soft); border: 1.5px solid #fecaca; }
        .qty-box label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; display: block; margin-bottom: 6px; }
        .qty-box.good label { color: var(--success); }
        .qty-box.rework label { color: var(--warning); }
        .qty-box.scrap label { color: var(--danger); }
        .qty-box input { background: rgba(255,255,255,.7); border: 1.5px solid transparent; border-radius: 8px; font-size: 28px; font-weight: 800; min-height: 64px; text-align: center; width: 100%; }
        .qty-box.good input { color: var(--success); }
        .qty-box.rework input { color: var(--warning); }
        .qty-box.scrap input { color: var(--danger); }
        .qty-box input:focus { outline: none; border-color: currentColor; }

        /* ── BUTTONS ── */
        .btn { border: 0; border-radius: var(--radius-sm); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 700; font-size: 13.5px; min-height: 40px; padding: 0 18px; transition: all .15s; }
        .btn:hover { opacity: .9; transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 2px 8px rgba(37,99,235,.3); }
        .btn-secondary { background: var(--bg); color: var(--ink2); border: 1.5px solid var(--line); }
        .btn-success { background: var(--success); color: #fff; box-shadow: 0 2px 8px rgba(22,163,74,.3); }
        .btn-danger { background: #fee2e2; color: var(--danger); }
        .btn-warning { background: var(--warning-soft); color: var(--warning); border: 1px solid #fde68a; }
        .btn-ghost { background: transparent; color: var(--primary); }
        .btn-sm { min-height: 32px; font-size: 12px; padding: 0 12px; }
        .btn-lg { min-height: 48px; font-size: 15px; padding: 0 24px; }
        .btn-full { width: 100%; }

        .link-btn { border-radius: var(--radius-sm); display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 700; font-size: 13px; min-height: 36px; padding: 0 14px; transition: all .15s; }
        .link-btn:hover { opacity: .9; }
        .link-btn-primary { background: var(--primary); color: #fff; }
        .link-btn-secondary { background: var(--bg); color: var(--ink2); border: 1px solid var(--line); }
        .link-btn-success { background: var(--success); color: #fff; }
        .link-btn-danger { background: #fee2e2; color: var(--danger); }

        /* ── BADGES / PILLS ── */
        .badge { display: inline-flex; align-items: center; border-radius: 999px; font-size: 11px; font-weight: 700; padding: 3px 10px; }
        .badge-primary { background: var(--primary-soft); color: var(--primary-dark); }
        .badge-success { background: var(--success-soft); color: var(--success); }
        .badge-warning { background: var(--warning-soft); color: var(--warning); }
        .badge-danger { background: var(--danger-soft); color: var(--danger); }
        .badge-neutral { background: #f1f5f9; color: var(--muted); }

        /* ── TABLE ── */
        .table-wrap { overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        thead th { background: #f8fafc; border-bottom: 2px solid var(--line); color: var(--muted); font-size: 11px; font-weight: 700; letter-spacing: .06em; padding: 12px 16px; text-align: left; text-transform: uppercase; white-space: nowrap; }
        tbody td { border-bottom: 1px solid var(--line); font-size: 13.5px; font-weight: 500; padding: 13px 16px; vertical-align: middle; white-space: nowrap; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover td { background: #f8fafc; }
        .td-num { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }
        .td-actions { display: flex; gap: 6px; }

        /* ── FILTER BAR ── */
        .filter-bar { background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius-sm); display: flex; align-items: center; gap: 10px; padding: 12px 16px; margin-bottom: 20px; }
        .filter-bar input, .filter-bar select { background: var(--bg); border: 1.5px solid var(--line); border-radius: 7px; min-height: 38px; padding: 8px 12px; font-size: 13.5px; font-weight: 500; }
        .filter-bar input:focus, .filter-bar select:focus { border-color: var(--primary); outline: none; }
        .filter-spacer { flex: 1; }

        /* ── DETAIL ── */
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .detail-item { display: flex; flex-direction: column; gap: 4px; padding: 12px 0; border-bottom: 1px solid var(--line); }
        .detail-item:last-child { border-bottom: 0; }
        .detail-label { color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; }
        .detail-value { color: var(--ink); font-size: 15px; font-weight: 600; }

        /* ── MISC ── */
        .empty-state { padding: 48px 24px; text-align: center; color: var(--muted); }
        .empty-state .empty-icon { font-size: 40px; margin-bottom: 12px; }
        .empty-state p { font-weight: 600; }
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .section-title { font-size: 18px; font-weight: 800; letter-spacing: -.02em; }
        .divider { height: 1px; background: var(--line); margin: 20px 0; }
        .text-muted { color: var(--muted); }
        .text-sm { font-size: 12px; }
        .font-bold { font-weight: 700; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }
        .gap-2 { gap: 8px; }
        .flex { display: flex; align-items: center; }

        @media (max-width:1100px) {
            .shell { grid-template-columns: 220px 1fr; }
            .form-row, .form-row-3 { grid-template-columns: 1fr 1fr; }
            .grid-4 { grid-template-columns: 1fr 1fr; }
            .processes { grid-template-columns: repeat(3,1fr); }
            .qty-grid { grid-template-columns: 1fr 1fr 1fr; }
        }

        @media (max-width:760px) {
            body.menu-open { overflow: hidden; }
            .shell { display: block; min-height: 100vh; }
            .mobile-bar {
                align-items: center;
                background: var(--sidebar-bg);
                border-bottom: 1px solid #1e293b;
                color: #fff;
                display: flex;
                gap: 10px;
                height: 56px;
                justify-content: space-between;
                padding: 0 12px;
                position: sticky;
                top: 0;
                z-index: 50;
            }
            .mobile-brand { display: flex; flex-direction: column; line-height: 1.2; min-width: 0; }
            .mobile-brand strong { font-size: 14px; font-weight: 850; }
            .mobile-brand span { color: var(--sidebar-muted); font-size: 10px; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
            .menu-button {
                align-items: center;
                background: #1e293b;
                border: 1px solid #334155;
                border-radius: 8px;
                color: #fff;
                cursor: pointer;
                display: inline-flex;
                font-size: 20px;
                height: 38px;
                justify-content: center;
                width: 42px;
            }
            .sidebar {
                box-shadow: 12px 0 30px rgba(15,23,42,.28);
                height: 100vh;
                left: 0;
                max-width: 82vw;
                position: fixed;
                top: 0;
                transform: translateX(-105%);
                transition: transform .2s ease;
                width: 280px;
                z-index: 70;
            }
            body.menu-open .sidebar { transform: translateX(0); }
            .sidebar-backdrop {
                background: rgba(15,23,42,.5);
                inset: 0;
                position: fixed;
                z-index: 60;
            }
            body.menu-open .sidebar-backdrop { display: block; }
            .topbar {
                align-items: stretch;
                flex-direction: column;
                gap: 12px;
                padding: 14px;
                position: static;
            }
            .topbar-left h1 { font-size: 18px; }
            .topbar-left p { font-size: 12px; }
            .topbar-right { align-items: stretch; flex-wrap: wrap; width: 100%; }
            .topbar-right .filter-bar {
                display: grid !important;
                grid-template-columns: 1fr 96px 72px;
                width: 100%;
            }
            .page-content { padding: 12px; }
            .panel { border-radius: 10px; }
            .panel-header { padding: 13px 14px; }
            .panel-body { padding: 14px; }
            .grid, .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr !important; gap: 12px; }
            .form-row, .form-row-2, .form-row-3 { grid-template-columns: 1fr; }
            .processes { grid-template-columns: 1fr 1fr !important; }
            .process-label { min-height: 48px; font-size: 12px; }
            .qty-grid { gap: 8px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .qty-box { padding: 10px 8px; }
            .qty-box label { font-size: 9px; margin-bottom: 4px; }
            .qty-box input { font-size: 24px; min-height: 54px; }
            .field input, .field select, .field textarea { min-height: 40px; }
            thead th { font-size: 10px; padding: 10px 12px; }
            tbody td { font-size: 12px; padding: 11px 12px; }
            .empty-state { padding: 32px 14px; }
        }
    </style>
</head>
<body>
<div class="mobile-bar">
    <button class="menu-button" type="button" data-menu-toggle aria-label="Buka menu">☰</button>
    <div class="mobile-brand">
        <strong>DDM Production</strong>
        <span>Admin Panel</span>
    </div>
    <span style="width:42px"></span>
</div>
<div class="sidebar-backdrop" data-menu-close></div>
<div class="shell">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <div class="brand-icon">🏭</div>
                <span class="brand-text">DDM Production</span>
            </div>
            <div class="brand-sub">Admin Panel</div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-group">
                <div class="nav-group-title">Operasional</div>
                <a class="nav-link {{ request()->is('spk') || request()->is('spk/create') || request()->is('spk/*') ? 'active' : '' }}" href="/spk">
                    <span class="icon">📋</span> SPK (PPIC)
                </a>
                <a class="nav-link {{ request()->is('warehouse*') ? 'active' : '' }}" href="/warehouse">
                    <span class="icon">🏪</span> Warehouse
                </a>
            </div>

            <div class="nav-divider"></div>

            <div class="nav-group">
                <div class="nav-group-title">Input Produksi</div>
                <a class="nav-link {{ request()->is('input-proses') ? 'active' : '' }}" href="/input-proses">
                    <span class="icon">⚙️</span> Input Proses (WIP)
                </a>
                <a class="nav-link {{ request()->is('input-hasil') ? 'active' : '' }}" href="/input-hasil">
                    <span class="icon">✅</span> Input Hasil (FG)
                </a>
                <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="/dashboard">
                    <span class="icon">📊</span> Dashboard
                </a>
            </div>

            <div class="nav-divider"></div>

            <div class="nav-group">
                <div class="nav-group-title">Master Data</div>
                <a class="nav-link {{ request()->is('masters/buyers') || request()->is('masters/buyers/create') ? 'active' : '' }}" href="/masters/buyers">
                    <span class="icon">👤</span> Buyer Master
                </a>
                <a class="nav-link {{ request()->is('masters/parts*') ? 'active' : '' }}" href="/masters/parts">
                    <span class="icon">📦</span> Part Master
                </a>
                <a class="nav-link {{ request()->is('masters/sizes*') ? 'active' : '' }}" href="/masters/sizes">
                    <span class="icon">📐</span> Size Master
                </a>
                <a class="nav-link {{ request()->is('masters/processes*') ? 'active' : '' }}" href="/masters/processes">
                    <span class="icon">🔄</span> Process Master
                </a>
            </div>

            <div class="nav-divider"></div>

            <div class="nav-group">
                <div class="nav-group-title">Report</div>
                <a class="nav-link {{ request()->is('reports/fg*') ? 'active' : '' }}" href="/reports/fg">
                    <span class="icon">📄</span> Report FG
                </a>
            </div>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>{{ $title ?? 'DDM Production' }}</h1>
                @isset($subtitle)<p>{{ $subtitle }}</p>@endisset
            </div>
            <div class="topbar-right">
                @yield('topbar-actions')
            </div>
        </div>

        <div class="page-content">
            @if(session('status'))
                <div class="alert alert-success">✅ {{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">⚠️ @foreach($errors->all() as $e){{ $e }} @endforeach</div>
            @endif

            @yield('content')
        </div>
    </main>
</div>
<script>
    document.querySelectorAll('[data-menu-toggle]').forEach((button) => {
        button.addEventListener('click', () => document.body.classList.toggle('menu-open'));
    });
    document.querySelectorAll('[data-menu-close], .sidebar .nav-link').forEach((target) => {
        target.addEventListener('click', () => document.body.classList.remove('menu-open'));
    });

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js').catch(() => {});
        });
    }
</script>
</body>
</html>
