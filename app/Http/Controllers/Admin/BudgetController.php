<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\PackageItemRecipient;
use App\Models\Personnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    // ── Budget Years ──────────────────────────────────────────

    public function index()
    {
        $years = BudgetYear::withCount('packages')
            ->orderByDesc('year')
            ->get();

        return view('admin.budget.index', compact('years'));
    }

    public function storeYear(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2050|unique:budget_years,year',
            'name' => 'nullable|string|max:255',
        ]);

        $validated['name'] = $validated['name'] ?? 'Tahun Anggaran '.$validated['year'];

        BudgetYear::create($validated);

        return redirect()->back()->with('success', 'Tahun anggaran berhasil ditambahkan');
    }

    public function updateYear(Request $request, BudgetYear $budgetYear)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2050|unique:budget_years,year,'.$budgetYear->id,
            'name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $budgetYear->update($validated);

        return redirect()->back()->with('success', 'Tahun anggaran berhasil diperbarui');
    }

    public function destroyYear(BudgetYear $budgetYear)
    {
        if ($budgetYear->packages()->exists()) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus tahun anggaran yang sudah memiliki paket.');
        }

        $budgetYear->delete();

        return redirect()->back()->with('success', 'Tahun anggaran berhasil dihapus');
    }

    // ── Budget Packages ──────────────────────────────────────

    public function showYear(BudgetYear $budgetYear)
    {
        $budgetYear->load(['packages' => function ($q) {
            $q->orderBy('name');
        }]);

        return view('admin.budget.packages', compact('budgetYear'));
    }

    public function storePackage(Request $request, BudgetYear $budgetYear)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $budgetYear->packages()->create($validated);

        return redirect()->back()->with('success', 'Paket berhasil ditambahkan');
    }

    public function updatePackage(Request $request, BudgetPackage $budgetPackage)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,finalized,archived',
        ]);

        $budgetPackage->update($validated);

        return redirect()->back()->with('success', 'Paket berhasil diperbarui');
    }

    public function destroyPackage(BudgetPackage $budgetPackage)
    {
        $yearId = $budgetPackage->budget_year_id;
        $budgetPackage->delete();

        return redirect()->route('admin.budget.show-year', $yearId)->with('success', 'Paket berhasil dihapus');
    }

    /**
     * Show package detail (will be used for Fase 3 wizard)
     */
    public function showPackage(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load([
            'budgetYear',
            'items.kaporItem.sizes',
            'items.recipients.satker',
        ]);

        // ── Auto-recalculate: sinkronkan matched_count & calculated_qty dengan data personel terkini ──
        DB::transaction(function () use ($budgetPackage) {
            foreach ($budgetPackage->items as $item) {
                $totalQty  = 0;
                $itemName  = $item->kaporItem->item_name ?? '';
                $autoGender = PackageItemRecipient::detectGenderFromItemName($itemName);

                foreach ($item->recipients as $recipient) {
                    $filters = $recipient->recipient_filters ?? [];
                    $q = Personnel::where('satker_id', $recipient->satker_id)->where('is_active', true);
                    if (!empty($filters['personnel_type'])) {
                        $mt = array_map(fn($t) => match (strtolower($t)) { 'polri' => 'Polri', 'pns' => 'PNS', 'pppk' => 'PPPK', default => $t }, $filters['personnel_type']);
                        $q->whereIn('personnel_type', $mt);
                    }
                    // Gender: filter eksplisit dari wizard, atau auto-deteksi dari nama item
                    if (!empty($filters['gender'])) {
                        $q->whereIn('gender', $filters['gender']);
                    } elseif ($autoGender !== null) {
                        $q->where('gender', $autoGender);
                    }
                    if (!empty($filters['rank_categories'])) $q->whereHas('rank', fn($rq) => $rq->whereIn('category', $filters['rank_categories']));
                    if (!empty($filters['keterangan']))      $q->whereIn('keterangan', $filters['keterangan']);
                    if (!empty($filters['golongan']))        $q->whereIn('golongan', $filters['golongan']);
                    $count = $q->count();
                    $recipient->update(['matched_count' => $count]);
                    $totalQty += $count;
                }
                $price = (float) ($item->custom_price ?? $item->kaporItem->price ?? 0);
                $item->update(['calculated_qty' => $totalQty, 'calculated_total' => $totalQty * $price]);
            }
            $budgetPackage->update(['total_budget' => $budgetPackage->items()->sum('calculated_total')]);
        });

        // Reload supaya data fresh setelah recalculate
        $budgetPackage->refresh()->load([
            'budgetYear',
            'items.kaporItem.sizes',
            'items.recipients.satker',
        ]);

        // ── Hitung peringatan ukuran belum diisi ──
        $sizeWarnings = [];

        // Helper: tentukan sizeKey dari nama item
        $getSizeKey = function (string $name): string {
            $name = strtoupper($name);
            if (str_contains($name, 'TOPI') || str_contains($name, 'PET') || str_contains($name, 'BARET') || str_contains($name, 'PECI')) return 'topi';
            if (str_contains($name, 'JILBAB')) return 'jilbab';
            if (str_contains($name, 'CELANA') || str_contains($name, 'ROK')) return 'celana';
            if (str_contains($name, 'SEPATU OLAHRAGA')) return 'sepatu_olahraga';
            if (str_contains($name, 'SEPATU')) return 'sepatu_dinas';
            if (str_contains($name, 'JAKET')) return 'jaket';
            if (str_contains($name, 'OLAHRAGA')) return 'olahraga';
            if (str_contains($name, 'SABUK')) return 'sabuk';
            return 'kemeja';
        };

        foreach ($budgetPackage->items as $item) {
            $kaporItem = $item->kaporItem;
            $sizeKey   = $getSizeKey($kaporItem->item_name);

            // Dapatkan daftar ukuran valid dari eager-loaded collection (tanpa query baru)
            $validSizes = array_values(array_unique($kaporItem->sizes->pluck('size_label')->toArray()));
            if (empty($validSizes)) continue; // skip jika item tidak punya ukuran

            $itemTotal   = 0;
            $itemValid   = 0;
            $bySatker    = [];

            foreach ($item->recipients as $recipient) {
                $filters = $recipient->recipient_filters ?? [];
                $satker  = $recipient->satker;

                $query = \App\Models\Personnel::where('satker_id', $satker->id)->where('is_active', true);

                if (!empty($filters['personnel_type'])) {
                    $mappedTypes = array_map(fn($t) => match(strtolower($t)) {
                        'polri' => 'Polri', 'pns' => 'PNS', 'pppk' => 'PPPK', default => $t
                    }, $filters['personnel_type']);
                    $query->whereIn('personnel_type', $mappedTypes);
                }
                if (!empty($filters['gender']))          $query->whereIn('gender', $filters['gender']);
                if (!empty($filters['rank_categories'])) $query->whereHas('rank', fn($q) => $q->whereIn('category', $filters['rank_categories']));
                if (!empty($filters['keterangan']))      $query->whereIn('keterangan', $filters['keterangan']);
                if (!empty($filters['golongan']))        $query->whereIn('golongan', $filters['golongan']);

                $personnels = $query->get(['kapor_sizes']);

                $satkerTotal = $personnels->count();
                $satkerValid = 0;

                foreach ($personnels as $p) {
                    $sizes   = is_string($p->kapor_sizes) ? json_decode($p->kapor_sizes, true) : ($p->kapor_sizes ?? []);
                    $sizeVal = (string) ($sizes[$sizeKey] ?? '');
                    if (!empty($sizeVal) && $sizeVal !== '-' && $sizeVal !== 'null' && in_array($sizeVal, $validSizes)) {
                        $satkerValid++;
                    }
                }

                $satkerMissing = $satkerTotal - $satkerValid;
                $itemTotal    += $satkerTotal;
                $itemValid    += $satkerValid;

                if ($satkerMissing > 0) {
                    $bySatker[] = [
                        'satker_id'   => $satker->id,
                        'satker_name' => $satker->name,
                        'total'       => $satkerTotal,
                        'valid'       => $satkerValid,
                        'missing'     => $satkerMissing,
                    ];

                }
            }

            $itemMissing = $itemTotal - $itemValid;
            if ($itemMissing > 0) {
                $sizeWarnings[] = [
                    'item_name' => $kaporItem->item_name,
                    'total'     => $itemTotal,
                    'valid'     => $itemValid,
                    'missing'   => $itemMissing,
                    'by_satker' => $bySatker,
                ];
            }
        }

        return view('admin.budget.package-detail', compact('budgetPackage', 'sizeWarnings'));
    }

    /**
     * Recalculate matched_count & calculated_qty berdasarkan data personel terkini.
     */
    public function recalculatePackage(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load(['items.kaporItem', 'items.recipients.satker']);

        DB::transaction(function () use ($budgetPackage) {
            foreach ($budgetPackage->items as $item) {
                $totalQty   = 0;
                $itemName   = $item->kaporItem->item_name ?? '';
                $autoGender = PackageItemRecipient::detectGenderFromItemName($itemName);

                foreach ($item->recipients as $recipient) {
                    $filters = $recipient->recipient_filters ?? [];

                    $query = Personnel::where('satker_id', $recipient->satker_id)
                        ->where('is_active', true);

                    if (!empty($filters['personnel_type'])) {
                        $mappedTypes = array_map(function ($t) {
                            $lower = strtolower($t);
                            if ($lower === 'polri') return 'Polri';
                            if ($lower === 'pns') return 'PNS';
                            if ($lower === 'pppk') return 'PPPK';
                            return $t;
                        }, $filters['personnel_type']);
                        $query->whereIn('personnel_type', $mappedTypes);
                    }

                    // Gender: eksplisit dari wizard ATAU auto-deteksi dari nama item
                    if (!empty($filters['gender'])) {
                        $query->whereIn('gender', $filters['gender']);
                    } elseif ($autoGender !== null) {
                        $query->where('gender', $autoGender);
                    }

                    if (!empty($filters['rank_categories'])) {
                        $query->whereHas('rank', function ($q) use ($filters) {
                            $q->whereIn('category', $filters['rank_categories']);
                        });
                    }

                    if (!empty($filters['keterangan'])) {
                        $query->whereIn('keterangan', $filters['keterangan']);
                    }

                    if (!empty($filters['golongan'])) {
                        $query->whereIn('golongan', $filters['golongan']);
                    }

                    $count = $query->count();
                    $recipient->update(['matched_count' => $count]);
                    $totalQty += $count;
                }

                $price = (float) ($item->custom_price ?? $item->kaporItem->price ?? 0);
                $item->update([
                    'calculated_qty'   => $totalQty,
                    'calculated_total' => $totalQty * $price,
                ]);
            }

            // Update total budget paket
            $budgetPackage->update([
                'total_budget' => $budgetPackage->items()->sum('calculated_total'),
            ]);
        });

        return redirect()
            ->route('admin.budget.show-package', $budgetPackage)
            ->with('success', 'Berhasil menyinkronkan jumlah penerima dengan data personel terkini.');
    }
}
