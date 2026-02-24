<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Satker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSatkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'ITWASDA', 'username' => 'itwasda', 'password' => 'itwasda1'],
            ['name' => 'BIRO OPS', 'username' => 'biroops', 'password' => 'biroops2'],
            ['name' => 'BIRO RENA', 'username' => 'birorena', 'password' => 'birorena3'],
            ['name' => 'BIRO SDM', 'username' => 'birosdm', 'password' => 'birosdm4'],
            ['name' => 'BIRO LOGISTIK', 'username' => 'birologistik', 'password' => 'birologistik5'],
            ['name' => 'DIT SAMAPTA', 'username' => 'ditsamapta', 'password' => 'ditsamapta6'],
            ['name' => 'DIT LANTAS', 'username' => 'ditlantas', 'password' => 'ditlantas7'],
            ['name' => 'DIT BINMAS', 'username' => 'ditbinmas', 'password' => 'ditbinmas8'],
            ['name' => 'DIT PAMOBVIT', 'username' => 'ditpamobvit', 'password' => 'ditpamobvit9'],
            ['name' => 'DIT TAHTI', 'username' => 'dittahti', 'password' => 'dittahti10'],
            ['name' => 'DIT POLAIRUD', 'username' => 'ditpolairud', 'password' => 'ditpolairud11'],
            ['name' => 'SAT BRIMOB', 'username' => 'satbrimob', 'password' => 'satbrimob12'],
            ['name' => 'DIT INTELKAM', 'username' => 'ditintelkam', 'password' => 'ditintelkam13'],
            ['name' => 'DIT RESKRIMSUS', 'username' => 'ditreskrimsus', 'password' => 'ditreskrimsus14'],
            ['name' => 'DIT RESKRIMUM', 'username' => 'ditreskrimum', 'password' => 'ditreskrimum15'],
            ['name' => 'DITRESPPAPPO', 'username' => 'ditresppappo', 'password' => 'ditresppappo16'],
            ['name' => 'DIT RESNARKOBA', 'username' => 'ditresnarkoba', 'password' => 'ditresnarkoba17'],
            ['name' => 'BID PROPAM', 'username' => 'bidpropam', 'password' => 'bidpropam18'],
            ['name' => 'BID KUM', 'username' => 'bidkum', 'password' => 'bidkum19'],
            ['name' => 'BID HUMAS', 'username' => 'bidhumas', 'password' => 'bidhumas20'],
            ['name' => 'BID DOKKES', 'username' => 'biddokkes', 'password' => 'biddokkes21'],
            ['name' => 'BID TIK', 'username' => 'bidtik', 'password' => 'bidtik22'],
            ['name' => 'BID KEU', 'username' => 'bidkeu', 'password' => 'bidkeu23'],
            ['name' => 'YANMA', 'username' => 'yanma', 'password' => 'yanma24'],
            ['name' => 'SPRIPIM', 'username' => 'spripim', 'password' => 'spripim25'],
            ['name' => 'SPN', 'username' => 'spn', 'password' => 'spn26'],
            ['name' => 'SETUM', 'username' => 'setum', 'password' => 'setum27'],
            ['name' => 'RUMKIT', 'username' => 'rumkit', 'password' => 'rumkit28'],
            ['name' => 'SPKT', 'username' => 'spkt', 'password' => 'spkt29'],
            ['name' => 'POLRESTA MATARAM', 'username' => 'polrestamataram', 'password' => 'polrestamataram30'],
            ['name' => 'POLRES LOMBOK BARAT', 'username' => 'polreslombokbarat', 'password' => 'polreslombokbarat31'],
            ['name' => 'POLRES LOMBOK UTARA', 'username' => 'polres_lombok_utara', 'password' => 'polres_lombok_utara32'],
            ['name' => 'POLRES LOMBOK UTARA', 'username' => 'polreslombokutara', 'password' => 'polreslombokutara32'],
            ['name' => 'POLRES LOMBOK TENGAH', 'username' => 'polreslomboktengah', 'password' => 'polreslomboktengah33'],
            ['name' => 'POLRES LOMBOK TIMUR', 'username' => 'polreslomboktimur', 'password' => 'polreslomboktimur34'],
            ['name' => 'POLRES SUMBAWA BARAT', 'username' => 'polressumbawabarat', 'password' => 'polressumbawabarat35'],
            ['name' => 'POLRES SUMBAWA', 'username' => 'polressumbawa', 'password' => 'polressumbawa36'],
            ['name' => 'POLRES DOMPU', 'username' => 'polresdompu', 'password' => 'polresdompu37'],
            ['name' => 'POLRES BIMA', 'username' => 'polresbima', 'password' => 'polresbima38'],
            ['name' => 'POLRES BIMA KOTA', 'username' => 'polresbimakota', 'password' => 'polresbimakota39'],
        ];

        // Unique by name to avoid duplicates if I typed twice
        $uniqueUsers = [];
        foreach ($users as $u) {
            $uniqueUsers[$u['username']] = $u;
        }

        foreach ($uniqueUsers as $u) {
            $satker = Satker::where('name', $u['name'])->first();

            if ($satker) {
                // Check if user already exists
                $user = User::where('nrp_nip', $u['username'])->first();

                if (!$user) {
                    $user = User::create([
                        'name' => 'Admin ' . $u['name'],
                        'nrp_nip' => $u['username'],
                        'password' => Hash::make($u['password']),
                        'satker_id' => $satker->id,
                        'is_active' => true,
                    ]);
                    $user->assignRole('admin_satker');
                }
            }
        }
    }
}
