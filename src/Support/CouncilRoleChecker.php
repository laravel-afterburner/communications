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
        return config('afterburner-communications.council_role_slugs', []);
    }

    public static function isCouncilMember(User $user, int $teamId): bool
    {
        if (TeamPermissionGate::allows($user, $teamId, 'manage_discussions')) {
            return true;
        }

        $slugs = static::slugs();

        if ($slugs === []) {
            return false;
        }

        return $user->roles()
            ->where('team_id', $teamId)
            ->whereIn('slug', $slugs)
            ->exists();
    }
}
