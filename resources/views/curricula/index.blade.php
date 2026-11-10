@extends('students.layout')

@section('title', 'Program Curricula')

@section('content')
    <style>
        .curriculum-heading { display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem; }
        .curriculum-heading h2 { color:#111827;font-size:1.45rem;font-weight:800; }
        .curriculum-heading p { margin-top:.3rem;color:#6b7280;font-size:.86rem; }
        .curriculum-grid { display:grid;grid-template-columns:minmax(0,1.3fr) minmax(280px,.7fr);gap:1.25rem;align-items:start; }
        .curriculum-card { padding:1.4rem;background:#fff;border:1px solid #e5e7eb;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.045); }
        .curriculum-card h3 { margin-bottom:.45rem;color:#172554;font-size:1rem;font-weight:750; }
        .curriculum-card > p { margin-bottom:1rem;color:#6b7280;font-size:.82rem;line-height:1.55; }
        .curriculum-form { display:grid;gap:.9rem; }
        .curriculum-form label { display:grid;gap:.4rem;color:#374151;font-size:.8rem;font-weight:650; }
        .curriculum-form input { width:100%;padding:.68rem .75rem;border:1px solid #d1d5db;border-radius:8px;font:inherit;font-size:.85rem; }
        .curriculum-form input:focus { outline:2px solid #bfdbfe;border-color:#3b82f6; }
        .curriculum-note { padding:.85rem .95rem;margin-bottom:1rem;border-radius:9px;color:#1e3a8a;background:#eff6ff;font-size:.78rem;line-height:1.55; }
        .curriculum-note code { color:#172554;font-weight:700; }
        .file-error { padding:.8rem .9rem;border:1px solid #fecaca;border-radius:9px;color:#991b1b;background:#fef2f2;font-size:.8rem;white-space:pre-line; }
        .curriculum-table-wrap { overflow-x:auto; }
        .curriculum-table { width:100%;border-collapse:collapse; }
        .curriculum-table th,.curriculum-table td { padding:.85rem .9rem;border-bottom:1px solid #eef2f7;text-align:left;font-size:.84rem; }
        .curriculum-table th { color:#6b7280;background:#f8fafc;font-size:.7rem;letter-spacing:.04em;text-transform:uppercase; }
        .curriculum-table tbody tr:last-child td { border-bottom:0; }
        .curriculum-table a { color:#1d4ed8;font-weight:700;text-decoration:none; }
        .curriculum-table a:hover { text-decoration:underline; }
        .curriculum-empty { padding:2rem 1rem;text-align:center;color:#6b7280;font-size:.85rem; }
        .pagination-wrap { padding:1rem;border-top:1px solid #eef2f7; }
        @media(max-width:900px) { .curriculum-grid { grid-template-columns:1fr; } }
        @media(max-width:640px) { .curriculum-heading { flex-direction:column; }.curriculum-card { padding:1rem; } }
    </style>

    <div class="curriculum-heading">
        <div>
            <h2>Program Curricula</h2>
            <p>Import and review program subject plans by year level and semester.</p>
        </div>
        <a href="{{ route('teaching-loads.index') }}" class="btn btn-primary">Teaching Loads &amp; Schedule</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="curriculum-grid">
        <section class="curriculum-card" aria-labelledby="curriculum-list-title">
            <h3 id="curriculum-list-title">Imported curricula</h3>
            <p>Open a curriculum to view its subjects organized by year and semester.</p>
            <div class="curriculum-table-wrap">
                <table class="curriculum-table">
                    <thead>
                        <tr><th>Program</th><th>Curriculum</th><th>Subjects</th><th>Imported</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($curricula as $curriculum)
                            <tr>
                                <td>{{ $curriculum->program }}</td>
                                <td><a href="{{ route('curricula.show', $curriculum) }}">{{ $curriculum->name }}</a></td>
                                <td>{{ $curriculum->subjects_count }}</td>
                                <td>{{ $curriculum->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="curriculum-empty">No curricula imported yet.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($curricula->hasPages())
                <div class="pagination-wrap">{{ $curricula->links() }}</div>
            @endif
        </section>

        <section class="curriculum-card" aria-labelledby="curriculum-import-title">
            <h3 id="curriculum-import-title">Upload curriculum</h3>
            <p>Import the first worksheet of a CSV or Excel workbook. Program name and version are read from supported workbook formats; otherwise enter them below.</p>
            <div class="curriculum-note">
                Download the <a href="{{ route('curricula.template') }}">CSV template</a>. Required columns:
                <code>subject_code</code>, <code>subject_name</code>, <code>units</code>,
                <code>year_level</code>, <code>semester</code>. Program curriculum workbooks with course tables are also supported.
                Course hours, prerequisites, co-requisites, and elective options are retained when present.
                Zero-unit non-credit courses are supported. Maximum 2,000 courses and 10 MB.
                Missing subjects are added to the subject catalog. Existing codes are linked without changing that catalog;
                the imported curriculum preserves its own code, title, and units for each version.
            </div>
            @error('file')
                <div class="file-error" role="alert">{{ $message }}</div>
            @enderror
            <form method="POST" action="{{ route('curricula.import') }}" enctype="multipart/form-data" class="curriculum-form" style="margin-top:1rem;">
                @csrf
                <label>
                    Program name
                    <input type="text" name="program" value="{{ old('program') }}" placeholder="Auto-detected from workbook if available" maxlength="255">
                    @error('program') <span class="file-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    Curriculum name or version
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Auto-detected from workbook if available" maxlength="255">
                    @error('name') <span class="file-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    CSV or XLSX file
                    <input type="file" name="file" accept=".csv,.xlsx" required>
                </label>
                <button type="submit" class="btn btn-primary">Import curriculum</button>
            </form>
        </section>
    </div>
@endsection
