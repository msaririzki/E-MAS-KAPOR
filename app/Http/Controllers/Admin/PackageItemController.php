<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetPackage;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Personnel;
use App\Models\Satker;
use App\Models\Setting;
use App\Services\PersonnelItemAllocationSnapshotService;
use App\Services\PersonnelKeteranganService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PackageItemController extends Controller
{
    public function __construct(
        private readonly PersonnelKeteranganService $personnelKeteranganService,
        private readonly PersonnelItemAllocationSnapshotService $personnelItemAllocationSnapshotService,
    ) {}

    /**
     * Langkah 1: Tampilkan semua item aktif untuk dipilih
     */
    public function selectItems(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load('budgetYear');

        if ($response = $this->redirectIfPackageReadOnly($budgetPackage)) {
            return $response;
        }

        $allItems = KaporItem::where('is_active', true)
            ->orderBy('category')
            ->orderBy('item_name')
            ->get();

        // Array of kapor_item_id in sorted order
        $selectedIds = $budgetPackage->items()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('kapor_item_id')->toArray();
        $packageItemMap = $budgetPackage->items()->pluck('id', 'kapor_item_id')->toArray();

        // Group items by category
        $groupedItems = $allItems->groupBy('category');

        return view('admin.budget.wizard.step1-items', compact(
            'budgetPackage', 'groupedItems', 'selectedIds', 'packageItemMap'
        ));
    }

    /**
     * Toggle item (tambah/hapus) dari paket via AJAX
     */
    public function toggleItem(Request $request, BudgetPackage $budgetPackage)
    {
        $this->ensurePackageEditable($budgetPackage);

        $validated = $request->validate([
            'kapor_item_id' => 'required|exists:kapor_items,id',
        ]);

        $existing = PackageItem::where('budget_package_id', $budgetPackage->id)
            ->where('kapor_item_id', $validated['kapor_item_id'])
            ->first();

        if ($existing) {
            $existing->recipients()->delete();
            $existing->delete();
            $action = 'removed';
        } else {
            PackageItem::create([
                'budget_package_id' => $budgetPackage->id,
                'kapor_item_id' => $validated['kapor_item_id'],
            ]);
            $action = 'added';
        }

        $count = $budgetPackage->items()->count();

        $packageItemId = $existing ? null : PackageItem::where('budget_package_id', $budgetPackage->id)
            ->where('kapor_item_id', $validated['kapor_item_id'])
            ->value('id');

        $this->refreshAllocationsForFinalizedPackage($budgetPackage);

        return response()->json([
            'action' => $action,
            'count' => $count,
            'package_item_id' => $packageItemId,
        ]);
    }

    /**
     * Menyimpan urutan baru untuk item yang telah dipilih pada Tahap 1
     */
    public function reorderItems(Request $request, BudgetPackage $budgetPackage)
    {
        $this->ensurePackageEditable($budgetPackage);

        $validated = $request->validate([
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'exists:package_items,id',
        ]);

        foreach ($validated['ordered_ids'] as $index => $packageItemId) {
            PackageItem::where('id', $packageItemId)
                ->where('budget_package_id', $budgetPackage->id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Langkah 2: Pilih penerima (satker) per barang
     */
    public function selectRecipients(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load(['budgetYear', 'items' => function ($q) {
            $q->orderBy('sort_order')->orderBy('id');
        }, 'items.kaporItem', 'items.recipients.satker']);

        if ($response = $this->redirectIfPackageReadOnly($budgetPackage)) {
            return $response;
        }

        if ($budgetPackage->items->isEmpty()) {
            return redirect()->route('admin.budget.wizard.step1', $budgetPackage)
                ->with('error', 'Pilih minimal 1 item terlebih dahulu.');
        }

        // Flatten semua satker untuk pilihan
        $allSatkers = Satker::orderBy('sort_order')->orderBy('name')->get();
        $keteranganOptions = $this->buildKeteranganOptions($allSatkers);

        return view('admin.budget.wizard.step2-recipients', compact(
            'budgetPackage', 'allSatkers', 'keteranganOptions'
        ));
    }

    /**
     * Simpan penerima per item via AJAX
     */
    public function saveRecipients(Request $request, PackageItem $packageItem)
    {
        $budgetPackage = $packageItem->budgetPackage()->with('budgetYear')->firstOrFail();
        $this->ensurePackageEditable($budgetPackage);

        $validated = $request->validate([
            'satker_ids' => 'present|array',
            'satker_ids.*' => 'exists:satkers,id',
            'filters' => 'nullable|array',
        ]);

        // Hapus recipients lama
        $packageItem->recipients()->delete();

        // Gunakan filter dari user (null = semua personil aktif di satker)
        $filters = $this->sanitizeRecipientFilters($request->input('filters'));

        if (! empty($validated['satker_ids'])) {
            foreach ($validated['satker_ids'] as $satkerId) {
                $recipient = PackageItemRecipient::create([
                    'package_item_id' => $packageItem->id,
                    'satker_id' => $satkerId,
                    'recipient_filters' => $filters,
                ]);
                $recipient->calculateMatchedCount();
            }
        }

        // Update calculated values on package item
        $this->recalculatePackageItem($packageItem);

        // Load satker relation so we can return recipient details to UI
        $packageItem->load('recipients.satker');

        $recipientsDetail = $packageItem->recipients->map(function ($r) {
            return [
                'satker_name' => $r->satker->name,
                'count' => $r->matched_count,
            ];
        });

        $this->refreshAllocationsForFinalizedPackage($budgetPackage);

        return response()->json([
            'success' => true,
            'total_recipients' => $packageItem->recipients()->sum('matched_count'),
            'recipients_detail' => $recipientsDetail,
            'filters' => $filters,
        ]);
    }

    /**
     * Langkah 3: Preview semua dan hitung total
     */
    public function preview(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load([
            'budgetYear',
            'items' => function ($q) {
                $q->orderBy('sort_order')->orderBy('id');
            },
            'items.kaporItem',
            'items.recipients.satker',
        ]);

        if ($response = $this->redirectIfPackageReadOnly($budgetPackage)) {
            return $response;
        }

        // Recalculate semua item
        foreach ($budgetPackage->items as $item) {
            $this->recalculatePackageItem($item);
        }

        $budgetPackage->refresh();
        $budgetPackage->load([
            'items.kaporItem',
            'items.recipients.satker',
        ]);

        // Grand total
        $grandTotal = $budgetPackage->items->sum('calculated_total');
        $totalItems = $budgetPackage->items->count();
        $totalRecipients = $budgetPackage->items->sum('calculated_qty');

        return view('admin.budget.wizard.step3-preview', compact(
            'budgetPackage', 'grandTotal', 'totalItems', 'totalRecipients'
        ));
    }

    /**
     * Recalculate qty dan total untuk satu package item
     */
    private function recalculatePackageItem(PackageItem $packageItem): void
    {
        $packageItem->load('kaporItem');

        $totalQty = $packageItem->recipients()->sum('matched_count');
        $price = (float) ($packageItem->custom_price ?? $packageItem->kaporItem->price ?? 0);
        $total = $totalQty * $price;

        $packageItem->update([
            'calculated_qty' => $totalQty,
            'calculated_total' => $total,
        ]);

        // Update total budget di package
        $package = $packageItem->budgetPackage;
        $package->update([
            'total_budget' => $package->items()->sum('calculated_total'),
        ]);
    }

    /**
     * Hapus satu item dari paket
     */
    public function removeItem(PackageItem $packageItem)
    {
        $budgetPackage = $packageItem->budgetPackage()->with('budgetYear')->firstOrFail();
        $this->ensurePackageEditable($budgetPackage);

        $package = $packageItem->budgetPackage;
        $packageItem->recipients()->delete();
        $packageItem->delete();

        // Update total
        $package->update([
            'total_budget' => $package->items()->sum('calculated_total'),
        ]);

        $this->refreshAllocationsForFinalizedPackage($budgetPackage);

        return redirect()->back()->with('success', 'Item berhasil dihapus dari paket');
    }

    /**
     * API: Ambil daftar keterangan unik per satker
     */
    public function getSatkerKeterangan(Satker $satker)
    {
        $fields = ['keterangan', 'keterangan_2', 'keterangan_3', 'keterangan_4'];
        $keteranganList = [];

        foreach ($fields as $field) {
            $counts = [];

            Personnel::query()
                ->where('satker_id', $satker->id)
                ->where('is_active', true)
                ->select([$field])
                ->orderBy('id')
                ->chunk(1000, function ($personnels) use (&$counts, $field) {
                    foreach ($personnels as $personnel) {
                        $value = $this->personnelKeteranganService->normalizeValue($personnel->{$field} ?? null);

                        if ($value === null) {
                            continue;
                        }

                        $counts[$value] = ($counts[$value] ?? 0) + 1;
                    }
                });

            $keteranganList[$field] = collect($counts)
                ->map(fn ($count, $value) => [
                    'value' => $value,
                    'count' => $count,
                ])
                ->sortBy(fn ($row) => strtolower($row['value']))
                ->values();
        }

        return response()->json($keteranganList);
    }

    private function buildKeteranganOptions(Collection $satkers): array
    {
        $scopeLabels = [
            'polda' => 'Polda',
            'polres' => 'Polres',
        ];
        $fieldLabels = [
            'keterangan' => 'Keterangan 1',
            'keterangan_2' => 'Keterangan 2',
            'keterangan_3' => 'Keterangan 3',
            'keterangan_4' => 'Keterangan 4',
        ];
        $scopeBySatkerId = $satkers
            ->mapWithKeys(fn (Satker $satker) => [$satker->id => $satker->recipientScope()])
            ->all();

        $options = [];
        foreach ($scopeLabels as $scope => $scopeLabel) {
            $options[$scope] = [
                'label' => $scopeLabel,
                'fields' => collect($fieldLabels)->mapWithKeys(fn ($fieldLabel, $fieldKey) => [
                    $fieldKey => [
                        'label' => $fieldLabel,
                        'options' => [],
                    ],
                ])->all(),
            ];
        }

        Personnel::query()
            ->where('is_active', true)
            ->select(['satker_id', 'keterangan', 'keterangan_2', 'keterangan_3', 'keterangan_4'])
            ->orderBy('id')
            ->chunk(1000, function ($personnels) use (&$options, $scopeBySatkerId, $fieldLabels) {
                foreach ($personnels as $personnel) {
                    $scope = $scopeBySatkerId[$personnel->satker_id] ?? 'polda';

                    foreach (array_keys($fieldLabels) as $fieldKey) {
                        $value = $this->personnelKeteranganService->normalizeValue($personnel->{$fieldKey} ?? null);
                        if ($value === null) {
                            continue;
                        }

                        $options[$scope]['fields'][$fieldKey]['options'][$value] = ($options[$scope]['fields'][$fieldKey]['options'][$value] ?? 0) + 1;
                    }
                }
            });

        foreach ($options as $scope => $scopeConfig) {
            foreach ($scopeConfig['fields'] as $fieldKey => $fieldConfig) {
                $sorted = collect($fieldConfig['options'])
                    ->map(fn ($count, $value) => [
                        'value' => $value,
                        'count' => $count,
                    ])
                    ->sort(function (array $left, array $right) {
                        $countComparison = $right['count'] <=> $left['count'];

                        if ($countComparison !== 0) {
                            return $countComparison;
                        }

                        return strcasecmp($left['value'], $right['value']);
                    })
                    ->values()
                    ->all();

                $options[$scope]['fields'][$fieldKey]['options'] = $sorted;
            }
        }

        return $options;
    }

    private function sanitizeRecipientFilters(mixed $filters): ?array
    {
        if (! is_array($filters)) {
            return null;
        }

        $cleaned = [];

        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $nested = $this->sanitizeRecipientFilters($value);
                if ($nested !== null) {
                    $cleaned[$key] = $nested;
                }

                continue;
            }

            if (filled($value)) {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned === [] ? null : $cleaned;
    }

    private function ensurePackageEditable(BudgetPackage $budgetPackage): void
    {
        $budgetPackage->loadMissing('budgetYear');

        abort_if(
            (int) $budgetPackage->budgetYear->year !== $this->activeFiscalYear(),
            403,
            'Paket pada tahun selain Tahun Sistem Aktif hanya bisa dilihat sebagai riwayat.',
        );
    }

    private function refreshAllocationsForFinalizedPackage(BudgetPackage $budgetPackage): void
    {
        $budgetPackage->loadMissing('budgetYear');

        if ($budgetPackage->status !== 'finalized') {
            return;
        }

        $this->personnelItemAllocationSnapshotService->regenerateForBudgetPackage($budgetPackage->fresh());
    }

    private function redirectIfPackageReadOnly(BudgetPackage $budgetPackage)
    {
        if ((int) $budgetPackage->budgetYear->year === $this->activeFiscalYear()) {
            return null;
        }

        return redirect()
            ->route('admin.budget.show-package', $budgetPackage)
            ->with('warning', 'Paket pada tahun selain Tahun Sistem Aktif hanya bisa dilihat sebagai riwayat.');
    }

    private function activeFiscalYear(): int
    {
        return (int) Setting::getValue('fiscal_year', date('Y'));
    }
}
