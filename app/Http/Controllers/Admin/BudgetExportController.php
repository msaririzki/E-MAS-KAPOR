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
