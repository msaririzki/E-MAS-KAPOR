<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PersonnelConsolidationExport;
use App\Http\Controllers\Controller;
use App\Models\Satker;
use App\Services\AuditLogger;
use App\Services\PersonnelConsolidationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class PersonnelConsolidationController extends Controller
{
    private const SESSION_KEY = 'personnel_consolidation_preview';

    public function __construct(private readonly PersonnelConsolidationService $service) {}

    public function index(Request $request)
    {
        $this->authorizeRole($request);
        $satkers = Satker::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $selectedSatker = $this->resolveSatker($request, false);

        return view('admin.personnel.consolidation.index', compact('satkers', 'selectedSatker'));
    }

    public function download(Request $request)
    {
        $this->authorizeRole($request);
        $satker = $this->resolveSatker($request);
        $this->service->ensureSyncTokens($satker->id);
        $safeName = preg_replace('/[\\/:*?"<>|]/', '_', $satker->name);

        AuditLogger::log(
            'Unduh Konsolidasi Personel',
            'Manajemen Personil',
            null,
            null,
            null,
            'info',
            "Satker: {$satker->name}"
        );

        return Excel::download(
            new PersonnelConsolidationExport($satker),
            'Konsolidasi_Personel_'.$safeName.'_'.now()->format('Ymd').'.xlsx'
        );
    }

    public function import(Request $request)
    {
        $this->authorizeRole($request);
        $request->validate([
            'satker_id' => ['nullable', 'exists:satkers,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:51200'],
        ]);

        $satker = $this->resolveSatker($request);
        $file = $request->file('file');

        try {
            $preview = $this->service->buildPreview(
                $file->getRealPath(),
                $satker,
                $file->getClientOriginalName(),
            );

            session([self::SESSION_KEY => $preview]);

            AuditLogger::log(
                'Pratinjau Konsolidasi Personel',
                'Manajemen Personil',
                null,
                null,
                null,
                'info',
                sprintf(
                    'Satker %s: %d baris, %d masalah, %d tidak ditemukan.',
                    $satker->name,
                    $preview['stats']['total'],
                    $preview['stats']['error'],
                    $preview['stats']['missing'],
                )
            );

            return redirect()->route('admin.personnel.consolidation.preview');
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with('error', 'File belum dapat diperiksa: '.$exception->getMessage());
        }
    }

    public function preview(Request $request)
    {
        $this->authorizeRole($request);
        $preview = session(self::SESSION_KEY);
        if (! is_array($preview)) {
            return redirect()
                ->route('admin.personnel.consolidation.index')
                ->with('error', 'Pratinjau sudah tidak tersedia. Unggah kembali file konsolidasi.');
        }
        $this->assertPreviewSatkerAccess($request, $preview);

        $status = (string) $request->query('status', '');
        $allowedStatuses = ['update', 'new', 'no_change', 'transfer', 'duplicate_ignored', 'error'];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $statusPriority = [
            'update' => 0,
            'new' => 1,
            'transfer' => 2,
            'error' => 3,
            'duplicate_ignored' => 4,
            'no_change' => 5,
        ];
        $rows = collect($preview['rows'])
            ->sort(function (array $left, array $right) use ($statusPriority): int {
                $priorityComparison = ($statusPriority[$left['status']] ?? 99)
                    <=> ($statusPriority[$right['status']] ?? 99);

                return $priorityComparison !== 0
                    ? $priorityComparison
                    : (($left['row_number'] ?? 0) <=> ($right['row_number'] ?? 0));
            })
            ->values();
        if ($status !== '') {
            $rows = $rows->where('status', $status);
        }

        $paginatedRows = $this->paginate($rows->values(), $request, 50);

        return view('admin.personnel.consolidation.preview', [
            'preview' => $preview,
            'rows' => $paginatedRows,
            'statusFilter' => $status,
        ]);
    }

    public function confirm(Request $request)
    {
        $this->authorizeRole($request);
        $preview = session(self::SESSION_KEY);
        if (! is_array($preview)) {
            return redirect()
                ->route('admin.personnel.consolidation.index')
                ->with('error', 'Pratinjau sudah tidak tersedia. Unggah kembali file konsolidasi.');
        }
        $this->assertPreviewSatkerAccess($request, $preview);

        $request->validate([
            'deactivate_ids' => ['nullable', 'array'],
            'deactivate_ids.*' => ['integer'],
            'confirm_deactivation' => ['sometimes', 'accepted'],
        ]);

        $deactivateIds = $request->input('deactivate_ids', []);
        if ($deactivateIds !== [] && ! $request->boolean('confirm_deactivation')) {
            return back()->with('error', 'Centang konfirmasi penonaktifan sebelum menyimpan.');
        }

        $results = $this->service->applyPreview($preview, $deactivateIds, $request->user());
        session()->forget(self::SESSION_KEY);

        AuditLogger::log(
            'Simpan Konsolidasi Personel',
            'Manajemen Personil',
            null,
            null,
            $results,
            $results['errors'] === [] ? 'success' : 'warning',
            "Satker: {$preview['satker_name']}"
        );

        $message = sprintf(
            '%d diperbarui, %d ditambahkan, %d permintaan mutasi dibuat, dan %d dinonaktifkan.',
            $results['updated'],
            $results['created'],
            $results['transfer_pending'],
            $results['deactivated'],
        );

        return redirect()
            ->route('admin.personnel.index')
            ->with($results['errors'] === [] ? 'success' : 'warning', $message)
            ->with('consolidation_errors', $results['errors']);
    }

    public function cancel(Request $request)
    {
        $this->authorizeRole($request);
        session()->forget(self::SESSION_KEY);

        return redirect()
            ->route('admin.personnel.consolidation.index')
            ->with('info', 'Pratinjau konsolidasi dibatalkan. Tidak ada data yang diubah.');
    }

    private function resolveSatker(Request $request, bool $required = true): ?Satker
    {
        $user = $request->user();
        if ($user->hasRole('admin_satker')) {
            return Satker::findOrFail($user->satker_id);
        }

        $satkerId = $request->integer('satker_id');
        if ($satkerId <= 0) {
            if ($required) {
                abort(422, 'Pilih satker yang akan dikelola.');
            }

            return null;
        }

        return Satker::findOrFail($satkerId);
    }

    private function authorizeRole(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['superadmin', 'admin', 'admin_satker']), 403);
    }

    private function assertPreviewSatkerAccess(Request $request, array $preview): void
    {
        if ($request->user()->hasRole('admin_satker')) {
            abort_unless((int) $preview['satker_id'] === (int) $request->user()->satker_id, 403);
        }
    }

    private function paginate(Collection $items, Request $request, int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
