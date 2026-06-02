@props([
    'label',
    'text' => null,
    'width' => 'w-56',
    'scrollable' => false,
])

@php
    $panelClasses = trim("fixed z-[120] {$width} rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800");
    $contentClasses = $scrollable ? 'max-h-48 overflow-y-auto' : '';
@endphp

<span
    {{ $attributes->merge(['class' => 'relative inline-flex shrink-0']) }}
    x-data="{
        tooltipOpen: false,
        panelTop: 0,
        panelLeft: 0,
        scrollHandler: null,
        positionPanel() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            this.panelTop = rect.bottom + 4;
            this.panelLeft = rect.left;
        },
        toggle() {
            this.tooltipOpen = ! this.tooltipOpen;
            if (this.tooltipOpen) {
                this.$nextTick(() => this.positionPanel());
            }
        },
    }"
    x-init="
        $watch('tooltipOpen', (open) => {
            if (open) {
                positionPanel();
                scrollHandler = () => positionPanel();
                document.addEventListener('scroll', scrollHandler, true);
            } else if (scrollHandler) {
                document.removeEventListener('scroll', scrollHandler, true);
                scrollHandler = null;
            }
        });
        $el.addEventListener('alpine:destroy', () => {
            if (scrollHandler) {
                document.removeEventListener('scroll', scrollHandler, true);
            }
        });
    "
    @click.away="tooltipOpen = false"
    @resize.window="if (tooltipOpen) { positionPanel() }"
>
    <button
        type="button"
        x-ref="trigger"
        @click="toggle()"
        class="inline-flex rounded-full text-gray-500 hover:text-gray-600 focus:outline-none active:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300 dark:active:text-gray-400"
        aria-label="{{ $label }}"
        :aria-expanded="tooltipOpen"
    >
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </button>
    <template x-teleport="body">
        <div
            x-show="tooltipOpen"
            x-cloak
            x-transition
            @click.stop
            role="tooltip"
            class="{{ $panelClasses }}"
            :style="`top: ${panelTop}px; left: ${panelLeft}px;`"
        >
            <div @class([$contentClasses => $scrollable])>
                @if (filled($text))
                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $text }}</p>
                @else
                    {{ $slot }}
                @endif
            </div>
        </div>
    </template>
</span>
