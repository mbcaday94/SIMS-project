@extends('students.layout')

@section('title', 'Student Details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Student Details</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <a href="{{ route('students.index') }}">Students</a> / Details</p>
        </div>
        <a href="{{ route('students.edit', $student) }}" class="btn btn-warning btn-sm">Edit Student</a>
    </div>

    <div class="form-card">
        @if ($student->photo_path)
            <div class="student-detail-photo">
                <img src="{{ asset('storage/'.$student->photo_path) }}" alt="{{ $student->first_name }} {{ $student->last_name }}">
            </div>
        @endif
        <div class="detail-grid">
            <div class="detail-item"><label>Student Number</label><p>{{ $student->student_number }}</p></div>
            <div class="detail-item"><label>Email</label><p>{{ $student->email }}</p></div>
            <div class="detail-item"><label>First Name</label><p>{{ $student->first_name }}</p></div>
            <div class="detail-item"><label>Last Name</label><p>{{ $student->last_name }}</p></div>
            <div class="detail-item"><label>Course</label><p>{{ $student->course }}</p></div>
            <div class="detail-item"><label>Year Level</label><p>{{ $student->year_level }}</p></div>
            <div class="detail-item"><label>Semester</label><p>{{ $student->semester ?: '—' }}</p></div>
            <div class="detail-item"><label>Block / Section</label><p>{{ $student->section ? 'Section '.$student->section : '—' }}</p></div>
        </div>
    </div>

    @if (auth()->user()->isAdministrator())
        <div class="form-card" style="margin-top:1.25rem;">
            <h2 style="font-size:1.05rem; margin-bottom:.5rem;">Student login account</h2>
            @if ($student->user)
                <p style="color:#4b5563;">Account linked to <strong>{{ $student->user->email }}</strong>. This login can view only this student's enrollments.</p>
            @else
                <p style="color:#6b7280; font-size:.875rem; margin-bottom:1rem;">Create a login using the student's email address. Share the password securely.</p>
                <form method="POST" action="{{ route('students.account.store', $student) }}" class="form-grid">
                    @csrf
                    <div class="form-group">
                        <label for="account-password">Temporary password <span class="req">*</span></label>
                        <input id="account-password" name="password" type="password" autocomplete="new-password" class="form-control no-icon @error('password') is-invalid @enderror" required minlength="8">
                        @error('password') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label for="account-password-confirmation">Confirm password <span class="req">*</span></label>
                        <input id="account-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-control no-icon" required minlength="8">
                    </div>
                    @error('account') <p class="error-msg form-group full">{{ $message }}</p> @enderror
                    <div class="form-actions form-group full" style="margin-top:0;">
                        <button type="submit" class="btn btn-primary">Create Student Login</button>
                    </div>
                </form>
            @endif
        </div>
    @endif
@endsection
