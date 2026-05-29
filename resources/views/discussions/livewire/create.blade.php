<form wire:submit="store" class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 space-y-6">
    <div>
        <x-label for="title" value="{{ __('Title') }}" />
        <x-input id="title" type="text" class="mt-1 block w-full" wire:model="title" />
        <x-input-error for="title" class="mt-2" />
    </div>

    <div>
        <x-label for="scope" value="{{ __('Scope') }}" />
        <select id="scope" wire:model.live="scope" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
            <option value="team">{{ __('Team (all members)') }}</option>
            <option value="council">{{ __('Council only') }}</option>
            @if($this->properties->isNotEmpty())
                <option value="property">{{ __('Property') }}</option>
            @endif
        </select>
        <x-input-error for="scope" class="mt-2" />
    </div>

    @if($scope === 'property' && $this->properties->isNotEmpty())
        <div>
            <x-label for="propertyId" value="{{ __('Property') }}" />
            <select id="propertyId" wire:model="propertyId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                <option value="">{{ __('Select property') }}</option>
                @foreach($this->properties as $property)
                    <option value="{{ $property->id }}">{{ __('Lot') }} {{ $property->lot_number }}</option>
                @endforeach
            </select>
            <x-input-error for="propertyId" class="mt-2" />
        </div>
    @endif

    <div>
        <x-label for="body" value="{{ __('Opening post') }}" />
        <textarea id="body" wire:model="body" rows="6" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"></textarea>
        <x-input-error for="body" class="mt-2" />
    </div>

    <div class="flex gap-3">
        <x-button type="submit">{{ __('Create thread') }}</x-button>
        <a href="{{ route('teams.discussions.index', $team) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700" wire:navigate>
            {{ __('Cancel') }}
        </a>
    </div>
</form>
