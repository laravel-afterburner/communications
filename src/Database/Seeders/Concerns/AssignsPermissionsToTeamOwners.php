<?php

namespace Afterburner\Communications\Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait AssignsPermissionsToTeamOwners
{
    protected function assignPermissionsToTeamOwners(array $insertedPermissionIds, array $permissions, $now): int
    {
        if (! DB::getSchemaBuilder()->hasTable('teams') || ! DB::getSchemaBuilder()->hasTable('roles')) {
            return 0;
        }

        $userRoleTable = null;
        foreach (['user_role', 'role_user', 'user_roles', 'role_users'] as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $userRoleTable = $table;
                break;
            }
        }

        if (! $userRoleTable) {
            return 0;
        }

        $rolePermissionColumns = DB::getSchemaBuilder()->getColumnListing('role_permission');
        $userRoleColumns = DB::getSchemaBuilder()->getColumnListing($userRoleTable);
        $rolesColumns = DB::getSchemaBuilder()->getColumnListing('roles');

        $hierarchyField = null;
        foreach (['hierarchy', 'hierarchy_number', 'level', 'order', 'hierarchy_level'] as $field) {
            if (in_array($field, $rolesColumns, true)) {
                $hierarchyField = $field;
                break;
            }
        }

        if (! $hierarchyField) {
            return 0;
        }

        $teams = DB::table('teams')->whereNotNull('user_id')->select('id', 'user_id')->get();
        $assignedCount = 0;
        $hasTimestamps = in_array('created_at', $rolePermissionColumns, true)
            && in_array('updated_at', $rolePermissionColumns, true);

        foreach ($teams as $team) {
            $ownerRolesQuery = DB::table($userRoleTable)
                ->join('roles', function ($join) use ($userRoleTable, $userRoleColumns) {
                    if (in_array('role_id', $userRoleColumns, true)) {
                        $join->on("{$userRoleTable}.role_id", '=', 'roles.id');
                    } elseif (in_array('role_slug', $userRoleColumns, true)) {
                        $join->on("{$userRoleTable}.role_slug", '=', 'roles.slug');
                    }
                })
                ->where("{$userRoleTable}.user_id", $team->user_id);

            if (in_array('team_id', $userRoleColumns, true)) {
                $ownerRolesQuery->where("{$userRoleTable}.team_id", $team->id);
            }

            $highestRole = $ownerRolesQuery
                ->select('roles.*')
                ->orderByDesc("roles.{$hierarchyField}")
                ->first();

            if (! $highestRole) {
                continue;
            }

            if (in_array('role_slug', $rolePermissionColumns, true) && in_array('permission_id', $rolePermissionColumns, true)) {
                foreach ($insertedPermissionIds as $permissionId) {
                    $data = ['role_slug' => $highestRole->slug, 'permission_id' => $permissionId];
                    if ($hasTimestamps) {
                        $data['created_at'] = $now;
                        $data['updated_at'] = $now;
                    }
                    DB::table('role_permission')->insertOrIgnore($data);
                }
                $assignedCount++;
            }
        }

        return $assignedCount;
    }
}
