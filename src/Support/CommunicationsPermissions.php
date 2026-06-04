<?php

namespace Afterburner\Communications\Support;

use App\Models\Team;
use App\Models\User;

/**
 * Communications module areas (announcements vs discussions) mapped to permission slugs.
 */
final class CommunicationsPermissions
{
    public const SECTION_ANNOUNCEMENTS = 'announcements';

    public const SECTION_DISCUSSIONS = 'discussions';

    /**
     * @return array<string, string>
     */
    public static function sectionPermissionMap(): array
    {
        return [
            self::SECTION_ANNOUNCEMENTS => 'view_announcements',
            self::SECTION_DISCUSSIONS => DiscussionPermissions::VIEW,
        ];
    }

    /**
     * @return list<string>
     */
    public static function sectionDisplayOrder(): array
    {
        $sections = [self::SECTION_ANNOUNCEMENTS];

        if (config('afterburner-communications.discussions.enabled', true)) {
            $sections[] = self::SECTION_DISCUSSIONS;
        }

        return $sections;
    }

    /**
     * @return list<string>
     */
    public static function moduleAccessSlugs(): array
    {
        return [
            'view_announcements',
            'post_announcements',
            'edit_announcements',
            'delete_announcements',
            'manage_announcements',
            DiscussionPermissions::VIEW,
            DiscussionPermissions::CREATE,
            DiscussionPermissions::EDIT,
            DiscussionPermissions::ARCHIVE,
            DiscussionPermissions::LOCK,
            DiscussionPermissions::DELETE,
            DiscussionPermissions::MODERATE_POSTS,
            DiscussionPermissions::LEGACY_MANAGE,
        ];
    }

    public static function canAccessModule(User $user, Team $team): bool
    {
        return TeamPermissionGate::allowsAny($user, $team->id, self::moduleAccessSlugs());
    }

    public static function canViewSection(User $user, Team $team, string $section): bool
    {
        $slug = self::sectionPermissionMap()[$section] ?? null;

        if ($slug === null) {
            return false;
        }

        if ($section === self::SECTION_ANNOUNCEMENTS) {
            return TeamPermissionGate::allowsAny($user, $team->id, [
                $slug,
                'manage_announcements',
                'post_announcements',
            ]);
        }

        return TeamPermissionGate::allowsAny($user, $team->id, [
            $slug,
            DiscussionPermissions::LEGACY_MANAGE,
            DiscussionPermissions::CREATE,
        ]);
    }

    /**
     * @return list<string>
     */
    public static function visibleSections(User $user, Team $team): array
    {
        $visible = [];

        foreach (self::sectionDisplayOrder() as $section) {
            if (self::canViewSection($user, $team, $section)) {
                $visible[] = $section;
            }
        }

        return $visible;
    }
}
