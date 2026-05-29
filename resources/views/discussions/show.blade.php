<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <a href="{{ route('teams.discussions.index', $team) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Discussions') }}</a>
            <span class="text-gray-400"> / </span>
            {{ $thread->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @livewire('discussions.show', ['team' => $team, 'thread' => $thread])
        </div>
    </div>
</x-app-layout>
