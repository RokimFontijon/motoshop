@extends('layouts.app')
@section('title', 'Motorcycles')

@section('content')
    <div class="page-head">
        <h1>Motorcycles</h1>
        <div class="actions">
            <a class="btn" href="{{ route('motorcycles.create') }}">+ New motorcycle</a>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('motorcycles.index') }}" class="filters">
            <div class="grow">
                <label for="q">Search</label>
                <input type="search" id="q" name="q" value="{{ $search }}" placeholder="Plate number, owner or model">
            </div>
            <button type="submit" class="btn btn-outline">Search</button>
            @if ($search !== '')
                <a class="btn btn-outline" href="{{ route('motorcycles.index') }}">Clear</a>
            @endif
        </form>

        @if ($motorcycles->isEmpty())
            <p class="empty">No motorcycles found.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr><th>Plate</th><th>Model</th><th>Owner</th><th class="num">Odometer</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($motorcycles as $motorcycle)
                        <tr>
                            <td><a href="{{ route('motorcycles.show', $motorcycle) }}"><strong>{{ $motorcycle->plate_number }}</strong></a></td>
                            <td>{{ $motorcycle->motorcycleModel->label() }}
                                @if ($motorcycle->year) <span class="small">({{ $motorcycle->year }})</span> @endif
                            </td>
                            <td><a href="{{ route('customers.show', $motorcycle->customer) }}">{{ $motorcycle->customer->full_name }}</a></td>
                            <td class="num">{{ number_format($motorcycle->current_odometer) }} km</td>
                            <td class="num">
                                <a class="btn btn-outline btn-sm" href="{{ route('motorcycles.edit', $motorcycle) }}">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $motorcycles->links('partials.pagination') }}
        @endif
    </div>
@endsection
