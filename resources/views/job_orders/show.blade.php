@extends('layouts.app')
@section('title', $jobOrder->job_number)

@section('content')
    @php($canEdit = $jobOrder->canBeEditedBy(auth()->user()))

    <div class="page-head">
        <div>
            <h1>{{ $jobOrder->job_number }} @include('partials.status-badge', ['status' => $jobOrder->status])</h1>
            <p class="sub">{{ $jobOrder->service_date->format('l, F j, Y') }}</p>
        </div>
        <div class="actions">
            @if ($canEdit)
                <a class="btn btn-outline" href="{{ route('job-orders.edit', $jobOrder) }}">Edit</a>
            @endif
            <button type="button" class="btn btn-outline" onclick="window.print()">Print</button>
            @if (auth()->user()->isAdmin())
                <form method="POST" action="{{ route('job-orders.destroy', $jobOrder) }}" class="inline"
                      onsubmit="return confirm('Delete this job order? Its parts will be returned to stock.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            @endif
        </div>
    </div>

    @unless ($canEdit)
        <p class="note">You can only view this job order. A mechanic can edit their own job orders until they are released.</p>
    @endunless

    <div class="grid two">
        <div class="card">
            <h2>Motorcycle and customer</h2>
            <dl class="details">
                <dt>Motorcycle</dt>
                <dd><a href="{{ route('motorcycles.show', $jobOrder->motorcycle) }}">{{ $jobOrder->motorcycle->plate_number }}</a>
                    &middot; {{ $jobOrder->motorcycle->motorcycleModel->label() }}</dd>
                <dt>Customer</dt>
                <dd><a href="{{ route('customers.show', $jobOrder->motorcycle->customer) }}">{{ $jobOrder->motorcycle->customer->full_name }}</a></dd>
                <dt>Phone</dt><dd>{{ $jobOrder->motorcycle->customer->phone ?: '-' }}</dd>
                <dt>Odometer</dt><dd>{{ number_format($jobOrder->odometer_at_service) }} km</dd>
            </dl>
        </div>
        <div class="card">
            <h2>Work</h2>
            <dl class="details">
                <dt>Mechanic</dt><dd>{{ $jobOrder->mechanic?->name ?? '-' }}</dd>
                <dt>Complaint</dt><dd>{{ $jobOrder->complaint ?: '-' }}</dd>
                <dt>Services</dt>
                <dd>
                    @forelse ($jobOrder->serviceTypes as $serviceType)
                        <span class="badge badge-info">{{ $serviceType->name }}</span>
                    @empty
                        -
                    @endforelse
                </dd>
            </dl>
        </div>
    </div>

    <div class="card" style="margin-top: 1.25rem;">
        <h2>Parts used</h2>

        @if ($jobOrder->partLines->isEmpty())
            <p class="empty">No parts added.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr><th>Part</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Amount</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($jobOrder->partLines as $line)
                        <tr>
                            <td>{{ $line->part->name }} <span class="small">{{ $line->part->part_code }}</span></td>
                            <td class="num">{{ $line->quantity }}</td>
                            <td class="num">&#8369;{{ number_format($line->unit_price, 2) }}</td>
                            <td class="num">&#8369;{{ number_format($line->lineTotal(), 2) }}</td>
                            <td class="num">
                                @if ($canEdit)
                                    <form method="POST" action="{{ route('job-orders.parts.destroy', [$jobOrder, $line]) }}" class="inline"
                                          onsubmit="return confirm('Remove this part? It goes back to stock.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="table-wrap">
            <table class="table" style="margin-top: .5rem;">
                <tfoot>
                <tr><td>Parts total</td><td class="num">&#8369;{{ number_format($jobOrder->partsTotal(), 2) }}</td></tr>
                <tr><td>Labor</td><td class="num">&#8369;{{ number_format($jobOrder->labor_cost, 2) }}</td></tr>
                <tr><td><strong>Grand total</strong></td><td class="num"><strong>&#8369;{{ number_format($jobOrder->grandTotal(), 2) }}</strong></td></tr>
                </tfoot>
            </table>
        </div>

        @if ($canEdit)
            <hr style="border: 0; border-top: 1px solid var(--line); margin: 1rem 0;">
            <form method="POST" action="{{ route('job-orders.parts.store', $jobOrder) }}" class="filters" style="margin-bottom: 0;">
                @csrf
                <div class="grow">
                    <label for="part_id">Add a part</label>
                    <select id="part_id" name="part_id" required>
                        <option value="">Choose a part</option>
                        @foreach ($availableParts as $part)
                            <option value="{{ $part->id }}" @selected(old('part_id') == $part->id)>
                                {{ $part->name }} &middot; &#8369;{{ number_format($part->unit_price, 2) }} &middot; {{ $part->stock_qty }} in stock
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="min-width: 110px; max-width: 130px;">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="1" max="1000" value="{{ old('quantity', 1) }}" required>
                </div>
                <button type="submit" class="btn">Add part</button>
            </form>
            <p class="hint" style="margin-bottom: 0;">The stock goes down automatically when you add a part (a database trigger does it).</p>
        @endif
    </div>
@endsection
