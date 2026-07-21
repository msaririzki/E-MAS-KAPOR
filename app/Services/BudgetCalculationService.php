<?php

namespace App\Services;

use App\Models\BudgetPackage;
use App\Models\PackageItem;
use App\Models\PersonnelItemAllocation;
use App\Models\Setting;

class BudgetCalculationService
{
    /**
     * Hitung semua data untuk satu paket: per item, per satker, breakdown total
     */
    public function calculatePackage(BudgetPackage $package): array
    {
        $package->load(['items.kaporItem', 'items.recipients.satker', 'budgetYear']);

        $items = [];
        $grandTotal = 0;
        $grandQty = 0;

        foreach ($package->items as $packageItem) {
            $itemData = $this->calculateItem($packageItem);
            $items[] = $itemData;
            $grandTotal += $itemData['total'];
            $grandQty += $itemData['qty'];
        }

        // Group items by invoice_group
        $groupedItems = collect($items)->groupBy('invoice_group');

        return [
            'package' => $package,
            'items' => $items,
            'grouped_items' => $groupedItems,
            'grand_total' => $grandTotal,
            'grand_qty' => $grandQty,
            'total_items' => count($items),
        ];
    }

    /**
     * Hitung data untuk satu package item
     */
    public function calculateItem(PackageItem $packageItem): array
    {
        $packageItem->loadMissing('budgetPackage.budgetYear', 'kaporItem', 'recipients.satker');

        $kaporItem = $packageItem->kaporItem;
        $price = (float) ($packageItem->custom_price ?? $kaporItem->price ?? 0);

        if ($this->shouldReadSnapshot($packageItem)) {
            return $this->calculateArchivedItem($packageItem, $price);
        }

        $recipients = [];
        $totalQty = 0;

        foreach ($packageItem->recipients as $recipient) {
            // Recalculate matched count
            $count = $recipient->calculateMatchedCount();
            $totalQty += $count;

            $recipients[] = [
                'satker_id' => $recipient->satker_id,
                'satker_name' => $recipient->satker->name,
                'matched_count' => $count,
                'subtotal' => $count * $price,
                'filters' => $recipient->recipient_filters,
            ];
        }

        $total = $totalQty * $price;

        // Update calculated fields
        $packageItem->update([
            'calculated_qty' => $totalQty,
            'calculated_total' => $total,
        ]);

        return [
            'package_item_id' => $packageItem->id,
            'item_name' => $kaporItem->item_name,
            'category' => $kaporItem->category,
            'invoice_group' => $kaporItem->invoice_group ?? $kaporItem->category,
            'unit' => $kaporItem->unit ?? 'PCS',
            'price' => $price,
            'qty' => $totalQty,
            'total' => $total,
            'recipients' => $recipients,
        ];
    }

    private function calculateArchivedItem(PackageItem $packageItem, float $price): array
    {
        $kaporItem = $packageItem->kaporItem;

        $allocationGroups = PersonnelItemAllocation::query()
            ->where('package_item_id', $packageItem->id)
            ->get(['satker_id', 'satker_name_snapshot'])
            ->groupBy(fn (PersonnelItemAllocation $allocation): string => (string) ($allocation->satker_id ?? $allocation->satker_name_snapshot ?? 'arsip'));

        $recipients = [];
        $totalQty = 0;

        foreach ($allocationGroups as $group) {
            $first = $group->first();
            $count = $group->count();
            $totalQty += $count;

            $recipients[] = [
                'satker_id' => $first?->satker_id,
                'satker_name' => $first?->satker_name_snapshot ?? 'Tanpa Satker',
                'matched_count' => $count,
                'subtotal' => $count * $price,
                'filters' => [],
            ];
        }

        if ($totalQty === 0) {
            $totalQty = (int) ($packageItem->calculated_qty ?? 0);
            $recipients = $packageItem->recipients
                ->map(fn ($recipient): array => [
                    'satker_id' => $recipient->satker_id,
                    'satker_name' => $recipient->satker?->name ?? 'Tanpa Satker',
                    'matched_count' => (int) ($recipient->matched_count ?? 0),
                    'subtotal' => (int) ($recipient->matched_count ?? 0) * $price,
                    'filters' => $recipient->recipient_filters,
                ])
                ->all();
        }

        $total = $totalQty * $price;

        return [
            'package_item_id' => $packageItem->id,
            'item_name' => $kaporItem->item_name,
            'category' => $kaporItem->category,
            'invoice_group' => $kaporItem->invoice_group ?? $kaporItem->category,
            'unit' => $kaporItem->unit ?? 'PCS',
            'price' => $price,
            'qty' => $totalQty,
            'total' => $total,
            'recipients' => $recipients,
        ];
    }

    private function shouldReadSnapshot(PackageItem $packageItem): bool
    {
        $activeFiscalYear = (int) Setting::getValue('fiscal_year', date('Y'));
        $budgetPackage = $packageItem->budgetPackage;
        $year = (int) ($budgetPackage?->budgetYear?->year ?? 0);

        return $budgetPackage?->status === 'archived' || ($year > 0 && $year < $activeFiscalYear);
    }
}
