<x-app-layout :title="\Afterburner\Communications\Support\PageHeader::make(__('Communications'), detail: __('Chat log'))">
    <x-slot name="header">
        <x-afterburner-communications::page-header :section="__('Communications')" :detail="__('Chat log')" />
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @livewire('communications.log-index', ['team' => $team])
        </div>
    </div>
</x-app-layout>
