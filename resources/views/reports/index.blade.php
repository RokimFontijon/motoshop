@extends('layouts.app')
@section('title', 'Reports')

@section('content')
    <div class="page-head">
        <div>
            <h1>Reports</h1>
            <p class="sub">Sales, services, inventory use and the accuracy of the due date estimates</p>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-outline" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="card">
        <h2>Daily sales <span class="hint">last 30 days, completed and released job orders only</span></h2>
        @if ($salesPerDay->isEmpty())
            <p class="empty">No finished job orders in the last 30 days.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Date</th><th class="num">Job orders</th><th class="num">Sales</th></tr></thead>
                    <tbody>
                    @foreach ($salesPerDay as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->day)->format('D, M d, Y') }}</td>
                            <td class="num">{{ $row->jobs }}</td>
                            <td class="num">&#8369;{{ number_format($row->revenue, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr><td>Total</td><td class="num">{{ $salesPerDay->sum('jobs') }}</td><td class="num">&#8369;{{ number_format($salesTotal, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    <div class="grid three">
        <div class="card">
            <h2>Most common services</h2>
            @forelse ($topServices as $row)
                <p style="margin: .25rem 0; display: flex; justify-content: space-between; gap: .5rem;">
                    <span>{{ $row->name }}</span><strong>{{ $row->times }}&times;</strong>
                </p>
            @empty
                <p class="empty">No data yet.</p>
            @endforelse
        </div>
        <div class="card">
            <h2>Parts used the most</h2>
            @forelse ($partsUsage as $row)
                <p style="margin: .25rem 0; display: flex; justify-content: space-between; gap: .5rem;">
                    <span>{{ $row->name }}</span><strong>{{ $row->qty }} pcs</strong>
                </p>
            @empty
                <p class="empty">No data yet.</p>
            @endforelse
        </div>
        <div class="card">
            <h2>Top customers</h2>
            @forelse ($topCustomers as $row)
                <p style="margin: .25rem 0; display: flex; justify-content: space-between; gap: .5rem;">
                    <span>{{ $row->name }}</span><strong>&#8369;{{ number_format($row->spent, 2) }}</strong>
                </p>
            @empty
                <p class="empty">No data yet.</p>
            @endforelse
        </div>
    </div>

    <div class="card" style="margin-top: 1.25rem;">
        <h2>Estimated vs actual service dates</h2>
        <p class="note">
            For every service that was done more than once, the system estimates the next date using only what was known
            at that time, then compares it with the date the customer really came back.
            A negative difference means the customer came earlier than estimated.
        </p>
        @if ($accuracy['mean_abs_days'] === null)
            <p class="empty">Not enough repeated services to compare yet.</p>
        @else
            <p>
                Average difference: <strong>{{ number_format($accuracy['mean_abs_days'], 1) }} days</strong>
                across {{ count($accuracy['rows']) }} comparisons.
            </p>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr><th>Motorcycle</th><th>Service</th><th>Serviced on</th><th>Estimated next</th><th>Actual next</th><th class="num">Difference</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($accuracy['rows'] as $row)
                        <tr>
                            <td>{{ $row['plate'] }}</td>
                            <td>{{ $row['service'] }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($row['serviced_on'])->format('M d, Y') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($row['estimated_due'])->format('M d, Y') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($row['actual_date'])->format('M d, Y') }}</td>
                            <td class="num">{{ $row['diff_days'] > 0 ? '+' : '' }}{{ $row['diff_days'] }} {{ abs($row['diff_days']) === 1 ? 'day' : 'days' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
