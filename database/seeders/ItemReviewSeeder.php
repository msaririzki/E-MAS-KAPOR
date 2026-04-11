<?php

namespace Database\Seeders;

use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\ItemReview;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\PersonnelItemAllocation;
use App\Models\Setting;
use App\Models\User;
use App\Services\PersonnelItemAllocationSnapshotService;
use Illuminate\Database\Seeder;

class ItemReviewSeeder extends Seeder
{
    public function run(): void
    {
        $personilUsers = User::query()
            ->role('personil')
            ->with(['personnel.rank', 'satker'])
            ->whereHas('personnel')
            ->orderBy('id')
            ->get();

        if ($personilUsers->isEmpty()) {
            $this->command?->warn('ItemReviewSeeder dilewati: belum ada user personil yang terhubung dengan data personel.');

            return;
        }

        $fiscalYear = (int) Setting::getValue('fiscal_year', date('Y'));
        $budgetYear = BudgetYear::firstOrCreate(
            ['year' => $fiscalYear],
            [
                'name' => 'Tahun Anggaran '.$fiscalYear,
                'is_active' => true,
            ],
        );

        $package = BudgetPackage::updateOrCreate(
            [
                'budget_year_id' => $budgetYear->id,
                'name' => 'Paket Demo Review Item TA '.$fiscalYear,
            ],
            [
                'description' => 'Paket demo untuk menampilkan snapshot review item kapor pada akun personil seeder.',
                'status' => 'finalized',
                'total_budget' => 0,
            ],
        );

        $recipientDefinitions = [
            [
                'item_name' => 'TOPI LAPANGAN BINTARA',
                'filters' => [
                    'personnel_type' => ['Polri'],
                    'rank_categories' => ['BINTARA'],
                    'gender' => ['L'],
                ],
            ],
            [
                'item_name' => 'PDH POLRI WANITA',
                'filters' => [
                    'personnel_type' => ['Polri'],
                    'gender' => ['P'],
                ],
            ],
            [
                'item_name' => 'TOPI LAPANGAN PNS GOL 3',
                'filters' => [
                    'personnel_type' => ['PNS'],
                ],
            ],
            [
                'item_name' => 'SEPATU OLAHRAGA',
                'filters' => [],
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

            foreach ($personilUsers->groupBy('satker_id') as $satkerId => $users) {
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

        $allocations = PersonnelItemAllocation::query()
            ->with(['user', 'kaporItem'])
            ->where('budget_package_id', $package->id)
            ->get()
            ->groupBy('user_id');

        foreach ($personilUsers as $user) {
            $userAllocations = $allocations->get($user->id, collect())->values();

            foreach ($userAllocations as $index => $allocation) {
                $responseStatus = match (true) {
                    $index === 0 && $user->name === 'Siti Nurhaliza' => ItemReview::STATUS_NOT_RECEIVED,
                    $index === 0 => ItemReview::STATUS_REVIEWED,
                    default => null,
                };

                if ($responseStatus === null) {
                    continue;
                }

                ItemReview::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'kapor_item_id' => $allocation->kapor_item_id,
                        'fiscal_year' => $fiscalYear,
                    ],
                    [
                        'personnel_item_allocation_id' => $allocation->id,
                        'personnel_id' => $allocation->personnel_id,
                        'response_status' => $responseStatus,
                        'rating' => $responseStatus === ItemReview::STATUS_REVIEWED ? 5 : null,
                        'comment' => $responseStatus === ItemReview::STATUS_NOT_RECEIVED
                            ? 'Item belum diterima sampai sekarang, mohon dicek distribusinya.'
                            : 'Item demo sudah diterima dan kualitasnya baik.',
                        'submitted_at' => now()->subDays($index + 1),
                    ],
                );
            }
        }

        $this->command?->info('ItemReviewSeeder selesai: paket finalized demo, snapshot allocation, dan review item personil berhasil dibuat.');
    }
}
