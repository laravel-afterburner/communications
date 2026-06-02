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
                'name' => 'Create Discussions',
                'slug' => DiscussionPermissions::CREATE,
                'description' => "Start new discussion threads for the {$entity}",
            ],
            [
                'name' => 'Edit Discussions',
                'slug' => DiscussionPermissions::EDIT,
                'description' => 'Edit discussion thread title, scope, and properties',
            ],
            [
                'name' => 'Archive Discussions',
                'slug' => DiscussionPermissions::ARCHIVE,
                'description' => 'Archive and restore discussion threads',
            ],
            [
                'name' => 'Lock Discussions',
                'slug' => DiscussionPermissions::LOCK,
                'description' => 'Lock and unlock discussion threads',
            ],
            [
                'name' => 'Delete Discussions',
                'slug' => DiscussionPermissions::DELETE,
                'description' => 'Permanently delete discussion threads',
            ],
            [
                'name' => 'Moderate Discussion Posts',
                'slug' => DiscussionPermissions::MODERATE_POSTS,
                'description' => "Edit or delete other members' discussion posts",
            ],
        ];
    }
}
