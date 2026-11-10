@extends('students.layout')

@section('title', 'Subject Details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Subject Details</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <a href="{{ route('subjects.index') }}">Subjects</a> / Details</p>
        </div>
        <a href="{{ route('subjects.edit', $subject) }}" class="btn btn-warning btn-sm">Edit Subject</a>
    </div>

    <div class="form-card">
        <div class="detail-grid">
            <div class="detail-item"><label>Subject Code</label><p>{{ $subject->code }}</p></div>
            <div class="detail-item"><label>Subject Name</label><p>{{ $subject->name }}</p></div>
            <div class="detail-item"><label>Units</label><p>{{ $subject->units }}</p></div>
            <div class="detail-item"><label>Description</label><p>{{ $subject->description ?: '—' }}</p></div>
        </div>
    </div>
@endsection
