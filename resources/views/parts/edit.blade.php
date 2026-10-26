@extends('layouts.app')
@section('title', 'Edit part')

@section('content')
    <div class="page-head"><h1>Edit part</h1></div>

    <div class="card">
        <form method="POST" action="{{ route('parts.update', $part) }}">
            @csrf
            @method('PUT')
            @include('parts._form')
            <div class="form-actions">
                <button type="submit" class="btn">Save changes</button>
                <a class="btn btn-outline" href="{{ route('parts.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
