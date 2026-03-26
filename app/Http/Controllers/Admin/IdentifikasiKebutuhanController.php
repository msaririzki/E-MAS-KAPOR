<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KaporItem;
use App\Models\Kebutuhan;
use App\Models\KebutuhanItem;
use App\Models\Satker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // ── Item Popularity Statistics ─────────────────────────────
        $totalKebutuhans = Kebutuhan::whereIn('status', ['diajukan', 'disetujui'])->count();

        $itemStats = collect();
        $categoryStats = collect();
        if ($totalKebutuhans > 0) {
            // Top 10 most requested items
            $itemStats = KebutuhanItem::query()
                ->select('kapor_item_id', DB::raw('COUNT(DISTINCT kebutuhan_id) as submission_count'))
                ->whereHas('kebutuhan', fn ($q) => $q->whereIn('status', ['diajukan', 'disetujui']))
                ->groupBy('kapor_item_id')
                ->orderByDesc('submission_count')
                ->limit(10)
                ->get()
                ->map(function ($row) use ($totalKebutuhans) {
                    $kaporItem = KaporItem::find($row->kapor_item_id);

                    return [
                        'item_name' => $kaporItem->item_name ?? '-',
                        'category' => $kaporItem->category ?? '-',
                        'submission_count' => $row->submission_count,
                        'percentage' => round(($row->submission_count / $totalKebutuhans) * 100, 1),
                    ];
                });

            // Category summary: how many unique items per category were requested
            $categoryStats = KebutuhanItem::query()
                ->join('kapor_items', 'kebutuhan_items.kapor_item_id', '=', 'kapor_items.id')
                ->join('kebutuhans', 'kebutuhan_items.kebutuhan_id', '=', 'kebutuhans.id')
                ->whereIn('kebutuhans.status', ['diajukan', 'disetujui'])
                ->select('kapor_items.category', DB::raw('COUNT(DISTINCT kebutuhan_items.kapor_item_id) as unique_items'), DB::raw('COUNT(kebutuhan_items.id) as total_requests'))
                ->groupBy('kapor_items.category')
                ->get()
                ->map(function ($row) {
                    $totalInCategory = KaporItem::where('category', $row->category)->count();
                    return [
                        'category' => $row->category,
                        'unique_items' => $row->unique_items,
                        'total_in_category' => $totalInCategory,
                        'total_requests' => $row->total_requests,
                        'coverage' => $totalInCategory > 0 ? round(($row->unique_items / $totalInCategory) * 100) : 0,
                    ];
                });
        }

        return view('admin.identifikasi-kebutuhan.index', compact(
            'kebutuhans',
            'satkers',
            'stats',
            'itemStats',
            'categoryStats',
            'totalKebutuhans',
        ));
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
