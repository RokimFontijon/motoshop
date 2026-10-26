<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="topbar">
    <div class="container bar">
        <a class="brand" href="{{ route('dashboard') }}">@include('partials.brand-mark')<span class="brand-text">Moto<span>Shop</span></span></a>

        @auth
            <nav class="nav" aria-label="Main">
                <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Dashboard</a>
                <a href="{{ route('job-orders.index') }}" @class(['active' => request()->routeIs('job-orders.*')])>Job Orders</a>
                <a href="{{ route('customers.index') }}" @class(['active' => request()->routeIs('customers.*')])>Customers</a>
                <a href="{{ route('motorcycles.index') }}" @class(['active' => request()->routeIs('motorcycles.*')])>Motorcycles</a>
                <a href="{{ route('parts.index') }}" @class(['active' => request()->routeIs('parts.*')])>Parts</a>
                <a href="{{ route('reports.index') }}" @class(['active' => request()->routeIs('reports.*')])>Reports</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('users.index') }}" @class(['active' => request()->routeIs('users.*')])>Staff</a>
                @endif
            </nav>

            <div class="userbox">
                <span>{{ auth()->user()->name }} <span class="badge badge-role">{{ auth()->user()->role->name }}</span></span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-link">Log out</button>
                </form>
            </div>
        @endauth
    </div>
</header>

<main class="container">
    @if (session('status'))
        <div class="flash success" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="flash error" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="flash error" role="alert">
            Please fix the following:
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer>
    Web-Based Job Order, Inventory, and Service Reminder System for a Motorcycle Repair Shop
</footer>
</body>
</html>
