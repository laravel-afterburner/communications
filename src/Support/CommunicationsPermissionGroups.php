<?php

namespace Afterburner\Communications\Support;

final class CommunicationsPermissionGroups
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function definitions(): array
    {
        if (class_exists(\App\Support\PermissionGroups::class)) {
            return collect(\App\Support\PermissionGroups::definitions())
                ->only(['Communications'])
                ->all();
        }

        return [
            'Communications' => [
                'manage_announcements',
                'view_announcements',
                'post_announcements',
                'edit_announcements',
                'delete_announcements',
                DiscussionPermissions::LEGACY_MANAGE,
                DiscussionPermissions::VIEW,
                ...DiscussionPermissions::all(),
            ],
        ];
    }
}
