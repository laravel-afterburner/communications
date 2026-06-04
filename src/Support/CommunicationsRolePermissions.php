<?php

namespace Afterburner\Communications\Support;

final class CommunicationsRolePermissions
{
    /**
     * Role slugs that receive post_announcements by default (mirrors former RoleTemplates maps).
     *
     * @return array<int, string>
     */
    public static function rolesWithPostAnnouncements(): array
    {
        return [
            'team_lead',
            'coordinator',
            'treasurer',
            'owner_manager',
            'supervisor',
            'department_lead',
            'senior_staff',
            'president',
            'secretary',
            'council_member',
            'executive_director',
            'program_manager',
            'board_member',
        ];
    }

    /**
     * Role slugs that can view announcements without posting.
     *
     * @return array<int, string>
     */
    public static function rolesWithViewAnnouncements(): array
    {
        return array_values(array_unique([
            ...self::rolesWithPostAnnouncements(),
            'strata_owner',
            'member',
        ]));
    }

    /**
     * Role slugs that can start discussion threads by default.
     *
     * @return array<int, string>
     */
    public static function rolesWithCreateDiscussions(): array
    {
        return self::rolesWithPostAnnouncements();
    }

    /**
     * Role slugs that can open the discussions index and read threads.
     *
     * @return array<int, string>
     */
    public static function rolesWithViewDiscussions(): array
    {
        return array_values(array_unique([
            ...self::rolesWithCreateDiscussions(),
            'treasurer',
            'council_member',
            'strata_owner',
            'member',
            'board_member',
        ]));
    }
}
