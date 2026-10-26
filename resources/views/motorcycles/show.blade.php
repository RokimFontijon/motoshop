@extends('layouts.app')
@section('title', $motorcycle->plate_number)

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $motorcycle->plate_number }}</h1>
            <p class="sub">{{ $motorcycle->motorcycleModel->label() }}</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('job-orders.create', ['motorcycle_id' => $motorcycle->id]) }}">+ New job order</a>
            <a class="btn btn-outline" href="{{ route('motorcycles.edit', $motorcycle) }}">Edit</a>
            @if (auth()->user()->isAdmin())
                <form method="POST" action="{{ route('motorcycles.destroy', $motorcycle) }}" class="inline"
                      onsubmit="return confirm('Delete this motorcycle?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <dl class="details">
            <dt>Owner</dt>
            <dd><a href="{{ route('customers.show', $motorcycle->customer) }}">{{ $motorcycle->customer->full_name }}</a>
                @if ($motorcycle->customer->phone) <span class="hint">&middot; {{ $motorcycle->customer->phone }}</span> @endif
            </dd>
            <dt>Year / color</dt><dd>{{ $motorcycle->year ?: '-' }} / {{ $motorcycle->color ?: '-' }}</dd>
            <dt>Odometer</dt><dd>{{ number_format($motorcycle->current_odometer) }} km</dd>
        </dl>
    </div>

    <div class="card">
        <h2>Next service estimates</h2>
        <p class="note">
            Estimated from the manufacturer's interval and this rider's own average daily mileage.
            The date shown is the earlier of the mileage date and the time limit.
        </p>
        @if (count($dueRows) === 0)
            <p class="empty">No finished service yet, so there is nothing to estimate.</p>
        @else
            @include('partials.due-table', ['rows' => $dueRows, 'compact' => true])
        @endif
    </div>

    <div class="card">
        <h2>Service history</h2>
        @if ($history->isEmpty())
            <p class="empty">No job orders yet.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr><th>Job order</th><th>Date</th><th class="num">Odometer</th><th>Services</th><th>Mechanic</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($history as $jobOrder)
                        <tr>
                            <td><a href="{{ route('job-orders.show', $jobOrder) }}">{{ $jobOrder->job_number }}</a></td>
                            <td>{{ $jobOrder->service_date->format('M d, Y') }}</td>
                            <td class="num">{{ number_format($jobOrder->odometer_at_service) }} km</td>
                            <td>
                                {{ $jobOrder->serviceTypes->pluck('name')->join(', ') ?: '-' }}
                                @if ($jobOrder->complaint) <br><span class="small">{{ $jobOrder->complaint }}</span> @endif
                            </td>
                            <td>{{ $jobOrder->mechanic?->name ?? '-' }}</td>
                            <td>@include('partials.status-badge', ['status' => $jobOrder->status])</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
