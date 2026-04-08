<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $legacyAdminRole = Role::where('name', 'admin')->first();
        if (! $legacyAdminRole) {
            return;
        }

        $superadminRole = Role::findOrCreate('superadmin', 'web');

        User::role('admin')->each(function (User $user) use ($superadminRole) {
            if (! $user->hasRole($superadminRole)) {
                $user->assignRole($superadminRole);
            }

            $user->removeRole('admin');
        });

        $legacyAdminRole->syncPermissions([]);
        $legacyAdminRole->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('admin', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
