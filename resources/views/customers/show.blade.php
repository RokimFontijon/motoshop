@extends('layouts.app')
@section('title', $customer->full_name)

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $customer->full_name }}</h1>
            <p class="sub">Customer since {{ $customer->created_at->format('M d, Y') }}</p>
        </div>
        <div class="actions">
            <a class="btn btn-outline" href="{{ route('customers.edit', $customer) }}">Edit</a>
            @if (auth()->user()->isAdmin())
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline"
                      onsubmit="return confirm('Delete this customer?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <dl class="details">
            <dt>Phone</dt><dd>{{ $customer->phone ?: '-' }}</dd>
            <dt>Address</dt><dd>{{ $customer->address ?: '-' }}</dd>
        </dl>
    </div>

    <div class="card">
        <h2>
            Motorcycles
            <a class="btn btn-sm" href="{{ route('motorcycles.create', ['customer_id' => $customer->id]) }}">+ Add motorcycle</a>
        </h2>
        @if ($customer->motorcycles->isEmpty())
            <p class="empty">This customer has no motorcycle yet.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Plate</th><th>Model</th><th>Year</th><th class="num">Odometer</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($customer->motorcycles as $motorcycle)
                        <tr>
                            <td><a href="{{ route('motorcycles.show', $motorcycle) }}"><strong>{{ $motorcycle->plate_number }}</strong></a></td>
                            <td>{{ $motorcycle->motorcycleModel->label() }}</td>
                            <td>{{ $motorcycle->year ?: '-' }}</td>
                            <td class="num">{{ number_format($motorcycle->current_odometer) }} km</td>
                            <td class="num">
                                <a class="btn btn-outline btn-sm" href="{{ route('job-orders.create', ['motorcycle_id' => $motorcycle->id]) }}">New job order</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
