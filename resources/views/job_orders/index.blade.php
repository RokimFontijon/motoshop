@extends('layouts.app')
@section('title', 'Job orders')

@section('content')
    <div class="page-head">
        <h1>Job orders</h1>
        <div class="actions">
            <a class="btn" href="{{ route('job-orders.create') }}">+ New job order</a>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('job-orders.index') }}" class="filters">
            <div class="grow">
                <label for="q">Search</label>
                <input type="search" id="q" name="q" value="{{ $search }}" placeholder="Job number, plate number or customer">
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-outline">Filter</button>
            @if ($search !== '' || $status)
                <a class="btn btn-outline" href="{{ route('job-orders.index') }}">Clear</a>
            @endif
        </form>

        @if ($jobOrders->isEmpty())
            <p class="empty">No job orders found.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr><th>Job</th><th>Date</th><th>Motorcycle</th><th>Mechanic</th><th>Status</th><th class="num">Total</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($jobOrders as $jobOrder)
                        <tr>
                            <td><a href="{{ route('job-orders.show', $jobOrder) }}"><strong>{{ $jobOrder->job_number }}</strong></a></td>
                            <td>{{ $jobOrder->service_date->format('M d, Y') }}</td>
                            <td>{{ $jobOrder->motorcycle->plate_number }}
                                <span class="small">{{ $jobOrder->motorcycle->motorcycleModel->label() }}</span><br>
                                <span class="small">{{ $jobOrder->motorcycle->customer->full_name }}</span></td>
                            <td>{{ $jobOrder->mechanic?->name ?? '-' }}</td>
                            <td>@include('partials.status-badge', ['status' => $jobOrder->status])</td>
                            <td class="num">&#8369;{{ number_format($jobOrder->grand_total, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $jobOrders->links('partials.pagination') }}
        @endif
    </div>
@endsection
