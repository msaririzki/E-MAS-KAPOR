<?php

namespace Database\Seeders;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class DummyPersonnelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $satkers = Satker::all();
        $ranks = Rank::all();

        if ($satkers->isEmpty() || $ranks->isEmpty()) {
            $this->command->error('Pastikan tabel satkers and ranks sudah memiliki data (jalankan SatkerSeeder and RankSeeder).');
            return;
        }

        $polriRanks = $ranks->where('category', '!=', 'PNS')->pluck('id')->toArray();
        $pnsRanks = $ranks->where('category', 'PNS')->pluck('id')->toArray();

        $sizes = [
            'topi' => ['54', '55', '56', '57', '58', '59', '60'],
            'kemeja' => ['14', '14.5', '15', '15.5', '16', '16.5', '17'],
            'celana' => ['28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38'],
            'sepatu_dinas' => ['38', '39', '40', '41', '42', '43', '44', '45'],
            'sepatu_olahraga' => ['38', '39', '40', '41', '42', '43', '44', '45'],
            'sabuk' => ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
            'jilbab' => ['M', 'L', 'XL'],
            'jaket' => ['S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
            'olahraga' => ['S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
            'tutup_kepala' => ['54', '55', '56', '57', '58', '59', '60'],
        ];

        $totalToCreate = 1000;

        $this->command->info("Memulai pembuatan $totalToCreate data personil dummy...");

        DB::beginTransaction();
        try {
            for ($i = 0; $i < $totalToCreate; $i++) {
                $gender = $faker->randomElement(['L', 'P']);
                $personnelType = $faker->randomElement(['Polri', 'PNS']);
                $rankId = ($personnelType === 'Polri')
                    ? $faker->randomElement($polriRanks)
                    : $faker->randomElement($pnsRanks);

                $fullName = $faker->name($gender === 'L' ? 'male' : 'female');
                $nrp = ($personnelType === 'Polri')
                    ? $faker->unique()->numerify('##########')
                    : $faker->unique()->numerify('19##########');

                $satker = $satkers->random();

                // Create User Account
                $user = User::create([
                    'name' => $fullName,
                    'nrp_nip' => $nrp,
                    'password' => Hash::make($nrp),
                    'satker_id' => $satker->id,
                    'is_active' => true,
                ]);
                $user->assignRole('personil');

                // Random Kapor Sizes
                $kaporSizes = [];
                foreach ($sizes as $key => $options) {
                    if ($key === 'jilbab' && $gender === 'L')
                        continue;
                    $kaporSizes[$key] = $faker->randomElement($options);
                }

                // Get Rank object for golongan logic
                $rank = $ranks->find($rankId);
                $golongan = ($rank->category === 'PNS')
                    ? $faker->randomElement(['III/a', 'III/b', 'II/c', 'IV/a'])
                    : ($rank->category ?? '—');

                Personnel::create([
                    'user_id' => $user->id,
                    'nrp' => $nrp,
                    'full_name' => $fullName,
                    'gender' => $gender,
                    'personnel_type' => $personnelType,
                    'rank_id' => $rankId,
                    'golongan' => $golongan,
                    'jabatan' => $faker->jobTitle,
                    'bagian' => $satker->name . ' - ' . $faker->word,
                    'satker_id' => $satker->id,
                    'phone' => $faker->phoneNumber,
                    'religion' => $faker->randomElement(['Islam', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu']),
                    'kapor_sizes' => $kaporSizes,
                    'is_active' => true,
                ]);

                if (($i + 1) % 100 === 0) {
                    $this->command->info("Berhasil membuat " . ($i + 1) . " data...");
                }
            }
            DB::commit();
            $this->command->info("Selesai! Berhasil membuat 1000 data personil.");
        }
        catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Gagal membuat data dummy: " . $e->getMessage());
        }
    }
}
