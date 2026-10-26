@extends('layouts.app')
@section('title', 'Staff accounts')

@section('content')
    <div class="page-head">
        <h1>Staff accounts</h1>
        <div class="actions">
            <a class="btn" href="{{ route('users.create') }}">+ New account</a>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th class="num">Job orders</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong> @if ($user->is(auth()->user())) <span class="hint">(you)</span> @endif</td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge {{ $user->isAdmin() ? 'badge-info' : 'badge-ok' }}">{{ $user->role->name }}</span></td>
                        <td class="num">{{ $user->job_orders_count }}</td>
                        <td class="num">
                            <a class="btn btn-outline btn-sm" href="{{ route('users.edit', $user) }}">Edit</a>
                            @unless ($user->is(auth()->user()))
                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline"
                                      onsubmit="return confirm('Delete this account? Its job orders are kept.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
