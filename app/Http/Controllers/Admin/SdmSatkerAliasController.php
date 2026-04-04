<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SdmSatkerAlias;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class SdmSatkerAliasController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'satker_id' => 'required|exists:satkers,id',
            'alias' => 'required|string|max:255',
            'notes' => 'nullable|string|max:255',
        ]);

        $alias = SdmSatkerAlias::create([
            'satker_id' => $validated['satker_id'],
            'alias' => strtoupper(trim($validated['alias'])),
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ]);

        AuditLogger::log('Tambah Alias Satker SDM', 'Pengaturan Sistem', $alias, null, $alias->toArray(), 'success', 'Menambahkan alias satker untuk resolver SDM.');

        return redirect()->route('superadmin.settings.index')->with('success', 'Alias satker SDM berhasil ditambahkan.');
    }

    public function update(Request $request, SdmSatkerAlias $sdmSatkerAlias)
    {
        $validated = $request->validate([
            'satker_id' => 'required|exists:satkers,id',
            'alias' => 'required|string|max:255',
            'notes' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $sdmSatkerAlias->update([
            'satker_id' => $validated['satker_id'],
            'alias' => strtoupper(trim($validated['alias'])),
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::log('Ubah Alias Satker SDM', 'Pengaturan Sistem', $sdmSatkerAlias, null, $sdmSatkerAlias->toArray(), 'success', 'Memperbarui alias satker untuk resolver SDM.');

        return redirect()->route('superadmin.settings.index')->with('success', 'Alias satker SDM berhasil diperbarui.');
    }

    public function destroy(SdmSatkerAlias $sdmSatkerAlias)
    {
        $label = $sdmSatkerAlias->alias;
        $sdmSatkerAlias->delete();

        AuditLogger::log('Hapus Alias Satker SDM', 'Pengaturan Sistem', null, null, null, 'success', "Menghapus alias satker SDM: {$label}");

        return redirect()->route('superadmin.settings.index')->with('success', 'Alias satker SDM berhasil dihapus.');
    }
}
