@extends('layouts.app')
@section('title', 'Edit motorcycle')

@section('content')
    <div class="page-head"><h1>Edit motorcycle</h1></div>

    <div class="card">
        <form method="POST" action="{{ route('motorcycles.update', $motorcycle) }}">
            @csrf
            @method('PUT')
            @include('motorcycles._form')
            <div class="form-actions">
                <button type="submit" class="btn">Save changes</button>
                <a class="btn btn-outline" href="{{ route('motorcycles.show', $motorcycle) }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
