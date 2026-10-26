@extends('layouts.guest')
@section('title', 'Register')

@section('content')
    <div class="auth">
        <p class="eyebrow">Join the shop</p>
        <h1>Create an account</h1>
        <p class="sub">New accounts are mechanic accounts. An admin can change roles later.</p>

        <div class="card">
            <form method="POST" action="{{ route('register') }}" class="stack">
                @csrf
                <div>
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
                </div>
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label for="password">Password <span class="hint">(at least 8 characters)</span></label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div>
                    <label for="password_confirmation">Confirm password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required>
                </div>
                <button type="submit" class="btn" style="width: 100%;">Register</button>
            </form>
        </div>

        <p style="text-align: center;">Already registered? <a href="{{ route('login') }}">Log in</a></p>
    </div>
@endsection
