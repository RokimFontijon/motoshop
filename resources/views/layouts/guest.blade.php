<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Log in') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="guest">

{{-- Layout for the pages people see BEFORE logging in (login and register).
     Left: a brand panel. Right: the form. On a phone the panel stacks on top. --}}
<div class="guest-wrap">

    <aside class="guest-hero" aria-hidden="false">
        <div class="guest-hero-inner">
            <a class="guest-brand" href="{{ route('login') }}">@include('partials.brand-mark')<span class="brand-text">Moto<span>Shop</span></span></a>

            <h2 class="guest-headline">Every motorcycle,<br>right on schedule.</h2>
            <p class="guest-lead">
                One place for job orders, spare parts and service reminders,
                so no customer is forgotten and no part runs out.
            </p>

            @include('partials.bike-art', ['class' => 'guest-bike'])

            <ul class="guest-points">
                <li><span class="dot"></span><span><strong>Job orders</strong> with parts, labor and totals</span></li>
                <li><span class="dot"></span><span><strong>Inventory</strong> that updates itself and warns when stock is low</span></li>
                <li><span class="dot"></span><span><strong>Service reminders</strong> that show who is overdue or due soon</span></li>
            </ul>
        </div>
    </aside>

    <section class="guest-main">
        <div class="guest-panel">
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
        </div>

        <footer>
            Web-Based Job Order, Inventory, and Service Reminder System for a Motorcycle Repair Shop
        </footer>
    </section>
</div>

</body>
</html>
