<?php

namespace Database\Seeders;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $polda = Satker::where('code', 'POLDA-NTB')->first();
        $resMataram = Satker::where('code', 'RES-MTR')->first();
        $resLotim = Satker::where('code', 'RES-LOTIM')->first();

        // ── 1. Superadmin ──────────────────────────────────────
        $superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin.kapor@gmail.com',
            'password' => Hash::make('password'),
            'satker_id' => $polda->id,
            'is_active' => true,
        ]);
        $superadmin->assignRole('superadmin');

        // ── 2. Admin ───────────────────────────────────────────
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin.kapor@gmail.com',
            'password' => Hash::make('password'),
            'satker_id' => $polda->id,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $adminGudang = User::create([
            'name' => 'Admin Gudang',
            'email' => 'admin.gudang.kapor@gmail.com',
            'password' => Hash::make('password'),
            'satker_id' => $polda->id,
            'is_active' => true,
        ]);
        $adminGudang->assignRole('admin_gudang');

        // ── 3. Admin Satker (Polresta Mataram) ─────────────────
        $adminSatker = User::create([
            'name' => 'Admin Satker Polresta Mataram',
            'email' => 'admin.satker.mataram@gmail.com',
            'password' => Hash::make('password'),
            'satker_id' => $resMataram->id,
            'is_active' => true,
        ]);
        $adminSatker->assignRole('admin_satker');

        // ── 4. Admin Satker (Polres Lombok Timur) ──────────────
        $adminSatker2 = User::create([
            'name' => 'Admin Satker Polres Lotim',
            'email' => 'admin.satker.lotim@gmail.com',
            'password' => Hash::make('password'),
            'satker_id' => $resLotim->id,
            'is_active' => true,
        ]);
        $adminSatker2->assignRole('admin_satker');

        // ── 5. Personil Pria (Bintara, Polresta Mataram) ──────
        $rankBintara = Rank::where('name', 'BRIPKA')->first();
        $personil1 = User::create([
            'nrp_nip' => '87654321',
            'name' => 'Bripka Ahmad Fauzi',
            'password' => Hash::make('password'),
            'satker_id' => $resMataram->id,
            'is_active' => true,
        ]);
        $personil1->assignRole('personil');
        Personnel::create([
            'user_id' => $personil1->id,
            'nrp' => '87654321',
            'full_name' => 'Ahmad Fauzi',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rankBintara->id,
            'satker_id' => $resMataram->id,
            'phone' => '081234567890',
        ]);

        // ── 6. Personil Wanita (Pama, Polresta Mataram) ───────
        $rankPama = Rank::where('name', 'IPTU')->first();
        $personil2 = User::create([
            'nrp_nip' => '76543210',
            'name' => 'Iptu Siti Nurhaliza',
            'password' => Hash::make('password'),
            'satker_id' => $resMataram->id,
            'is_active' => true,
        ]);
        $personil2->assignRole('personil');
        Personnel::create([
            'user_id' => $personil2->id,
            'nrp' => '76543210',
            'full_name' => 'Siti Nurhaliza',
            'gender' => 'P',
            'personnel_type' => 'Polri',
            'rank_id' => $rankPama->id,
            'satker_id' => $resMataram->id,
            'phone' => '081234567891',
        ]);

        // ── 7. Personil PNS (Polres Lombok Timur) ─────────────
        $rankPns = Rank::where('name', 'Penata')->first();
        $personil3 = User::create([
            'nrp_nip' => '198501012010011001',
            'name' => 'Lalu Muhamad Zainul',
            'password' => Hash::make('password'),
            'satker_id' => $resLotim->id,
            'is_active' => true,
        ]);
        $personil3->assignRole('personil');
        Personnel::create([
            'user_id' => $personil3->id,
            'nrp' => '198501012010011001',
            'full_name' => 'Lalu Muhamad Zainul',
            'gender' => 'L',
            'personnel_type' => 'PNS',
            'rank_id' => $rankPns->id,
            'satker_id' => $resLotim->id,
            'phone' => '081234567892',
        ]);
    }
}
