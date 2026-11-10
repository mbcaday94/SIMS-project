@extends('students.layout')

@section('title', 'Add Student')

@section('content')
    <div class="page-header">
        <div>
            <h1>Add Student</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <a href="{{ route('students.index') }}">Students</a> / Add</p>
        </div>
    </div>

    <div class="form-card student-form-card">
        <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data">
            @csrf
            @include('students._form')
        </form>
    </div>
@endsection
