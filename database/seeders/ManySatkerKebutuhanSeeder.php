<?php

namespace Database\Seeders;

use App\Models\IdentifikasiItem;
use App\Models\Kebutuhan;
use App\Models\Satker;
use App\Models\User;
use App\Services\KebutuhanEligibilityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManySatkerKebutuhanSeeder extends Seeder
{
    public function run(): void
    {
        if (! IdentifikasiItem::where('is_active', true)->exists()) {
            $this->seedIdentifikasiItems();
        }

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

        $eligibilityService = app(KebutuhanEligibilityService::class);

        DB::transaction(function () use ($satkers, $itemsByCategory, $fiscalYear, $eligibilityService): void {
            foreach ($satkers as $index => $satker) {
                $user = $this->adminSatkerUser($satker);
                $selectedItems = $this->selectItemsForSatker($itemsByCategory, $satker, $index, $eligibilityService);

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

    private function selectItemsForSatker(
        $itemsByCategory,
        Satker $satker,
        int $satkerIndex,
        KebutuhanEligibilityService $eligibilityService
    ) {
        $eligibleItemIds = $eligibilityService
            ->eligibleItemsForSatker($satker)
            ->pluck('id')
            ->all();

        $itemsByCategory = $itemsByCategory
            ->map(fn ($items) => $items->whereIn('id', $eligibleItemIds)->values())
            ->filter(fn ($items) => $items->isNotEmpty());

        if ($itemsByCategory->isEmpty()) {
            return collect();
        }

        $catalog = $itemsByCategory->flatten(1)->values();
        $selected = collect();
        $targetItemCount = $this->targetItemCount($satker, $satkerIndex);

        $this->pushPopularCommonItems($selected, $catalog, $satkerIndex);
        $this->pushCommonItems($selected, $itemsByCategory, $eligibilityService, $satkerIndex);
        $this->pushMatches(
            $selected,
            $catalog,
            $this->specialKeywordsForSatker($satker, $satkerIndex),
            $this->specialItemLimit($satker, $satkerIndex)
        );

        foreach (['Tutup_Kepala', 'Tutup_Badan', 'Tutup_Kaki'] as $category) {
            if ($selected->contains('category', $category)) {
                continue;
            }

            $items = $itemsByCategory->get($category, collect())->values();
            if ($items->isNotEmpty()) {
                $selected->push($items[$satkerIndex % $items->count()]);
            }
        }

        $this->fillRemainingItems($selected, $catalog, $targetItemCount, $satkerIndex);

        return $selected
            ->unique('id')
            ->take($targetItemCount)
            ->values();
    }

    private function pushPopularCommonItems($selected, $catalog, int $satkerIndex): void
    {
        $popularRules = [
            ['keyword' => 'PDH POLRI', 'skipEvery' => 3],
            ['keyword' => 'SEPATU OLAHRAGA', 'skipEvery' => 3],
            ['keyword' => 'KAOS KAKI OLAHRAGA', 'skipEvery' => 3],
            ['keyword' => 'JAKET POLRI', 'skipEvery' => 3],
            ['keyword' => 'TOPI LAPANGAN', 'skipEvery' => 3],
            ['keyword' => 'PAKAIAN OLAHRAGA', 'skipEvery' => 3],
        ];

        foreach ($popularRules as $ruleIndex => $rule) {
            if (($satkerIndex + $ruleIndex) % $rule['skipEvery'] === 0) {
                continue;
            }

            $item = $catalog
                ->filter(fn (IdentifikasiItem $item): bool => strtoupper($item->item_name) === $rule['keyword'])
                ->first();

            if ($item && ! $selected->contains('id', $item->id)) {
                $selected->push($item);
            }
        }
    }

    private function pushCommonItems(
        $selected,
        $itemsByCategory,
        KebutuhanEligibilityService $eligibilityService,
        int $satkerIndex
    ): void {
        $categoryTakes = [
            'Tutup_Kepala' => 1 + ($satkerIndex % 2),
            'Tutup_Badan' => 2 + ($satkerIndex % 3),
            'Tutup_Kaki' => 1 + (int) (($satkerIndex / 2) % 2),
        ];

        foreach ($categoryTakes as $category => $take) {
            $items = $itemsByCategory
                ->get($category, collect())
                ->filter(fn (IdentifikasiItem $item): bool => $eligibilityService->itemGroup($item) === null)
                ->values();

            $this->pushRotatedItems($selected, $items, $satkerIndex + strlen($category), $take);
        }
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function pushFirstMatches($selected, $catalog, array $keywords): void
    {
        foreach ($keywords as $keyword) {
            $item = $catalog
                ->filter(fn (IdentifikasiItem $item): bool => str_contains(strtoupper($item->item_name), strtoupper($keyword)))
                ->sortBy('item_name')
                ->first();

            if ($item && ! $selected->contains('id', $item->id)) {
                $selected->push($item);
            }
        }
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function pushMatches($selected, $catalog, array $keywords, int $limit): void
    {
        $added = 0;

        foreach ($keywords as $keyword) {
            $matches = $catalog
                ->filter(fn (IdentifikasiItem $item): bool => str_contains(strtoupper($item->item_name), strtoupper($keyword)))
                ->sortBy('item_name')
                ->take($limit);

            foreach ($matches as $item) {
                if ($selected->contains('id', $item->id)) {
                    continue;
                }

                $selected->push($item);
                $added++;

                if ($added >= $limit) {
                    return;
                }
            }
        }
    }

    private function pushRotatedItems($selected, $items, int $offset, int $take): void
    {
        $items = $items->values();

        if ($items->isEmpty()) {
            return;
        }

        for ($i = 0; $i < $take; $i++) {
            $item = $items[($offset + $i) % $items->count()];

            if (! $selected->contains('id', $item->id)) {
                $selected->push($item);
            }
        }
    }

    private function fillRemainingItems($selected, $catalog, int $targetItemCount, int $satkerIndex): void
    {
        $catalog = $catalog->values();

        if ($catalog->isEmpty()) {
            return;
        }

        for ($i = 0; $selected->unique('id')->count() < $targetItemCount && $i < $catalog->count(); $i++) {
            $item = $catalog[($satkerIndex + $i) % $catalog->count()];

            if (! $selected->contains('id', $item->id)) {
                $selected->push($item);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function specialKeywordsForSatker(Satker $satker, int $satkerIndex): array
    {
        $name = strtoupper($satker->name.' '.$satker->code);

        $profiles = [
            'BRIMOB' => ['BRIMOB'],
            'POLAIR' => ['POLAIR', 'AIRUD'],
            'AIRUD' => ['POLAIR', 'AIRUD'],
            'LANTAS' => ['LANTAS', 'POLANTAS', 'PET LANTAS'],
            'TIK' => ['TIK'],
            'HUMAS' => ['HUMAS'],
            'PROPAM' => ['PROVOS', 'PROVOST'],
            'PROVOS' => ['PROVOS', 'PROVOST'],
            'PROVOST' => ['PROVOS', 'PROVOST'],
            'RESKRIM' => ['RESKRIM', 'RESINTEL', 'RESINTELPAM', 'TACTICAL RESINTEL'],
            'RESNARKOBA' => ['RESKRIM', 'RESINTEL', 'RESINTELPAM', 'TACTICAL RESINTEL'],
            'INTEL' => ['RESKRIM', 'RESINTEL', 'RESINTELPAM', 'TACTICAL RESINTEL'],
            'PPA' => ['RESKRIM', 'RESINTEL', 'RESINTELPAM', 'TACTICAL RESINTEL'],
            'PPO' => ['RESKRIM', 'RESINTEL', 'RESINTELPAM', 'TACTICAL RESINTEL'],
            'SAMAPTA' => ['SAMAPTA'],
            'SATWA' => ['SATWA'],
            'YANMA' => ['YANMA'],
        ];

        foreach ($profiles as $needle => $keywords) {
            if ($this->satkerMatches($name, $needle)) {
                return $keywords;
            }
        }

        if (str_contains($name, 'POLRES')) {
            $polresProfiles = [
                ['LANTAS', 'POLANTAS', 'PET LANTAS'],
                ['RESKRIM', 'RESINTEL', 'TACTICAL RESINTEL'],
                ['POLAIR', 'AIRUD'],
                ['TIK'],
                ['HUMAS'],
            ];

            return array_values(array_unique(array_merge(
                $polresProfiles[$satkerIndex % count($polresProfiles)],
                $polresProfiles[($satkerIndex + 2) % count($polresProfiles)]
            )));
        }

        return match ($satkerIndex % 5) {
            0 => ['PDL I POLRI', 'PDU I/III POLRI'],
            1 => ['ROMPI KESELAMATAN', 'T-SHIRT'],
            2 => ['PAKAIAN KORPRI', 'PECI KORPRI'],
            3 => ['SEPATU PDL II', 'TOPI LAPANGAN'],
            default => ['PDH POLRI', 'PDL I POLRI'],
        };
    }

    private function targetItemCount(Satker $satker, int $satkerIndex): int
    {
        $name = strtoupper($satker->name.' '.$satker->code);

        if ($this->satkerMatches($name, 'BRIMOB') || $this->satkerMatches($name, 'POLAIR') || $this->satkerMatches($name, 'AIRUD')) {
            return 11;
        }

        if ($this->satkerMatches($name, 'TIK') || $this->satkerMatches($name, 'HUMAS') || $this->satkerMatches($name, 'LANTAS')) {
            return 9;
        }

        if (str_contains($name, 'POLRES')) {
            return 7 + ($satkerIndex % 3);
        }

        return 6 + ($satkerIndex % 3);
    }

    private function specialItemLimit(Satker $satker, int $satkerIndex): int
    {
        $name = strtoupper($satker->name.' '.$satker->code);

        if (str_contains($name, 'POLRES')) {
            return 2 + ($satkerIndex % 3);
        }

        if ($this->satkerMatches($name, 'BRIMOB') || $this->satkerMatches($name, 'POLAIR') || $this->satkerMatches($name, 'AIRUD')) {
            return 5;
        }

        return 2 + ($satkerIndex % 2);
    }

    private function satkerMatches(string $name, string $needle): bool
    {
        if (in_array($needle, ['TIK', 'PPA', 'PPO'], true)) {
            return (bool) preg_match('/(^|[^A-Z0-9])'.preg_quote($needle, '/').'([^A-Z0-9]|$)/', $name);
        }

        return str_contains($name, $needle);
    }

    private function seedIdentifikasiItems(): void
    {
        $this->call(IdentifikasiItemSeeder::class);
    }
}
