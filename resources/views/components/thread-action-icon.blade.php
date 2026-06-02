@props([
    'type' => 'archive',
    'title' => '',
])

@php
    $baseClass = 'rounded p-1 text-gray-400 transition';

    $hoverClass = match ($type) {
        'archive', 'unarchive' => 'hover:text-gray-600 dark:hover:text-gray-300',
        'lock', 'unlock' => 'hover:text-amber-600 dark:hover:text-amber-400',
        default => 'hover:text-gray-600 dark:hover:text-gray-300',
    };

    $class = trim("{$baseClass} {$hoverClass}");

    $icons = [
        'archive' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>',
        'unarchive' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"></path>',
        'lock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"></path>',
        'unlock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"></path>',
    ];

    $iconPath = $icons[$type] ?? $icons['archive'];
@endphp

<button
    type="button"
    {{ $attributes->merge(['class' => $class]) }}
    @if ($title)
        title="{{ $title }}"
    @endif
>
    @if ($title)
        <span class="sr-only">{{ $title }}</span>
    @endif
    <svg
        class="h-5 w-5"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
    >
        {!! $iconPath !!}
    </svg>
</button>
