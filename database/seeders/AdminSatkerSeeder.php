<?php

namespace Database\Seeders;

use App\Models\Satker;
use App\Models\User;
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
            ['name' => 'ITWASDA', 'email_prefix' => 'itwasda', 'password' => 'itwasda1'],
            ['name' => 'BIRO OPS', 'email_prefix' => 'biroops', 'password' => 'biroops2'],
            ['name' => 'BIRO RENA', 'email_prefix' => 'birorena', 'password' => 'birorena3'],
            ['name' => 'BIRO SDM', 'email_prefix' => 'birosdm', 'password' => 'birosdm4'],
            ['name' => 'BIRO LOGISTIK', 'email_prefix' => 'birologistik', 'password' => 'birologistik5'],
            ['name' => 'DIT SAMAPTA', 'email_prefix' => 'ditsamapta', 'password' => 'ditsamapta6'],
            ['name' => 'DIT LANTAS', 'email_prefix' => 'ditlantas', 'password' => 'ditlantas7'],
            ['name' => 'DIT BINMAS', 'email_prefix' => 'ditbinmas', 'password' => 'ditbinmas8'],
            ['name' => 'DIT PAMOBVIT', 'email_prefix' => 'ditpamobvit', 'password' => 'ditpamobvit9'],
            ['name' => 'DIT TAHTI', 'email_prefix' => 'dittahti', 'password' => 'dittahti10'],
            ['name' => 'DIT POLAIRUD', 'email_prefix' => 'ditpolairud', 'password' => 'ditpolairud11'],
            ['name' => 'SAT BRIMOB', 'email_prefix' => 'satbrimob', 'password' => 'satbrimob12'],
            ['name' => 'DIT INTELKAM', 'email_prefix' => 'ditintelkam', 'password' => 'ditintelkam13'],
            ['name' => 'DIT RESKRIMSUS', 'email_prefix' => 'ditreskrimsus', 'password' => 'ditreskrimsus14'],
            ['name' => 'DIT RESKRIMUM', 'email_prefix' => 'ditreskrimum', 'password' => 'ditreskrimum15'],
            ['name' => 'DITRESPPAPPO', 'email_prefix' => 'ditresppappo', 'password' => 'ditresppappo16'],
            ['name' => 'DIT RESNARKOBA', 'email_prefix' => 'ditresnarkoba', 'password' => 'ditresnarkoba17'],
            ['name' => 'BID PROPAM', 'email_prefix' => 'bidpropam', 'password' => 'bidpropam18'],
            ['name' => 'BID KUM', 'email_prefix' => 'bidkum', 'password' => 'bidkum19'],
            ['name' => 'BID HUMAS', 'email_prefix' => 'bidhumas', 'password' => 'bidhumas20'],
            ['name' => 'BID DOKKES', 'email_prefix' => 'biddokkes', 'password' => 'biddokkes21'],
            ['name' => 'BID TIK', 'email_prefix' => 'bidtik', 'password' => 'bidtik22'],
            ['name' => 'BID KEU', 'email_prefix' => 'bidkeu', 'password' => 'bidkeu23'],
            ['name' => 'YANMA', 'email_prefix' => 'yanma', 'password' => 'yanma24'],
            ['name' => 'SPRIPIM', 'email_prefix' => 'spripim', 'password' => 'spripim25'],
            ['name' => 'SPN', 'email_prefix' => 'spn', 'password' => 'spn26'],
            ['name' => 'SETUM', 'email_prefix' => 'setum', 'password' => 'setum27'],
            ['name' => 'RUMKIT', 'email_prefix' => 'rumkit', 'password' => 'rumkit28'],
            ['name' => 'SPKT', 'email_prefix' => 'spkt', 'password' => 'spkt29'],
            ['name' => 'POLRESTA MATARAM', 'email_prefix' => 'polrestamataram', 'password' => 'polrestamataram30'],
            ['name' => 'POLRES LOMBOK BARAT', 'email_prefix' => 'polreslombokbarat', 'password' => 'polreslombokbarat31'],
            ['name' => 'POLRES LOMBOK UTARA', 'email_prefix' => 'polres_lombok_utara', 'password' => 'polres_lombok_utara32'],
            ['name' => 'POLRES LOMBOK UTARA', 'email_prefix' => 'polreslombokutara', 'password' => 'polreslombokutara32'],
            ['name' => 'POLRES LOMBOK TENGAH', 'email_prefix' => 'polreslomboktengah', 'password' => 'polreslomboktengah33'],
            ['name' => 'POLRES LOMBOK TIMUR', 'email_prefix' => 'polreslomboktimur', 'password' => 'polreslomboktimur34'],
            ['name' => 'POLRES SUMBAWA BARAT', 'email_prefix' => 'polressumbawabarat', 'password' => 'polressumbawabarat35'],
            ['name' => 'POLRES SUMBAWA', 'email_prefix' => 'polressumbawa', 'password' => 'polressumbawa36'],
            ['name' => 'POLRES DOMPU', 'email_prefix' => 'polresdompu', 'password' => 'polresdompu37'],
            ['name' => 'POLRES BIMA', 'email_prefix' => 'polresbima', 'password' => 'polresbima38'],
            ['name' => 'POLRES BIMA KOTA', 'email_prefix' => 'polresbimakota', 'password' => 'polresbimakota39'],
        ];

        $uniqueUsers = [];
        foreach ($users as $userData) {
            $uniqueUsers[$userData['email_prefix']] = $userData;
        }

        foreach ($uniqueUsers as $userData) {
            $satker = Satker::where('name', $userData['name'])->first();

            if (! $satker) {
                continue;
            }

            $email = strtolower($userData['email_prefix'].'@gmail.com');
            $user = User::where('email', $email)->first();

            if ($user) {
                continue;
            }

            $user = User::create([
                'name' => 'Admin '.$userData['name'],
                'email' => $email,
                'password' => Hash::make($userData['password']),
                'satker_id' => $satker->id,
                'is_active' => true,
            ]);

            $user->assignRole('admin_satker');
        }
    }
}
