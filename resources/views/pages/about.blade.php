@extends('layouts.app')

@section('title', 'About')

@section('content')
    <h2>About This Project</h2>
    <p>This site was built to practice Laravel routing and Blade templating.</p>

    @if (count($skills) > 0)
        <x-alert type="info">
            Here are the skills we practiced while building this project:
        </x-alert>

        <ul>
            @foreach ($skills as $skill)
                <li>{{ $skill }}</li>
            @endforeach
        </ul>
    @else
        <p>No skills to display yet.</p>
    @endif
@endsection
