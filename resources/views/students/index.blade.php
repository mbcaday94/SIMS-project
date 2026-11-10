@extends('students.layout')

@section('title', 'Students')

@section('content')

    <style>
        .students-table { min-width:1180px;table-layout:fixed; }
        .students-table th,.students-table td { padding:.85rem .7rem; }
        .students-table th:nth-child(1),.students-table td:nth-child(1) { width:42px; }
        .students-table th:nth-child(2),.students-table td:nth-child(2) { width:112px; }
        .students-table th:nth-child(3),.students-table td:nth-child(3) { width:100px; }
        .students-table th:nth-child(4),.students-table td:nth-child(4) { width:110px; }
        .students-table th:nth-child(5),.students-table td:nth-child(5) { width:200px;overflow-wrap:anywhere; }
        .students-table th:nth-child(6),.students-table td:nth-child(6) { width:260px; }
        .students-table th:nth-child(7),.students-table td:nth-child(7) { width:105px; }
        .students-table th:nth-child(8),.students-table td:nth-child(8) { width:105px; }
        .students-table th:nth-child(9),.students-table td:nth-child(9) { width:280px; }
        .students-table td:nth-child(6) { color:#34405d;font-size:.82rem;line-height:1.45; }
        .students-table td:nth-child(9) > div { flex-wrap:nowrap !important; }
        @media(max-width:600px) {
            .students-table th,.students-table td { padding:.75rem .55rem; }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>Students</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / Students</p>
        </div>
        <a href="{{ route('students.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Student
        </a>
    </div>

    {{-- Toolbar: search --}}
    <div class="toolbar">
        <form method="GET" action="{{ route('students.index') }}" class="search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Search by name or student number…"
                value="{{ $search }}"
            >
        </form>
        @if ($search)
            <a href="{{ route('students.index') }}" class="btn btn-secondary btn-sm">Clear</a>
        @endif
        <span style="font-size:0.8rem; color:#6b7280; margin-left:auto;">
            {{ $students->total() }} {{ Str::plural('student', $students->total()) }} found
        </span>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="students-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student No.</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Semester</th>
                        <th>Section</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $i => $student)
                        <tr>
                            <td style="color:#9ca3af;">{{ $students->firstItem() + $i }}</td>
                            <td>
                                <span class="badge badge-blue">{{ $student->student_number }}</span>
                            </td>
                            <td style="font-weight:600;">{{ $student->last_name }}</td>
                            <td>{{ $student->first_name }}</td>
                            <td>{{ $student->email }}</td>
                            <td>{{ $student->course }}</td>
                            <td>{{ $student->year_level }}</td>
                            <td>{{ $student->semester ?: '—' }}</td>
                            <td>{{ $student->section ? 'Section '.$student->section : '—' }}</td>
                            <td>
                                <div style="display:flex; gap:0.4rem; justify-content:center; flex-wrap:wrap;">
                                    <a href="{{ route('students.show', $student) }}" class="btn btn-secondary btn-sm">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        View
                                    </a>
                                    <a href="{{ route('students.edit', $student) }}" class="btn btn-warning btn-sm">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                        Edit
                                    </a>
                                    @if (auth()->user()->isAdministrator())
                                        <form method="POST" action="{{ route('students.destroy', $student) }}"
                                              onsubmit="return confirm('Delete {{ addslashes($student->first_name . ' ' . $student->last_name) }}? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                    <path d="M10 11v6"/><path d="M14 11v6"/>
                                                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                    <p>
                                        @if ($search)
                                            No students match "<strong>{{ $search }}</strong>".
                                        @else
                                            No students yet. <a href="{{ route('students.create') }}" style="color:#1e40af;">Add the first one.</a>
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->hasPages())
            <div class="pagination-wrap">
                <span>Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }}</span>
                {{ $students->links('pagination::simple-bootstrap-4') }}
            </div>
        @endif
    </div>

@endsection
