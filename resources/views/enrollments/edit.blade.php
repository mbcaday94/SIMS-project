@extends('students.layout')

@section('title', 'Edit Enrollment')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit Enrollment</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <a href="{{ route('enrollments.index') }}">Enrollments</a> / Edit</p>
        </div>
    </div>

    <div class="form-card">
        <form method="POST" action="{{ route('enrollments.update', $enrollment) }}">
            @csrf
            @method('PUT')
            @include('enrollments._form')
        </form>
    </div>
@endsection
