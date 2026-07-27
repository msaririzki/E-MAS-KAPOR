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
            : 'pending';
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

        $approved = 0;
        $rejected = 0;
        $errors = [];

        foreach (array_unique($validated['request_ids']) as $requestId) {
            try {
                DB::transaction(function () use ($requestId, $validated, $request, &$approved, &$rejected): void {
                    $transfer = PersonnelTransferRequest::query()
                        ->whereKey($requestId)
                        ->where('status', 'pending')
                        ->lockForUpdate()
                        ->first();
                    if (! $transfer) {
                        return;
                    }

                    if ($validated['action'] === 'approve') {
                        $personnel = $transfer->personnel()->lockForUpdate()->firstOrFail();
                        $this->service->applyPayload($personnel, $transfer->payload, $transfer->to_satker_id);
                        $transfer->update([
                            'status' => 'approved',
                            'reviewed_by' => $request->user()->id,
                            'review_note' => $validated['review_note'] ?? null,
                            'reviewed_at' => now(),
                        ]);
                        PersonnelTransferRequest::query()
                            ->where('personnel_id', $personnel->id)
                            ->whereKeyNot($transfer->id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'superseded',
                                'reviewed_by' => $request->user()->id,
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
                        'reviewed_by' => $request->user()->id,
                        'review_note' => $validated['review_note'] ?? null,
                        'reviewed_at' => now(),
                    ]);
                    $rejected++;
                });
            } catch (\Throwable $exception) {
                $errors[] = "Permintaan #{$requestId}: {$exception->getMessage()}";
            }
        }

        AuditLogger::log(
            'Pemeriksaan Mutasi Personel',
            'Manajemen Personil',
            null,
            null,
            ['approved' => $approved, 'rejected' => $rejected, 'errors' => $errors],
            $errors === [] ? 'success' : 'warning',
        );

        $message = "{$approved} mutasi disetujui dan {$rejected} ditolak.";

        return back()
            ->with($errors === [] ? 'success' : 'warning', $message)
            ->with('transfer_review_errors', $errors);
    }
}
