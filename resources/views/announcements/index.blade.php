<x-app-layout title="{{ Str::title(config('afterburner.entity_label')) }} Announcements">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ Str::title(config('afterburner.entity_label')) }} Announcements
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @livewire('team-announcements.announcement-manager', ['team' => $team], key('announcements-'.$team->id))
        </div>
    </div>
</x-app-layout>

