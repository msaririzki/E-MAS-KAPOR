<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\Personnel;
use App\Models\Setting;
use App\Services\KaporRequirementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function __construct(
        private readonly KaporRequirementService $kaporRequirementService
    ) {}

    public function index()
    {
        $years = BudgetYear::withCount('packages')
            ->orderByDesc('year')
            ->get();

        $activeFiscalYear = (int) Setting::getValue('fiscal_year', date('Y'));
        $activeBudgetYear = $years->firstWhere('is_active', true);

        return view('admin.budget.index', compact('years', 'activeFiscalYear', 'activeBudgetYear'));
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
        $yearName = $budgetYear->name;

        DB::transaction(function () use ($budgetYear) {
            foreach ($budgetYear->packages as $package) {
                foreach ($package->items as $item) {
                    $item->recipients()->delete();
                }
                $package->items()->delete();
            }
            $budgetYear->packages()->delete();
            $budgetYear->delete();
        });

        \App\Services\AuditLogger::log('Menghapus tahun anggaran: '.$yearName, 'budget');

        return redirect()->back()->with('success', 'Tahun anggaran "'.$yearName.'" beserta semua data terkait berhasil dihapus.');
    }

    public function showYear(BudgetYear $budgetYear)
    {
        $budgetYear->load(['packages' => function ($query) {
            $query->orderBy('name');
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

    public function showPackage(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load([
            'budgetYear',
            'items.kaporItem.sizes',
            'items.recipients.satker',
        ]);

        DB::transaction(function () use ($budgetPackage) {
            foreach ($budgetPackage->items as $item) {
                $totalQty = 0;

                foreach ($item->recipients as $recipient) {
                    $query = Personnel::where('satker_id', $recipient->satker_id)
                        ->where('is_active', true);

                    $this->kaporRequirementService->applyRecipientFilters(
                        $query,
                        $recipient->recipient_filters ?? [],
                        $recipient->satker
                    );

                    $count = $query->count();
                    $recipient->update(['matched_count' => $count]);
                    $totalQty += $count;
                }

                $price = (float) ($item->custom_price ?? $item->kaporItem->price ?? 0);
                $item->update([
                    'calculated_qty' => $totalQty,
                    'calculated_total' => $totalQty * $price,
                ]);
            }

            $budgetPackage->update([
                'total_budget' => $budgetPackage->items()->sum('calculated_total'),
            ]);
        });

        $budgetPackage->refresh()->load([
            'budgetYear',
            'items.kaporItem.sizes',
            'items.recipients.satker',
        ]);

        $sizeWarnings = $this->kaporRequirementService->buildPackageSizeWarnings($budgetPackage);

        return view('admin.budget.package-detail', compact('budgetPackage', 'sizeWarnings'));
    }

    public function recalculatePackage(BudgetPackage $budgetPackage)
    {
        $budgetPackage->load(['items.kaporItem', 'items.recipients.satker']);

        DB::transaction(function () use ($budgetPackage) {
            foreach ($budgetPackage->items as $item) {
                $totalQty = 0;

                foreach ($item->recipients as $recipient) {
                    $query = Personnel::where('satker_id', $recipient->satker_id)
                        ->where('is_active', true);

                    $this->kaporRequirementService->applyRecipientFilters(
                        $query,
                        $recipient->recipient_filters ?? [],
                        $recipient->satker
                    );

                    $count = $query->count();
                    $recipient->update(['matched_count' => $count]);
                    $totalQty += $count;
                }

                $price = (float) ($item->custom_price ?? $item->kaporItem->price ?? 0);
                $item->update([
                    'calculated_qty' => $totalQty,
                    'calculated_total' => $totalQty * $price,
                ]);
            }

            $budgetPackage->update([
                'total_budget' => $budgetPackage->items()->sum('calculated_total'),
            ]);
        });

        return redirect()
            ->route('admin.budget.show-package', $budgetPackage)
            ->with('success', 'Berhasil menyinkronkan jumlah penerima dengan data personel terkini.');
    }
}
