<div>
    @if ($canCreate)
        <div class="mb-6 flex justify-end">
            <x-button href="{{ route('teams.discussions.create', ['team' => $team]) }}" wire:navigate>
                {{ __('New thread') }}
            </x-button>
        </div>
    @endif

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
                @forelse($this->threads as $thread)
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
                                <span
                                    class="inline-flex items-center gap-1.5"
                                    x-data="{ tooltipOpen: false }"
                                    @click.away="tooltipOpen = false"
                                >
                                    <span>{{ __('Property') }}</span>
                                    <span class="relative inline-flex">
                                        <button
                                            type="button"
                                            @click="tooltipOpen = ! tooltipOpen"
                                            class="inline-flex rounded-full text-gray-500 hover:text-gray-600 focus:outline-none active:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300 dark:active:text-gray-400"
                                            aria-label="{{ __('View properties for this thread') }}"
                                            @if($propertyLabels->isNotEmpty())
                                                title="{{ $propertyLabels->implode(', ') }}"
                                            @endif
                                        >
                                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </button>
                                        <div
                                            x-show="tooltipOpen"
                                            x-cloak
                                            x-transition
                                            @click.away="tooltipOpen = false"
                                            class="absolute left-0 top-full z-50 mt-1 w-56 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800"
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
                                        </div>
                                    </span>
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
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No discussion threads yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $this->threads->links() }}
    </div>
</div>
