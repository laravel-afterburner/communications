<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div class="flex flex-wrap items-end gap-4">
            <div class="w-full sm:w-auto sm:min-w-[12rem]">
                <x-label for="scopeFilter" value="{{ __('Scope') }}" />
                <x-select-input id="scopeFilter" wire:model.live="scopeFilter" class="mt-1 block w-full">
                    <option value="">{{ __('All') }}</option>
                    <option value="council">{{ __('Council') }}</option>
                    <option value="team">{{ __('Team') }}</option>
                    <option value="property">{{ __('Property') }}</option>
                </x-select-input>
            </div>

            <div class="w-full sm:w-auto sm:min-w-[12rem]">
                <x-label for="archiveFilter" value="{{ __('Status') }}" />
                <x-select-input id="archiveFilter" wire:model.live="archiveFilter" class="mt-1 block w-full">
                    <option value="active">{{ __('Active') }}</option>
                    <option value="archived">{{ __('Archived') }}</option>
                    <option value="all">{{ __('All') }}</option>
                </x-select-input>
            </div>
        </div>

        @if ($canCreate)
            <x-button wire:click="createThread" no-spinner>
                {{ __('New thread') }}
            </x-button>
        @endif
    </div>

    <div class="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Thread') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Scope') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Updated') }}
                    </th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">{{ __('Actions') }}</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse($this->threads as $thread)
                    <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="thread-row-{{ $thread->id }}">
                        <td class="px-6 py-4">
                            <button
                                type="button"
                                wire:click="viewThread({{ $thread->id }})"
                                class="text-left text-sm font-medium text-gray-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                            >
                                {{ $thread->title }}
                            </button>
                            <div class="mt-1 flex flex-wrap gap-1">
                                @if($thread->isLocked())
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        {{ __('Locked') }}
                                    </span>
                                @endif
                                @if($thread->isArchived())
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                        {{ __('Archived') }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $thread->scope->label() }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {!! format_date_superscript($thread->updated_at, 'datetime') !!}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <x-action-icon type="view" wire:click="viewThread({{ $thread->id }})" title="{{ __('View thread') }}" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            @if($archiveFilter === 'archived')
                                {{ __('No archived discussion threads.') }}
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
        {{ $this->threads->links() }}
    </div>
</div>
