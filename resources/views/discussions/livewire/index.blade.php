<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="w-full sm:max-w-md">
            <x-input
                id="discussion-search"
                type="search"
                class="mt-1 block w-full"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search threads and posts…') }}"
            />
        </div>

        @if ($canCreate)
            <x-button href="{{ route('teams.discussions.create', ['team' => $team]) }}" wire:navigate>
                {{ __('New thread') }}
            </x-button>
        @endif
    </div>

    <div class="overflow-x-auto bg-white shadow sm:rounded-lg dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Thread Topic') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Scope') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Last reply') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Status') }}
                    </th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">{{ __('Actions') }}</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse($threads as $thread)
                    <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="thread-row-{{ $thread->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                            <a
                                href="{{ route('teams.discussions.show', ['team' => $team, 'thread' => $thread]) }}"
                                wire:navigate
                                class="hover:text-indigo-600 dark:hover:text-indigo-400"
                            >
                                {{ $thread->title }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            @if($thread->scope->value === 'property' && \Afterburner\Communications\Models\DiscussionThread::propertyModelClass())
                                @php($propertyLabels = $thread->propertyLotLabels())
                                <span class="inline-flex items-center gap-1.5">
                                    <span>{{ __('Property') }}</span>
                                    <x-afterburner-communications::info-hint
                                        :label="__('View properties for this thread')"
                                        width="w-56"
                                    >
                                        @if($propertyLabels->isNotEmpty())
                                            <ul class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                                @foreach($propertyLabels as $label)
                                                    <li>{{ $label }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('No properties linked to this thread.') }}</p>
                                        @endif
                                    </x-afterburner-communications::info-hint>
                                </span>
                            @else
                                {{ $thread->scope->label() }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $thread->latestReplyAuthorName() ?? '—' }} - {!! format_date_superscript($thread->updated_at, 'datetime') !!}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            @if($thread->isArchived())
                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                    {{ __('Archived') }}
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    {{ __('Active') }}
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <x-action-icon type="view" href="{{ route('teams.discussions.show', ['team' => $team, 'thread' => $thread]) }}" wire:navigate title="{{ __('View thread') }}" />
                                @if($thread->isLocked())
                                    <x-action-icon type="lock" title="{{ __('Locked') }}" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            @if($search !== '')
                                {{ __('No threads match your search.') }}
                            @else
                                {{ __('No discussion threads yet.') }}
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $threads->links() }}
    </div>

    @if($postMatches)
        <div class="mt-10">
            <h2 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Matching posts') }}
            </h2>

            <div class="overflow-x-auto bg-white shadow sm:rounded-lg dark:bg-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Post') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('In thread') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Author') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Posted') }}
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">{{ __('Actions') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @forelse($postMatches as $post)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="post-match-row-{{ $post->id }}">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <a
                                        href="{{ \Afterburner\Communications\Support\DiscussionPostUrl::forPost($post) }}"
                                        wire:navigate
                                        class="line-clamp-2 hover:text-indigo-600 dark:hover:text-indigo-400"
                                    >
                                        {{ trim(Str::limit($post->body, 120)) }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <a
                                        href="{{ route('teams.discussions.show', ['team' => $team, 'thread' => $post->thread]) }}"
                                        wire:navigate
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400"
                                    >
                                        {{ $post->thread->title }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $post->user?->name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {!! format_date_superscript($post->created_at, 'datetime') !!}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <x-action-icon
                                        type="view"
                                        href="{{ \Afterburner\Communications\Support\DiscussionPostUrl::forPost($post) }}"
                                        wire:navigate
                                        title="{{ __('View post') }}"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('No posts match your search.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $postMatches->links() }}
            </div>
        </div>
    @endif
</div>
