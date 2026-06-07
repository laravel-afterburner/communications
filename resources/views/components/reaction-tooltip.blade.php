@props([
    'label',
    'names' => [],
])

@php
    $namesList = collect($names)->filter()->values();
@endphp

@if ($namesList->isEmpty())
    <span {{ $attributes->merge(['class' => 'inline-flex min-w-[1ch] tabular-nums text-gray-400 dark:text-gray-500']) }}>0</span>
@else
    <span
        {{ $attributes->merge(['class' => 'relative inline-flex shrink-0']) }}
        x-data="{
            tooltipOpen: false,
            hoverCapable: window.matchMedia('(hover: hover) and (pointer: fine)').matches,
            open() {
                this.tooltipOpen = true;
            },
            close() {
                this.tooltipOpen = false;
            },
            toggle() {
                this.tooltipOpen = ! this.tooltipOpen;
            },
        }"
        @mouseenter="if (hoverCapable) { open() }"
        @mouseleave="if (hoverCapable) { close() }"
        @click.stop="toggle()"
        @click.away="close()"
    >
        <button
            type="button"
            class="inline-flex min-w-[1ch] cursor-default tabular-nums text-gray-500 underline decoration-dotted decoration-gray-400 underline-offset-2 hover:text-gray-700 dark:text-gray-400 dark:decoration-gray-500 dark:hover:text-gray-300"
            aria-label="{{ $label }}"
            :aria-expanded="tooltipOpen"
        >
            {{ $namesList->count() }}
        </button>

        <div
            x-show="tooltipOpen"
            x-cloak
            x-transition
            @click.stop
            role="tooltip"
            class="absolute bottom-full left-0 z-20 mb-1 max-h-40 w-max min-w-[8rem] max-w-xs overflow-y-auto rounded-lg border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                {{ $namesList->join(', ') }}
            </p>
        </div>
    </span>
@endif
