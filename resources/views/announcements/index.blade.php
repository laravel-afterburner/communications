<x-app-layout :title="\App\Support\PageHeader::make(__('Announcements'))">
    <x-slot name="header">
        <x-afterburner-communications::page-header :section="__('Announcements')" />
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto px-4 py-6 sm:py-10 sm:px-6 lg:px-8">
            @livewire('team-announcements.announcement-manager', ['team' => $team], key('announcements-'.$team->id))
        </div>
    </div>
</x-app-layout>
