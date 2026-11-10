@extends('students.layout')

@section('title', 'Teaching Loads & Schedule')
@section('page-class', 'page-landscape')

@section('content')
    <style>
        .schedule-header { display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.2rem; }
        .schedule-header h1 { color:#172554;font-size:1.4rem;font-weight:800; }
        .schedule-header p { margin-top:.3rem;color:#64748b;font-size:.82rem; }
        .schedule-actions { display:flex;gap:.5rem;flex-wrap:wrap; }
        .schedule-toolbar { display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;margin-bottom:1rem; }
        .schedule-filter { display:flex;align-items:center;gap:.55rem;flex:1;flex-wrap:wrap; }
        .schedule-select { min-height:39px;padding:.5rem .75rem;border:1.5px solid #e5e7eb;border-radius:9px;background:#fff;color:#111827;font:inherit;font-size:.85rem; }
        .schedule-board { display:grid;grid-template-columns:repeat(7,minmax(170px,1fr));gap:.65rem;overflow-x:auto;padding-bottom:.5rem; }
        .schedule-day { min-height:220px;overflow:hidden;background:#eaf0fb;border:1px solid #dce5f3;border-radius:12px; }
        .schedule-day h2 { padding:.7rem .75rem;color:#fff;background:linear-gradient(110deg,#1e3a8a,#2563eb);font-size:.8rem;font-weight:800; }
        .schedule-day-list { display:grid;align-content:start;gap:.5rem;padding:.55rem; }
        .schedule-entry { display:grid;gap:.28rem;padding:.65rem;border:1px solid #e1e8f2;border-radius:9px;background:#fff;color:inherit;text-decoration:none;box-shadow:0 2px 6px rgba(15,23,42,.04); }
        .schedule-entry:hover { border-color:#93c5fd; }
        .schedule-entry-time { color:#1d4ed8;font-size:.68rem;font-weight:800; }
        .schedule-entry-title { color:#172554;font-size:.76rem;font-weight:800; }
        .schedule-entry-meta { color:#64748b;font-size:.66rem;line-height:1.4;overflow-wrap:anywhere; }
        .schedule-entry-units { justify-self:start;padding:.2rem .42rem;border-radius:999px;color:#1e3a8a;background:#eff6ff;font-size:.65rem;font-weight:800; }
        .faculty-unit-loads { margin-top:1rem;padding:1rem;background:#fff;border:1px solid #e5e7eb;border-radius:12px; }
        .faculty-unit-loads h2 { margin-bottom:.75rem;color:#172554;font-size:.95rem;font-weight:800; }
        .faculty-unit-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.6rem; }
        .faculty-unit-card { display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.75rem;border:1px solid #e5e7eb;border-radius:9px; }
        .faculty-unit-copy { display:grid;gap:.2rem; }
        .faculty-unit-copy strong { color:#1f2937;font-size:.78rem; }
        .faculty-unit-copy small { color:#64748b;font-size:.65rem; }
        .faculty-unit-value { display:grid;justify-items:end;gap:.25rem; }
        .faculty-unit-value > span { color:#1d4ed8;font-size:.76rem;font-weight:800;white-space:nowrap; }
        .faculty-load-status { padding:.16rem .38rem;border-radius:999px;font-size:.6rem;font-weight:800;white-space:nowrap; }
        .faculty-load-under { color:#92400e;background:#fef3c7; }
        .faculty-load-normal { color:#166534;background:#dcfce7; }
        .faculty-load-over { color:#9a3412;background:#ffedd5; }
        .faculty-load-limits { margin:-.3rem 0 .75rem;color:#64748b;font-size:.7rem; }
        .schedule-empty { padding:1.3rem .6rem;color:#94a3b8;font-size:.72rem;text-align:center; }
        .schedule-count { margin-left:auto;color:#64748b;font-size:.8rem; }
        .schedule-empty-state { padding:2.5rem 1rem;border:1px solid #e5e7eb;border-radius:14px;background:#fff;color:#64748b;text-align:center; }
        @media(max-width:640px) {
            .schedule-header { flex-direction:column; }
            .schedule-actions { width:100%; }
            .schedule-filter { width:100%; }
            .schedule-select { flex:1;min-width:0; }
        }
        @media print {
            .sidebar,.workspace-header,.schedule-actions,.schedule-toolbar { display:none!important; }
            .workspace { margin:0!important; }
            .page-landscape { width:100%!important;padding:0!important;margin:0!important; }
            .schedule-board { grid-template-columns:repeat(7,minmax(0,1fr));gap:4pt;overflow:visible; }
            .schedule-day { min-height:160pt;break-inside:avoid; }
        }
    </style>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <header class="schedule-header">
        <div>
            <h1>Teaching Loads &amp; Schedule</h1>
            <p>Plot subject schedules by section, then assign faculty now or later.</p>
        </div>
        <div class="schedule-actions">
            <a href="{{ route('faculties.index') }}" class="btn btn-secondary">Manage Faculty</a>
            <button type="button" class="btn btn-secondary" onclick="window.print()">Print Schedule</button>
            <a href="{{ route('teaching-loads.create') }}" class="btn btn-primary">Plot Subject Schedule</a>
        </div>
    </header>

    <form method="GET" action="{{ route('teaching-loads.index') }}" class="schedule-toolbar">
        <div class="schedule-filter">
            <select name="school_year" class="schedule-select" aria-label="Filter by school year">
                <option value="">All school years</option>
                @foreach ($schoolYears as $year)
                    <option value="{{ $year }}" @selected($schoolYear === $year)>{{ $year }}</option>
                @endforeach
            </select>
            <select name="semester" class="schedule-select" aria-label="Filter by semester">
                <option value="">All semesters</option>
                @foreach ($semesters as $term)
                    <option value="{{ $term }}" @selected($semester === $term)>{{ $term }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            @if ($schoolYear || $semester)
                <a href="{{ route('teaching-loads.index') }}" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </div>
        <span class="schedule-count">{{ $loadCount }} {{ Str::plural('assignment', $loadCount) }}</span>
    </form>

    @if ($loadCount === 0)
        <div class="schedule-empty-state">
            No subject schedules match this term. <a href="{{ route('teaching-loads.create') }}">Plot the first subject schedule.</a>
        </div>
    @else
        <div class="schedule-board" aria-label="Weekly teaching schedule">
            @foreach ($schedule as $day => $loads)
                <section class="schedule-day">
                    <h2>{{ $day }}</h2>
                    <div class="schedule-day-list">
                        @forelse ($loads as $meeting)
                            @php($load = $meeting['load'])
                            <a class="schedule-entry" href="{{ route('teaching-loads.edit', $load) }}">
                                <span class="schedule-entry-time">{{ $meeting['type'] }} · {{ \Illuminate\Support\Carbon::parse($meeting['start_time'])->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($meeting['end_time'])->format('g:i A') }}</span>
                                <span class="schedule-entry-title">{{ $load->subject->code }} · {{ $load->section }}</span>
                                <span class="schedule-entry-units">{{ $load->units }} {{ Str::plural('unit', $load->units) }}</span>
                                <span class="schedule-entry-meta">{{ $load->program }} · {{ $load->year_level }}</span>
                                <span class="schedule-entry-meta">{{ $load->faculty?->name ?? 'Faculty not assigned' }} · {{ $meeting['room'] }}</span>
                                <span class="schedule-entry-meta">{{ $load->school_year }} · {{ $load->semester }}</span>
                            </a>
                        @empty
                            <p class="schedule-empty">No classes</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
        <section class="faculty-unit-loads" aria-labelledby="faculty-unit-load-title">
            <h2 id="faculty-unit-load-title">Faculty Unit Load{{ $schoolYear || $semester ? ' · '.implode(' · ', array_filter([$schoolYear, $semester])) : '' }}</h2>
            <p class="faculty-load-limits">Minimum: {{ $minimumFacultyUnits }} units · Regular load: up to {{ $minimumFacultyUnits }} units · Overload: {{ $minimumFacultyUnits + 1 }}–{{ $maximumFacultyUnits }} units · Maximum: {{ $maximumFacultyUnits }} units per faculty, school year, and semester.</p>
            @if ($facultyUnitLoads->isEmpty())
                <p class="schedule-entry-meta">No faculty have been assigned to the plotted schedules in this view.</p>
            @else
                <div class="faculty-unit-grid">
                    @foreach ($facultyUnitLoads as $facultyLoad)
                        <article class="faculty-unit-card">
                            <div class="faculty-unit-copy">
                                <strong>{{ $facultyLoad['faculty']->name }}</strong>
                                <small>{{ $facultyLoad['school_year'] }} · {{ $facultyLoad['semester'] }} · {{ $facultyLoad['assignments'] }} {{ Str::plural('subject', $facultyLoad['assignments']) }}</small>
                            </div>
                            <div class="faculty-unit-value">
                                <span>{{ $facultyLoad['units'] }} {{ Str::plural('unit', $facultyLoad['units']) }}</span>
                                @if ($facultyLoad['units'] < $minimumFacultyUnits)
                                    <small class="faculty-load-status faculty-load-under">Below minimum</small>
                                @elseif ($facultyLoad['units'] > $minimumFacultyUnits)
                                    <small class="faculty-load-status faculty-load-over">Overload</small>
                                @else
                                    <small class="faculty-load-status faculty-load-normal">Regular load</small>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
@endsection
