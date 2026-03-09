<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetPackage;
use App\Models\InvoiceSetting;
use App\Models\Personnel;
use App\Services\BudgetCalculationService;
use Illuminate\Http\Request;

class BudgetExportController extends Controller
{
    protected BudgetCalculationService $calcService;

    public function __construct(BudgetCalculationService $calcService)
    {
        $this->calcService = $calcService;
    }

    /**
     * Preview Rekapan + Analisis Duplikasi Personil
     */
    public function previewRecap(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load([
            'budgetYear',
            'items.kaporItem',
            'items.recipients.satker',
        ]);

        // Hitung total anggaran
        $grandTotal = $budgetPackage->items->sum('calculated_total');
        $totalItems = $budgetPackage->items->count();
        $totalRecipients = $budgetPackage->items->sum('calculated_qty');

        // ── Analisis Duplikasi Personil ──
        $personnelItemMap = [];

        foreach ($budgetPackage->items as $item) {
            foreach ($item->recipients as $recipient) {
                $query = Personnel::where('satker_id', $recipient->satker_id)
                    ->where('is_active', true);

                $filters = $recipient->recipient_filters ?? [];

                if (! empty($filters['personnel_type'])) {
                    $mappedTypes = array_map(function ($t) {
                        $lower = strtolower($t);
                        if ($lower === 'polri') return 'Polri';
                        if ($lower === 'pns') return 'PNS';
                        if ($lower === 'pppk') return 'PPPK';
                        return $t;
                    }, $filters['personnel_type']);
                    $query->whereIn('personnel_type', $mappedTypes);
                }

                if (! empty($filters['gender'])) {
                    $query->whereIn('gender', $filters['gender']);
                }

                if (! empty($filters['rank_categories'])) {
                    $query->whereHas('rank', function ($q) use ($filters) {
                        $q->whereIn('category', $filters['rank_categories']);
                    });
                }

                if (! empty($filters['keterangan'])) {
                    $query->whereIn('keterangan', $filters['keterangan']);
                }

                if (! empty($filters['golongan'])) {
                    $query->whereIn('golongan', $filters['golongan']);
                }

                $matchedIds = $query->pluck('id')->toArray();

                // Label filter untuk display
                $filterLabels = [];
                if (! empty($filters['personnel_type'])) {
                    $filterLabels = array_merge($filterLabels, $filters['personnel_type']);
                }
                if (! empty($filters['gender'])) {
                    $genderMap = ['L' => 'Pria', 'P' => 'Wanita'];
                    foreach ($filters['gender'] as $g) {
                        $filterLabels[] = $genderMap[$g] ?? $g;
                    }
                }
                if (! empty($filters['rank_categories'])) {
                    $filterLabels = array_merge($filterLabels, $filters['rank_categories']);
                }
                if (! empty($filters['keterangan'])) {
                    $filterLabels = array_merge($filterLabels, $filters['keterangan']);
                }
                if (! empty($filters['golongan'])) {
                    $filterLabels = array_merge($filterLabels, $filters['golongan']);
                }

                $itemInfo = [
                    'item_id' => $item->id,
                    'item_name' => $item->kaporItem->item_name,
                    'category' => $item->kaporItem->category,
                    'satker_name' => $recipient->satker->name,
                    'filters' => $filterLabels,
                    'price' => $item->effective_price,
                ];

                foreach ($matchedIds as $pid) {
                    if (! isset($personnelItemMap[$pid])) {
                        $personnelItemMap[$pid] = [];
                    }
                    $exists = false;
                    foreach ($personnelItemMap[$pid] as $existing) {
                        if ($existing['item_id'] === $item->id && $existing['satker_name'] === $recipient->satker->name) {
                            $exists = true;
                            break;
                        }
                    }
                    if (! $exists) {
                        $personnelItemMap[$pid][] = $itemInfo;
                    }
                }
            }
        }

        // Filter duplikasi (>= 2 barang)
        $duplicatedIds = array_keys(array_filter($personnelItemMap, fn($items) => count($items) >= 2));

        $duplicates = collect();
        $groupedDuplicates = collect();
        if (! empty($duplicatedIds)) {
            $personnels = Personnel::with(['rank', 'satker'])
                ->whereIn('id', $duplicatedIds)
                ->get()
                ->sortBy([
                    fn ($a, $b) => ($a->satker->code === 'POLDA-NTB' ? 1 : 0) <=> ($b->satker->code === 'POLDA-NTB' ? 1 : 0),
                    fn ($a, $b) => ($a->satker->sort_order ?? 999) <=> ($b->satker->sort_order ?? 999),
                    fn ($a, $b) => strnatcasecmp($a->full_name, $b->full_name),
                ])
                ->values();

            foreach ($personnels as $person) {
                $duplicates->push([
                    'personnel' => $person,
                    'items' => $personnelItemMap[$person->id],
                    'total_items' => count($personnelItemMap[$person->id]),
                ]);
            }

            // Group by satker name
            $groupedDuplicates = $duplicates->groupBy(function ($item) {
                return $item['personnel']->satker->name ?? 'Tanpa Satker';
            });
        }

        $totalDuplicates = $duplicates->count();

        return view('admin.budget.recap', compact(
            'budgetPackage', 'grandTotal', 'totalItems', 'totalRecipients',
            'groupedDuplicates', 'totalDuplicates'
        ));
    }

    /**
     * Preview Invoice HPS di browser
     */
    public function previewInvoice(BudgetPackage $budgetPackage)
    {
        $data = $this->calcService->calculatePackage($budgetPackage);
        $settings = InvoiceSetting::getSettings();
        $budgetPackage->load('budgetYear');

        return view('admin.budget.invoice', array_merge($data, [
            'budgetPackage' => $budgetPackage,
            'settings' => $settings,
        ]));
    }

    /**
     * Update invoice settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'signatory_name' => 'nullable|string|max:255',
            'signatory_rank' => 'nullable|string|max:255',
            'signatory_nrp' => 'nullable|string|max:100',
            'signatory_title' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'header_title' => 'nullable|string|max:500',
            'work_type' => 'nullable|string|max:255',
        ]);

        $settings = InvoiceSetting::getSettings();
        $settings->update(array_filter($validated, fn ($v) => $v !== null));

        return redirect()->back()->with('success', 'Pengaturan invoice berhasil disimpan');
    }

    /**
     * Export rekapan sebagai Excel
     */
    public function exportRecapExcel(BudgetPackage $budgetPackage)
    {
        $filename = 'Rekapan_'.str_replace(' ', '_', $budgetPackage->name).'_'.$budgetPackage->budgetYear->year.'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PackageRecapExport($budgetPackage), $filename);
    }

    /**
     * Export detail penerima sebagai Excel (daftar nominatif per personel)
     */
    public function exportDetailExcel(BudgetPackage $budgetPackage)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');

        $budgetPackage->load('budgetYear');
        $filename = 'Detail_Penerima_'.str_replace(' ', '_', $budgetPackage->name).'_'.$budgetPackage->budgetYear->year.'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PackageDetailExport($budgetPackage), $filename);
    }
}
