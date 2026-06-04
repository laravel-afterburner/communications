<?php

namespace Afterburner\Communications\Database\Seeders;

use Afterburner\Communications\Database\Seeders\Concerns\AssignsPermissionsToRoles;
use Afterburner\Communications\Database\Seeders\Concerns\AssignsPermissionsToTeamOwners;
use Afterburner\Communications\Database\Seeders\Concerns\MigratesLegacyManageDiscussions;
use Afterburner\Communications\Support\CommunicationsPermissionDefinitions;
use Afterburner\Communications\Support\CommunicationsRolePermissions;
use Afterburner\Communications\Support\DiscussionPermissions;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommunicationsPermissionsSeeder extends Seeder
{
    use AssignsPermissionsToRoles;
    use AssignsPermissionsToTeamOwners;
    use MigratesLegacyManageDiscussions;

    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('permissions')) {
            return;
        }

        $now = Carbon::now();
        $permissions = array_map(
            fn (array $permission) => $permission + ['created_at' => $now, 'updated_at' => $now],
            CommunicationsPermissionDefinitions::all()
        );

        $insertedPermissionIds = [];
        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore($permission);
            $permissionRecord = DB::table('permissions')->where('slug', $permission['slug'])->first();
            if ($permissionRecord) {
                $insertedPermissionIds[] = $permissionRecord->id;
            }
        }

        if (! empty($insertedPermissionIds) && DB::getSchemaBuilder()->hasTable('role_permission')) {
            $this->assignPermissionsToTeamOwners($insertedPermissionIds, $permissions, $now);
            $this->migrateLegacyManageDiscussions($now);

            $postAnnouncementsId = DB::table('permissions')
                ->where('slug', 'post_announcements')
                ->value('id');

            if ($postAnnouncementsId) {
                $this->assignPermissionToRoles(
                    (int) $postAnnouncementsId,
                    CommunicationsRolePermissions::rolesWithPostAnnouncements(),
                    $now
                );
            }

            $createDiscussionsId = DB::table('permissions')
                ->where('slug', DiscussionPermissions::CREATE)
                ->value('id');

            if ($createDiscussionsId) {
                $this->assignPermissionToRoles(
                    (int) $createDiscussionsId,
                    CommunicationsRolePermissions::rolesWithCreateDiscussions(),
                    $now
                );
            }

            $viewDiscussionsId = DB::table('permissions')
                ->where('slug', DiscussionPermissions::VIEW)
                ->value('id');

            if ($viewDiscussionsId) {
                $this->assignPermissionToRoles(
                    (int) $viewDiscussionsId,
                    CommunicationsRolePermissions::rolesWithViewDiscussions(),
                    $now
                );
            }

            $viewAnnouncementsId = DB::table('permissions')
                ->where('slug', 'view_announcements')
                ->value('id');

            if ($viewAnnouncementsId) {
                $this->assignPermissionToRoles(
                    (int) $viewAnnouncementsId,
                    CommunicationsRolePermissions::rolesWithViewAnnouncements(),
                    $now
                );
            }
        }
    }
}
