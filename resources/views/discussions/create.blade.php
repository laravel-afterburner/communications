<x-app-layout :title="\Afterburner\Communications\Support\PageHeader::make(__('Discussions'), action: __('New thread'))">
    <x-slot name="header">
        <x-afterburner-communications::page-header :section="__('Discussions')" :action="__('New thread')" />
    </x-slot>

    <div>
        <div class="max-w-3xl mx-auto py-10 sm:px-6 lg:px-8">
            @livewire('discussions.create', ['team' => $team])
        </div>
    </div>
</x-app-layout>
