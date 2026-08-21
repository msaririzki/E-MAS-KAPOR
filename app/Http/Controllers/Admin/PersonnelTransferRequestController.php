<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\PersonnelImport;
use App\Models\PersonnelTransferRequest;
use App\Services\AuditLogger;
use App\Services\PersonnelConsolidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonnelTransferRequestController extends Controller
{
    public function __construct(private readonly PersonnelConsolidationService $service) {}

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true)
            ? $request->query('status')
            : 'approved';
        $requests = PersonnelTransferRequest::query()
            ->with(['personnel.rank', 'fromSatker', 'toSatker', 'requester', 'reviewer'])
            ->where('status', $status)
            ->latest()
            ->paginate(50)
            ->withQueryString();
        $counts = PersonnelTransferRequest::query()
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.personnel.transfer_requests.index', compact('requests', 'counts', 'status'));
    }

    public function review(Request $request)
    {
        $validated = $request->validate([
            'request_ids' => ['required', 'array', 'min:1'],
            'request_ids.*' => ['integer', 'exists:personnel_transfer_requests,id'],
            'action' => ['required', 'in:approve,reject'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $results = $this->processReviews(
            $validated['request_ids'],
            $validated['action'],
            $request->user()->id,
            $validated['review_note'] ?? null,
        );

        AuditLogger::log(
            'Pemeriksaan Mutasi Personel',
            'Manajemen Personil',
            null,
            null,
            $results,
            $results['errors'] === [] ? 'success' : 'warning',
        );

        $message = "{$results['approved']} mutasi disetujui dan {$results['rejected']} ditolak.";

        return back()
            ->with($results['errors'] === [] ? 'success' : 'warning', $message)
            ->with('transfer_review_errors', $results['errors']);
    }

    public function approveAllPending(Request $request)
    {
        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $requestIds = PersonnelTransferRequest::query()
            ->where('status', 'pending')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($requestIds === []) {
            return back()->with('info', 'Tidak ada pengajuan mutasi yang menunggu persetujuan.');
        }

        $results = $this->processReviews(
            $requestIds,
            'approve',
            $request->user()->id,
            $validated['review_note'] ?? null,
        );

        AuditLogger::log(
            'Setujui Semua Mutasi Personel',
            'Manajemen Personil',
            null,
            null,
            array_merge($results, ['requested_total' => count($requestIds)]),
            $results['errors'] === [] ? 'success' : 'warning',
            'Persetujuan massal seluruh pengajuan mutasi yang sedang menunggu.',
        );

        $message = "{$results['approved']} pengajuan mutasi berhasil disetujui.";

        return back()
            ->with($results['errors'] === [] ? 'success' : 'warning', $message)
            ->with('transfer_review_errors', $results['errors']);
    }

    private function processReviews(array $requestIds, string $action, int $reviewerId, ?string $reviewNote): array
    {
        $approved = 0;
        $rejected = 0;
        $errors = [];

        foreach (array_unique($requestIds) as $requestId) {
            try {
                DB::transaction(function () use ($requestId, $action, $reviewerId, $reviewNote, &$approved, &$rejected): void {
                    $transfer = PersonnelTransferRequest::query()
                        ->whereKey($requestId)
                        ->where('status', 'pending')
                        ->lockForUpdate()
                        ->first();
                    if (! $transfer) {
                        return;
                    }

                    if ($action === 'approve') {
                        $personnel = $transfer->personnel()->lockForUpdate()->firstOrFail();
                        $this->service->applyPayload($personnel, $transfer->payload, $transfer->to_satker_id);
                        $transfer->update([
                            'status' => 'approved',
                            'reviewed_by' => $reviewerId,
                            'review_note' => $reviewNote,
                            'reviewed_at' => now(),
                        ]);
                        PersonnelTransferRequest::query()
                            ->where('personnel_id', $personnel->id)
                            ->whereKeyNot($transfer->id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'superseded',
                                'reviewed_by' => $reviewerId,
                                'review_note' => 'Ditutup otomatis karena mutasi lain telah disetujui.',
                                'reviewed_at' => now(),
                            ]);
                        PersonnelImport::recalculateSatkerCount($transfer->from_satker_id);
                        PersonnelImport::recalculateSatkerCount($transfer->to_satker_id);
                        $approved++;

                        return;
                    }

                    $transfer->update([
                        'status' => 'rejected',
                        'reviewed_by' => $reviewerId,
                        'review_note' => $reviewNote,
                        'reviewed_at' => now(),
                    ]);
                    $rejected++;
                });
            } catch (\Throwable $exception) {
                $errors[] = "Permintaan #{$requestId}: {$exception->getMessage()}";
            }
        }

        return compact('approved', 'rejected', 'errors');
    }
}
