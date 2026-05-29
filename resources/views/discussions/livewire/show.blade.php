<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
        <div class="flex justify-between items-start gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $thread->scope->label() }}</p>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $thread->title }}</h3>
            </div>
            @can('lock', $thread)
                <div class="flex gap-2">
                    @if($thread->isLocked())
                        <x-secondary-button wire:click="unlockThread" type="button">{{ __('Unlock') }}</x-secondary-button>
                    @else
                        <x-secondary-button wire:click="lockThread" type="button">{{ __('Lock') }}</x-secondary-button>
                    @endif
                </div>
            @endcan
        </div>
    </div>

    <div class="space-y-4">
        @foreach($thread->posts as $post)
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4" wire:key="post-{{ $post->id }}">
                <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-2">
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $post->user->name }}</span>
                    <span>{{ $post->created_at->format('M j, Y g:i A') }}</span>
                </div>
                <div class="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $post->body }}</div>
            </div>
        @endforeach
    </div>

    @can('post', $thread)
        <form wire:submit="postReply" class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 space-y-4">
            <div>
                <x-label for="replyBody" value="{{ __('Reply') }}" />
                <textarea id="replyBody" wire:model="replyBody" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"></textarea>
                <x-input-error for="replyBody" class="mt-2" />
            </div>
            <x-button type="submit">{{ __('Post reply') }}</x-button>
        </form>
    @elseif($thread->isLocked())
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('This thread is locked.') }}</p>
    @endif
</div>
