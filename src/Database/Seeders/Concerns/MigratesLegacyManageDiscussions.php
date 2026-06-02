<?php

namespace Afterburner\Communications\Database\Seeders\Concerns;

use Afterburner\Communications\Support\DiscussionPermissions;
use Illuminate\Support\Facades\DB;

trait MigratesLegacyManageDiscussions
{
    protected function migrateLegacyManageDiscussions($now): int
    {
        if (! DB::getSchemaBuilder()->hasTable('role_permission')) {
            return 0;
        }

        $legacySlug = DiscussionPermissions::LEGACY_MANAGE;
        $newSlugs = DiscussionPermissions::all();
        $rolePermissionColumns = DB::getSchemaBuilder()->getColumnListing('role_permission');
        $hasTimestamps = in_array('created_at', $rolePermissionColumns, true)
            && in_array('updated_at', $rolePermissionColumns, true);
        $assignedCount = 0;

        if (in_array('role_slug', $rolePermissionColumns, true) && in_array('permission_id', $rolePermissionColumns, true)) {
            $legacyPermissionId = DB::table('permissions')->where('slug', $legacySlug)->value('id');

            if (! $legacyPermissionId) {
                return 0;
            }

            $roleSlugs = DB::table('role_permission')
                ->where('permission_id', $legacyPermissionId)
                ->pluck('role_slug')
                ->unique()
                ->all();

            foreach ($roleSlugs as $roleSlug) {
                foreach ($newSlugs as $newSlug) {
                    $permissionId = DB::table('permissions')->where('slug', $newSlug)->value('id');

                    if (! $permissionId) {
                        continue;
                    }

                    $data = [
                        'role_slug' => $roleSlug,
                        'permission_id' => $permissionId,
                    ];

                    if ($hasTimestamps) {
                        $data['created_at'] = $now;
                        $data['updated_at'] = $now;
                    }

                    DB::table('role_permission')->insertOrIgnore($data);
                    $assignedCount++;
                }
            }
        } elseif (in_array('role_id', $rolePermissionColumns, true) && in_array('permission_id', $rolePermissionColumns, true)) {
            $legacyPermissionId = DB::table('permissions')->where('slug', $legacySlug)->value('id');

            if (! $legacyPermissionId) {
                return 0;
            }

            $roleIds = DB::table('role_permission')
                ->where('permission_id', $legacyPermissionId)
                ->pluck('role_id')
                ->unique()
                ->all();

            foreach ($roleIds as $roleId) {
                foreach ($newSlugs as $newSlug) {
                    $permissionId = DB::table('permissions')->where('slug', $newSlug)->value('id');

                    if (! $permissionId) {
                        continue;
                    }

                    $data = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ];

                    if ($hasTimestamps) {
                        $data['created_at'] = $now;
                        $data['updated_at'] = $now;
                    }

                    DB::table('role_permission')->insertOrIgnore($data);
                    $assignedCount++;
                }
            }
        }

        return $assignedCount;
    }
}
