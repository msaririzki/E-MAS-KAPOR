<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetPackage;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Personnel;
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

        if ($budgetPackage->items->isEmpty()) {
            return redirect()->route('admin.budget.wizard.step1', $budgetPackage)
                ->with('error', 'Pilih minimal 1 item terlebih dahulu.');
        }

        // Ambil semua satker level atas (parent_id null = Polda level)
        $satkers = Satker::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Flatten semua satker untuk pilihan
        $allSatkers = Satker::orderBy('sort_order')->orderBy('name')->get();

        // Ambil semua keterangan unik dari personel aktif
        $allKeterangan = Personnel::where('is_active', true)
            ->whereNotNull('keterangan')
            ->where('keterangan', '!=', '')
            ->selectRaw('keterangan, COUNT(*) as jumlah')
            ->groupBy('keterangan')
            ->orderByDesc('jumlah')
            ->get();

        return view('admin.budget.wizard.step2-recipients', compact(
            'budgetPackage', 'allSatkers', 'allKeterangan'
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
            $filters = array_filter($filters, fn ($v) => ! empty($v));
            if (empty($filters)) {
                $filters = null;
            }
        }

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
            'items' => function ($q) {
                $q->orderBy('sort_order')->orderBy('id');
            },
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

    /**
     * API: Ambil daftar keterangan unik per satker
     */
    public function getSatkerKeterangan(Satker $satker)
    {
        $keteranganList = Personnel::where('satker_id', $satker->id)
            ->where('is_active', true)
            ->whereNotNull('keterangan')
            ->where('keterangan', '!=', '')
            ->selectRaw('keterangan, COUNT(*) as jumlah')
            ->groupBy('keterangan')
            ->orderBy('keterangan')
            ->get()
            ->map(fn ($row) => [
                'value' => $row->keterangan,
                'count' => $row->jumlah,
            ]);

        return response()->json($keteranganList);
    }
}
