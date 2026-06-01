<?php

namespace Afterburner\Communications\Database\Seeders;

use Afterburner\Communications\Database\Seeders\Concerns\AssignsPermissionsToRoles;
use Afterburner\Communications\Database\Seeders\Concerns\AssignsPermissionsToTeamOwners;
use Afterburner\Communications\Support\CommunicationsPermissionDefinitions;
use Afterburner\Communications\Support\CommunicationsRolePermissions;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommunicationsPermissionsSeeder extends Seeder
{
    use AssignsPermissionsToRoles;
    use AssignsPermissionsToTeamOwners;

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
        }
    }
}
