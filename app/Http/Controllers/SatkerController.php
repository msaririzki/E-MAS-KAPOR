<?php

namespace App\Http\Controllers;

use App\Models\Satker;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\AuditLogger;

class SatkerController extends Controller
{
    /**
     * Display listing of satkers.
     */
    public function index()
    {
        $satkers = Satker::with('parent')
            ->orderBy('sort_order')
            ->get();

        $parentSatkers = Satker::whereNull('parent_id')->orderBy('sort_order')->get();

        return view('admin.satkers.index', compact('satkers', 'parentSatkers'));
    }

    /**
     * Get the redirect route based on user role.
     */
    private function getRedirectRoute()
    {
        if (auth()->user()->hasRole('superadmin')) {
            return 'superadmin.satkers.index';
        }
        return 'admin.satkers.index';
    }

    /**
     * Store a newly created satker.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'polri_count' => 'nullable|integer|min:0',
            'pns_count' => 'nullable|integer|min:0',
        ]);

        $name = $validated['name'];
        // Generate code automatically from name
        $code = Str::upper(Str::slug($name));

        // Ensure uniqueness for code
        $baseCode = $code;
        $counter = 1;
        while (Satker::where('code', $code)->exists()) {
            $code = $baseCode . '-' . $counter;
            $counter++;
        }

        $satker = Satker::create([
            'name' => $name,
            'code' => $code,
            'polri_count' => $validated['polri_count'] ?? 0,
            'pns_count' => $validated['pns_count'] ?? 0,
            'sort_order' => Satker::max('sort_order') + 1,
            'parent_id' => null, // Default to root as per form simplification
        ]);

        AuditLogger::log('Tambah Satker', 'Data Organisasi', $satker, null, $satker->toArray(), 'success', "Menambah Satker baru: {$satker->name}");

        return redirect()->route($this->getRedirectRoute())->with('success', 'Satker berhasil ditambahkan.');
    }

    /**
     * Update the specified satker.
     */
    public function update(Request $request, Satker $satker)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'polri_count' => 'nullable|integer|min:0',
            'pns_count' => 'nullable|integer|min:0',
        ]);

        // Keep existing code unless name changes significantly? 
        // For simplicity, let's keep the old code to avoid link breakage if any, 
        // but we can update it if you want. Usually slug is only generated at creation.

        $satker->update($validated);

        AuditLogger::log('Edit Satker', 'Data Organisasi', $satker, null, $satker->toArray(), 'success', "Memperbarui data Satker: {$satker->name}");

        return redirect()->route($this->getRedirectRoute())->with('success', 'Satker berhasil diperbarui.');
    }

    /**
     * Update only personnel counts (inline edit).
     */
    public function updatePersonnelCount(Request $request, Satker $satker)
    {
        $validated = $request->validate([
            'polri_count' => 'required|integer|min:0',
            'pns_count' => 'required|integer|min:0',
        ]);

        $satker->update($validated);

        return redirect()->route($this->getRedirectRoute())->with('success', "Jumlah personil {$satker->name} berhasil diperbarui.");
    }

    /**
     * Remove the specified satker.
     */
    public function destroy(Satker $satker)
    {
        // Cek jika satker memiliki child
        if ($satker->children()->count() > 0) {
            return redirect()->route($this->getRedirectRoute())->with('error', 'Tidak bisa menghapus satker yang memiliki sub-satker.');
        }

        // Cek jika satker memiliki personil terdaftar
        if ($satker->personnels()->count() > 0) {
            return redirect()->route($this->getRedirectRoute())->with('error', 'Tidak bisa menghapus satker yang memiliki personil.');
        }

        $satkerName = $satker->name;
        $satker->delete();

        AuditLogger::log('Hapus Satker', 'Data Organisasi', null, null, null, 'success', "Menghapus Satker: {$satkerName}");

        return redirect()->route($this->getRedirectRoute())->with('success', 'Satker berhasil dihapus.');
    }
}
