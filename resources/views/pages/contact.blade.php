@extends('layouts.app')

@section('title', 'Contact')

@section('content')
    <h2>Contact Us</h2>
    <p>Have a question? Reach out to us at <strong>hello@example.com</strong>.</p>

    <x-alert type="warning">
        This is a static demo page — no form submission is wired up yet.
    </x-alert>
@endsection
