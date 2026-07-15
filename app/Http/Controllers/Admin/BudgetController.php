<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\Personnel;
use App\Models\PersonnelItemAllocation;
use App\Models\Setting;
use App\Services\KaporRequirementService;
use App\Services\PersonnelItemAllocationSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function __construct(
        private readonly KaporRequirementService $kaporRequirementService, private readonly PersonnelItemAllocationSnapshotService $personnelItemAllocationSnapshotService
    ) {}

    public function index()
    {
        $years = BudgetYear::withCount('packages')
            ->orderByDesc('year')
            ->get();

        $activeFiscalYear = $this->activeFiscalYear();
        $activeBudgetYear = $years->firstWhere('is_active', true);

        return view('admin.budget.index', compact('years', 'activeFiscalYear', 'activeBudgetYear'));
    }

    public function storeYear(Request $request)
    {
        $this->ensureBudgetManager();

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
        $this->ensureBudgetManager();

        if ($budgetYear->year < $this->activeFiscalYear()) {
            return redirect()->back()->with('error', 'Tahun anggaran yang sudah lewat hanya bisa dilihat sebagai arsip.');
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2050|unique:budget_years,year,'.$budgetYear->id,
            'name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $activeFiscalYear = $this->activeFiscalYear();
        $targetYear = (int) $validated['year'];
        $isActive = (bool) ($validated['is_active'] ?? false);

        if ($isActive && $targetYear !== $activeFiscalYear) {
            return redirect()->back()->with('error', 'Budget year aktif harus sama dengan Tahun Sistem Aktif (TA '.$activeFiscalYear.').');
        }

        if ($isActive) {
            BudgetYear::query()->whereKeyNot($budgetYear->id)->update(['is_active' => false]);
        }

        $budgetYear->update($validated);

        return redirect()->back()->with('success', 'Tahun anggaran berhasil diperbarui');
    }

    public function destroyYear(BudgetYear $budgetYear)
    {
        $this->ensureBudgetManager();

        if ($budgetYear->year === $this->activeFiscalYear()) {
            return redirect()->back()->with('error', 'Tahun sistem aktif tidak dapat dihapus dari modul budget.');
        }

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

        $activeFiscalYear = $this->activeFiscalYear();

        return view('admin.budget.packages', compact('budgetYear', 'activeFiscalYear'));
    }

    public function storePackage(Request $request, BudgetYear $budgetYear)
    {
        $this->ensureBudgetManager();
        $this->ensureBudgetYearProcessable($budgetYear);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $budgetYear->packages()->create($validated);

        return redirect()->back()->with('success', 'Paket berhasil ditambahkan');
    }

    public function updatePackage(Request $request, BudgetPackage $budgetPackage)
    {
        $this->ensureBudgetManager();
        $budgetPackage->loadMissing('budgetYear');
        $this->ensureBudgetYearProcessable($budgetPackage->budgetYear);

        $previousStatus = $budgetPackage->status;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,finalized,archived',
        ]);

        DB::transaction(function () use ($budgetPackage, $validated, $previousStatus): void {
            $budgetPackage->update($validated);

            if ($budgetPackage->status === 'finalized') {
                $this->syncPackageCalculatedFields($budgetPackage);
                $this->personnelItemAllocationSnapshotService->regenerateForBudgetPackage($budgetPackage->fresh());

                return;
            }

            if ($previousStatus === 'finalized' && $budgetPackage->status !== 'finalized') {
                PersonnelItemAllocation::query()
                    ->where('budget_package_id', $budgetPackage->id)
                    ->delete();
            }
        });

        return redirect()->back()->with('success', $budgetPackage->status === 'finalized'
            ? 'Paket berhasil difinalkan dan snapshot alokasi review item telah diperbarui.'
            : 'Paket berhasil diperbarui');
    }

    public function destroyPackage(BudgetPackage $budgetPackage)
    {
        $this->ensureBudgetManager();
        $budgetPackage->loadMissing('budgetYear');
        $this->ensureBudgetYearProcessable($budgetPackage->budgetYear);

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
        ])->loadCount('sppmAssignments');

        if (! auth()->user()?->isReadOnlyAdmin()) {
            DB::transaction(function () use ($budgetPackage): void {
                $this->syncPackageCalculatedFields($budgetPackage);
            });

            $budgetPackage->refresh()->load([
                'budgetYear',
                'items.kaporItem.sizes',
                'items.recipients.satker',
            ])->loadCount('sppmAssignments');
        }

        $sizeWarnings = $this->kaporRequirementService->buildPackageSizeWarnings($budgetPackage);
        $activeFiscalYear = $this->activeFiscalYear();

        return view('admin.budget.package-detail', compact('budgetPackage', 'sizeWarnings', 'activeFiscalYear'));
    }

    public function recalculatePackage(BudgetPackage $budgetPackage)
    {
        $this->ensureBudgetManager();
        $budgetPackage->loadMissing('budgetYear');
        $this->ensureBudgetYearProcessable($budgetPackage->budgetYear);

        $budgetPackage->load(['items.kaporItem', 'items.recipients.satker']);

        DB::transaction(function () use ($budgetPackage): void {
            $this->syncPackageCalculatedFields($budgetPackage);

            if ($budgetPackage->status === 'finalized') {
                $this->personnelItemAllocationSnapshotService->regenerateForBudgetPackage($budgetPackage->fresh());
            }
        });

        return redirect()
            ->route('admin.budget.show-package', $budgetPackage)
            ->with('success', 'Berhasil menyinkronkan jumlah penerima dengan data personel terkini.');
    }

    private function ensureBudgetManager(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['superadmin', 'admin']), 403, 'Hanya admin pengelola anggaran yang dapat melakukan aksi ini.');
    }

    private function ensureBudgetYearProcessable(BudgetYear $budgetYear): void
    {
        abort_if(
            (int) $budgetYear->year !== $this->activeFiscalYear(),
            403,
            'Hanya tahun sistem aktif yang dapat diubah. Tahun lain bersifat baca-saja sebagai riwayat atau persiapan.',
        );
    }

    private function activeFiscalYear(): int
    {
        return (int) Setting::getValue('fiscal_year', date('Y'));
    }

    private function syncPackageCalculatedFields(BudgetPackage $budgetPackage): void
    {
        $budgetPackage->load(['items.kaporItem', 'items.recipients.satker']);

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
    }
}
