<?php

namespace App\Services;

use App\Models\BudgetPackage;
use App\Models\PackageItem;

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
        $kaporItem = $packageItem->kaporItem;
        $price = (float) ($packageItem->custom_price ?? $kaporItem->price ?? 0);

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
}
