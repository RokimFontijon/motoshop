@extends('layouts.guest')
@section('title', 'Log in')

@section('content')
    <div class="auth">
        <p class="eyebrow">Welcome back</p>
        <h1>Log in</h1>
        <p class="sub">Motorcycle repair shop system</p>

        <div class="card">
            <form method="POST" action="{{ route('login') }}" class="stack">
                @csrf
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           @class(['invalid' => $errors->has('email')])>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <label class="inline-check" style="padding-bottom: 0;">
                    <input type="checkbox" name="remember" value="1"> Remember me
                </label>
                <button type="submit" class="btn" style="width: 100%;">Log in</button>
            </form>
        </div>

        <p style="text-align: center;">No account yet? <a href="{{ route('register') }}">Register as a mechanic</a></p>

        @if (app()->environment('local'))
            <div class="demo-box">
                <strong>Demo accounts</strong> (password: <code>password</code>)<br>
                <code>admin@motorshop.test</code> (admin)<br>
                <code>mechanic1@motorshop.test</code> (mechanic)<br>
                <code>mechanic2@motorshop.test</code> (mechanic)
            </div>
        @endif
    </div>
@endsection
