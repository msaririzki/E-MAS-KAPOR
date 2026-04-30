<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\IdentifikasiItem;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class IdentifikasiItemController extends Controller
{
    private const CATEGORIES = [
        'Tutup_Kepala' => 'Tutup Kepala',
        'Tutup_Badan' => 'Tutup Badan',
        'Tutup_Kaki' => 'Tutup Kaki',
        'Lainnya' => 'Lainnya',
    ];

    /**
     * Daftar semua item identifikasi kebutuhan.
     */
    public function index(Request $request)
    {
        $query = IdentifikasiItem::orderBy('category')->orderBy('item_name');

        if ($request->filled('search')) {
            $query->where('item_name', 'LIKE', '%'.$request->search.'%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            if ($request->status === 'aktif') {
                $query->where('is_active', true);
            } elseif ($request->status === 'nonaktif') {
                $query->where('is_active', false);
            }
        }

        $items = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => IdentifikasiItem::count(),
            'aktif' => IdentifikasiItem::where('is_active', true)->count(),
            'nonaktif' => IdentifikasiItem::where('is_active', false)->count(),
        ];

        return view('superadmin.identifikasi-items.index', [
            'items' => $items,
            'categories' => self::CATEGORIES,
            'stats' => $stats,
        ]);
    }

    /**
     * Simpan item baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name'             => 'required|string|max:255',
            'category'              => 'required|in:Tutup_Kepala,Tutup_Badan,Tutup_Kaki,Lainnya',
            'eligible_satker_count' => 'nullable|integer|min:1|max:9999',
        ], [
            'item_name.required'             => 'Nama item wajib diisi.',
            'category.required'              => 'Kategori wajib dipilih.',
            'category.in'                    => 'Kategori tidak valid.',
            'eligible_satker_count.integer'  => 'Jumlah satker eligible harus berupa angka.',
            'eligible_satker_count.min'      => 'Jumlah satker eligible minimal 1.',
        ]);

        // nullable: kosongkan jika tidak diisi
        $validated['eligible_satker_count'] = $validated['eligible_satker_count'] ?: null;
        $validated['is_active'] = true;

        $item = IdentifikasiItem::create($validated);

        AuditLogger::log(
            action: 'create_identifikasi_item',
            category: 'Item Identifikasi',
            model: $item,
            details: "Superadmin menambahkan item identifikasi: {$item->item_name} (Kategori: {$item->category})"
        );

        return redirect()->back()->with('success', "Item \"{$item->item_name}\" berhasil ditambahkan.");
    }

    /**
     * Update item.
     */
    public function update(Request $request, IdentifikasiItem $identifikasiItem)
    {
        $validated = $request->validate([
            'item_name'             => 'required|string|max:255',
            'category'              => 'required|in:Tutup_Kepala,Tutup_Badan,Tutup_Kaki,Lainnya',
            'is_active'             => 'boolean',
            'eligible_satker_count' => 'nullable|integer|min:1|max:9999',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['eligible_satker_count'] = $validated['eligible_satker_count'] ?: null;

        $identifikasiItem->update($validated);

        AuditLogger::log(
            action: 'update_identifikasi_item',
            category: 'Item Identifikasi',
            model: $identifikasiItem,
            details: "Superadmin memperbarui item identifikasi: {$identifikasiItem->item_name}"
        );

        return redirect()->back()->with('success', "Item \"{$identifikasiItem->item_name}\" berhasil diperbarui.");
    }

    /**
     * Hapus item.
     */
    public function destroy(IdentifikasiItem $identifikasiItem)
    {
        // Cek apakah item dipakai di pengajuan kebutuhan
        if ($identifikasiItem->kebutuhanItems()->exists()) {
            return redirect()->back()->with('error', "Item \"{$identifikasiItem->item_name}\" tidak dapat dihapus karena sudah digunakan dalam pengajuan kebutuhan.");
        }

        $name = $identifikasiItem->item_name;
        $identifikasiItem->delete();

        AuditLogger::log(
            action: 'delete_identifikasi_item',
            category: 'Item Identifikasi',
            details: "Superadmin menghapus item identifikasi: {$name}"
        );

        return redirect()->back()->with('success', "Item \"{$name}\" berhasil dihapus.");
    }

    /**
     * Toggle status aktif item.
     */
    public function toggleActive(IdentifikasiItem $identifikasiItem)
    {
        $identifikasiItem->is_active = ! $identifikasiItem->is_active;
        $identifikasiItem->save();

        $status = $identifikasiItem->is_active ? 'diaktifkan' : 'dinonaktifkan';

        AuditLogger::log(
            action: 'toggle_identifikasi_item',
            category: 'Item Identifikasi',
            model: $identifikasiItem,
            details: "Superadmin mengubah status item identifikasi: {$identifikasiItem->item_name} menjadi {$status}"
        );

        return redirect()->back()->with('success', "Item \"{$identifikasiItem->item_name}\" berhasil {$status}.");
    }
}
