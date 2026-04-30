<?php

namespace Database\Seeders;

use App\Models\IdentifikasiItem;
use App\Models\Kebutuhan;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManySatkerKebutuhanSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFallbackIdentifikasiItems();

        $itemsByCategory = IdentifikasiItem::query()
            ->where('is_active', true)
            ->orderByRaw("CASE
                WHEN category = 'Tutup_Kepala' THEN 1
                WHEN category = 'Tutup_Badan' THEN 2
                WHEN category = 'Tutup_Kaki' THEN 3
                ELSE 999 END")
            ->orderBy('item_name')
            ->get()
            ->groupBy('category');

        if ($itemsByCategory->isEmpty()) {
            $this->command?->warn('Item identifikasi aktif tidak ditemukan. Seeder kebutuhan banyak satker dilewati.');

            return;
        }

        $satkers = Satker::query()
            ->whereNotNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($satkers->isEmpty()) {
            $this->command?->warn('Data satker turunan tidak ditemukan. Seeder kebutuhan banyak satker dilewati.');

            return;
        }

        $fiscalYear = (string) ((int) date('Y') + 1);

        DB::transaction(function () use ($satkers, $itemsByCategory, $fiscalYear): void {
            foreach ($satkers as $index => $satker) {
                $user = $this->adminSatkerUser($satker);
                $selectedItems = $this->selectItemsForSatker($itemsByCategory, $index);

                if ($selectedItems->isEmpty()) {
                    continue;
                }

                $kebutuhan = Kebutuhan::updateOrCreate([
                    'satker_id' => $satker->id,
                    'fiscal_year' => $fiscalYear,
                ], [
                    'user_id' => $user->id,
                    'title' => 'Pengajuan Kebutuhan TA '.$fiscalYear,
                    'status' => $index % 6 === 0 ? 'disetujui' : 'diajukan',
                    'notes' => 'Data dummy identifikasi kebutuhan untuk '.$satker->name.'.',
                    'admin_notes' => $index % 6 === 0 ? 'Data demo disetujui untuk pengujian statistik.' : null,
                    'submitted_at' => now()->subDays($index % 12),
                    'reviewed_at' => $index % 6 === 0 ? now()->subDays($index % 5) : null,
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
            }
        });

        $this->command?->info('Data kebutuhan banyak satker berhasil dibuat: '.$satkers->count().' satker.');
    }

    private function adminSatkerUser(Satker $satker): User
    {
        $code = Str::slug(Str::lower($satker->code ?: $satker->name), '.');

        $user = User::firstOrCreate([
            'email' => 'admin.satker.'.$code.'@kapor.local',
        ], [
            'name' => 'Admin Satker '.$satker->name,
            'password' => Hash::make('87654321'),
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);

        $user->forceFill([
            'satker_id' => $satker->id,
            'is_active' => true,
        ])->save();

        if (! $user->hasRole('admin_satker')) {
            $user->assignRole('admin_satker');
        }

        return $user;
    }

    private function selectItemsForSatker($itemsByCategory, int $satkerIndex)
    {
        return $itemsByCategory
            ->flatMap(function ($items) use ($satkerIndex) {
                $items = $items->values();
                $selected = collect();

                if ($satkerIndex % 7 !== 0 && $items->has(0)) {
                    $selected->push($items[0]);
                }

                if ($satkerIndex % 2 === 0 && $items->has(1)) {
                    $selected->push($items[1]);
                }

                if ($satkerIndex % 3 === 0 && $items->has(2)) {
                    $selected->push($items[2]);
                }

                if ($satkerIndex % 5 === 0 && $items->has(3)) {
                    $selected->push($items[3]);
                }

                if ($selected->isEmpty() && $items->isNotEmpty()) {
                    $selected->push($items[$satkerIndex % $items->count()]);
                }

                return $selected;
            })
            ->unique('id')
            ->values();
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
