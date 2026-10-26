@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <section class="hero">
        <div class="hero-text">
            <p class="eyebrow">{{ now()->format('l, F j, Y') }}</p>
            <h1>{{ now()->hour < 12 ? 'Good morning' : (now()->hour < 18 ? 'Good afternoon' : 'Good evening') }}, {{ auth()->user()->name }}.</h1>
            <p class="hero-lead">
                @if ($overdueCount + $dueSoonCount > 0)
                    {{ $overdueCount }} overdue and {{ $dueSoonCount }} due soon. These customers need a call.
                @else
                    No services are overdue or due soon. The shop is on schedule.
                @endif
            </p>
            <div class="actions">
                <a class="btn" href="{{ route('job-orders.create') }}">+ New job order</a>
                <a class="btn btn-ghost" href="{{ route('customers.create') }}">+ New customer</a>
            </div>
        </div>
        @include('partials.bike-art', ['class' => 'hero-bike'])
    </section>

    <div class="stats">
        <div class="stat bad">
            <span class="stat-icon">@include('partials.icon', ['name' => 'alert'])</span>
            <div class="num">{{ $overdueCount }}</div>
            <div class="label">Services overdue</div>
        </div>
        <div class="stat soon">
            <span class="stat-icon">@include('partials.icon', ['name' => 'clock'])</span>
            <div class="num">{{ $dueSoonCount }}</div>
            <div class="label">Services due within 14 days</div>
        </div>
        <div class="stat">
            <span class="stat-icon">@include('partials.icon', ['name' => 'clipboard'])</span>
            <div class="num">{{ $openJobOrders->count() }}</div>
            <div class="label">Open job orders</div>
        </div>
        <div class="stat {{ $lowStockParts->count() ? 'bad' : 'ok' }}">
            <span class="stat-icon">@include('partials.icon', ['name' => 'box'])</span>
            <div class="num">{{ $lowStockParts->count() }}</div>
            <div class="label">Parts low on stock</div>
        </div>
        <div class="stat ok">
            <span class="stat-icon">@include('partials.icon', ['name' => 'bike'])</span>
            <div class="num">{{ $motorcycleCount }}</div>
            <div class="label">Motorcycles &middot; {{ $customerCount }} customers</div>
        </div>
    </div>

    <div class="card">
        <h2>
            Service reminders
            <span class="hint">Call these customers to book their next service</span>
        </h2>
        @include('partials.due-table', ['rows' => $reminders])
        @if ($totalReminders > count($reminders))
            <p class="hint" style="margin-bottom: 0;">Showing the {{ count($reminders) }} most urgent of {{ $totalReminders }}.</p>
        @endif
    </div>

    <div class="grid two">
        <div class="card">
            <h2>Open job orders <a class="btn btn-outline btn-sm" href="{{ route('job-orders.index') }}">All job orders</a></h2>
            @if ($openJobOrders->isEmpty())
                <p class="empty">No open job orders.</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Job</th><th>Motorcycle</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($openJobOrders as $jobOrder)
                            <tr>
                                <td><a href="{{ route('job-orders.show', $jobOrder) }}">{{ $jobOrder->job_number }}</a><br>
                                    <span class="small">{{ $jobOrder->service_date->format('M d') }}</span></td>
                                <td>{{ $jobOrder->motorcycle->plate_number }}<br>
                                    <span class="small">{{ $jobOrder->motorcycle->customer->full_name }}</span></td>
                                <td>@include('partials.status-badge', ['status' => $jobOrder->status])</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card">
            <h2>Low stock <a class="btn btn-outline btn-sm" href="{{ route('parts.index', ['low' => 1]) }}">View all</a></h2>
            @if ($lowStockParts->isEmpty())
                <p class="empty">All parts are above their reorder level.</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Part</th><th class="num">In stock</th><th class="num">Reorder at</th></tr></thead>
                        <tbody>
                        @foreach ($lowStockParts as $part)
                            <tr>
                                <td>{{ $part->name }}<br><span class="small">{{ $part->part_code }}</span></td>
                                <td class="num"><span class="badge badge-bad">{{ $part->stock_qty }}</span></td>
                                <td class="num">{{ $part->reorder_level }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
