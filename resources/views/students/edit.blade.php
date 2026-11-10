@extends('students.layout')

@section('title', 'Edit Student')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit Student</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <a href="{{ route('students.index') }}">Students</a> / Edit</p>
        </div>
    </div>

    <div class="form-card student-form-card">
        <form method="POST" action="{{ route('students.update', $student) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('students._form')
        </form>
    </div>
@endsection
