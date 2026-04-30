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
            $this->seedIdentifikasiItems();
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

    private function seedIdentifikasiItems(): void
    {
        $this->call(IdentifikasiItemSeeder::class);
    }
}
