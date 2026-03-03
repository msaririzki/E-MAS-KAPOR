<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetPackage;
use App\Models\InvoiceSetting;
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
     * Preview Rekapan di browser
     */
    public function previewRecap(BudgetPackage $budgetPackage)
    {
        $data = $this->calcService->calculatePackage($budgetPackage);
        $budgetPackage->load('budgetYear');

        return view('admin.budget.recap', array_merge($data, [
            'budgetPackage' => $budgetPackage,
        ]));
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
        $settings->update(array_filter($validated, fn($v) => $v !== null));

        return redirect()->back()->with('success', 'Pengaturan invoice berhasil disimpan');
    }

    /**
     * Export rekapan sebagai Excel
     */
    public function exportRecapExcel(BudgetPackage $budgetPackage)
    {
        $data = $this->calcService->calculatePackage($budgetPackage);
        $budgetPackage->load('budgetYear');

        // Generate simple CSV/Excel style
        $filename = 'Rekapan_' . str_replace(' ', '_', $budgetPackage->name) . '_' . $budgetPackage->budgetYear->year . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['NO', 'NAMA BARANG', 'KATEGORI', 'SATUAN', 'HARGA SATUAN', 'VOLUME', 'JUMLAH HARGA']);

            $no = 1;
            foreach ($data['items'] as $item) {
                fputcsv($file, [
                    $no++,
                    $item['item_name'],
                    $item['category'],
                    $item['unit'],
                    $item['price'],
                    $item['qty'],
                    $item['total'],
                ]);
            }

            fputcsv($file, ['', '', '', '', 'GRAND TOTAL', $data['grand_qty'], $data['grand_total']]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
