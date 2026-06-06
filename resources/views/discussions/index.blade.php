<x-app-layout :title="\App\Support\PageHeader::make(__('Discussions'))">
    <x-slot name="header">
        <x-afterburner-communications::page-header :section="__('Discussions')" />
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto px-4 py-6 sm:py-10 sm:px-6 lg:px-8">
            @livewire('discussions.index', ['team' => $team])
        </div>
    </div>
</x-app-layout>
