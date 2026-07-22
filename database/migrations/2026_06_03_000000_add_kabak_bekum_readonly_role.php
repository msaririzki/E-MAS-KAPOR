<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends \Illuminate\Database\Migrations\Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            'view-global-reports',
            'view-satker-data',
            'view-own-profile',
        ] as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $role = Role::findOrCreate('kabak_bekum', 'web');
        $role->syncPermissions([
            'view-global-reports',
            'view-satker-data',
            'view-own-profile',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::where('name', 'kabak_bekum')->where('guard_name', 'web')->first();

        if ($role) {
            $role->syncPermissions([]);
            $role->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
