@extends('layouts.app')
@section('title', 'Edit job order')

@section('content')
    <div class="page-head"><h1>Edit {{ $jobOrder->job_number }}</h1></div>

    <div class="card">
        <form method="POST" action="{{ route('job-orders.update', $jobOrder) }}">
            @csrf
            @method('PUT')
            @include('job_orders._form')
            <div class="form-actions">
                <button type="submit" class="btn">Save changes</button>
                <a class="btn btn-outline" href="{{ route('job-orders.show', $jobOrder) }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
