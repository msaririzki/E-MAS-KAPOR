<?php

namespace App\Services;

use App\Models\BudgetPackage;
use App\Models\Personnel;
use App\Models\PersonnelItemAllocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PersonnelItemAllocationSnapshotService
{
    public function __construct(
        private readonly KaporRequirementService $kaporRequirementService,
    ) {}

    public function regenerateForBudgetPackage(BudgetPackage $budgetPackage): void
    {
        $budgetPackage->loadMissing([
            'budgetYear',
            'items.kaporItem',
            'items.recipients.satker',
        ]);

        $desiredRows = collect();

        foreach ($budgetPackage->items as $packageItem) {
            $kaporItem = $packageItem->kaporItem;

            if ($kaporItem === null) {
                continue;
            }

            foreach ($packageItem->recipients as $recipient) {
                $satker = $recipient->satker;

                if ($satker === null) {
                    continue;
                }

                $query = Personnel::query()
                    ->with(['rank:id,name', 'user:id,name,nrp_nip,satker_id,is_active', 'satker:id,name'])
                    ->where('satker_id', $satker->id)
                    ->where('is_active', true)
                    ->where(function ($query): void {
                        $query->whereNotNull('user_id')
                            ->orWhereNotNull('student_batch_id');
                    });

                $this->kaporRequirementService->applyRecipientFilters($query, $recipient->recipient_filters ?? [], $satker);

                $query->chunkById(500, function ($personnels) use ($budgetPackage, $packageItem, $kaporItem, $desiredRows): void {
                    foreach ($personnels as $personnel) {
                        $desiredRows->push([
                            'package_item_id' => $packageItem->id,
                            'user_id' => $personnel->user_id,
                            'budget_package_id' => $budgetPackage->id,
                            'kapor_item_id' => $kaporItem->id,
                            'personnel_id' => $personnel->id,
                            'satker_id' => $personnel->satker_id,
                            'fiscal_year' => (int) ($budgetPackage->budgetYear->year ?? date('Y')),
                            'allocation_status' => 'eligible',
                            'allocated_at' => now(),
                            'nrp_snapshot' => User::normalizeLoginIdentifier($personnel->user?->nrp_nip ?? $personnel->nrp),
                            'full_name_snapshot' => $personnel->full_name,
                            'satker_name_snapshot' => $personnel->satker?->name,
                            'rank_snapshot' => $personnel->rank?->name ?? $personnel->golongan,
                            'jabatan_snapshot' => $personnel->jabatan,
                            'bagian_snapshot' => $personnel->bagian,
                            'gender_snapshot' => $personnel->gender,
                            'personnel_type_snapshot' => $personnel->personnel_type,
                            'kapor_sizes_snapshot' => $personnel->kapor_sizes,
                            'kapor_item_name_snapshot' => $kaporItem->item_name,
                            'item_category_snapshot' => str_replace('_', ' ', (string) $kaporItem->category),
                            'budget_package_name_snapshot' => $budgetPackage->name,
                        ]);
                    }
                }, 'id');
            }
        }

        DB::transaction(function () use ($budgetPackage, $desiredRows): void {
            $desiredKeys = $desiredRows
                ->map(fn (array $row): string => $row['package_item_id'].'|'.$row['personnel_id'])
                ->all();

            $existingAllocations = PersonnelItemAllocation::query()
                ->where('budget_package_id', $budgetPackage->id)
                ->get(['id', 'package_item_id', 'personnel_id']);

            $existingAllocations
                ->reject(fn (PersonnelItemAllocation $allocation): bool => in_array($allocation->package_item_id.'|'.$allocation->personnel_id, $desiredKeys, true))
                ->each
                ->delete();

            foreach ($desiredRows as $row) {
                PersonnelItemAllocation::updateOrCreate(
                    [
                        'package_item_id' => $row['package_item_id'],
                        'personnel_id' => $row['personnel_id'],
                    ],
                    $row,
                );
            }
        });
    }
}
