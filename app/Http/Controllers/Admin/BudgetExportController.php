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
                        if ($lower === 'polri') {
                            return 'Polri';
                        }
                        if ($lower === 'pns') {
                            return 'PNS';
                        }
                        if ($lower === 'pppk') {
                            return 'PPPK';
                        }

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
        $duplicatedIds = array_keys(array_filter($personnelItemMap, fn ($items) => count($items) >= 2));

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
     * Export rekapan sebagai PDF
     */
    public function exportRecapPdf(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load([
            'budgetYear',
            'items.kaporItem.sizes',
            'items.recipients.satker',
        ]);

        $settings = \App\Models\InvoiceSetting::getSettings();
        $recapExport = new \App\Exports\PackageRecapExport($budgetPackage);

        // Ukuran celana standar (sama dengan PackageRecapExport)
        $celanaPriaSizes = [
            '27', '28', '29', '30', '31', '32', '33', '34', '35',
            '36', '37', '38', '39', '40', '41', '42', '43', '44',
            '45', '46', '47', '48', '49', '50',
        ];
        $celanaWanitaSizes = ['K', 'SD', 'B', 'EB', 'EEB', 'EEEB', 'EEEEB'];

        // Helper: apakah item STEL (perlu sheet celana)
        $needsCelana = function ($packageItem) {
            $unit = strtoupper($packageItem->kaporItem->unit ?? '');
            $name = strtoupper($packageItem->kaporItem->item_name);
            $isStel = $unit === 'STEL';
            $isAlreadyCelana = str_contains($name, 'CELANA') || str_contains($name, 'ROK');
            $isNonClothing = str_contains($name, 'TOPI') || str_contains($name, 'SEPATU')
                || str_contains($name, 'JILBAB') || str_contains($name, 'SABUK')
                || str_contains($name, 'BARET') || str_contains($name, 'PECI')
                || str_contains($name, 'PET');

            return $isStel && ! $isAlreadyCelana && ! $isNonClothing;
        };

        // Helper: apakah item olahraga (combined gender)
        $isOlahraga = function ($packageItem) {
            $name = strtoupper($packageItem->kaporItem->item_name);

            return str_contains($name, 'OLAHRAGA') || str_contains($name, 'T-SHIRT') || str_contains($name, 'T SHIRT');
        };

        // Helper: tentukan sizeKey
        $getSizeKey = function ($packageItem, ?string $sizeKeyOverride) {
            if ($sizeKeyOverride !== null) {
                return $sizeKeyOverride;
            }
            $name = strtoupper($packageItem->kaporItem->item_name);
            if (str_contains($name, 'TOPI') || str_contains($name, 'PET') || str_contains($name, 'BARET') || str_contains($name, 'PECI')) {
                return 'topi';
            }
            if (str_contains($name, 'JILBAB')) {
                return 'jilbab';
            }
            if (str_contains($name, 'CELANA') || str_contains($name, 'ROK')) {
                return 'celana';
            }
            if (str_contains($name, 'SEPATU OLAHRAGA')) {
                return 'sepatu_olahraga';
            }
            if (str_contains($name, 'SEPATU')) {
                return 'sepatu_dinas';
            }
            if (str_contains($name, 'JAKET')) {
                return 'jaket';
            }
            if (str_contains($name, 'OLAHRAGA')) {
                return 'olahraga';
            }
            if (str_contains($name, 'SABUK')) {
                return 'sabuk';
            }

            return 'kemeja';
        };

        // Helper: build matrix normal (satu gender)
        $buildMatrix = function ($packageItem, string $sizeKey, array $availableSizes, ?string $genderFilter) {
            $matrix = [];
            $totalPerSize = array_fill_keys($availableSizes, 0);
            $grandTotal = 0;

            foreach ($packageItem->recipients as $recipient) {
                $filters = $recipient->recipient_filters ?? [];
                $satker = $recipient->satker;

                $query = \App\Models\Personnel::where('satker_id', $satker->id)->where('is_active', true);
                if ($genderFilter !== null) {
                    $query->where('gender', $genderFilter);
                }
                if (! empty($filters['personnel_type'])) {
                    $mappedTypes = array_map(fn ($t) => match (strtolower($t)) {
                        'polri' => 'Polri', 'pns' => 'PNS', 'pppk' => 'PPPK', default => $t
                    }, $filters['personnel_type']);
                    $query->whereIn('personnel_type', $mappedTypes);
                }
                if (! empty($filters['gender'])) {
                    $query->whereIn('gender', $filters['gender']);
                }
                if (! empty($filters['rank_categories'])) {
                    $query->whereHas('rank', fn ($q) => $q->whereIn('category', $filters['rank_categories']));
                }
                if (! empty($filters['keterangan'])) {
                    $query->whereIn('keterangan', $filters['keterangan']);
                }
                if (! empty($filters['golongan'])) {
                    $query->whereIn('golongan', $filters['golongan']);
                }

                $personnels = $query->get(['kapor_sizes']);
                $row = ['satker_name' => $satker->name, 'sizes' => array_fill_keys($availableSizes, 0), 'row_total' => 0];

                foreach ($personnels as $p) {
                    $sizes = is_string($p->kapor_sizes) ? json_decode($p->kapor_sizes, true) : $p->kapor_sizes;
                    $sizeVal = (string) ($sizes[$sizeKey] ?? null);
                    if (! empty($sizeVal) && $sizeVal !== '-' && $sizeVal !== 'null' && in_array($sizeVal, $availableSizes)) {
                        $row['sizes'][$sizeVal]++;
                        $totalPerSize[$sizeVal]++;
                    }
                    $row['row_total']++;
                    $grandTotal++;
                }
                if ($row['row_total'] > 0) {
                    $matrix[] = $row;
                }
            }

            return compact('matrix', 'totalPerSize', 'grandTotal');
        };

        // Helper: build matrix combined (pria+wanita per baris)
        $buildCombined = function ($packageItem, string $sizeKey, array $availableSizes) {
            $matrix = [];
            $totalPerSizePria = array_fill_keys($availableSizes, 0);
            $totalPerSizeWanita = array_fill_keys($availableSizes, 0);
            $grandTotalPria = 0;
            $grandTotalWanita = 0;

            foreach ($packageItem->recipients as $recipient) {
                $filters = $recipient->recipient_filters ?? [];
                $satker = $recipient->satker;

                $query = \App\Models\Personnel::where('satker_id', $satker->id)->where('is_active', true);
                if (! empty($filters['personnel_type'])) {
                    $mappedTypes = array_map(fn ($t) => match (strtolower($t)) {
                        'polri' => 'Polri', 'pns' => 'PNS', 'pppk' => 'PPPK', default => $t
                    }, $filters['personnel_type']);
                    $query->whereIn('personnel_type', $mappedTypes);
                }
                if (! empty($filters['gender'])) {
                    $query->whereIn('gender', $filters['gender']);
                }
                if (! empty($filters['rank_categories'])) {
                    $query->whereHas('rank', fn ($q) => $q->whereIn('category', $filters['rank_categories']));
                }
                if (! empty($filters['keterangan'])) {
                    $query->whereIn('keterangan', $filters['keterangan']);
                }
                if (! empty($filters['golongan'])) {
                    $query->whereIn('golongan', $filters['golongan']);
                }

                $personnels = $query->get(['gender', 'kapor_sizes']);
                $row = [
                    'satker_name' => $satker->name,
                    'sizes_pria' => array_fill_keys($availableSizes, 0),
                    'total_pria' => 0,
                    'sizes_wanita' => array_fill_keys($availableSizes, 0),
                    'total_wanita' => 0,
                ];

                foreach ($personnels as $p) {
                    $sizes = is_string($p->kapor_sizes) ? json_decode($p->kapor_sizes, true) : $p->kapor_sizes;
                    $sizeVal = (string) ($sizes[$sizeKey] ?? null);
                    if (! empty($sizeVal) && $sizeVal !== '-' && $sizeVal !== 'null' && in_array($sizeVal, $availableSizes)) {
                        if ($p->gender === 'L') {
                            $row['sizes_pria'][$sizeVal]++;
                            $totalPerSizePria[$sizeVal]++;
                            $row['total_pria']++;
                            $grandTotalPria++;
                        } elseif ($p->gender === 'P') {
                            $row['sizes_wanita'][$sizeVal]++;
                            $totalPerSizeWanita[$sizeVal]++;
                            $row['total_wanita']++;
                            $grandTotalWanita++;
                        }
                    }
                }
                if ($row['total_pria'] > 0 || $row['total_wanita'] > 0) {
                    $matrix[] = $row;
                }
            }

            return compact('matrix', 'totalPerSizePria', 'totalPerSizeWanita', 'grandTotalPria', 'grandTotalWanita');
        };

        // Helper: dapatkan availableSizes dari KaporItem
        $getAvailableSizes = function ($kaporItem, ?string $gender, ?array $overrideSizes) {
            if ($overrideSizes !== null) {
                return $overrideSizes;
            }
            $sizesQuery = $kaporItem->sizes()->orderBy('sort_order');
            if ($gender !== null) {
                $sizesQuery->where(fn ($q) => $q->where('gender', $gender)->orWhereNull('gender'));
            } else {
                $sizesQuery->where(fn ($q) => $q->where('gender', 'L')->orWhereNull('gender'));
            }
            $result = $sizesQuery->pluck('size_label')->toArray();

            return empty($result) ? ['-'] : $result;
        };

        // ── Bangun semua halaman PDF ──
        $pages = [];

        foreach ($budgetPackage->items as $packageItem) {
            $kaporItem = $packageItem->kaporItem;
            $itemName = $kaporItem->item_name;

            // Kumpulkan gender yang ada
            $gendersInItem = [];
            foreach ($packageItem->recipients as $recipient) {
                $filterGenders = ($recipient->recipient_filters ?? [])['gender'] ?? [];
                if (empty($filterGenders)) {
                    $gendersInItem['L'] = true;
                    $gendersInItem['P'] = true;
                } else {
                    foreach ($filterGenders as $g) {
                        $gendersInItem[$g] = true;
                    }
                }
            }
            if (empty($gendersInItem)) {
                $gendersInItem = ['L' => true, 'P' => true];
            }

            $hasCelana = $needsCelana($packageItem);
            $combineGender = $isOlahraga($packageItem) && isset($gendersInItem['L']) && isset($gendersInItem['P']);

            // ── OLAHRAGA (combined) ──
            if ($combineGender) {
                $sizeKey = $getSizeKey($packageItem, null);
                $availableSizes = $getAvailableSizes($kaporItem, null, null);
                $data = $buildCombined($packageItem, $sizeKey, $availableSizes);
                $pages[] = array_merge($data, [
                    'mode' => 'combined',
                    'item_name' => $itemName,
                    'gender_label' => null,
                    'size_label' => null,
                    'available_sizes' => $availableSizes,
                ]);

                continue;
            }

            // ── NORMAL (per gender) ──
            foreach (['L', 'P'] as $g) {
                if (! isset($gendersInItem[$g])) {
                    continue;
                }
                $genderLabel = $g === 'L' ? 'PRIA' : 'WANITA';
                $sizeKey = $getSizeKey($packageItem, null);
                $availableSizes = $getAvailableSizes($kaporItem, $g, null);
                $data = $buildMatrix($packageItem, $sizeKey, $availableSizes, $g);
                $pages[] = array_merge($data, [
                    'mode' => 'normal',
                    'item_name' => $itemName,
                    'gender_label' => $hasCelana ? 'BAJU '.$genderLabel : $genderLabel,
                    'size_label' => $hasCelana ? 'Ukuran Baju' : null,
                    'available_sizes' => $availableSizes,
                ]);

                // ── CELANA (companion STEL) ──
                if ($hasCelana) {
                    $overrideSizes = $g === 'L' ? $celanaPriaSizes : $celanaWanitaSizes;
                    $celanaAvailable = $overrideSizes;
                    $celanaData = $buildMatrix($packageItem, 'celana', $celanaAvailable, $g);
                    $pages[] = array_merge($celanaData, [
                        'mode' => 'normal',
                        'item_name' => $itemName,
                        'gender_label' => 'CELANA '.$genderLabel,
                        'size_label' => 'Ukuran Celana',
                        'available_sizes' => $celanaAvailable,
                    ]);
                }
            }
        }

        set_time_limit(120);
        ini_set('memory_limit', '512M');

        // ── Auto-detect orientasi per halaman ──
        foreach ($pages as &$p) {
            $p['is_landscape'] = $p['mode'] === 'combined' || count($p['available_sizes']) > 15;
        }
        unset($p);

        // Setup dasar dokumen mPDF bergantung ke halaman pertama
        $firstPageLandscape = ! empty($pages[0]['is_landscape']) && $pages[0]['is_landscape'];
        $defaultOrientation = $firstPageLandscape ? 'L' : 'P';
        $defaultMarginTop = $firstPageLandscape ? 20 : 25;
        $defaultMarginLeft = $firstPageLandscape ? 20 : 25;

        // Gunakan mPDF dengan properti margin awal
        $pdf = \Mccarlosen\LaravelMpdf\Facades\LaravelMpdf::loadView('admin.exports.recap_pdf', [
            'pages' => $pages,
            'budgetPackage' => $budgetPackage,
            'settings' => $settings,
        ], [], [
            'format' => 'A4',
            'orientation' => $defaultOrientation,
            'margin_left' => $defaultMarginLeft,
            'margin_right' => 20,
            'margin_top' => $defaultMarginTop,
            'margin_bottom' => 20,
            'margin_header' => 0,
            'margin_footer' => 0,
            'default_font' => 'DejaVu Sans',
        ]);

        $filename = 'Rekapan_'.str_replace(' ', '_', $budgetPackage->name).'_'.$budgetPackage->budgetYear->year.'.pdf';

        return $pdf->download($filename);
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
