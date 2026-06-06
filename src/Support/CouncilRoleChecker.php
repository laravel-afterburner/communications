<?php

namespace Afterburner\Communications\Support;

use App\Models\User;

final class CouncilRoleChecker
{
    /**
     * @return array<int, string>
     */
    public static function slugs(): array
    {
        $resolver = config('afterburner-communications.council_role_resolver');

        if (is_string($resolver) && class_exists($resolver) && method_exists($resolver, 'slugs')) {
            return $resolver::slugs();
        }

        return config('afterburner-communications.council_role_slugs', []);
    }

    public static function hasCouncilRole(User $user, int $teamId): bool
    {
        $slugs = self::slugs();

        if ($slugs === []) {
            return false;
        }

        return $user->roles()
            ->where('team_id', $teamId)
            ->whereIn('slug', $slugs)
            ->exists();
    }

    public static function isCouncilMember(User $user, int $teamId): bool
    {
        if (DiscussionPermissions::canAccessCouncilDiscussions($user, $teamId)) {
            return true;
        }

        return self::hasCouncilRole($user, $teamId);
    }
}
