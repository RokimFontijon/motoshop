@props(['type' => 'info'])

@php
    $colors = [
        'info' => ['bg' => '#ebf8ff', 'border' => '#3182ce', 'text' => '#2c5282'],
        'success' => ['bg' => '#f0fff4', 'border' => '#38a169', 'text' => '#276749'],
        'warning' => ['bg' => '#fffaf0', 'border' => '#dd6b20', 'text' => '#9c4221'],
    ];
    $color = $colors[$type] ?? $colors['info'];
@endphp

<div style="background: {{ $color['bg'] }}; border-left: 4px solid {{ $color['border'] }}; color: {{ $color['text'] }}; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
    {{ $slot }}
</div>
