<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BagianOption;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class BagianOptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:bagian_options,name',
        ]);

        $option = BagianOption::create([
            'name' => strtoupper(trim($validated['name'])),
            'sort_order' => (BagianOption::max('sort_order') ?? 0) + 1,
            'is_active' => true,
        ]);

        AuditLogger::log('Tambah Master Bagian/Fungsi', 'Pengaturan Sistem', $option, null, $option->toArray(), 'success', 'Menambahkan opsi bagian/fungsi baru.');

        return redirect()->route('superadmin.settings.index')->with('success', 'Master bagian/fungsi berhasil ditambahkan.');
    }

    public function update(Request $request, BagianOption $bagianOption)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:bagian_options,name,'.$bagianOption->id,
            'is_active' => 'nullable|boolean',
        ]);

        $bagianOption->update([
            'name' => strtoupper(trim($validated['name'])),
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::log('Ubah Master Bagian/Fungsi', 'Pengaturan Sistem', $bagianOption, null, $bagianOption->toArray(), 'success', 'Memperbarui opsi bagian/fungsi.');

        return redirect()->route('superadmin.settings.index')->with('success', 'Master bagian/fungsi berhasil diperbarui.');
    }

    public function destroy(BagianOption $bagianOption)
    {
        $name = $bagianOption->name;
        $bagianOption->delete();

        AuditLogger::log('Hapus Master Bagian/Fungsi', 'Pengaturan Sistem', null, null, null, 'success', "Menghapus opsi bagian/fungsi: {$name}");

        return redirect()->route('superadmin.settings.index')->with('success', 'Master bagian/fungsi berhasil dihapus.');
    }
}
