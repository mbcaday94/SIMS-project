@extends('students.layout')

@section('title', 'Enroll Student')

@section('content')
    <div class="page-header">
        <div>
            <h1>Enroll Student</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <a href="{{ route('enrollments.index') }}">Enrollments</a> / Enroll</p>
        </div>
    </div>

    <div class="form-card">
        @if ($students->isEmpty() || $subjects->isEmpty())
            <p>Create at least one student and one subject before enrolling.</p>
        @else
            <form method="POST" action="{{ route('enrollments.store') }}">
                @csrf
                @include('enrollments._form')
            </form>
        @endif
    </div>
@endsection
