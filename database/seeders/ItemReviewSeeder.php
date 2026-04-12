<?php

namespace Database\Seeders;

use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\ItemReview;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Personnel;
use App\Models\PersonnelItemAllocation;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use App\Services\PersonnelItemAllocationSnapshotService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ItemReviewSeeder extends Seeder
{
    public const SEEDED_PERSONNEL_COUNT = 50;

    public const PERSONNEL_MARKER = 'TESTIMONI_SEEDER_50';

    public const IDENTIFIER_PREFIX = '98502650';

    public function run(): void
    {
        $fiscalYear = (int) Setting::getValue('fiscal_year', date('Y'));
        $seededUsers = $this->ensureSeederPersonnel();

        if ($seededUsers->count() !== self::SEEDED_PERSONNEL_COUNT) {
            $this->command?->warn('ItemReviewSeeder dilewati: jumlah personel testimoni tidak lengkap.');

            return;
        }

        $budgetYear = BudgetYear::updateOrCreate(
            ['year' => $fiscalYear],
            [
                'name' => 'Tahun Anggaran '.$fiscalYear,
                'is_active' => true,
            ],
        );

        $package = BudgetPackage::updateOrCreate(
            [
                'budget_year_id' => $budgetYear->id,
                'name' => 'Paket Seeder Testimoni 50 Personel TA '.$fiscalYear,
            ],
            [
                'description' => 'Paket seeder presisi untuk monitoring review item kapor 50 personel.',
                'status' => 'finalized',
                'total_budget' => 0,
            ],
        );

        $recipientDefinitions = [
            [
                'item_name' => 'TOPI LAPANGAN BINTARA',
                'filters' => [
                    'keterangan' => [self::PERSONNEL_MARKER],
                    'personnel_type' => ['Polri'],
                    'rank_categories' => ['BINTARA'],
                    'gender' => ['L'],
                ],
            ],
            [
                'item_name' => 'PDH POLRI WANITA',
                'filters' => [
                    'keterangan' => [self::PERSONNEL_MARKER],
                    'personnel_type' => ['Polri'],
                    'gender' => ['P'],
                ],
            ],
            [
                'item_name' => 'TOPI LAPANGAN PNS GOL 3',
                'filters' => [
                    'keterangan' => [self::PERSONNEL_MARKER],
                    'personnel_type' => ['PNS'],
                ],
            ],
            [
                'item_name' => 'SEPATU OLAHRAGA',
                'filters' => [
                    'keterangan' => [self::PERSONNEL_MARKER],
                ],
            ],
        ];

        foreach ($recipientDefinitions as $definition) {
            $kaporItem = KaporItem::query()->where('item_name', $definition['item_name'])->first();

            if ($kaporItem === null) {
                $this->command?->warn('ItemReviewSeeder melewati item '.$definition['item_name'].' karena tidak ditemukan di master kapor.');

                continue;
            }

            $packageItem = PackageItem::updateOrCreate(
                [
                    'budget_package_id' => $package->id,
                    'kapor_item_id' => $kaporItem->id,
                ],
                [
                    'custom_price' => $kaporItem->price,
                ],
            );

            foreach ($seededUsers->pluck('satker_id')->unique()->values() as $satkerId) {
                PackageItemRecipient::updateOrCreate(
                    [
                        'package_item_id' => $packageItem->id,
                        'satker_id' => (int) $satkerId,
                    ],
                    [
                        'recipient_filters' => $definition['filters'],
                    ],
                );
            }
        }

        app(PersonnelItemAllocationSnapshotService::class)->regenerateForBudgetPackage($package->fresh());

        $seededUserIds = $seededUsers->pluck('id');

        $allocationsByUser = PersonnelItemAllocation::query()
            ->with(['kaporItem', 'user', 'personnel.rank'])
            ->where('budget_package_id', $package->id)
            ->whereIn('user_id', $seededUserIds)
            ->get()
            ->groupBy('user_id');

        ItemReview::query()
            ->where('fiscal_year', $fiscalYear)
            ->whereIn('user_id', $seededUserIds)
            ->delete();

        foreach ($seededUsers->values() as $index => $user) {
            $allocation = $this->resolvePrimaryAllocation(
                $allocationsByUser->get($user->id, collect()),
                $user->personnel,
            );

            if ($allocation === null) {
                $this->command?->warn('ItemReviewSeeder melewati '.$user->name.' karena tidak ada alokasi item yang cocok.');

                continue;
            }

            $responseStatus = $index % 5 === 0
                ? ItemReview::STATUS_NOT_RECEIVED
                : ItemReview::STATUS_REVIEWED;

            $rating = $responseStatus === ItemReview::STATUS_REVIEWED
                ? 3 + ($index % 3)
                : null;

            ItemReview::create([
                'personnel_item_allocation_id' => $allocation->id,
                'user_id' => $allocation->user_id,
                'personnel_id' => $allocation->personnel_id,
                'kapor_item_id' => $allocation->kapor_item_id,
                'fiscal_year' => $fiscalYear,
                'response_status' => $responseStatus,
                'rating' => $rating,
                'comment' => $this->buildComment($allocation, $responseStatus, $rating),
                'submitted_at' => now()->subDays($index % 14)->subMinutes($index * 7),
            ]);
        }

        $createdReviews = ItemReview::query()
            ->where('fiscal_year', $fiscalYear)
            ->whereIn('user_id', $seededUserIds)
            ->count();

        $this->command?->info("ItemReviewSeeder selesai: {$seededUsers->count()} personel testimoni dan {$createdReviews} review item berhasil dibuat.");
    }

    private function ensureSeederPersonnel(): Collection
    {
        $satkers = Satker::query()
            ->whereIn('code', ['POLDA-NTB', 'RES-MTR', 'RES-LOBAR', 'RES-LOTENG', 'RES-LOTIM'])
            ->get()
            ->keyBy('code');

        $rankMap = Rank::query()
            ->whereIn('name', ['BRIPKA', 'BRIGADIR', 'IPTU', 'AKP', 'Penata', 'Penata Muda'])
            ->get()
            ->keyBy('name');

        if ($satkers->count() < 5 || $rankMap->count() < 6) {
            return collect();
        }

        $profiles = $this->profiles();

        foreach ($profiles as $profile) {
            $satker = $satkers->get($profile['satker_code']);
            $rank = $rankMap->get($profile['rank_name']);

            if ($satker === null || $rank === null) {
                continue;
            }

            $user = User::createOrUpdatePersonnelAccount(
                User::query()->where('nrp_nip', $profile['identifier'])->first(),
                $profile['identifier'],
                $profile['name'],
                $satker->id,
                true,
                $profile['phone'],
                true,
            );

            Personnel::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nrp' => $profile['identifier'],
                    'full_name' => $profile['name'],
                    'gender' => $profile['gender'],
                    'personnel_type' => $profile['personnel_type'],
                    'rank_id' => $rank->id,
                    'golongan' => $profile['golongan'],
                    'jabatan' => $profile['jabatan'],
                    'bagian' => $satker->name.' - Seeder Testimoni',
                    'satker_id' => $satker->id,
                    'phone' => $profile['phone'],
                    'religion' => $profile['religion'],
                    'keterangan' => self::PERSONNEL_MARKER,
                    'kapor_sizes' => $this->kaporSizesForGender($profile['gender']),
                    'verification_status' => 'verified',
                    'is_active' => true,
                ],
            );
        }

        return User::query()
            ->role('personil')
            ->with(['personnel.rank', 'satker'])
            ->where('nrp_nip', 'like', self::IDENTIFIER_PREFIX.'%')
            ->orderBy('nrp_nip')
            ->get();
    }

    private function profiles(): Collection
    {
        $satkerCodes = ['POLDA-NTB', 'RES-MTR', 'RES-LOBAR', 'RES-LOTENG', 'RES-LOTIM'];
        $profiles = collect();

        foreach (range(1, 18) as $index) {
            $profiles->push([
                'identifier' => $this->identifier('1', $index),
                'name' => sprintf('Seeder Testimoni Polri Pria %02d', $index),
                'gender' => 'L',
                'personnel_type' => 'Polri',
                'rank_name' => $index % 2 === 0 ? 'BRIGADIR' : 'BRIPKA',
                'satker_code' => $satkerCodes[($index - 1) % count($satkerCodes)],
                'golongan' => '-',
                'jabatan' => 'Bintara Operasional',
                'phone' => $this->phone($index),
                'religion' => 'Islam',
            ]);
        }

        foreach (range(1, 16) as $index) {
            $profiles->push([
                'identifier' => $this->identifier('2', $index),
                'name' => sprintf('Seeder Testimoni Polri Wanita %02d', $index),
                'gender' => 'P',
                'personnel_type' => 'Polri',
                'rank_name' => $index % 2 === 0 ? 'AKP' : 'IPTU',
                'satker_code' => $satkerCodes[($index - 1) % count($satkerCodes)],
                'golongan' => '-',
                'jabatan' => 'Perwira Administrasi',
                'phone' => $this->phone(100 + $index),
                'religion' => $index % 3 === 0 ? 'Kristen Protestan' : 'Islam',
            ]);
        }

        foreach (range(1, 16) as $index) {
            $profiles->push([
                'identifier' => $this->identifier('3', $index),
                'name' => sprintf('Seeder Testimoni PNS %02d', $index),
                'gender' => $index % 3 === 0 ? 'P' : 'L',
                'personnel_type' => 'PNS',
                'rank_name' => $index % 2 === 0 ? 'Penata' : 'Penata Muda',
                'satker_code' => $satkerCodes[($index - 1) % count($satkerCodes)],
                'golongan' => $index % 2 === 0 ? 'III/c' : 'III/a',
                'jabatan' => 'Staf PNS Administrasi',
                'phone' => $this->phone(200 + $index),
                'religion' => $index % 4 === 0 ? 'Hindu' : 'Islam',
            ]);
        }

        return $profiles;
    }

    private function resolvePrimaryAllocation(Collection $allocations, Personnel $personnel): ?PersonnelItemAllocation
    {
        $preferredItemName = match (true) {
            $personnel->personnel_type === 'PNS' => 'TOPI LAPANGAN PNS GOL 3',
            $personnel->gender === 'P' => 'PDH POLRI WANITA',
            default => 'TOPI LAPANGAN BINTARA',
        };

        return $allocations
            ->sortBy(fn (PersonnelItemAllocation $allocation): string => (string) $allocation->kaporItem?->item_name)
            ->firstWhere('kaporItem.item_name', $preferredItemName)
            ?? $allocations
                ->sortBy(fn (PersonnelItemAllocation $allocation): string => (string) $allocation->kaporItem?->item_name)
                ->first();
    }

    private function buildComment(PersonnelItemAllocation $allocation, string $responseStatus, ?int $rating): string
    {
        $itemName = $allocation->kaporItem?->item_name ?? 'item kapor';

        if ($responseStatus === ItemReview::STATUS_NOT_RECEIVED) {
            return 'Seeder presisi: '.$itemName.' belum diterima, mohon cek proses distribusi pada satker terkait.';
        }

        return 'Seeder presisi: '.$itemName.' sudah diterima dengan kondisi baik dan penilaian '.$rating.'/5.';
    }

    private function kaporSizesForGender(string $gender): array
    {
        return array_filter([
            'topi' => '57',
            'kemeja' => $gender === 'P' ? 'SD' : '15.5',
            'celana' => $gender === 'P' ? 'B' : '32',
            'sepatu_dinas' => $gender === 'P' ? '39' : '42',
            'sepatu_olahraga' => $gender === 'P' ? '38' : '42',
            'sabuk' => '40',
            'jaket' => $gender === 'P' ? 'M' : 'L',
            'olahraga' => $gender === 'P' ? 'SD' : 'B',
            'jilbab' => $gender === 'P' ? 'SD' : null,
        ], static fn ($value) => $value !== null);
    }

    private function identifier(string $group, int $index): string
    {
        return self::IDENTIFIER_PREFIX.$group.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    }

    private function phone(int $index): string
    {
        return '08127'.str_pad((string) $index, 7, '0', STR_PAD_LEFT);
    }
}
