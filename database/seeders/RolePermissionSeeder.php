<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Create Permissions ─────────────────────────────────

        $permissions = [
            // System
            'manage-system-settings',

            // User Management
            'manage-all-users', // CRUD semua user (termasuk superadmin)
            'manage-non-super-users', // CRUD user kecuali superadmin

            // Satker Management
            'manage-satkers', // CRUD data satker

            // Personnel Management
            'manage-satker-personnel', // Kelola personil (scoped)
            'view-satker-data', // Lihat data satker

            // Reports
            'view-global-reports', // Statistik global

            // Kapor
            'submit-kapor-sizes', // Input ukuran kapor (personil only)

            // General
            'view-own-profile', // Lihat profil sendiri
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // ── Create Roles & Assign Permissions ──────────────────

        // Superadmin: God-mode
        $superadmin = Role::create(['name' => 'superadmin']);
        $superadmin->givePermissionTo(Permission::all());

        // Admin: Global tapi tidak bisa kelola superadmin & system settings
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'manage-non-super-users',
            'manage-satker-personnel',
            'view-satker-data',
            'view-global-reports',
            'view-own-profile',
        ]);

        // Admin Satker: Scope terbatas ke satker sendiri
        $adminSatker = Role::create(['name' => 'admin_satker']);
        $adminSatker->givePermissionTo([
            'manage-satker-personnel',
            'view-satker-data',
            'view-own-profile',
        ]);

        // Personil: End-user, input kapor
        $personil = Role::create(['name' => 'personil']);
        $personil->givePermissionTo([
            'submit-kapor-sizes',
            'view-own-profile',
        ]);
    }
}
