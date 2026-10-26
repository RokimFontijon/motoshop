@extends('layouts.app')
@section('title', 'New account')

@section('content')
    <div class="page-head"><h1>New staff account</h1></div>

    <div class="card">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            @include('users._form')
            <div class="form-actions">
                <button type="submit" class="btn">Create account</button>
                <a class="btn btn-outline" href="{{ route('users.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
