{{-- Service reminder table. Needs $rows (from ServiceDueReport). Optional $compact hides the motorcycle columns. --}}
@php($compact = $compact ?? false)

@if (count($rows) === 0)
    <p class="empty">Nothing to show.</p>
@else
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                @unless ($compact)
                    <th>Motorcycle</th>
                    <th>Customer</th>
                @endunless
                <th>Service</th>
                <th>Next due</th>
                <th>When</th>
                <th>Based on</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($rows as $row)
                <tr>
                    @unless ($compact)
                        <td>
                            <a href="{{ route('motorcycles.show', $row['motorcycle']) }}"><strong>{{ $row['motorcycle']->plate_number }}</strong></a><br>
                            <span class="small">{{ $row['motorcycle']->motorcycleModel->label() }}</span>
                        </td>
                        <td>
                            {{ $row['motorcycle']->customer->full_name }}<br>
                            <span class="small">{{ $row['motorcycle']->customer->phone }}</span>
                        </td>
                    @endunless
                    <td>{{ $row['service'] }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($row['next_due'])->format('M d, Y') }}</td>
                    <td>
                        @if ($row['days_left'] < 0)
                            {{ abs($row['days_left']) }} {{ \Illuminate\Support\Str::plural('day', abs($row['days_left'])) }} overdue
                        @elseif ($row['days_left'] === 0)
                            today
                        @else
                            in {{ $row['days_left'] }} {{ \Illuminate\Support\Str::plural('day', $row['days_left']) }}
                        @endif
                    </td>
                    <td class="small" title="Average use: {{ number_format($row['km_per_day'], 1) }} km/day ({{ $row['usage_source'] === 'history' ? 'from service history' : 'default, not enough history yet' }})">
                        @if ($row['basis'] === 'mileage')
                            Mileage: reaches {{ number_format($row['due_odometer']) }} km
                        @else
                            Time limit
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $row['status'] }}">
                            {{ ['overdue' => 'Overdue', 'due_soon' => 'Due soon', 'ok' => 'OK'][$row['status']] }}
                        </span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
