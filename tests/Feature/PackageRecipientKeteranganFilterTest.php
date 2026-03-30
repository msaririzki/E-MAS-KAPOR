<?php

namespace Tests\Feature;

use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\Personnel;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PackageRecipientKeteranganFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
    }

    public function test_save_recipients_applies_scoped_keterangan_filters_by_satker_group_and_field(): void
    {
        $rootSatker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 0,
        ]);

        $poldaSatker = Satker::create([
            'name' => 'DIT LANTAS',
            'code' => 'LANTAS-POLDA',
            'parent_id' => $rootSatker->id,
            'sort_order' => 1,
        ]);

        $polresSatker = Satker::create([
            'name' => 'POLRESTA MATARAM',
            'code' => 'RES-MTR',
            'parent_id' => $rootSatker->id,
            'sort_order' => 2,
        ]);

        $user = User::factory()->create([
            'satker_id' => $rootSatker->id,
        ]);
        $user->assignRole('admin');

        $budgetYear = BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $budgetPackage = BudgetPackage::create([
            'budget_year_id' => $budgetYear->id,
            'name' => 'Paket Uji Filter',
            'status' => 'draft',
            'total_budget' => 0,
        ]);

        $kaporItem = KaporItem::create([
            'category' => 'Tutup_Badan',
            'item_name' => 'PDH POLRI',
            'price' => 150000,
            'unit' => 'PCS',
            'is_active' => true,
        ]);

        $packageItem = PackageItem::create([
            'budget_package_id' => $budgetPackage->id,
            'kapor_item_id' => $kaporItem->id,
        ]);

        Personnel::create([
            'full_name' => 'Personel Polda Match',
            'nrp' => '1001',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'satker_id' => $poldaSatker->id,
            'keterangan' => 'DALAM',
            'is_active' => true,
        ]);

        Personnel::create([
            'full_name' => 'Personel Polda Salah Kolom',
            'nrp' => '1002',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'satker_id' => $poldaSatker->id,
            'keterangan_2' => 'DALAM',
            'is_active' => true,
        ]);

        Personnel::create([
            'full_name' => 'Personel Polres Match',
            'nrp' => '2001',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'satker_id' => $polresSatker->id,
            'keterangan_2' => 'LUAR',
            'is_active' => true,
        ]);

        Personnel::create([
            'full_name' => 'Personel Polres Salah Kolom',
            'nrp' => '2002',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'satker_id' => $polresSatker->id,
            'keterangan' => 'LUAR',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson(
            route('admin.budget.wizard.save-recipients', $packageItem),
            [
                'satker_ids' => [$poldaSatker->id, $polresSatker->id],
                'filters' => [
                    'keterangan_scoped' => [
                        'polda' => [
                            'keterangan' => ['DALAM'],
                        ],
                        'polres' => [
                            'keterangan_2' => ['LUAR'],
                        ],
                    ],
                ],
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_recipients', 2);

        $this->assertDatabaseHas('package_item_recipients', [
            'package_item_id' => $packageItem->id,
            'satker_id' => $poldaSatker->id,
            'matched_count' => 1,
        ]);

        $this->assertDatabaseHas('package_item_recipients', [
            'package_item_id' => $packageItem->id,
            'satker_id' => $polresSatker->id,
            'matched_count' => 1,
        ]);

        $packageItem->refresh();
        $this->assertSame(2, (int) $packageItem->calculated_qty);

        $storedFilters = $packageItem->recipients()->orderBy('satker_id')->first()->recipient_filters;

        $this->assertSame([
            'keterangan_scoped' => [
                'polda' => [
                    'keterangan' => ['DALAM'],
                ],
                'polres' => [
                    'keterangan_2' => ['LUAR'],
                ],
            ],
        ], $storedFilters);
    }
}
