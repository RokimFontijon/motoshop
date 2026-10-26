{{-- Simple line icons. Usage: @include('partials.icon', ['name' => 'alert']) --}}
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('alert')
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            @break
        @case('clipboard')
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>
            @break
        @case('box')
            <path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><polyline points="3.3 7 12 12 20.7 7"/><line x1="12" y1="22" x2="12" y2="12"/>
            @break
        @case('bike')
            <circle cx="5.5" cy="17" r="3.5"/><circle cx="18.5" cy="17" r="3.5"/><path d="M5.5 17L9 9h5l4.5 8"/><path d="M9 9L7.5 6H5"/><path d="M14 9l1-3h2.5"/>
            @break
    @endswitch
</svg>
