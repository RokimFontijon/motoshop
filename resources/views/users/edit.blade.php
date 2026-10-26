@extends('layouts.app')
@section('title', 'Edit account')

@section('content')
    <div class="page-head"><h1>Edit account</h1></div>

    @if ($user->is(auth()->user()))
        <p class="note">This is your own account. Your role cannot be changed here, so you cannot lock yourself out.</p>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('users._form')
            <div class="form-actions">
                <button type="submit" class="btn">Save changes</button>
                <a class="btn btn-outline" href="{{ route('users.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
