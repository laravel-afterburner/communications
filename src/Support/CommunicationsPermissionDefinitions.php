<?php

namespace Afterburner\Communications\Support;

final class CommunicationsPermissionDefinitions
{
    /**
     * @return array<int, array{name: string, slug: string, description: string}>
     */
    public static function all(): array
    {
        $entity = config('afterburner.entity_label', 'team');

        return [
            [
                'name' => 'Post Announcements',
                'slug' => 'post_announcements',
                'description' => "Post announcements to the {$entity}",
            ],
            [
                'name' => 'Manage Discussions',
                'slug' => 'manage_discussions',
                'description' => "Create and moderate discussion threads for the {$entity}",
            ],
        ];
    }
}
