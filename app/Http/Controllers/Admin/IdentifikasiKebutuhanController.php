<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kebutuhan;
use App\Models\Satker;
use Illuminate\Http\Request;

class IdentifikasiKebutuhanController extends Controller
{
    /**
     * Semua pengajuan kebutuhan (Admin/Superadmin melihat semua satker).
     */
    public function index(Request $request)
    {
        $query = Kebutuhan::with(['satker', 'user', 'items'])
            ->orderByDesc('created_at');

        // Filter by satker
        if ($request->filled('satker_id')) {
            $query->where('satker_id', $request->satker_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhereHas('satker', fn ($sq) => $sq->where('name', 'LIKE', "%{$search}%"));
            });
        }

        $kebutuhans = $query->paginate(15)->withQueryString();

        $satkers = Satker::orderBy('name')->get();

        // Stats
        $stats = [
            'total' => Kebutuhan::count(),
            'diajukan' => Kebutuhan::where('status', 'diajukan')->count(),
            'disetujui' => Kebutuhan::where('status', 'disetujui')->count(),
            'ditolak' => Kebutuhan::where('status', 'ditolak')->count(),
        ];

        return view('admin.identifikasi-kebutuhan.index', compact('kebutuhans', 'satkers', 'stats'));
    }

    /**
     * Detail pengajuan kebutuhan.
     */
    public function show(Kebutuhan $kebutuhan)
    {
        $kebutuhan->load(['satker', 'user', 'reviewer', 'items.kaporItem']);

        return view('admin.identifikasi-kebutuhan.show', compact('kebutuhan'));
    }

    /**
     * Setujui pengajuan.
     */
    public function approve(Request $request, Kebutuhan $kebutuhan)
    {
        if (! $request->user()->hasAnyRole(['admin', 'superadmin'])) {
            abort(403, 'Hanya Admin/Superadmin yang dapat menyetujui pengajuan.');
        }

        if (! $kebutuhan->isDiajukan()) {
            return back()->with('error', 'Hanya pengajuan berstatus "Diajukan" yang dapat disetujui.');
        }

        $kebutuhan->update([
            'status' => 'disetujui',
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Pengajuan kebutuhan berhasil disetujui.');
    }

    /**
     * Tolak pengajuan.
     */
    public function reject(Request $request, Kebutuhan $kebutuhan)
    {
        if (! $request->user()->hasAnyRole(['admin', 'superadmin'])) {
            abort(403, 'Hanya Admin/Superadmin yang dapat menolak pengajuan.');
        }

        if (! $kebutuhan->isDiajukan()) {
            return back()->with('error', 'Hanya pengajuan berstatus "Diajukan" yang dapat ditolak.');
        }

        $request->validate([
            'admin_notes' => 'required|string|max:2000',
        ], [
            'admin_notes.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $kebutuhan->update([
            'status' => 'ditolak',
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Pengajuan kebutuhan telah ditolak.');
    }
}
