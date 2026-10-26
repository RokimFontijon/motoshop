@extends('layouts.app')
@section('title', 'New customer')

@section('content')
    <div class="page-head"><h1>New customer</h1></div>

    <div class="card">
        <form method="POST" action="{{ route('customers.store') }}">
            @csrf
            @include('customers._form')
            <div class="form-actions">
                <button type="submit" class="btn">Save customer</button>
                <a class="btn btn-outline" href="{{ route('customers.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
