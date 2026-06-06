<p class="text-xs text-gray-600 dark:text-gray-400">
    Leave empty to save as draft. Announcements will be visible when the publish date arrives.
</p>
<p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
    <strong>Note:</strong> Times will be saved in your {{ entity_label() }}'s timezone ({{ $team->timezone ?? config('app.timezone', 'UTC') }}), even though the picker displays your computer's timezone.
</p>
