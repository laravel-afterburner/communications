<?php

namespace Afterburner\Communications\Support;

final class CommunicationsPermissionGroups
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function definitions(): array
    {
        return [
            'Communications' => [
                'post_announcements',
                'manage_discussions',
            ],
        ];
    }
}
