@extends('students.layout')

@section('title', 'Edit Subject')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit Subject</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <a href="{{ route('subjects.index') }}">Subjects</a> / Edit</p>
        </div>
    </div>

    <div class="form-card">
        <form method="POST" action="{{ route('subjects.update', $subject) }}">
            @csrf
            @method('PUT')
            @include('subjects._form')
        </form>
    </div>
@endsection
