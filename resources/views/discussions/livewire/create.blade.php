<form wire:submit="store" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="max-w-xl">
        <x-label for="title" value="{{ __('Title') }}" />
        <x-input id="title" type="text" class="mt-1 block w-full" wire:model="title" />
        <x-input-error for="title" class="mt-2" />
    </div>

    <div class="max-w-xs">
        <x-label for="scope" value="{{ __('Scope') }}" />
        <x-select-input id="scope" wire:model.live="scope" class="mt-1 block w-full">
            <option value="team">{{ __('Team (all members)') }}</option>
            <option value="council">{{ __('Council only') }}</option>
            @if($this->properties->isNotEmpty())
                <option value="property">{{ __('Property') }}</option>
            @endif
        </x-select-input>
        <x-input-error for="scope" class="mt-2" />
    </div>

    @if($scope === 'property' && $this->properties->isNotEmpty())
        <div class="max-w-xs">
            <x-label for="propertyId" value="{{ __('Property') }}" />
            <x-select-input id="propertyId" wire:model="propertyId" class="mt-1 block w-full">
                <option value="">{{ __('Select property') }}</option>
                @foreach($this->properties as $property)
                    <option value="{{ $property->id }}">{{ __('Lot') }} {{ $property->lot_number }}</option>
                @endforeach
            </x-select-input>
            <x-input-error for="propertyId" class="mt-2" />
        </div>
    @endif

    <div>
        <x-label for="body" value="{{ __('Opening post') }}" />
        <x-textarea-input id="body" wire:model="body" rows="6" class="mt-1 block w-full" />
        <x-input-error for="body" class="mt-2" />
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3">
        <x-secondary-button type="button" wire:click="$redirectRoute('teams.discussions.index', ['team' => $team->id])" no-spinner>
            {{ __('Cancel') }}
        </x-secondary-button>
        <x-button type="submit" wire:loading.attr="disabled" wire:target="store">
            {{ __('Create thread') }}
        </x-button>
    </div>
</form>
