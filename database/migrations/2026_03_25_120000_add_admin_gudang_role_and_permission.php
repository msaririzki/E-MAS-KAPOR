<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $manageWarehouse = Permission::findOrCreate('manage-warehouse', 'web');
        $viewOwnProfile = Permission::findOrCreate('view-own-profile', 'web');

        $admin = Role::findOrCreate('admin', 'web');
        $superadmin = Role::findOrCreate('superadmin', 'web');
        $adminGudang = Role::findOrCreate('admin_gudang', 'web');

        $admin->givePermissionTo($manageWarehouse);
        $superadmin->givePermissionTo($manageWarehouse);
        $adminGudang->givePermissionTo([$manageWarehouse, $viewOwnProfile]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminGudang = Role::where('name', 'admin_gudang')->first();
        $manageWarehouse = Permission::where('name', 'manage-warehouse')->first();

        if ($adminGudang) {
            $adminGudang->syncPermissions([]);
            $adminGudang->delete();
        }

        if ($manageWarehouse) {
            foreach (['admin', 'superadmin', 'admin_satker', 'personil'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role && $role->hasPermissionTo($manageWarehouse)) {
                    $role->revokePermissionTo($manageWarehouse);
                }
            }

            $manageWarehouse->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
