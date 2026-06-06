<x-app-layout :title="\Afterburner\Communications\Support\PageHeader::make(__('Discussions'), detail: $thread->title)">
    <x-slot name="header">
        <x-afterburner-communications::page-header :section="__('Discussions')" :detail="$thread->title" />
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 py-6 sm:py-10 sm:px-6 lg:px-8">
            @livewire('discussions.show', ['team' => $team, 'thread' => $thread])
        </div>
    </div>
</x-app-layout>
