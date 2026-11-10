<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard — SIMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #1677f2;
            --primary-light: #429cf5;
            --accent: #655bd8;
        }
        body { font-family:'Inter',sans-serif; background:#f3f5fb; min-height:100vh; color:#17213b; }
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
        .main { padding:2rem 2.25rem 3rem; max-width:1440px; margin:0 auto; }
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
            .main { padding:1.25rem 1rem 2rem; }
        }

        /* ── Flash alert ── */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert svg { width: 16px; height: 16px; }

        /* ── Welcome card ── */
        .dashboard-heading { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
        .dashboard-heading h2 { color:#202d52; font-size:1.5rem; font-weight:750; letter-spacing:-.03em; }
        .dashboard-heading p { margin-top:.35rem; color:#7b8498; font-size:.9rem; }
        .dashboard-actions { display:flex; gap:.65rem; }
        .dashboard-action { display:inline-flex; align-items:center; justify-content:center; min-height:42px; padding:.65rem 1.15rem; border-radius:999px; background:#e5f0ff; color:#2582eb; font-size:.82rem; font-weight:700; text-decoration:none; }
        .dashboard-action.primary { background:#1677f2; color:#fff; }
        .dashboard-action:hover { filter:brightness(.97); }
        .welcome-card { display:none; }
        .welcome-card::after {
            content: '';
            position: absolute;
            right: -40px; top: -40px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
        }
        .welcome-card h2 { font-size: 1.6rem; font-weight: 800; }
        .welcome-card p  { font-size: 0.9rem; opacity: 0.85; margin-top: 0.4rem; }
        .welcome-meta {
            display: flex;
            gap: 1.5rem;
            margin-top: 1.25rem;
        }
        .welcome-meta span {
            font-size: 0.8rem;
            background: rgba(255,255,255,0.15);
            padding: 0.3rem 0.75rem;
            border-radius: 99px;
            backdrop-filter: blur(4px);
        }

        /* ── Stat cards ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 7px 22px rgba(32,45,82,.07);
            display: flex;
            align-items: center;
            gap: 1rem;
            color: inherit;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 5px 16px rgba(0,0,0,0.1); }
        .stat-icon {
            width: 60px; height: 60px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon svg { width: 22px; height: 22px; }
        .stat-info strong { display: block; font-size: 1.4rem; font-weight: 800; color: #202d52; }
        .stat-info span   { font-size: 0.82rem; color: #8992a4; }

        .dashboard-panels { display:grid; grid-template-columns:minmax(260px,.78fr) minmax(0,1.65fr); gap:1.25rem; margin:0 0 2rem; align-items:start; }
        .dashboard-panel { min-width:0; overflow:hidden; background:#fff; border-radius:12px; box-shadow:0 7px 22px rgba(32,45,82,.07); }
        .dashboard-panel-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1.2rem 1.25rem; border-bottom:1px solid #edf0f6; }
        .dashboard-panel-header h3 { color:#202d52; font-size:1.02rem; font-weight:700; }
        .dashboard-panel-header a { color:#1677f2; font-size:.78rem; font-weight:700; text-decoration:none; }
        .recent-students { display:grid; }
        .recent-student { display:flex; align-items:center; gap:.75rem; padding:.82rem 1.25rem; color:inherit; text-decoration:none; transition:background .15s; }
        .recent-student:hover { background:#f8faff; }
        .recent-student-avatar { display:grid; place-items:center; width:42px; height:42px; flex:none; border-radius:50%; background:#e9f2ff; color:#1677f2; font-size:.83rem; font-weight:800; }
        .recent-student-copy { display:grid; min-width:0; gap:.2rem; }
        .recent-student-copy strong { overflow:hidden; color:#202d52; font-size:.82rem; text-overflow:ellipsis; white-space:nowrap; }
        .recent-student-copy span { overflow:hidden; color:#8992a4; font-size:.74rem; text-overflow:ellipsis; white-space:nowrap; }
        .recent-enrollments { width:100%; border-collapse:collapse; }
        .recent-enrollments th { padding:.78rem .9rem; background:#f7f9fc; color:#8992a4; font-size:.68rem; font-weight:700; letter-spacing:.06em; text-align:left; text-transform:uppercase; white-space:nowrap; }
        .recent-enrollments td { padding:.82rem .9rem; border-top:1px solid #edf0f6; color:#46516b; font-size:.77rem; vertical-align:middle; }
        .recent-enrollments td:first-child { color:#202d52; font-weight:650; }
        .recent-enrollments td:first-child a:hover { color:#1677f2 !important; }
        .recent-enrollments .status-pill { display:inline-block; padding:.25rem .55rem; border-radius:999px; font-size:.68rem; font-weight:700; white-space:nowrap; }
        .recent-enrollments .status-enrolled,.recent-enrollments .status-completed { background:#e9f9ee; color:#20a348; }
        .recent-enrollments .status-other { background:#fff0f0; color:#d54b58; }
        .recent-enrollment-empty { padding:2rem 1.25rem; color:#8992a4; font-size:.85rem; text-align:center; }
        .dashboard-table-wrap { overflow-x:auto; }

        /* ── Quick links ── */
        .section-title { font-size: 1rem; font-weight: 700; margin: 2.5rem 0 1rem; color: #374151; }
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .quick-link {
            position: relative;
            overflow: hidden;
            background: #fff;
            border-radius: 12px;
            padding: 1.1rem 1.25rem 1.1rem 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-decoration: none;
            color: #111;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1.5px solid #e5e7eb;
            transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
        }
        .quick-link::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: var(--quick-link-color);
        }
        .quick-link:hover {
            border-color: var(--quick-link-color);
            box-shadow: 0 4px 16px color-mix(in srgb, var(--quick-link-color) 20%, transparent);
            transform: translateY(-2px);
        }
        .quick-link svg { width: 20px; height: 20px; color: var(--quick-link-color); flex-shrink: 0; }
        .quick-link-students { --quick-link-color: #2563eb; }
        .quick-link-subjects { --quick-link-color: #16a34a; }
        .quick-link-enrollments { --quick-link-color: #d97706; }
        .quick-link-reports { --quick-link-color: #9333ea; }
        .quick-link-curricula { --quick-link-color: #0891b2; }
        .quick-link-disabled { color: #6b7280; cursor: default; }
        .quick-link-disabled svg { color: #9ca3af; }
        .quick-link-status { margin-left: auto; font-size: 0.7rem; font-weight: 500; }
        @media(max-width:1000px) { .dashboard-panels { grid-template-columns:1fr; } }
        @media(max-width:600px) {
            .dashboard-heading h2 { font-size:1.3rem; }
            .dashboard-actions { width:100%; }
            .dashboard-action { flex:1; }
            .stat-card { padding:1rem; }
            .stat-icon { width:50px; height:50px; }
            .recent-enrollments { min-width:620px; }
        }
        @media(max-width:760px) {
            .header-date { display:none; }
            .header-name { max-width:110px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        }
    </style>
</head>
<body>

    <div class="app-shell">
        @include('partials.staff-sidebar')
        <div class="workspace">
            <header class="workspace-header">
                <div>
                    <h1>Dashboard</h1>
                    <p>Student Information Management System</p>
                </div>
                <div class="header-user">
                    <span class="header-date">{{ now()->format('D, M j, Y') }}</span>
                    <span class="header-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="header-name">{{ auth()->user()->name }}</span>
                </div>
            </header>
            <main class="main">

        @if (session('success'))
            <div class="alert alert-success">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-5.121-5.121a1 1 0 011.414-1.414L8.414 12.172l6.879-6.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="dashboard-heading">
            <div>
                <h2>Dashboard</h2>
                <p>Student Information Management System overview</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <a href="{{ route('students.index') }}" class="stat-card">
                <div class="stat-icon" style="background:#1976ee;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="stat-info">
                    <strong>{{ $totalStudents }}</strong>
                    <span>Total Students</span>
                </div>
            </a>
            <a href="{{ route('subjects.index') }}" class="stat-card">
                <div class="stat-icon" style="background:#42aaf5;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div class="stat-info">
                    <strong>{{ $totalSubjects }}</strong>
                    <span>Subjects</span>
                </div>
            </a>
            <a href="{{ route('enrollments.index') }}" class="stat-card">
                <div class="stat-icon" style="background:#2dcc46;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="stat-info">
                    <strong>{{ $totalEnrollments }}</strong>
                    <span>Enrollments</span>
                </div>
            </a>
            <a href="{{ route('enrollments.index') }}" class="stat-card">
                <div class="stat-icon" style="background:#665bd8;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-info">
                    <strong>{{ $currentSemesterEnrollments }}</strong>
                    <span>This Semester</span>
                </div>
            </a>
        </div>

        <div class="dashboard-panels">
            <section class="dashboard-panel" aria-labelledby="recent-students-heading">
                <div class="dashboard-panel-header">
                    <h3 id="recent-students-heading">Recently Added Students</h3>
                    <a href="{{ route('students.index') }}">View all</a>
                </div>
                <div class="recent-students">
                    @forelse ($recentStudents as $student)
                        <a class="recent-student" href="{{ route('students.show', $student) }}">
                            <span class="recent-student-avatar">{{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}</span>
                            <span class="recent-student-copy">
                                <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>
                                <span>{{ $student->course }} · {{ $student->year_level }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="recent-enrollment-empty">No students have been added yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="dashboard-panel" aria-labelledby="recent-enrollments-heading">
                <div class="dashboard-panel-header">
                    <h3 id="recent-enrollments-heading">Recent Enrollments</h3>
                    <a href="{{ route('enrollments.index') }}">View all</a>
                </div>
                @if ($recentEnrollments->isEmpty())
                    <p class="recent-enrollment-empty">No enrollments have been recorded yet.</p>
                @else
                    <div class="dashboard-table-wrap">
                        <table class="recent-enrollments">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Term</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentEnrollments as $enrollment)
                                    <tr>
                                        <td><a href="{{ route('enrollments.show', $enrollment) }}" style="color:inherit;text-decoration:none;">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</a></td>
                                        <td>{{ $enrollment->subject->code }}</td>
                                        <td>{{ $enrollment->school_year }} · {{ $enrollment->semester }}</td>
                                        <td><span class="status-pill {{ $enrollment->status === 'enrolled' ? 'status-enrolled' : ($enrollment->status === 'completed' ? 'status-completed' : 'status-other') }}">{{ ucfirst($enrollment->status) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <!-- Quick links -->
        <p class="section-title">Quick Links</p>
        <div class="quick-links">
            <a href="{{ route('students.index') }}" class="quick-link quick-link-students">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Students
            </a>
            <a href="{{ route('subjects.index') }}" class="quick-link quick-link-subjects">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Subjects
            </a>
            <a href="{{ route('enrollments.index') }}" class="quick-link quick-link-enrollments">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Enrollments
            </a>
            <a href="{{ route('reports.index') }}" class="quick-link quick-link-reports">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Reports
            </a>
            @if (auth()->user()->isAdministrator())
                <a href="{{ route('curricula.index') }}" class="quick-link quick-link-curricula">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    Program Curricula
                </a>
            @endif
        </div>

    </div>
        </main>
        </div>
    </div>

</body>
</html>
