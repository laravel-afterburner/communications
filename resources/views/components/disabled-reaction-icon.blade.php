@props([
    'type' => 'up',
    'message',
])

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
    <span
        class="inline-flex cursor-not-allowed rounded p-0.5 text-gray-400 opacity-70 dark:text-gray-500"
        role="img"
        aria-label="{{ $message }}"
        :aria-describedby="$id('reaction-denied-tooltip')"
        :aria-expanded="tooltipOpen"
    >
        <x-afterburner-communications::reaction-icon :type="$type" />
    </span>

    <div
        x-show="tooltipOpen"
        x-cloak
        x-transition
        @click.stop
        :id="$id('reaction-denied-tooltip')"
        role="tooltip"
        class="absolute bottom-full left-0 z-20 mb-1 w-max min-w-[8rem] max-w-xs rounded-lg border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-800"
    >
        <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-400">
            {{ $message }}
        </p>
    </div>
</span>
