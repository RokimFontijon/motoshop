@extends('layouts.app')
@section('title', 'Parts inventory')

@section('content')
    @php($isAdmin = auth()->user()->isAdmin())

    <div class="page-head">
        <h1>Parts inventory</h1>
        @if ($isAdmin)
            <div class="actions">
                <a class="btn" href="{{ route('parts.create') }}">+ New part</a>
            </div>
        @endif
    </div>

    <div class="card">
        <form method="GET" action="{{ route('parts.index') }}" class="filters">
            <div class="grow">
                <label for="q">Search</label>
                <input type="search" id="q" name="q" value="{{ $search }}" placeholder="Part name or code">
            </div>
            <label class="inline-check">
                <input type="checkbox" name="low" value="1" @checked($lowOnly)> Low stock only
            </label>
            <button type="submit" class="btn btn-outline">Filter</button>
            @if ($search !== '' || $lowOnly)
                <a class="btn btn-outline" href="{{ route('parts.index') }}">Clear</a>
            @endif
        </form>

        @if ($parts->isEmpty())
            <p class="empty">No parts found.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Code</th><th>Part</th><th class="num">Unit price</th>
                        <th class="num">In stock</th><th class="num">Reorder at</th>
                        @if ($isAdmin) <th></th> @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($parts as $part)
                        <tr>
                            <td>{{ $part->part_code }}</td>
                            <td>{{ $part->name }}</td>
                            <td class="num">&#8369;{{ number_format($part->unit_price, 2) }}</td>
                            <td class="num">
                                @if ($part->isLowStock())
                                    <span class="badge badge-bad">{{ $part->stock_qty }} low</span>
                                @else
                                    {{ $part->stock_qty }}
                                @endif
                            </td>
                            <td class="num">{{ $part->reorder_level }}</td>
                            @if ($isAdmin)
                                <td class="num">
                                    <a class="btn btn-outline btn-sm" href="{{ route('parts.edit', $part) }}">Edit</a>
                                    <form method="POST" action="{{ route('parts.destroy', $part) }}" class="inline"
                                          onsubmit="return confirm('Delete this part?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $parts->links('partials.pagination') }}
        @endif
    </div>
@endsection
