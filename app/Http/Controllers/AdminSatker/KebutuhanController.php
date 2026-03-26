<?php

namespace App\Http\Controllers\AdminSatker;

use App\Http\Controllers\Controller;
use App\Models\KaporItem;
use App\Models\Kebutuhan;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KebutuhanController extends Controller
{
    /**
     * Daftar pengajuan milik satker sendiri.
     */
    public function index(Request $request)
    {
        $satkerId = $request->user()->satker_id;

        $query = Kebutuhan::with(['items'])
            ->where('satker_id', $satkerId)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $kebutuhans = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Kebutuhan::where('satker_id', $satkerId)->count(),
            'draft' => Kebutuhan::where('satker_id', $satkerId)->where('status', 'draft')->count(),
            'diajukan' => Kebutuhan::where('satker_id', $satkerId)->where('status', 'diajukan')->count(),
            'disetujui' => Kebutuhan::where('satker_id', $satkerId)->where('status', 'disetujui')->count(),
            'ditolak' => Kebutuhan::where('satker_id', $satkerId)->where('status', 'ditolak')->count(),
        ];

        return view('admin-satker.kebutuhan.index', compact('kebutuhans', 'stats'));
    }

    /**
     * Form buat pengajuan baru — menampilkan semua item kapor sebagai card selectable.
     */
    public function create()
    {
        $kaporItems = KaporItem::where('is_active', true)
            ->orderBy('category')
            ->orderBy('item_name')
            ->get()
            ->groupBy('category');

        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        return view('admin-satker.kebutuhan.create', compact('kaporItems', 'fiscalYear'));
    }

    /**
     * Simpan pengajuan baru — hanya item IDs (tanpa quantity).
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*' => 'exists:kapor_items,id',
        ], [
            'items.required' => 'Minimal pilih 1 item kebutuhan.',
            'items.min' => 'Minimal pilih 1 item kebutuhan.',
        ]);

        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        $kebutuhan = DB::transaction(function () use ($request, $fiscalYear) {
            $kebutuhan = Kebutuhan::create([
                'satker_id' => $request->user()->satker_id,
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'fiscal_year' => $fiscalYear,
                'status' => 'diajukan',
                'notes' => $request->notes,
                'submitted_at' => now(),
            ]);

            foreach ($request->items as $kaporItemId) {
                $kebutuhan->items()->create([
                    'kapor_item_id' => $kaporItemId,
                    'quantity' => 1,
                ]);
            }

            return $kebutuhan;
        });

        return redirect()->route('admin-satker.kebutuhan.show', $kebutuhan)
            ->with('success', 'Pengajuan kebutuhan berhasil dikirim!');
    }

    /**
     * Detail pengajuan.
     */
    public function show(Kebutuhan $kebutuhan)
    {
        $this->authorizeSatker($kebutuhan);

        $kebutuhan->load(['satker', 'user', 'reviewer', 'items.kaporItem']);

        return view('admin-satker.kebutuhan.show', compact('kebutuhan'));
    }

    /**
     * Form edit pengajuan (draft only) — card selectable.
     */
    public function edit(Kebutuhan $kebutuhan)
    {
        $this->authorizeSatker($kebutuhan);

        if (! $kebutuhan->isDraft()) {
            return back()->with('error', 'Hanya pengajuan berstatus draft yang dapat diedit.');
        }

        $kebutuhan->load('items');

        $kaporItems = KaporItem::where('is_active', true)
            ->orderBy('category')
            ->orderBy('item_name')
            ->get()
            ->groupBy('category');

        $selectedIds = $kebutuhan->items->pluck('kapor_item_id')->toArray();

        return view('admin-satker.kebutuhan.edit', compact('kebutuhan', 'kaporItems', 'selectedIds'));
    }

    /**
     * Update pengajuan (draft only) — hanya item IDs.
     */
    public function update(Request $request, Kebutuhan $kebutuhan)
    {
        $this->authorizeSatker($kebutuhan);

        if (! $kebutuhan->isDraft()) {
            return back()->with('error', 'Hanya pengajuan berstatus draft yang dapat diedit.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*' => 'exists:kapor_items,id',
        ]);

        DB::transaction(function () use ($request, $kebutuhan) {
            $kebutuhan->update([
                'title' => $request->title,
                'notes' => $request->notes,
            ]);

            // Replace items
            $kebutuhan->items()->delete();
            foreach ($request->items as $kaporItemId) {
                $kebutuhan->items()->create([
                    'kapor_item_id' => $kaporItemId,
                    'quantity' => 1,
                ]);
            }
        });

        return redirect()->route('admin-satker.kebutuhan.show', $kebutuhan)
            ->with('success', 'Pengajuan kebutuhan berhasil diperbarui.');
    }

    /**
     * Hapus pengajuan (draft only).
     */
    public function destroy(Kebutuhan $kebutuhan)
    {
        $this->authorizeSatker($kebutuhan);

        if (! $kebutuhan->isDraft()) {
            return back()->with('error', 'Hanya pengajuan berstatus draft yang dapat dihapus.');
        }

        $kebutuhan->delete();

        return redirect()->route('admin-satker.kebutuhan.index')
            ->with('success', 'Pengajuan kebutuhan berhasil dihapus.');
    }

    /**
     * Kirim pengajuan (draft → diajukan).
     */
    public function submit(Kebutuhan $kebutuhan)
    {
        $this->authorizeSatker($kebutuhan);

        if (! $kebutuhan->isDraft()) {
            return back()->with('error', 'Hanya pengajuan berstatus draft yang dapat dikirim.');
        }

        if ($kebutuhan->items()->count() === 0) {
            return back()->with('error', 'Tidak bisa mengirim pengajuan tanpa item.');
        }

        $kebutuhan->update([
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan kebutuhan berhasil dikirim untuk direview.');
    }

    /**
     * Cetak / Print halaman pengajuan kebutuhan (render view khusus print).
     */
    public function printPdf(Kebutuhan $kebutuhan)
    {
        $this->authorizeSatker($kebutuhan);

        $kebutuhan->load(['satker', 'user', 'reviewer', 'items.kaporItem']);

        return view('admin-satker.kebutuhan.print', compact('kebutuhan'));
    }

    /**
     * Pastikan kebutuhan milik satker user saat ini.
     */
    private function authorizeSatker(Kebutuhan $kebutuhan): void
    {
        if ($kebutuhan->satker_id !== auth()->user()->satker_id) {
            abort(403, 'Anda tidak memiliki akses ke pengajuan ini.');
        }
    }
}
