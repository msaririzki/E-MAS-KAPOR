<?php

namespace App\Http\Controllers\AdminSatker;

use App\Http\Controllers\Controller;
use App\Models\Kebutuhan;
use App\Services\ExportSignatorySettingService;
use App\Services\KebutuhanEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        // Cek apakah sudah ada pengajuan untuk tahun anggaran berikutnya
        $nextFiscalYear = (int) date('Y') + 1;
        $hasSubmissionThisYear = Kebutuhan::where('satker_id', $satkerId)
            ->where('fiscal_year', $nextFiscalYear)
            ->exists();

        return view('admin-satker.kebutuhan.index', compact('kebutuhans', 'stats', 'hasSubmissionThisYear', 'nextFiscalYear'));
    }

    /**
     * Form buat pengajuan baru — menampilkan semua item kapor sebagai card selectable.
     */
    public function create(KebutuhanEligibilityService $eligibilityService)
    {
        // Cek apakah satker sudah mengajukan untuk tahun anggaran ini
        $nextFiscalYear = (int) date('Y') + 1;
        $existing = Kebutuhan::where('satker_id', auth()->user()->satker_id)
            ->where('fiscal_year', $nextFiscalYear)
            ->exists();

        if ($existing) {
            return redirect()->route('admin-satker.kebutuhan.index')
                ->with('error', 'Satker Anda sudah memiliki pengajuan untuk TA '.$nextFiscalYear.'. Hanya diperbolehkan 1 pengajuan per tahun anggaran.');
        }

        $kaporItems = $eligibilityService
            ->eligibleItemsForSatker(auth()->user()->satker)
            ->groupBy('category');

        return view('admin-satker.kebutuhan.create', compact('kaporItems'));
    }

    /**
     * Simpan pengajuan baru — hanya item IDs (tanpa quantity).
     */
    public function store(Request $request, KebutuhanEligibilityService $eligibilityService)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*' => 'exists:identifikasi_items,id',
        ], [
            'items.required' => 'Minimal pilih 1 item kebutuhan.',
            'items.min' => 'Minimal pilih 1 item kebutuhan.',
        ]);

        $this->validateEligibleItems($request, $eligibilityService);

        // Tahun anggaran otomatis = tahun sekarang + 1
        $fiscalYear = (int) date('Y') + 1;

        // Cek duplikasi: 1 satker hanya boleh 1 pengajuan per tahun anggaran
        $existing = Kebutuhan::where('satker_id', $request->user()->satker_id)
            ->where('fiscal_year', $fiscalYear)
            ->exists();

        if ($existing) {
            return redirect()->route('admin-satker.kebutuhan.index')
                ->with('error', 'Satker Anda sudah memiliki pengajuan untuk TA '.$fiscalYear.'. Hanya diperbolehkan 1 pengajuan per tahun anggaran.');
        }

        $kebutuhan = DB::transaction(function () use ($request, $fiscalYear) {
            $kebutuhan = Kebutuhan::create([
                'satker_id' => $request->user()->satker_id,
                'user_id' => $request->user()->id,
                'title' => 'Pengajuan Kebutuhan TA '.$fiscalYear,
                'fiscal_year' => $fiscalYear,
                'status' => 'diajukan',
                'notes' => null,
                'submitted_at' => now(),
            ]);

            foreach ($request->items as $kaporItemId) {
                $kebutuhan->items()->create([
                    'identifikasi_item_id' => $kaporItemId,
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

        $kebutuhan->load(['satker', 'user', 'reviewer', 'items.identifikasiItem']);

        return view('admin-satker.kebutuhan.show', compact('kebutuhan'));
    }

    /**
     * Form edit pengajuan (draft only) — card selectable.
     */
    public function edit(Kebutuhan $kebutuhan, KebutuhanEligibilityService $eligibilityService)
    {
        $this->authorizeSatker($kebutuhan);

        if (in_array($kebutuhan->status, ['disetujui', 'ditolak'])) {
            return back()->with('error', 'Pengajuan yang sudah diproses tidak dapat diedit.');
        }

        $kebutuhan->load('items');

        $kaporItems = $eligibilityService
            ->eligibleItemsForSatker(auth()->user()->satker)
            ->groupBy('category');

        $selectedIds = $kebutuhan->items->pluck('identifikasi_item_id')->toArray();

        return view('admin-satker.kebutuhan.edit', compact('kebutuhan', 'kaporItems', 'selectedIds'));
    }

    /**
     * Update pengajuan (draft only) — hanya item IDs.
     */
    public function update(Request $request, Kebutuhan $kebutuhan, KebutuhanEligibilityService $eligibilityService)
    {
        $this->authorizeSatker($kebutuhan);

        if (in_array($kebutuhan->status, ['disetujui', 'ditolak'])) {
            return back()->with('error', 'Pengajuan yang sudah diproses tidak dapat diedit.');
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*' => 'exists:identifikasi_items,id',
        ]);

        $this->validateEligibleItems($request, $eligibilityService);

        // Tahun anggaran otomatis = tahun sekarang + 1
        $fiscalYear = (int) date('Y') + 1;

        DB::transaction(function () use ($request, $kebutuhan, $fiscalYear) {
            $kebutuhan->update([
                'title' => 'Pengajuan Kebutuhan TA '.$fiscalYear,
                'fiscal_year' => $fiscalYear,
                'notes' => null,
            ]);

            // Replace items
            $kebutuhan->items()->delete();
            foreach ($request->items as $kaporItemId) {
                $kebutuhan->items()->create([
                    'identifikasi_item_id' => $kaporItemId,
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

        if (in_array($kebutuhan->status, ['disetujui', 'ditolak'])) {
            return back()->with('error', 'Pengajuan yang sudah diproses tidak dapat dihapus.');
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

        if (in_array($kebutuhan->status, ['disetujui', 'ditolak'])) {
            return back()->with('error', 'Pengajuan sudah diproses sehingga tidak bisa dikirim ulang.');
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

        $kebutuhan->load(['satker', 'user', 'reviewer', 'items.identifikasiItem']);
        $signatorySettings = app(ExportSignatorySettingService::class)->resolveForUser(auth()->user());

        return view('admin-satker.kebutuhan.print', compact('kebutuhan', 'signatorySettings'));
    }

    /**
     * Export to Excel.
     */
    public function exportExcel(Kebutuhan $kebutuhan)
    {
        $this->authorizeSatker($kebutuhan);
        $kebutuhan->load(['satker', 'user', 'reviewer', 'items.identifikasiItem']);
        $signatorySettings = app(ExportSignatorySettingService::class)->resolveForUser(auth()->user());

        $filename = 'Usulan_Kaporlap_'.($kebutuhan->satker->name ?? 'Satker').'_'.$kebutuhan->fiscal_year.'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\KebutuhanExport($kebutuhan, $signatorySettings), $filename);
    }

    /**
     * Export to PDF.
     */
    public function exportPdf(Kebutuhan $kebutuhan)
    {
        $this->authorizeSatker($kebutuhan);
        $kebutuhan->load(['satker', 'user', 'reviewer', 'items.identifikasiItem']);
        $signatorySettings = app(ExportSignatorySettingService::class)->resolveForUser(auth()->user());

        $filename = 'Usulan_Kaporlap_'.($kebutuhan->satker->name ?? 'Satker').'_'.$kebutuhan->fiscal_year.'.pdf';

        // We will reuse the 'print' view or a new 'pdf' view for actual PDF download
        $pdf = \PDF::loadView('admin-satker.kebutuhan.print', compact('kebutuhan', 'signatorySettings'));

        return $pdf->download($filename);
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

    private function validateEligibleItems(Request $request, KebutuhanEligibilityService $eligibilityService): void
    {
        $eligibleItemIds = $eligibilityService
            ->eligibleItemsForSatker($request->user()->satker)
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id);

        $invalidItemIds = collect($request->input('items', []))
            ->map(fn ($id): string => (string) $id)
            ->diff($eligibleItemIds);

        if ($invalidItemIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Terdapat item yang tidak sesuai kewenangan satker Anda.',
            ]);
        }
    }
}
