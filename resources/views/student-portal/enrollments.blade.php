<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Enrolled Subjects — SIMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:#f3f5fb; color:#17213b; font-family:Inter,Arial,sans-serif; }
        .app-shell { min-height:100vh; }
        .sidebar { position:fixed; inset:0 auto 0 0; z-index:100; width:252px; display:flex; flex-direction:column; padding:1.35rem 1rem 1rem; color:#c8ccda; background:#191f32; box-shadow:8px 0 30px rgba(15,23,42,.1); }
        .brand { display:flex; align-items:center; gap:.65rem; height:48px; margin:0 .6rem 2rem; color:#fff; font-weight:800; font-size:1.1rem; text-decoration:none; }
        .brand svg { width:26px;height:26px;fill:currentColor; }
        .sidebar-section-label { padding:0 .75rem; margin-bottom:.6rem; color:#777f96; font-size:.68rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .sidebar-link,.logout { display:flex; align-items:center; gap:.8rem; width:100%; min-height:46px; padding:.7rem .8rem; border:0; border-radius:8px; color:#fff; background:#272d42; box-shadow:inset 3px 0 #655bd8; font:inherit; font-size:.88rem; font-weight:600; text-align:left; text-decoration:none; }
        .sidebar-link svg,.logout svg { width:19px;height:19px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round; }
        .sidebar-user { display:flex; align-items:center; gap:.7rem; margin-top:auto; padding:1rem .35rem .85rem; border-top:1px solid rgba(255,255,255,.1); color:#fff; font-size:.82rem; }
        .sidebar-avatar { display:grid;place-items:center;width:38px;height:38px;flex:none;border-radius:50%;background:#1677f2;border:1px solid rgba(255,255,255,.16);font-weight:800; }
        .sidebar-user span { display:block;margin-top:.18rem;color:#929ab0;font-size:.73rem; }
        .logout { margin-top:.15rem; color:#aeb4c5; background:transparent; box-shadow:none; cursor:pointer; }
        .logout:hover { background:rgba(255,255,255,.07); }
        .workspace { min-height:100vh; margin-left:252px; }
        .workspace-header { display:flex;align-items:center;justify-content:space-between;min-height:76px;padding:1rem 2.25rem;background:#fff;border-bottom:1px solid #edf0f6; }
        .workspace-header h1 { color:#17213b;font-size:1rem;font-weight:700; }
        .workspace-header p { margin-top:.2rem;color:#7b8498;font-size:.76rem; }
        main { max-width:1320px; margin:0 auto; padding:2rem 2.25rem 3rem; }
        .welcome { padding:1.75rem 2rem; margin-bottom:1.5rem; border-radius:12px; color:#fff; background:linear-gradient(110deg,#1677f2,#429cf5); box-shadow:0 8px 20px rgba(22,119,242,.15); }
        .welcome h1 { margin:0; font-size:1.55rem; }
        .welcome p { margin:.5rem 0 0; opacity:.9; font-size:.9rem; }
        .flash { padding:.85rem 1rem; margin-bottom:1rem; background:#dcfce7; color:#166534; border-radius:10px; }
        .section-head { display:flex; align-items:end; justify-content:space-between; gap:1rem; margin:1.75rem 0 1rem; }
        .section-head h2 { margin:0; font-size:1.15rem; }
        .enrollment-filters { display:grid;grid-template-columns:minmax(220px,1fr) repeat(2,minmax(150px,.45fr)) auto auto;align-items:end;gap:.75rem;padding:1rem;margin-bottom:.9rem;border:1px solid #e5e7eb;border-radius:12px;background:#fff;box-shadow:0 4px 14px rgba(32,45,82,.04); }
        .filter-field label { display:block;margin-bottom:.35rem;color:#374151;font-size:.75rem;font-weight:700; }
        .filter-field input,.filter-field select { width:100%;min-height:42px;padding:.55rem .7rem;border:1px solid #dfe5ef;border-radius:8px;background:#fff;color:#17213b;font:inherit;font-size:.82rem; }
        .filter-button,.filter-reset { display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:.55rem .9rem;border:1px solid #1677f2;border-radius:8px;background:#1677f2;color:#fff;font:inherit;font-size:.82rem;font-weight:700;text-decoration:none;cursor:pointer; }
        .filter-reset { border-color:#e5e7eb;background:#fff;color:#374151; }
        .filter-button:hover { background:#1266d0; }
        .filter-reset:hover { background:#f8fafc; }
        .section-head span { color:#6b7280; font-size:.85rem; }
        .table-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 7px 22px rgba(32,45,82,.07); overflow:hidden; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; text-align:left; }
        th,td { padding:.95rem 1rem; border-bottom:1px solid #eef2f7; font-size:.875rem; }
        th { background:#f8fafc; color:#6b7280; font-size:.73rem; text-transform:uppercase; letter-spacing:.04em; }
        tbody tr:last-child td { border-bottom:0; }
        .muted { display:block; margin-top:.2rem; color:#6b7280; font-size:.78rem; }
        .status { display:inline-block; padding:.25rem .65rem; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:.75rem; font-weight:700; }
        .schedule-detail { display:grid;gap:.3rem;min-width:170px; }
        .schedule-slot { display:grid;gap:.12rem;padding:.45rem .55rem;border:1px solid #e5e7eb;border-radius:8px;background:#f8fafc; }
        .schedule-slot strong { color:#172554;font-size:.78rem; }
        .schedule-slot span,.schedule-unavailable { color:#64748b;font-size:.72rem; }
        .faculty-detail { min-width:140px;font-weight:600; }
        .empty { padding:3rem 1.25rem; text-align:center; color:#6b7280; }
        .pagination { display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem;border-top:1px solid #eef2f7;color:#6b7280;font-size:.8rem; }
        .pagination nav { display:flex;justify-content:flex-end;margin-left:auto; }
        .pagination nav ul.pagination { display:flex;align-items:center;gap:.4rem;padding:0;margin:0;list-style:none; }
        .pagination nav .page-item { display:block;margin:0; }
        .pagination nav .page-link { display:inline-flex;align-items:center;justify-content:center;min-width:36px;min-height:36px;padding:.4rem .65rem;border:1px solid #e5e7eb;border-radius:8px;background:#fff;color:#17213b;font-size:.8rem;font-weight:600;line-height:1.2;text-decoration:none; }
        .pagination nav a.page-link:hover { border-color:#bfdbfe;background:#eff6ff;color:#1d4ed8; }
        .pagination nav .page-item.active .page-link { border-color:#1677f2;background:#1677f2;color:#fff; }
        .pagination nav .page-item.disabled .page-link { background:#f9fafb;color:#9ca3af;cursor:not-allowed; }
        @media(max-width:600px) { .pagination { align-items:flex-start;flex-direction:column; }.pagination nav { margin-left:0; } }
        @media(max-width:760px) {
            .sidebar { position:static;width:100%;min-height:0;padding:.75rem; }
            .brand { height:38px;margin:0 .35rem .7rem; }
            .sidebar-section-label,.sidebar-user { display:none; }
            .sidebar-link,.logout { width:auto;min-width:max-content;min-height:40px;padding:.55rem .7rem;font-size:.78rem; }
            .logout { position:absolute;top:.75rem;right:.75rem;margin:0; }
            .workspace { margin-left:0; }
            .workspace-header { min-height:60px;padding:.8rem 1rem; }
            main { padding:1.25rem 1rem 2rem; }
        }
        @media(max-width:600px) { .welcome{padding:1.4rem}.welcome h1{font-size:1.25rem} }
        @media(max-width:900px) { .enrollment-filters { grid-template-columns:repeat(2,minmax(0,1fr)); }.filter-search { grid-column:1/-1; } }
        @media(max-width:480px) { .enrollment-filters { grid-template-columns:1fr; }.filter-search { grid-column:auto; }.filter-button,.filter-reset { width:100%; } }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a href="{{ route('student.enrollments') }}" class="brand">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 2.18L19.5 9 12 12.82 4.5 9 12 5.18zM5 13.18V17l7 4 7-4v-3.82L12 17l-7-3.82z"/></svg>
                SIMS Student Portal
            </a>
            <div class="sidebar-section-label">My account</div>
            <nav aria-label="Student navigation">
                <a href="{{ route('student.enrollments') }}" class="sidebar-link" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg>
                    <span>My Enrolled Subjects</span>
                </a>
            </nav>
            <div class="sidebar-user">
                <div class="sidebar-avatar">{{ strtoupper(substr($student->first_name, 0, 1)) }}</div>
                <div>{{ $student->first_name }} {{ $student->last_name }}<span>Student</span></div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout" type="submit">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    <span>Sign out</span>
                </button>
            </form>
        </aside>
        <div class="workspace">
            <header class="workspace-header">
                <div><h1>My Enrolled Subjects</h1><p>Student Information Management System</p></div>
                <div style="display:flex;align-items:center;gap:.65rem;">
                    <span style="color:#7b8498;font-size:.8rem;">{{ now()->format('D, M j, Y') }}</span>
                    <span aria-hidden="true" style="display:grid;place-items:center;width:36px;height:36px;border-radius:50%;background:#1677f2;color:#fff;font-size:.8rem;font-weight:800;">{{ strtoupper(substr($student->first_name, 0, 1)) }}</span>
                    <span style="color:#26324e;font-size:.82rem;font-weight:600;">{{ $student->first_name }}</span>
                </div>
            </header>
            <main>
        @if (session('success'))
            <div class="flash">{{ session('success') }}</div>
        @endif

        <section class="welcome">
            <h1>Welcome, {{ $student->first_name }}!</h1>
            <p>{{ $student->student_number }} · {{ $student->course }} · {{ $student->year_level }}</p>
        </section>

        <div class="section-head">
            <h2>My Enrolled Subjects</h2>
            <span>{{ $enrollments->total() }} {{ Str::plural('subject', $enrollments->total()) }}</span>
        </div>

        <form method="GET" action="{{ route('student.enrollments') }}" class="enrollment-filters">
            <div class="filter-field filter-search">
                <label for="enrollment-search">Search subject</label>
                <input id="enrollment-search" type="search" name="search" value="{{ $search }}" placeholder="Search by subject code or name">
            </div>
            <div class="filter-field">
                <label for="school-year-filter">School Year</label>
                <select id="school-year-filter" name="school_year">
                    <option value="">All school years</option>
                    @foreach ($schoolYears as $option)
                        <option value="{{ $option }}" @selected($schoolYear === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="semester-filter">Semester</label>
                <select id="semester-filter" name="semester">
                    <option value="">All semesters</option>
                    @foreach ($semesters as $option)
                        <option value="{{ $option }}" @selected($semester === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="filter-button">Search</button>
            <a href="{{ route('student.enrollments') }}" class="filter-reset">Clear</a>
        </form>

        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Course ID / Subject</th>
                            <th>Schedule Day &amp; Time</th>
                            <th>Faculty Assigned</th>
                            <th>Units</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrollments as $enrollment)
                            @php($scheduleKey = implode('|', [$enrollment->subject_id, $enrollment->school_year, $enrollment->semester]))
                            <tr>
                                <td><strong>{{ $enrollment->subject->name }}</strong><span class="muted">{{ $enrollment->subject->code }}</span></td>
                                <td>
                                    <div class="schedule-detail">
                                        @forelse ($teachingLoads->get($scheduleKey, collect()) as $teachingLoad)
                                            @foreach ($teachingLoad->meetings() as $meeting)
                                                <div class="schedule-slot">
                                                    <strong>{{ $meeting['type'] }} · {{ implode(', ', $meeting['days']) }} · {{ \Illuminate\Support\Carbon::parse($meeting['start_time'])->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($meeting['end_time'])->format('g:i A') }}</strong>
                                                    <span>{{ $meeting['room'] }} · Section {{ $teachingLoad->section }}</span>
                                                </div>
                                            @endforeach
                                        @empty
                                            <span class="schedule-unavailable">Schedule not yet plotted</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="faculty-detail">
                                    @forelse ($teachingLoads->get($scheduleKey, collect())->pluck('faculty')->filter()->unique('id') as $faculty)
                                        <span>{{ $faculty->name }}</span>@if (!$loop->last)<br>@endif
                                    @empty
                                        <span class="schedule-unavailable">Not assigned</span>
                                    @endforelse
                                </td>
                                <td>{{ $enrollment->subject->units }}</td>
                                <td>{{ $enrollment->school_year }}</td>
                                <td>{{ $enrollment->semester }}</td>
                                <td><span class="status">{{ ucfirst($enrollment->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="empty">You don't have any enrolled subjects yet. Please contact the school office if you think this is an error.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($enrollments->hasPages())
                <div class="pagination">
                    <span>Showing {{ $enrollments->firstItem() }}–{{ $enrollments->lastItem() }} of {{ $enrollments->total() }} subjects</span>
                    {{ $enrollments->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </section>
            </main>
        </div>
    </div>
</body>
</html>
