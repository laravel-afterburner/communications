<?php

namespace Afterburner\Communications\Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait AssignsPermissionsToRoles
{
    protected function assignPermissionToRoles(int $permissionId, array $roleSlugs, $now): int
    {
        if (! DB::getSchemaBuilder()->hasTable('roles') || ! DB::getSchemaBuilder()->hasTable('role_permission')) {
            return 0;
        }

        $rolePermissionColumns = DB::getSchemaBuilder()->getColumnListing('role_permission');
        $hasTimestamps = in_array('created_at', $rolePermissionColumns, true)
            && in_array('updated_at', $rolePermissionColumns, true);
        $assignedCount = 0;

        foreach ($roleSlugs as $roleSlug) {
            $role = DB::table('roles')->where('slug', $roleSlug)->first();

            if (! $role) {
                continue;
            }

            if (in_array('role_id', $rolePermissionColumns, true) && in_array('permission_id', $rolePermissionColumns, true)) {
                $data = [
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                ];
            } elseif (in_array('role_slug', $rolePermissionColumns, true) && in_array('permission_id', $rolePermissionColumns, true)) {
                $data = [
                    'role_slug' => $roleSlug,
                    'permission_id' => $permissionId,
                ];
            } else {
                continue;
            }

            if ($hasTimestamps) {
                $data['created_at'] = $now;
                $data['updated_at'] = $now;
            }

            DB::table('role_permission')->insertOrIgnore($data);
            $assignedCount++;
        }

        return $assignedCount;
    }
}
