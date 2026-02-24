<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KaporItem;
use Illuminate\Http\Request;

class KaporItemController extends Controller
{
    public function index(Request $request)
    {
        $query = KaporItem::withCount('sizes')->orderBy('category')->orderBy('item_name');

        if ($request->filled('search')) {
            $query->where('item_name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $perPage = $request->input('per_page', 10);
        $items = $query->paginate($perPage)->withQueryString();

        // Categories list for filter map
        $categories = [
            'Tutup_Kepala' => 'Tutup Kepala',
            'Tutup_Badan' => 'Tutup Badan',
            'Tutup_Kaki' => 'Tutup Kaki',
            'Atribut' => 'Atribut'
        ];

        // Simple Stats
        $stats = [
            'total' => KaporItem::count(),
            'active' => KaporItem::where('is_active', true)->count(),
            'kepala' => KaporItem::where('category', 'Tutup_Kepala')->count(),
            'badan' => KaporItem::where('category', 'Tutup_Badan')->count(),
            'kaki' => KaporItem::where('category', 'Tutup_Kaki')->count(),
        ];

        if ($request->ajax()) {
            return view('admin.kapor-items.partials.table', compact('items'))->render();
        }

        return view('admin.kapor-items.index', compact('items', 'categories', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'category' => 'required|in:Tutup_Kepala,Tutup_Badan,Tutup_Kaki,Atribut',
            'description' => 'nullable|string',
            'gender_specific' => 'nullable|in:L,P',
        ]);

        $validated['is_active'] = true; // Default active

        KaporItem::create($validated);

        return redirect()->back()->with('success', 'Item berhasil ditambahkan');
    }

    public function update(Request $request, KaporItem $kaporItem)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'category' => 'required|in:Tutup_Kepala,Tutup_Badan,Tutup_Kaki,Atribut',
            'description' => 'nullable|string',
            'gender_specific' => 'nullable|in:L,P',
            'is_active' => 'boolean'
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->input('is_active') == '1';
        }

        $kaporItem->update($validated);

        return redirect()->back()->with('success', 'Item berhasil diperbarui');
    }

    public function destroy(KaporItem $kaporItem)
    {
        // Check if has submissions
        if ($kaporItem->submissions()->exists()) {
            return redirect()->back()->with('error', 'Item tidak dapat dihapus karena sudah ada data ukuran personel yang terkait.');
        }

        $kaporItem->sizes()->delete(); // Delete related sizes first
        $kaporItem->delete();

        return redirect()->back()->with('success', 'Item berhasil dihapus');
    }
}
