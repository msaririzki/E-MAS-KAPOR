<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use Illuminate\Http\Request;

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

        $validated['name'] = $validated['name'] ?? 'Tahun Anggaran ' . $validated['year'];

        BudgetYear::create($validated);

        return redirect()->back()->with('success', 'Tahun anggaran berhasil ditambahkan');
    }

    public function updateYear(Request $request, BudgetYear $budgetYear)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2050|unique:budget_years,year,' . $budgetYear->id,
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
        $budgetPackage->load(['budgetYear', 'items.kaporItem', 'items.recipients.satker']);

        return view('admin.budget.package-detail', compact('budgetPackage'));
    }
}
