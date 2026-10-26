@extends('layouts.app')
@section('title', 'Customers')

@section('content')
    <div class="page-head">
        <h1>Customers</h1>
        <div class="actions">
            <a class="btn" href="{{ route('customers.create') }}">+ New customer</a>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('customers.index') }}" class="filters">
            <div class="grow">
                <label for="q">Search</label>
                <input type="search" id="q" name="q" value="{{ $search }}" placeholder="Name or phone number">
            </div>
            <button type="submit" class="btn btn-outline">Search</button>
            @if ($search !== '')
                <a class="btn btn-outline" href="{{ route('customers.index') }}">Clear</a>
            @endif
        </form>

        @if ($customers->isEmpty())
            <p class="empty">No customers found.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr><th>Name</th><th>Phone</th><th>Address</th><th class="num">Motorcycles</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td><a href="{{ route('customers.show', $customer) }}"><strong>{{ $customer->full_name }}</strong></a></td>
                            <td>{{ $customer->phone ?: '-' }}</td>
                            <td>{{ $customer->address ?: '-' }}</td>
                            <td class="num">{{ $customer->motorcycles_count }}</td>
                            <td class="num">
                                <a class="btn btn-outline btn-sm" href="{{ route('customers.edit', $customer) }}">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $customers->links('partials.pagination') }}
        @endif
    </div>
@endsection
