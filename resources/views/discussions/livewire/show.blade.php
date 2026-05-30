<div class="space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium tracking-wide text-gray-500 dark:text-gray-400">{{ $thread->scope->label() }}</p>
                <h3 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $thread->title }}</h3>
            </div>

            @can('lock', $thread)
                <div class="flex flex-wrap items-center gap-2">
                    @if($thread->isLocked())
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            {{ __('Locked') }}
                        </span>
                        <x-secondary-button wire:click="unlockThread" type="button" no-spinner>{{ __('Unlock') }}</x-secondary-button>
                    @else
                        <x-secondary-button wire:click="lockThread" type="button" no-spinner>{{ __('Lock thread') }}</x-secondary-button>
                    @endif
                </div>
            @elseif($thread->isLocked())
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    {{ __('Locked') }}
                </span>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        @foreach($thread->posts as $post)
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800" wire:key="post-{{ $post->id }}">
                <div class="mb-2 flex justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $post->user->name }}</span>
                    <span>{!! format_date_superscript($post->created_at, 'datetime') !!}</span>
                </div>
                <div class="prose dark:prose-invert max-w-none whitespace-pre-wrap text-gray-800 dark:text-gray-200">{{ $post->body }}</div>
            </div>
        @endforeach
    </div>

    @can('post', $thread)
        <form wire:submit="postReply" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-4">
            <div>
                <x-label for="replyBody" value="{{ __('Reply') }}" />
                <x-textarea-input id="replyBody" wire:model="replyBody" rows="4" class="mt-1 block w-full" />
                <x-input-error for="replyBody" class="mt-2" />
            </div>
            <div class="flex items-center justify-end gap-3">
                <x-action-message on="replied" />
                <x-button type="submit" wire:loading.attr="disabled" wire:target="postReply">
                    {{ __('Post reply') }}
                </x-button>
            </div>
        </form>
    @elseif($thread->isLocked())
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('This thread is locked.') }}</p>
    @endif
</div>
