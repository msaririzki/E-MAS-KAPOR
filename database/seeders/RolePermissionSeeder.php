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

            // Warehouse
            'manage-warehouse', // Kelola data gudang & laporan pengeluaran

            // Kapor
            'submit-kapor-sizes', // Input ukuran kapor (personil only)

            // General
            'view-own-profile', // Lihat profil sendiri
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // ── Create Roles & Assign Permissions ──────────────────

        // Superadmin: God-mode
        $superadmin = Role::findOrCreate('superadmin', 'web');
        $superadmin->syncPermissions(Permission::all());

        // Kabak Bekum: akses baca setara superadmin, mutasi diblokir middleware read-only
        $kabakBekum = Role::findOrCreate('kabak_bekum', 'web');
        $kabakBekum->syncPermissions([
            'view-global-reports',
            'view-satker-data',
            'view-own-profile',
        ]);

        // Admin Gudang: fokus pada pengelolaan gudang
        $adminGudang = Role::findOrCreate('admin_gudang', 'web');
        $adminGudang->syncPermissions([
            'manage-warehouse',
            'view-own-profile',
        ]);

        // Kepala Gudang: fokus pada pemantauan dan persetujuan pengelolaan gudang
        $kepalaGudang = Role::findOrCreate('kepala_gudang', 'web');
        $kepalaGudang->syncPermissions([
            'manage-warehouse',
            'view-own-profile',
        ]);

        // Admin Satker: Scope terbatas ke satker sendiri
        $adminSatker = Role::findOrCreate('admin_satker', 'web');
        $adminSatker->syncPermissions([
            'manage-satker-personnel',
            'view-satker-data',
            'view-own-profile',
        ]);

        // Personil: End-user, input kapor
        $personil = Role::findOrCreate('personil', 'web');
        $personil->syncPermissions([
            'submit-kapor-sizes',
            'view-own-profile',
        ]);

        $legacyAdminRole = Role::where('name', 'admin')->first();
        if ($legacyAdminRole) {
            $legacyAdminRole->syncPermissions([]);
            $legacyAdminRole->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
