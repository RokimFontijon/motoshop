@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <h2>Welcome!</h2>
    <p>This is the home page of our multi-page Laravel + Blade site.</p>

    <x-alert type="success">
        This page is rendered using a shared layout and a reusable Blade component.
    </x-alert>
@endsection
