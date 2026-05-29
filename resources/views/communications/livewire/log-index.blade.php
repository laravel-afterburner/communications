<div>
    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <x-label for="search" value="{{ __('Search') }}" />
            <x-input id="search" type="search" class="mt-1 block w-full" wire:model.live.debounce.300ms="search" placeholder="{{ __('Subject, body, recipients…') }}" />
        </div>
        <div>
            <x-label for="channelFilter" value="{{ __('Channel') }}" />
            <select id="channelFilter" wire:model.live="channelFilter" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                <option value="">{{ __('All') }}</option>
                @foreach($channels as $channel)
                    <option value="{{ $channel->value }}">{{ ucfirst(str_replace('_', ' ', $channel->value)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-label for="dateFrom" value="{{ __('From') }}" />
            <x-input id="dateFrom" type="date" class="mt-1 block w-full" wire:model.live="dateFrom" />
        </div>
        <div>
            <x-label for="dateTo" value="{{ __('To') }}" />
            <x-input id="dateTo" type="date" class="mt-1 block w-full" wire:model.live="dateTo" />
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('When') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Channel') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Subject') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Recipients') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Sent by') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($this->entries as $entry)
                    <tr wire:key="log-{{ $entry->id }}">
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $entry->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $entry->channel->value)) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $entry->subject }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate" title="{{ $entry->recipient_summary }}">{{ $entry->recipient_summary }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $entry->sender?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-sm text-gray-500 dark:text-gray-400 text-center">{{ __('No communication log entries yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->entries->links() }}
    </div>
</div>
