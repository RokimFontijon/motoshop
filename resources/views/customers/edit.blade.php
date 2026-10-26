@extends('layouts.app')
@section('title', 'Edit customer')

@section('content')
    <div class="page-head"><h1>Edit customer</h1></div>

    <div class="card">
        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PUT')
            @include('customers._form')
            <div class="form-actions">
                <button type="submit" class="btn">Save changes</button>
                <a class="btn btn-outline" href="{{ route('customers.show', $customer) }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
