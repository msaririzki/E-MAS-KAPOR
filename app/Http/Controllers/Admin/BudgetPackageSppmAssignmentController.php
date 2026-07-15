<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetPackage;
use App\Models\BudgetPackageSppmAssignment;
use App\Models\Personnel;
use App\Models\Satker;
use App\Services\AuditLogger;
use App\Services\BudgetPackageSppmAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BudgetPackageSppmAssignmentController extends Controller
{
    public function __construct(
        private readonly BudgetPackageSppmAssignmentService $assignmentService,
    ) {}

    public function index(Request $request, BudgetPackage $budgetPackage)
    {
        $this->ensureBudgetManager();

        $budgetPackage->load('budgetYear');
        $sourceSatkers = $this->assignmentService->sourceSatkers($budgetPackage);
        $selectedSourceSatker = $this->resolveSelectedSourceSatker($request, $sourceSatkers);
        $targetSatkers = Satker::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $rows = collect();
        if ($selectedSourceSatker instanceof Satker) {
            $rows = $this->assignmentService->eligibleRows(
                $budgetPackage,
                $selectedSourceSatker,
                $request->input('search'),
            );
        }

        $assignedRows = $rows->filter(fn (array $row): bool => $row['assignment'] !== null)->values();
        $unassignedRows = $rows->filter(fn (array $row): bool => $row['assignment'] === null)->values();
        $summaryByTarget = $assignedRows
            ->groupBy(fn (array $row): int => $row['assignment']->sppm_satker_id)
            ->map(function ($items): array {
                $assignment = $items->first()['assignment'];

                return [
                    'satker' => $assignment->sppmSatker,
                    'count' => $items->count(),
                ];
            })
            ->values();

        return view('admin.budget.sppm-assignments.index', compact(
            'budgetPackage',
            'sourceSatkers',
            'selectedSourceSatker',
            'targetSatkers',
            'rows',
            'assignedRows',
            'unassignedRows',
            'summaryByTarget',
        ));
    }

    public function store(Request $request, BudgetPackage $budgetPackage)
    {
        $this->ensureBudgetManager();

        $validated = $request->validate([
            'source_satker_id' => ['required', 'exists:satkers,id'],
            'sppm_satker_id' => ['required', 'exists:satkers,id', 'different:source_satker_id'],
            'personnel_ids' => ['required', 'array', 'min:1'],
            'personnel_ids.*' => ['integer', 'exists:personnels,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $sourceSatker = Satker::findOrFail($validated['source_satker_id']);
        $eligibleIds = $this->assignmentService
            ->eligibleRows($budgetPackage, $sourceSatker)
            ->pluck('personnel_id')
            ->all();

        $selectedIds = collect($validated['personnel_ids'])
            ->map(fn ($id): int => (int) $id)
            ->intersect($eligibleIds)
            ->values();

        if ($selectedIds->isEmpty()) {
            return redirect()
                ->route('admin.budget.sppm-assignments.index', [
                    'budgetPackage' => $budgetPackage,
                    'source_satker_id' => $sourceSatker->id,
                ])
                ->with('error', 'Personel yang dipilih tidak termasuk nominatif paket dari satker asal tersebut.');
        }

        $personnels = Personnel::query()
            ->whereIn('id', $selectedIds)
            ->get(['id', 'satker_id']);

        DB::transaction(function () use ($budgetPackage, $validated, $personnels): void {
            foreach ($personnels as $personnel) {
                BudgetPackageSppmAssignment::updateOrCreate(
                    [
                        'budget_package_id' => $budgetPackage->id,
                        'personnel_id' => $personnel->id,
                    ],
                    [
                        'original_satker_id' => $personnel->satker_id,
                        'sppm_satker_id' => $validated['sppm_satker_id'],
                        'assigned_by' => auth()->id(),
                        'notes' => $validated['notes'] ?? null,
                    ],
                );
            }
        });

        AuditLogger::log('Mengatur titipan SPPM '.$selectedIds->count().' personel pada paket '.$budgetPackage->name, 'budget');

        return redirect()
            ->route('admin.budget.sppm-assignments.index', [
                'budgetPackage' => $budgetPackage,
                'source_satker_id' => $sourceSatker->id,
            ])
            ->with('success', $selectedIds->count().' personel berhasil dititipkan ke satker SPPM yang dipilih.');
    }

    public function update(Request $request, BudgetPackage $budgetPackage, BudgetPackageSppmAssignment $assignment)
    {
        $this->ensureBudgetManager();
        abort_unless($assignment->budget_package_id === $budgetPackage->id, 404);

        $validated = $request->validate([
            'sppm_satker_id' => [
                'required',
                'exists:satkers,id',
                Rule::notIn([$assignment->original_satker_id]),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $assignment->update([
            'sppm_satker_id' => $validated['sppm_satker_id'],
            'assigned_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLogger::log('Mengubah titipan SPPM personel '.$assignment->personnel_id.' pada paket '.$budgetPackage->name, 'budget');

        return redirect()
            ->route('admin.budget.sppm-assignments.index', [
                'budgetPackage' => $budgetPackage,
                'source_satker_id' => $assignment->original_satker_id,
            ])
            ->with('success', 'Satker titipan SPPM berhasil diperbarui.');
    }

    public function destroy(BudgetPackage $budgetPackage, BudgetPackageSppmAssignment $assignment)
    {
        $this->ensureBudgetManager();
        abort_unless($assignment->budget_package_id === $budgetPackage->id, 404);

        $sourceSatkerId = $assignment->original_satker_id;
        $assignment->delete();

        AuditLogger::log('Menghapus titipan SPPM personel pada paket '.$budgetPackage->name, 'budget');

        return redirect()
            ->route('admin.budget.sppm-assignments.index', [
                'budgetPackage' => $budgetPackage,
                'source_satker_id' => $sourceSatkerId,
            ])
            ->with('success', 'Titipan SPPM personel berhasil dihapus.');
    }

    private function resolveSelectedSourceSatker(Request $request, $sourceSatkers): ?Satker
    {
        if ($sourceSatkers->isEmpty()) {
            return null;
        }

        if ($request->filled('source_satker_id')) {
            return $sourceSatkers->firstWhere('id', (int) $request->input('source_satker_id')) ?? $sourceSatkers->first();
        }

        return $sourceSatkers->first(
            fn (Satker $satker): bool => str_contains(strtoupper($satker->name.' '.$satker->code), 'SISWA')
        ) ?? $sourceSatkers->first();
    }

    private function ensureBudgetManager(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['superadmin', 'admin']), 403, 'Hanya admin pengelola anggaran yang dapat mengatur titipan SPPM.');
    }
}
