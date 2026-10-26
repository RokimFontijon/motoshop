@extends('layouts.app')
@section('title', 'New part')

@section('content')
    <div class="page-head"><h1>New part</h1></div>

    <div class="card">
        <form method="POST" action="{{ route('parts.store') }}">
            @csrf
            @include('parts._form')
            <div class="form-actions">
                <button type="submit" class="btn">Save part</button>
                <a class="btn btn-outline" href="{{ route('parts.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
