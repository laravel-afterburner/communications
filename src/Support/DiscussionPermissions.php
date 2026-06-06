<?php

namespace Afterburner\Communications\Support;

use App\Models\User;
use App\Support\TeamPermissionGate;

final class DiscussionPermissions
{
    public const LEGACY_MANAGE = 'manage_discussions';

    public const CREATE = 'create_discussions';

    public const EDIT = 'edit_discussions';

    public const ARCHIVE = 'archive_discussions';

    public const LOCK = 'lock_discussions';

    public const DELETE = 'delete_discussions';

    public const MODERATE_POSTS = 'moderate_discussion_posts';

    public const VIEW = 'view_discussions';

    /**
     * @return list<string>
     */
    public static function viewAccessSlugs(): array
    {
        return [
            self::VIEW,
            self::LEGACY_MANAGE,
            ...self::all(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CREATE,
            self::EDIT,
            self::ARCHIVE,
            self::LOCK,
            self::DELETE,
            self::MODERATE_POSTS,
        ];
    }

    /**
     * @return list<string>
     */
    public static function councilAccessSlugs(): array
    {
        return [
            self::LEGACY_MANAGE,
            ...self::all(),
        ];
    }

    public static function canCreate(User $user, int $teamId): bool
    {
        return TeamPermissionGate::allowsAny($user, $teamId, [self::CREATE, self::LEGACY_MANAGE]);
    }

    public static function canEdit(User $user, int $teamId): bool
    {
        return TeamPermissionGate::allowsAny($user, $teamId, [self::EDIT, self::LEGACY_MANAGE]);
    }

    public static function canArchive(User $user, int $teamId): bool
    {
        return TeamPermissionGate::allowsAny($user, $teamId, [self::ARCHIVE, self::LEGACY_MANAGE]);
    }

    public static function canLock(User $user, int $teamId): bool
    {
        return TeamPermissionGate::allowsAny($user, $teamId, [self::LOCK, self::LEGACY_MANAGE]);
    }

    public static function canDelete(User $user, int $teamId): bool
    {
        return TeamPermissionGate::allowsAny($user, $teamId, [self::DELETE, self::LEGACY_MANAGE]);
    }

    public static function canModeratePosts(User $user, int $teamId): bool
    {
        return TeamPermissionGate::allowsAny($user, $teamId, [self::MODERATE_POSTS, self::LEGACY_MANAGE]);
    }

    public static function canView(User $user, int $teamId): bool
    {
        return TeamPermissionGate::allowsAny($user, $teamId, self::viewAccessSlugs());
    }

    public static function canAccessCouncilDiscussions(User $user, int $teamId): bool
    {
        return TeamPermissionGate::allowsAny($user, $teamId, self::councilAccessSlugs());
    }
}
