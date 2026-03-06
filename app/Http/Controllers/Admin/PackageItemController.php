<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetPackage;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Satker;
use Illuminate\Http\Request;

class PackageItemController extends Controller
{
    /**
     * Langkah 1: Tampilkan semua item aktif untuk dipilih
     */
    public function selectItems(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load('budgetYear');

        $allItems = KaporItem::where('is_active', true)
            ->orderBy('category')
            ->orderBy('item_name')
            ->get();

        $selectedIds = $budgetPackage->items()->pluck('kapor_item_id')->toArray();

        // Group items by category
        $groupedItems = $allItems->groupBy('category');

        return view('admin.budget.wizard.step1-items', compact(
            'budgetPackage', 'groupedItems', 'selectedIds'
        ));
    }

    /**
     * Toggle item (tambah/hapus) dari paket via AJAX
     */
    public function toggleItem(Request $request, BudgetPackage $budgetPackage)
    {
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

        return response()->json(['action' => $action, 'count' => $count]);
    }

    /**
     * Langkah 2: Pilih penerima (satker) per barang
     */
    public function selectRecipients(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load(['budgetYear', 'items.kaporItem', 'items.recipients.satker']);

        if ($budgetPackage->items->isEmpty()) {
            return redirect()->route('admin.budget.wizard.step1', $budgetPackage)
                ->with('error', 'Pilih minimal 1 item terlebih dahulu.');
        }

        // Ambil semua satker level atas (parent_id null = Polda level)
        $satkers = Satker::whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();

        // Flatten semua satker untuk pilihan
        $allSatkers = Satker::orderBy('name')->get();

        return view('admin.budget.wizard.step2-recipients', compact(
            'budgetPackage', 'allSatkers'
        ));
    }

    /**
     * Simpan penerima per item via AJAX
     */
    public function saveRecipients(Request $request, PackageItem $packageItem)
    {
        $validated = $request->validate([
            'satker_ids' => 'present|array',
            'satker_ids.*' => 'exists:satkers,id',
            'filters' => 'nullable|array',
        ]);

        // Hapus recipients lama
        $packageItem->recipients()->delete();

        // Gunakan filter dari user (null = semua personil aktif di satker)
        $filters = $request->input('filters');

        // Bersihkan filter kosong
        if (is_array($filters)) {
            $filters = array_filter($filters, fn($v) => !empty($v));
            if (empty($filters)) {
                $filters = null;
            }
        }

        if (!empty($validated['satker_ids'])) {
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

        return response()->json([
            'success' => true,
            'total_recipients' => $packageItem->recipients()->sum('matched_count'),
            'recipients_detail' => $recipientsDetail,
        ]);
    }

    /**
     * Langkah 3: Preview semua dan hitung total
     */
    public function preview(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load([
            'budgetYear',
            'items.kaporItem',
            'items.recipients.satker',
        ]);

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
        $package = $packageItem->budgetPackage;
        $packageItem->recipients()->delete();
        $packageItem->delete();

        // Update total
        $package->update([
            'total_budget' => $package->items()->sum('calculated_total'),
        ]);

        return redirect()->back()->with('success', 'Item berhasil dihapus dari paket');
    }
}
