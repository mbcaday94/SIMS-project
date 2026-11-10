@extends('students.layout')

@section('title', 'Subjects')

@section('content')
    <style>
        .subject-filter-form { display:flex;align-items:center;gap:.65rem;flex:1;flex-wrap:wrap; }
        .subject-filter-form .search-input { width:min(340px,100%);min-width:200px;padding-left:.9rem; }
        .subject-filter-select { min-height:39px;padding:.5rem .75rem;border:1.5px solid #e5e7eb;border-radius:9px;background:#fff;color:#111827;font:inherit;font-size:.875rem; }
        .subject-filter-select:focus { border-color:#3b82f6;outline:none; }
        @media(max-width:600px) {
            .subject-filter-form { width:100%; }
            .subject-filter-form .search-input,.subject-filter-select { flex:1;min-width:0; }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>Subjects</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / Subjects</p>
        </div>
        <a href="{{ route('subjects.create') }}" class="btn btn-primary">Add Subject</a>
    </div>

    <div class="toolbar">
        <form method="GET" action="{{ route('subjects.index') }}" class="subject-filter-form">
            <input type="text" name="search" class="search-input" placeholder="Search by subject code or name" value="{{ $search }}">
            <select name="school_year" class="subject-filter-select" aria-label="Filter by school year">
                <option value="">All school years</option>
                @foreach ($schoolYears as $year)
                    <option value="{{ $year }}" @selected($schoolYear === $year)>{{ $year }}</option>
                @endforeach
            </select>
            <select name="semester" class="subject-filter-select" aria-label="Filter by semester">
                <option value="">All semesters</option>
                @foreach ($semesters as $term)
                    <option value="{{ $term }}" @selected($semester === $term)>{{ $term }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
        @if ($search || $schoolYear || $semester)
            <a href="{{ route('subjects.index') }}" class="btn btn-secondary btn-sm">Clear</a>
        @endif
        <span style="font-size:0.8rem; color:#6b7280; margin-left:auto;">
            {{ $subjects->total() }} {{ Str::plural('subject', $subjects->total()) }} found
        </span>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Units</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subjects as $i => $subject)
                        <tr>
                            <td style="color:#9ca3af;">{{ $subjects->firstItem() + $i }}</td>
                            <td><span class="badge badge-blue">{{ $subject->code }}</span></td>
                            <td style="font-weight:600;">{{ $subject->name }}</td>
                            <td>{{ $subject->units }}</td>
                            <td>
                                <div style="display:flex; gap:0.4rem; justify-content:center; flex-wrap:wrap;">
                                    <a href="{{ route('subjects.show', $subject) }}" class="btn btn-secondary btn-sm">View</a>
                                    <a href="{{ route('subjects.edit', $subject) }}" class="btn btn-warning btn-sm">Edit</a>
                                    @if (auth()->user()->isAdministrator())
                                        <form method="POST" action="{{ route('subjects.destroy', $subject) }}"
                                              onsubmit="return confirm('Delete {{ addslashes($subject->name) }}? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty">
                                    <p>
                                        @if ($schoolYear || $semester)
                                            No subjects match the selected filters.
                                        @elseif ($search)
                                            No subjects match "<strong>{{ $search }}</strong>".
                                        @else
                                            No subjects yet. <a href="{{ route('subjects.create') }}" style="color:#1e40af;">Add the first one.</a>
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subjects->hasPages())
            <div class="pagination-wrap">
                <span>Showing {{ $subjects->firstItem() }}–{{ $subjects->lastItem() }} of {{ $subjects->total() }}</span>
                {{ $subjects->links('pagination::simple-bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection
