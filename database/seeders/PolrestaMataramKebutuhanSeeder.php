<?php

namespace Database\Seeders;

use App\Models\IdentifikasiItem;
use App\Models\Kebutuhan;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PolrestaMataramKebutuhanSeeder extends Seeder
{
    public function run(): void
    {
        $satker = Satker::where('code', 'RES-MTR')
            ->orWhere('name', 'POLRESTA MATARAM')
            ->first();

        if (! $satker) {
            $this->command?->warn('Satker POLRESTA MATARAM tidak ditemukan. Seeder kebutuhan dilewati.');

            return;
        }

        $user = User::firstOrCreate([
            'email' => 'admin.satker.mataram@gmail.com',
        ], [
            'name' => 'Admin Satker Polresta Mataram',
            'password' => Hash::make('87654321'),
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);

        if (! $user->hasRole('admin_satker')) {
            $user->assignRole('admin_satker');
        }

        $fiscalYear = (string) ((int) date('Y') + 1);

        if (! IdentifikasiItem::where('is_active', true)->exists()) {
            $this->seedFallbackIdentifikasiItems();
        }

        $selectedItems = IdentifikasiItem::query()
            ->where('is_active', true)
            ->orderByRaw("CASE
                WHEN category = 'Tutup_Kepala' THEN 1
                WHEN category = 'Tutup_Badan' THEN 2
                WHEN category = 'Tutup_Kaki' THEN 3
                ELSE 999 END")
            ->orderBy('item_name')
            ->get()
            ->groupBy('category')
            ->flatMap(fn ($items) => $items->take(3))
            ->take(12)
            ->values();

        DB::transaction(function () use ($satker, $user, $fiscalYear, $selectedItems): void {
            $kebutuhan = Kebutuhan::updateOrCreate([
                'satker_id' => $satker->id,
                'fiscal_year' => $fiscalYear,
            ], [
                'user_id' => $user->id,
                'title' => 'Pengajuan Kebutuhan TA '.$fiscalYear,
                'status' => 'diajukan',
                'notes' => 'Data dummy identifikasi kebutuhan untuk POLRESTA MATARAM.',
                'admin_notes' => null,
                'submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]);

            $kebutuhan->items()->delete();

            foreach ($selectedItems as $item) {
                $kebutuhan->items()->create([
                    'identifikasi_item_id' => $item->id,
                    'quantity' => 1,
                    'notes' => null,
                ]);
            }
        });

        $this->command?->info('Data kebutuhan POLRESTA MATARAM berhasil dibuat.');
    }

    private function seedFallbackIdentifikasiItems(): void
    {
        $items = [
            ['item_name' => 'TOPI LAPANGAN', 'category' => 'Tutup_Kepala'],
            ['item_name' => 'BARET', 'category' => 'Tutup_Kepala'],
            ['item_name' => 'HELM TAKTIS', 'category' => 'Tutup_Kepala'],
            ['item_name' => 'TOPI DINAS HARIAN', 'category' => 'Tutup_Kepala'],
            ['item_name' => 'PET POLRI', 'category' => 'Tutup_Kepala'],
            ['item_name' => 'MUTS POLRI', 'category' => 'Tutup_Kepala'],
            ['item_name' => 'TOPI RIMBA', 'category' => 'Tutup_Kepala'],
            ['item_name' => 'PDH POLRI', 'category' => 'Tutup_Badan'],
            ['item_name' => 'PDL POLRI', 'category' => 'Tutup_Badan'],
            ['item_name' => 'JAKET LAPANGAN', 'category' => 'Tutup_Badan'],
            ['item_name' => 'KAOS DALAM POLRI', 'category' => 'Tutup_Badan'],
            ['item_name' => 'KAOS OLAHRAGA', 'category' => 'Tutup_Badan'],
            ['item_name' => 'CELANA PDL', 'category' => 'Tutup_Badan'],
            ['item_name' => 'ROMPI LAPANGAN', 'category' => 'Tutup_Badan'],
            ['item_name' => 'JAS HUJAN', 'category' => 'Tutup_Badan'],
            ['item_name' => 'SEPATU PDL', 'category' => 'Tutup_Kaki'],
            ['item_name' => 'SEPATU PDH', 'category' => 'Tutup_Kaki'],
            ['item_name' => 'KAOS KAKI DINAS', 'category' => 'Tutup_Kaki'],
            ['item_name' => 'SEPATU OLAHRAGA', 'category' => 'Tutup_Kaki'],
            ['item_name' => 'SEPATU LAPANGAN', 'category' => 'Tutup_Kaki'],
            ['item_name' => 'SEPATU BOOT', 'category' => 'Tutup_Kaki'],
            ['item_name' => 'KAOS KAKI OLAHRAGA', 'category' => 'Tutup_Kaki'],
            ['item_name' => 'SABUK DINAS', 'category' => 'Atribut'],
            ['item_name' => 'TANDA PANGKAT', 'category' => 'Atribut'],
            ['item_name' => 'TALI KUR', 'category' => 'Atribut'],
            ['item_name' => 'BORGOL', 'category' => 'Atribut'],
            ['item_name' => 'PELUIT', 'category' => 'Atribut'],
            ['item_name' => 'TONGKAT POLRI', 'category' => 'Atribut'],
            ['item_name' => 'SARUNG TANGAN', 'category' => 'Atribut'],
            ['item_name' => 'MASKER LAPANGAN', 'category' => 'Atribut'],
        ];

        foreach ($items as $item) {
            IdentifikasiItem::firstOrCreate([
                'item_name' => $item['item_name'],
                'category' => $item['category'],
            ], [
                'description' => 'Data dummy item identifikasi kebutuhan.',
                'is_active' => true,
            ]);
        }
    }
}
