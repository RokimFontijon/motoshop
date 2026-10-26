@extends('layouts.app')
@section('title', 'New job order')

@section('content')
    <div class="page-head"><h1>New job order</h1></div>

    <div class="card">
        <form method="POST" action="{{ route('job-orders.store') }}">
            @csrf
            @include('job_orders._form')
            <div class="form-actions">
                <button type="submit" class="btn">Create job order</button>
                <a class="btn btn-outline" href="{{ route('job-orders.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
