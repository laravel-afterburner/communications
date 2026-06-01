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
}
