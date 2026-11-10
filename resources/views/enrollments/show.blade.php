@extends('students.layout')

@section('title', 'Enrollment Details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Enrollment Details</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <a href="{{ route('enrollments.index') }}">Enrollments</a> / Details</p>
        </div>
        <a href="{{ route('enrollments.edit', $enrollment) }}" class="btn btn-warning btn-sm">Edit Enrollment</a>
    </div>

    <div class="form-card">
        <div class="detail-grid">
            <div class="detail-item"><label>Student</label><p>{{ $enrollment->student->last_name }}, {{ $enrollment->student->first_name }}</p></div>
            <div class="detail-item"><label>Student Number</label><p>{{ $enrollment->student->student_number }}</p></div>
            <div class="detail-item"><label>Subject</label><p>{{ $enrollment->subject->code }} — {{ $enrollment->subject->name }}</p></div>
            <div class="detail-item"><label>Units</label><p>{{ $enrollment->subject->units }}</p></div>
            <div class="detail-item"><label>School Year</label><p>{{ $enrollment->school_year }}</p></div>
            <div class="detail-item"><label>Semester</label><p>{{ $enrollment->semester }}</p></div>
            <div class="detail-item"><label>Status</label><p>{{ ucfirst($enrollment->status) }}</p></div>
            <div class="detail-item"><label>Enrollment Date</label><p>{{ $enrollment->enrolled_at?->format('M d, Y') ?? '—' }}</p></div>
        </div>
    </div>
@endsection
