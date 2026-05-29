<div>
    <div class="mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <x-label for="scopeFilter" value="{{ __('Scope') }}" />
            <select id="scopeFilter" wire:model.live="scopeFilter" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                <option value="">{{ __('All') }}</option>
                <option value="council">{{ __('Council') }}</option>
                <option value="team">{{ __('Team') }}</option>
                <option value="property">{{ __('Property') }}</option>
            </select>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($this->threads as $thread)
            <a href="{{ route('teams.discussions.show', [$team, $thread]) }}" class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:navigate>
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $thread->title }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $thread->scope->label() }}
                            @if($thread->isLocked())
                                · {{ __('Locked') }}
                            @endif
                        </p>
                    </div>
                    <span class="text-xs text-gray-400">{{ $thread->updated_at->diffForHumans() }}</span>
                </div>
            </a>
        @empty
            <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">{{ __('No discussion threads yet.') }}</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $this->threads->links() }}
    </div>
</div>
