@extends('students.layout')

@section('title', 'Faculty Directory')

@section('content')
    <style>
        .faculty-header { display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.2rem; }
        .faculty-header h1 { color:#172554;font-size:1.4rem;font-weight:800; }
        .faculty-header p { margin-top:.3rem;color:#64748b;font-size:.82rem; }
        .faculty-table-wrap { overflow-x:auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px; }
        .faculty-table { width:100%;border-collapse:collapse; }
        .faculty-table th,.faculty-table td { padding:.8rem .9rem;border-bottom:1px solid #eef2f7;text-align:left;font-size:.82rem; }
        .faculty-table th { color:#6b7280;background:#f8fafc;font-size:.68rem;letter-spacing:.04em;text-transform:uppercase; }
        .faculty-actions { display:flex;justify-content:flex-end;gap:.4rem;align-items:center; }
        .faculty-actions form { margin:0; }
        .faculty-empty { padding:2rem;color:#64748b;text-align:center; }
    </style>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <header class="faculty-header">
        <div>
            <h1>Faculty Directory</h1>
            <p>Manage faculty members available for teaching-load assignment.</p>
        </div>
        <div style="display:flex;gap:.5rem;">
            <a href="{{ route('teaching-loads.index') }}" class="btn btn-secondary">Schedule</a>
            <a href="{{ route('faculties.create') }}" class="btn btn-primary">Add Faculty</a>
        </div>
    </header>

    <div class="faculty-table-wrap">
        <table class="faculty-table">
            <thead>
                <tr><th>Name</th><th>Employee ID</th><th>Email</th><th>Assigned Loads</th><th style="text-align:right;">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($faculties as $faculty)
                    <tr>
                        <td><strong>{{ $faculty->name }}</strong></td>
                        <td>{{ $faculty->employee_number ?: '—' }}</td>
                        <td>{{ $faculty->email ?: '—' }}</td>
                        <td>{{ $faculty->teaching_loads_count }}</td>
                        <td>
                            <div class="faculty-actions">
                                <a href="{{ route('faculties.edit', $faculty) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form method="POST" action="{{ route('faculties.destroy', $faculty) }}" onsubmit="return confirm('Delete {{ addslashes($faculty->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="faculty-empty">No faculty members have been added.</div></td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($faculties->hasPages())
            <div class="pagination-wrap">{{ $faculties->links() }}</div>
        @endif
    </div>
@endsection
