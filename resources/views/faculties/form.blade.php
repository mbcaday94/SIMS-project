@extends('students.layout')

@section('title', $faculty->exists ? 'Edit Faculty' : 'Add Faculty')

@section('content')
    <style>
        .faculty-form-card { max-width:620px;padding:1.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.045); }
        .faculty-form-card h1 { margin-bottom:.35rem;color:#172554;font-size:1.35rem;font-weight:800; }
        .faculty-form-card > p { margin-bottom:1.3rem;color:#64748b;font-size:.82rem; }
        .faculty-form-fields { display:grid;gap:1rem; }
        .faculty-form-fields label { display:grid;gap:.4rem;color:#374151;font-size:.78rem;font-weight:700; }
        .faculty-form-fields input { width:100%;min-height:42px;padding:.6rem .7rem;border:1px solid #d1d5db;border-radius:8px;font:inherit;font-size:.84rem; }
        .faculty-error { color:#b91c1c;font-size:.74rem; }
        .faculty-form-actions { display:flex;gap:.6rem;margin-top:1.3rem; }
    </style>

    <section class="faculty-form-card">
        <h1>{{ $faculty->exists ? 'Edit Faculty' : 'Add Faculty' }}</h1>
        <p>Faculty records are used to assign and check teaching schedules.</p>

        <form method="POST" action="{{ $faculty->exists ? route('faculties.update', $faculty) : route('faculties.store') }}">
            @csrf
            @if ($faculty->exists)
                @method('PUT')
            @endif
            <div class="faculty-form-fields">
                <label for="name">Full name
                    <input id="name" name="name" value="{{ old('name', $faculty->name) }}" maxlength="255" required>
                    @error('name') <span class="faculty-error">{{ $message }}</span> @enderror
                </label>
                <label for="employee_number">Employee ID <span style="font-weight:400;">(optional)</span>
                    <input id="employee_number" name="employee_number" value="{{ old('employee_number', $faculty->employee_number) }}" maxlength="255">
                    @error('employee_number') <span class="faculty-error">{{ $message }}</span> @enderror
                </label>
                <label for="email">Email <span style="font-weight:400;">(optional)</span>
                    <input type="email" id="email" name="email" value="{{ old('email', $faculty->email) }}" maxlength="255">
                    @error('email') <span class="faculty-error">{{ $message }}</span> @enderror
                </label>
            </div>
            <div class="faculty-form-actions">
                <button type="submit" class="btn btn-primary">{{ $faculty->exists ? 'Save changes' : 'Add faculty' }}</button>
                <a href="{{ route('faculties.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </section>
@endsection
