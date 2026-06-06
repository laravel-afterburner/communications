<?php

namespace Afterburner\Communications\Support;

use Afterburner\Support\EntityLabel;

final class CommunicationsPermissionDefinitions
{
    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return [
            'manage_announcements',
            'view_announcements',
            'post_announcements',
            'edit_announcements',
            'delete_announcements',
            DiscussionPermissions::LEGACY_MANAGE,
            DiscussionPermissions::VIEW,
            ...DiscussionPermissions::all(),
        ];
    }

    /**
     * @return array<int, array{name: string, slug: string, description: string}>
     */
    public static function all(): array
    {
        if (class_exists(\App\Support\PermissionCatalog::class)) {
            return collect(\App\Support\PermissionCatalog::definitions())
                ->filter(fn (array $permission) => in_array($permission['slug'], self::slugs(), true))
                ->values()
                ->all();
        }

        $entity = EntityLabel::singular();

        return [
            [
                'name' => 'Manage Announcements',
                'slug' => 'manage_announcements',
                'description' => "Full access to {$entity} announcements",
            ],
            [
                'name' => 'View Announcements',
                'slug' => 'view_announcements',
                'description' => "View {$entity} announcements",
            ],
            [
                'name' => 'Post Announcements',
                'slug' => 'post_announcements',
                'description' => "Post announcements to the {$entity}",
            ],
            [
                'name' => 'Edit Announcements',
                'slug' => 'edit_announcements',
                'description' => 'Edit existing announcements',
            ],
            [
                'name' => 'Delete Announcements',
                'slug' => 'delete_announcements',
                'description' => 'Delete announcements',
            ],
            [
                'name' => 'Manage Discussions',
                'slug' => DiscussionPermissions::LEGACY_MANAGE,
                'description' => 'Full access to discussion threads',
            ],
            [
                'name' => 'View Discussions',
                'slug' => DiscussionPermissions::VIEW,
                'description' => 'View council and team discussion threads',
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
