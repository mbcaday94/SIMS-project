<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Students') — SIMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:       #1677f2;
            --primary-dark:  #125fca;
            --primary-light: #429cf5;
            --accent:        #655bd8;
            --success:       #16a34a;
            --error:         #dc2626;
            --warning:       #d97706;
            --bg:            #f3f5fb;
            --card:          #ffffff;
            --border:        #e5e7eb;
            --text:          #111827;
            --muted:         #6b7280;
            --input-bg:      #f9fafb;
        }

        body { font-family:'Inter',sans-serif; background:var(--bg); min-height:100vh; color:var(--text); }
        .app-shell { min-height:100vh; }
        .sidebar { position:fixed; inset:0 auto 0 0; z-index:100; width:252px; display:flex; flex-direction:column; padding:1.35rem 1rem 1rem; color:#c8ccda; background:#191f32; box-shadow:8px 0 30px rgba(15,23,42,.1); }
        .sidebar-brand { display:flex; align-items:center; gap:.7rem; height:48px; margin:0 .6rem 2rem; color:#fff; font-size:1.18rem; font-weight:800; letter-spacing:.02em; text-decoration:none; }
        .sidebar-brand svg { width:27px; height:27px; fill:currentColor; }
        .sidebar-section-label { padding:0 .75rem; margin-bottom:.6rem; color:#777f96; font-size:.68rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .sidebar-nav { display:grid; gap:.35rem; }
        .sidebar-link,.sidebar-logout { display:flex; align-items:center; gap:.8rem; width:100%; min-height:46px; padding:.7rem .8rem; border:0; border-radius:8px; color:#c8ccda; background:transparent; font:inherit; font-size:.88rem; font-weight:600; text-align:left; text-decoration:none; transition:background .18s,color .18s; cursor:pointer; }
        .sidebar-link svg,.sidebar-logout svg { width:19px; height:19px; flex:none; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
        .sidebar-link:hover,.sidebar-logout:hover { color:#fff; background:rgba(255,255,255,.07); }
        .sidebar-link.active { color:#fff; background:#272d42; box-shadow:inset 3px 0 #655bd8; }
        .sidebar-user { display:flex; align-items:center; gap:.7rem; margin-top:auto; padding:1rem .35rem .85rem; border-top:1px solid rgba(255,255,255,.1); }
        .sidebar-avatar { display:grid; place-items:center; width:38px; height:38px; flex:none; border-radius:50%; color:#fff; background:#1677f2; border:1px solid rgba(255,255,255,.16); font-size:.85rem; font-weight:800; }
        .sidebar-user-copy { display:grid; min-width:0; gap:.15rem; }
        .sidebar-user-copy strong { overflow:hidden; color:#fff; font-size:.79rem; text-overflow:ellipsis; white-space:nowrap; }
        .sidebar-user-copy span { color:#929ab0; font-size:.73rem; }
        .sidebar-logout-form { margin-top:.15rem; }
        .sidebar-logout { color:#aeb4c5; }
        .workspace { min-height:100vh; margin-left:252px; }
        .workspace-header { display:flex; align-items:center; justify-content:space-between; min-height:76px; padding:1rem 2.25rem; background:#fff; border-bottom:1px solid #edf0f6; }
        .workspace-header h1 { color:#17213b; font-size:1rem; font-weight:700; }
        .workspace-header p { margin-top:.2rem; color:#7b8498; font-size:.76rem; }
        .header-user { display:flex; align-items:center; gap:.65rem; }
        .header-date { color:#7b8498; font-size:.8rem; }
        .header-avatar { display:grid; place-items:center; width:36px; height:36px; border-radius:50%; color:#fff; background:#1677f2; font-size:.8rem; font-weight:800; }
        .header-name { color:#26324e; font-size:.82rem; font-weight:600; }
        .page { width:min(1280px,100%); padding:2rem 2.25rem 3rem; margin:0 auto; }
        .page-landscape { width:100%;max-width:none;padding:1.5rem 1.5rem 2.5rem; }
        @media(max-width:760px) {
            .sidebar { position:static; width:100%; min-height:0; padding:.75rem; }
            .sidebar-brand { height:38px; margin:0 .35rem .7rem; }
            .sidebar-section-label,.sidebar-user { display:none; }
            .sidebar-nav { display:flex; gap:.4rem; overflow-x:auto; padding-bottom:.15rem; }
            .sidebar-link { width:auto; min-width:max-content; min-height:40px; padding:.55rem .7rem; font-size:.78rem; }
            .sidebar-logout-form { position:absolute; top:.75rem; right:.75rem; margin:0; }
            .sidebar-logout { width:auto; min-height:38px; padding:.5rem .65rem; font-size:.77rem; }
            .sidebar-logout svg { width:16px; height:16px; }
            .workspace { margin-left:0; }
            .workspace-header { min-height:60px; padding:.8rem 1rem; }
            .header-date { display:none; }
            .header-name { max-width:110px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
            .page { padding:1.25rem 1rem 2rem; }
            .page-landscape { padding:1rem .75rem 2rem; }
        }

        /* ── Page header ── */
        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem;
        }
        .page-header h1 { font-size: 1.4rem; font-weight: 800; }
        .breadcrumb { font-size: 0.8rem; color: var(--muted); margin-top: 0.2rem; }
        .breadcrumb a { color: var(--primary); text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }

        /* ── Buttons ── */
        .btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.55rem 1.15rem; font-size: 0.875rem; font-weight: 600;
            font-family: inherit; border-radius: 9px; border: none;
            cursor: pointer; text-decoration: none; transition: opacity 0.2s, transform 0.1s;
        }
        .btn:active { transform: scale(0.98); }
        .btn svg { width: 16px; height: 16px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-secondary { background: #fff; color: var(--text); border: 1.5px solid var(--border); }
        .btn-secondary:hover { border-color: #9ca3af; }
        .btn-danger { background: #fee2e2; color: var(--error); border: 1.5px solid #fecaca; }
        .btn-danger:hover { background: #fecaca; }
        .btn-warning { background: #fffbeb; color: var(--warning); border: 1.5px solid #fde68a; }
        .btn-warning:hover { background: #fde68a; }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }

        /* ── Flash alert ── */
        .alert {
            padding: 0.8rem 1rem; border-radius: 10px; font-size: 0.875rem;
            display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;
        }
        .alert svg { width: 16px; height: 16px; flex-shrink: 0; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* ── Card ── */
        .card {
            background: var(--card); border-radius: 12px;
            box-shadow: 0 7px 22px rgba(32,45,82,.07);
            overflow: hidden;
        }

        /* ── Search bar ── */
        .toolbar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap;
        }
        .search-wrap { position: relative; flex: 1; min-width: 200px; max-width: 340px; }
        .search-wrap svg {
            position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%);
            width: 16px; height: 16px; color: var(--muted); pointer-events: none;
        }
        .search-input {
            width: 100%; padding: 0.55rem 0.9rem 0.55rem 2.4rem;
            font-size: 0.875rem; font-family: inherit;
            background: #fff; border: 1.5px solid var(--border);
            border-radius: 9px; outline: none; color: var(--text);
            transition: border-color 0.2s;
        }
        .search-input:focus { border-color: var(--primary-light); }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8fafc; }
        th {
            padding: 0.8rem 1rem; text-align: left;
            font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: var(--muted); border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        td {
            padding: 0.9rem 1rem; font-size: 0.875rem;
            border-bottom: 1px solid #f3f4f6; vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #fafbff; }

        /* ── Badge ── */
        .badge {
            display: inline-block; padding: 0.2rem 0.6rem;
            font-size: 0.72rem; font-weight: 600; border-radius: 99px;
        }
        .badge-blue { background: #eff6ff; color: #1d4ed8; }

        /* ── Empty state ── */
        .empty {
            text-align: center; padding: 4rem 1rem; color: var(--muted);
        }
        .empty svg { width: 48px; height: 48px; margin: 0 auto 1rem; display: block; opacity: 0.4; }
        .empty p { font-size: 0.95rem; }

        /* ── Pagination ── */
        .pagination-wrap {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem; border-top: 1px solid var(--border);
            font-size: 0.8rem; color: var(--muted); flex-wrap: wrap; gap: 0.5rem;
        }
        .pagination-wrap nav { display: flex; margin-left: auto; }
        .pagination-wrap nav ul.pagination {
            display: flex; align-items: center; gap: 0.45rem;
            padding: 0; margin: 0; list-style: none;
        }
        .pagination-wrap nav .page-item { display: block; margin: 0; }
        .pagination-wrap nav .page-link {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 38px; padding: 0.45rem 0.8rem;
            border: 1px solid var(--border); border-radius: 8px;
            background: #fff; color: var(--text); font-size: 0.8rem;
            font-weight: 600; line-height: 1.2; text-decoration: none;
            white-space: nowrap; transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .pagination-wrap nav a.page-link:hover {
            border-color: #bfdbfe; background: #eff6ff; color: var(--primary);
        }
        .pagination-wrap nav .page-item.disabled .page-link {
            border-color: #e5e7eb; background: #f9fafb; color: #9ca3af;
            cursor: not-allowed;
        }

        /* ── Form ── */
        .form-card { background: var(--card); border-radius: 12px; padding: 2rem; box-shadow: 0 7px 22px rgba(32,45,82,.07); max-width: 760px; border: 1px solid rgba(226,232,240,0.8); }
        .student-form-card { padding: 2rem; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 1.25rem; }
        @media (max-width: 540px) { .form-grid { grid-template-columns: 1fr; } .form-card, .student-form-card { padding: 1.25rem; } }
        .form-group { margin-bottom: 1.25rem; min-width: 0; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label {
            display: block; font-size: 0.82rem; font-weight: 600;
            color: var(--text); margin-bottom: 0.4rem;
        }
        .form-group label .req { color: var(--error); margin-left: 2px; }
        .input-wrap { position: relative; }
        .input-wrap .icon {
            position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%);
            color: var(--muted); pointer-events: none;
        }
        .input-wrap .icon svg { width: 16px; height: 16px; }
        .form-control {
            width: 100%; min-height: 44px; padding: 0.65rem 0.9rem;
            font-size: 0.9rem; font-family: inherit;
            background: var(--input-bg); border: 1.5px solid var(--border);
            border-radius: 11px; color: var(--text); outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .form-control:focus { border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(59,130,246,0.12); background: #fff; }
        .form-control.is-invalid { border-color: var(--error); box-shadow: 0 0 0 3px rgba(220,38,38,0.08); }
        .error-msg { font-size: 0.78rem; color: var(--error); margin-top: 0.3rem; display: flex; align-items: center; gap: 0.3rem; }
        .error-msg svg { width: 13px; height: 13px; flex-shrink: 0; }
        .form-actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; flex-wrap: wrap; }
        .student-form-intro { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding-bottom:1.35rem; margin-bottom:1.5rem; border-bottom:1px solid #eef2f7; }
        .photo-upload { display:flex; align-items:center; gap:1rem; min-width:0; }
        .photo-preview { width:82px; height:82px; border-radius:50%; overflow:hidden; flex:0 0 auto; display:grid; place-items:center; color:#94a3b8; background:linear-gradient(145deg,#eff6ff,#f8fafc); border:3px solid #fff; box-shadow:0 0 0 1px #dbeafe, 0 4px 12px rgba(30,64,175,.1); }
        .photo-preview svg { width:34px; height:34px; }
        .photo-preview img { width:100%; height:100%; object-fit:cover; }
        .photo-copy { display:grid; gap:.3rem; }
        .photo-copy strong { color:#1f2937; font-size:.9rem; }
        .photo-copy > span { color:var(--muted); font-size:.76rem; }
        .photo-select { justify-self:start; margin-top:.2rem; padding:.45rem .8rem; border:1px solid #bfdbfe; border-radius:8px; background:#eff6ff; color:#1d4ed8; font-size:.78rem; font-weight:700; cursor:pointer; }
        .photo-select:hover { background:#dbeafe; }
        .photo-file-name { max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .photo-input { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; clip-path:inset(50%); }
        .photo-input:focus-visible { clip:auto; clip-path:none; width:auto; height:auto; position:static; }
        .form-required-note { color:var(--muted); font-size:.76rem; }
        .student-detail-photo { margin-bottom:1.5rem; }
        .student-detail-photo img { width:120px; height:120px; object-fit:cover; border-radius:16px; box-shadow:0 4px 14px rgba(15,23,42,.12); }

        /* ── Detail view ── */
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        @media (max-width: 540px) { .detail-grid { grid-template-columns: 1fr; } }
        .detail-item label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); display: block; margin-bottom: 0.3rem; }
        .detail-item p { font-size: 0.95rem; font-weight: 500; }

        @media (max-width:540px) { .page-header h1 { font-size:1.2rem; } }
    </style>
</head>
<body>

    <div class="app-shell">
        @include('partials.staff-sidebar')
        <div class="workspace">
            <header class="workspace-header">
                <div>
                    <h1>@yield('title', 'SIMS')</h1>
                    <p>Student Information Management System</p>
                </div>
                <div class="header-user">
                    <span class="header-date">{{ now()->format('D, M j, Y') }}</span>
                    <span class="header-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="header-name">{{ auth()->user()->name }}</span>
                </div>
            </header>
            <main class="page @yield('page-class')">
        @if (session('success'))
            <div class="alert alert-success">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-5.121-5.121a1 1 0 011.414-1.414L8.414 12.172l6.879-6.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
            </main>
        </div>
    </div>

</body>
</html>
