@props([
    'id',
    'label' => null,
    'wireModel',
    'rows' => 4,
    'mentionableUsers' => [],
])

@php
    $inputId = $id;
@endphp

<div
    x-data="{
        users: @js($mentionableUsers),
        query: '',
        showSuggestions: false,
        activeIndex: 0,
        mentionStart: null,
        get filteredUsers() {
            if (this.query === '') {
                return this.users;
            }

            const needle = this.query.toLowerCase();

            return this.users.filter(user => user.name.toLowerCase().includes(needle));
        },
        handleInput(event) {
            const textarea = event.target;
            const value = textarea.value;
            const cursor = textarea.selectionStart;
            const beforeCursor = value.slice(0, cursor);
            const match = beforeCursor.match(/@([^\s@]*)$/);

            if (! match) {
                this.closeSuggestions();

                return;
            }

            this.mentionStart = cursor - match[0].length;
            this.query = match[1];
            this.showSuggestions = this.filteredUsers.length > 0;
            this.activeIndex = 0;
        },
        closeSuggestions() {
            this.showSuggestions = false;
            this.query = '';
            this.mentionStart = null;
            this.activeIndex = 0;
        },
        insertMention(user) {
            const textarea = this.$refs.textarea;
            const value = textarea.value;
            const cursor = textarea.selectionStart;
            const start = this.mentionStart ?? cursor;
            const before = value.slice(0, start);
            const after = value.slice(cursor);
            const mention = '@' + user.name + ' ';
            const nextValue = before + mention + after;

            textarea.value = nextValue;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));

            const nextCursor = start + mention.length;
            textarea.focus();
            textarea.setSelectionRange(nextCursor, nextCursor);

            this.closeSuggestions();
        },
        handleKeydown(event) {
            if (! this.showSuggestions) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.activeIndex = Math.min(this.activeIndex + 1, this.filteredUsers.length - 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.activeIndex = Math.max(this.activeIndex - 1, 0);
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                if (this.filteredUsers.length > 0) {
                    event.preventDefault();
                    this.insertMention(this.filteredUsers[this.activeIndex]);
                }
            } else if (event.key === 'Escape') {
                this.closeSuggestions();
            }
        },
    }"
    class="relative"
    @click.away="closeSuggestions()"
>
    @if($label)
        <x-label for="{{ $inputId }}" value="{{ $label }}" />
    @endif

    <x-textarea-input
        x-ref="textarea"
        id="{{ $inputId }}"
        wire:model="{{ $wireModel }}"
        rows="{{ $rows }}"
        class="{{ $label ? 'mt-1' : '' }} block w-full"
        x-on:input="handleInput($event)"
        x-on:keydown="handleKeydown($event)"
        {{ $attributes->except(['class']) }}
    />

    <ul
        x-show="showSuggestions"
        x-cloak
        class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-600 dark:bg-gray-800"
    >
        <template x-for="(user, index) in filteredUsers" :key="user.id">
            <li>
                <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-indigo-950/40"
                    :class="{ 'bg-indigo-50 dark:bg-indigo-950/40': index === activeIndex }"
                    x-on:mousedown.prevent="insertMention(user)"
                    x-text="user.name"
                ></button>
            </li>
        </template>
    </ul>
</div>
