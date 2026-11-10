@extends('students.layout')

@section('title', 'Enrollments')

@section('content')
    <style>
        .enrollment-groups { display:grid;gap:1.15rem; }
        .program-enrollment-group { display:grid;gap:.65rem; }
        .program-enrollment-heading { display:flex;align-items:center;gap:.7rem;padding:.85rem 1.1rem;border-radius:12px;color:#fff;background:linear-gradient(110deg,#1677f2,#429cf5);box-shadow:0 6px 16px rgba(22,119,242,.16);font-size:1rem;font-weight:750; }
        .program-enrollment-heading svg { width:20px;height:20px;flex:none;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round; }
        .program-student-count { margin-left:auto;padding:.3rem .65rem;border:1px solid rgba(255,255,255,.28);border-radius:999px;background:rgba(255,255,255,.16);color:#fff;font-size:.72rem;font-weight:700;white-space:nowrap; }
        .student-enrollment-group { overflow:hidden;background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 2px 8px rgba(15,23,42,.04); }
        .student-group-summary { display:flex;align-items:center;gap:.85rem;padding:1rem 1.15rem;cursor:pointer;list-style:none; }
        .student-group-summary::-webkit-details-marker { display:none; }
        .student-group-summary::marker { content:''; }
        .student-group-summary:hover { background:#f8fafc; }
        .student-avatar { display:grid;place-items:center;width:42px;height:42px;flex:none;border-radius:12px;color:#1e40af;background:#eff6ff;font-size:.95rem;font-weight:800; }
        .student-summary-copy { display:grid;min-width:0;gap:.2rem; }
        .student-summary-copy strong { color:#172554;font-size:.94rem; }
        .student-summary-copy span { color:#6b7280;font-size:.77rem; }
        .student-subject-count { margin-left:auto;padding:.35rem .65rem;border-radius:999px;color:#1d4ed8;background:#eff6ff;font-size:.73rem;font-weight:700;white-space:nowrap; }
        .student-chevron { width:18px;height:18px;flex:none;color:#64748b;transition:transform .18s; }
        .student-chevron svg { width:100%;height:100%;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round; }
        .student-enrollment-group[open] .student-chevron { transform:rotate(180deg); }
        .student-enrollment-content { border-top:1px solid #e5e7eb; }
        .student-enrollment-table-wrap { overflow-x:auto; }
        .student-enrollment-table { width:100%;border-collapse:collapse; }
        .student-enrollment-table th,.student-enrollment-table td { padding:.78rem .9rem;border-bottom:1px solid #eef2f7;text-align:left;font-size:.82rem;vertical-align:middle; }
        .student-enrollment-table th { color:#6b7280;background:#f8fafc;font-size:.68rem;letter-spacing:.04em;text-transform:uppercase; }
        .student-enrollment-table tbody tr:last-child td { border-bottom:0; }
        .enrollment-subject-name { display:block;margin-top:.18rem;color:#6b7280;font-size:.75rem; }
        .enrollment-actions { display:flex;justify-content:flex-end;gap:.35rem;flex-wrap:wrap; }
        .enrollment-actions .btn { white-space:nowrap; }
        .student-groups-empty { padding:2rem 1rem;text-align:center;color:#6b7280; }
        .student-groups-pagination { display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem;border-top:1px solid #eef2f7;font-size:.78rem;color:#6b7280; }
        .student-groups-pagination nav { margin-left:auto; }
        .enrollment-filter-form { display:flex;align-items:center;gap:.65rem;flex:1;flex-wrap:wrap; }
        .enrollment-filter-form .search-input { width:min(340px,100%);min-width:220px;padding-left:.9rem; }
        .enrollment-filter-select { min-height:39px;padding:.5rem .75rem;border:1.5px solid #e5e7eb;border-radius:9px;background:#fff;color:#111827;font:inherit;font-size:.875rem; }
        .enrollment-filter-select:focus { border-color:#3b82f6;outline:none; }
        @media(max-width:720px) {
            .student-group-summary { gap:.6rem;padding:.85rem; }
            .student-avatar { width:36px;height:36px; }
            .student-subject-count { margin-left:auto;font-size:.67rem; }
            .student-enrollment-table { min-width:720px; }
            .student-groups-pagination { align-items:flex-start;flex-direction:column; }
            .student-groups-pagination nav { margin-left:0; }
        }
        @media(max-width:600px) {
            .enrollment-filter-form { width:100%; }
            .enrollment-filter-form .search-input,.enrollment-filter-select { flex:1;min-width:0; }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>Enrollments</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / Enrollments</p>
        </div>
        <a href="{{ route('enrollments.create') }}" class="btn btn-primary">Enroll Student</a>
    </div>

    <div class="toolbar">
        <form method="GET" action="{{ route('enrollments.index') }}" class="enrollment-filter-form">
            <input type="text" name="search" class="search-input" placeholder="Search by student ID, name, or subject" value="{{ $search }}">
            <select name="school_year" class="enrollment-filter-select" aria-label="Filter by school year">
                <option value="">All school years</option>
                @foreach ($schoolYears as $year)
                    <option value="{{ $year }}" @selected($schoolYear === $year)>{{ $year }}</option>
                @endforeach
            </select>
            <select name="semester" class="enrollment-filter-select" aria-label="Filter by semester">
                <option value="">All semesters</option>
                @foreach ($semesters as $term)
                    <option value="{{ $term }}" @selected($semester === $term)>{{ $term }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
        @if ($search || $schoolYear || $semester)
            <a href="{{ route('enrollments.index') }}" class="btn btn-secondary btn-sm">Clear</a>
        @endif
        <span style="font-size:0.8rem; color:#6b7280; margin-left:auto;">
            {{ $students->total() }} {{ Str::plural('student', $students->total()) }} ·
            {{ $enrollmentCount }} {{ Str::plural('enrollment', $enrollmentCount) }} found
        </span>
    </div>

    <div class="enrollment-groups">
        @forelse ($students->getCollection()->groupBy(fn ($student) => trim((string) $student->course) ?: 'Unspecified program') as $program => $programStudents)
            <section class="program-enrollment-group" aria-labelledby="program-{{ $loop->index }}">
                <h2 class="program-enrollment-heading" id="program-{{ $loop->index }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5-10-5z"/><path d="M6 12v5c3.5 2.5 8.5 2.5 12 0v-5"/></svg>
                    {{ $program }}
                    <span class="program-student-count">{{ $programStudents->count() }} {{ Str::plural('student', $programStudents->count()) }}</span>
                </h2>
                @foreach ($programStudents as $student)
                    @php($totalUnits = $student->enrollments->sum(fn ($enrollment) => (int) ($enrollment->subject->units ?? 0)))
                    <details class="student-enrollment-group">
                <summary class="student-group-summary">
                    <span class="student-avatar">{{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}</span>
                    <span class="student-summary-copy">
                        <strong>{{ $student->last_name }}, {{ $student->first_name }}</strong>
                        <span>{{ $student->student_number }} · {{ $student->course }}</span>
                    </span>
                    <span class="student-subject-count">
                        {{ $student->enrollments->count() }} {{ Str::plural('subject', $student->enrollments->count()) }} ·
                        {{ $totalUnits }} {{ Str::plural('unit', $totalUnits) }}
                    </span>
                    <span class="student-chevron" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </summary>
                <div class="student-enrollment-content">
                    <div class="student-enrollment-table-wrap">
                        <table class="student-enrollment-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>School Year</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>Enrolled</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($student->enrollments as $enrollment)
                                    <tr>
                                        <td>
                                            <strong>{{ $enrollment->subject->code }}</strong>
                                            <span class="enrollment-subject-name">{{ $enrollment->subject->name }}</span>
                                        </td>
                                        <td>{{ $enrollment->school_year }}</td>
                                        <td>{{ $enrollment->semester }}</td>
                                        <td><span class="badge badge-blue">{{ ucfirst($enrollment->status) }}</span></td>
                                        <td>{{ $enrollment->enrolled_at?->format('M d, Y') ?? '—' }}</td>
                                        <td>
                                            <div class="enrollment-actions">
                                                <a href="{{ route('enrollments.show', $enrollment) }}" class="btn btn-secondary btn-sm">View</a>
                                                <a href="{{ route('enrollments.edit', $enrollment) }}" class="btn btn-warning btn-sm">Edit</a>
                                                @if (auth()->user()->isAdministrator())
                                                    <form method="POST" action="{{ route('enrollments.destroy', $enrollment) }}"
                                                          onsubmit="return confirm('Delete this enrollment? This cannot be undone.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                    </details>
                @endforeach
            </section>
        @empty
            <div class="card student-groups-empty">
                @if ($search || $schoolYear || $semester)
                    @if ($search)
                        No enrollments match "<strong>{{ $search }}</strong>".
                    @else
                        No enrollments match the selected filters.
                    @endif
                @else
                    No enrollments yet. <a href="{{ route('enrollments.create') }}" style="color:#1e40af;">Enroll a student.</a>
                @endif
            </div>
        @endforelse
    </div>

    @if ($students->hasPages())
        <div class="card student-groups-pagination" style="margin-top:1rem;">
            <span>Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }} students</span>
            {{ $students->links('pagination::simple-bootstrap-4') }}
        </div>
    @endif
@endsection
