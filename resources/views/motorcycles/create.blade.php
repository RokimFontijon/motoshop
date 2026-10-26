@extends('layouts.app')
@section('title', 'New motorcycle')

@section('content')
    <div class="page-head"><h1>New motorcycle</h1></div>

    <div class="card">
        <form method="POST" action="{{ route('motorcycles.store') }}">
            @csrf
            @include('motorcycles._form')
            <div class="form-actions">
                <button type="submit" class="btn">Save motorcycle</button>
                <a class="btn btn-outline" href="{{ route('motorcycles.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
