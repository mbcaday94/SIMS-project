@extends('students.layout')

@section('title', 'Add Subject')

@section('content')
    <div class="page-header">
        <div>
            <h1>Add Subject</h1>
            <p class="breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <a href="{{ route('subjects.index') }}">Subjects</a> / Add</p>
        </div>
    </div>

    <div class="form-card">
        <form method="POST" action="{{ route('subjects.store') }}">
            @csrf
            @include('subjects._form')
        </form>
    </div>
@endsection
