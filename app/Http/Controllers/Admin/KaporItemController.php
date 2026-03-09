<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KaporItem;
use App\Models\KaporSize;
use Illuminate\Http\Request;

class KaporItemController extends Controller
{
    public function index(Request $request)
    {
        $query = KaporItem::withCount('sizes')->orderBy('category')->orderBy('item_name');

        if ($request->filled('search')) {
            $query->where('item_name', 'LIKE', '%'.$request->search.'%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $perPage = $request->input('per_page', 10);
        $items = $query->paginate($perPage)->withQueryString();

        $categories = [
            'Tutup_Kepala' => 'Tutup Kepala',
            'Tutup_Badan'  => 'Tutup Badan',
            'Tutup_Kaki'   => 'Tutup Kaki',
            'Atribut'      => 'Atribut',
        ];

        $stats = [
            'total'       => KaporItem::count(),
            'active'      => KaporItem::where('is_active', true)->count(),
            'kepala'      => KaporItem::where('category', 'Tutup_Kepala')->count(),
            'badan'       => KaporItem::where('category', 'Tutup_Badan')->count(),
            'kaki'        => KaporItem::where('category', 'Tutup_Kaki')->count(),
            'total_value' => KaporItem::where('is_active', true)->sum('price'),
        ];

        $unitOptions = ['PCS' => 'PCS (Pieces)', 'STEL' => 'STEL (Setel)', 'PASANG' => 'PASANG', 'SET' => 'SET', 'BUAH' => 'BUAH'];

        if ($request->ajax()) {
            return view('admin.kapor-items.partials.table', compact('items'))->render();
        }

        return view('admin.kapor-items.index', compact('items', 'categories', 'stats', 'unitOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name'      => 'required|string|max:255',
            'category'       => 'required|in:Tutup_Kepala,Tutup_Badan,Tutup_Kaki,Atribut,Lainnya',
            'description'    => 'nullable|string',
            'price'          => 'nullable|numeric|min:0',
            'unit'           => 'nullable|string|max:50',
            'invoice_group'  => 'nullable|string|max:255',
            'gender_specific'=> 'nullable|in:L,P',
        ]);

        $validated['is_active'] = true;
        $validated['unit'] = $validated['unit'] ?? 'PCS';

        KaporItem::create($validated);

        return redirect()->back()->with('success', 'Item berhasil ditambahkan');
    }

    public function update(Request $request, KaporItem $kaporItem)
    {
        $validated = $request->validate([
            'item_name'      => 'required|string|max:255',
            'category'       => 'required|in:Tutup_Kepala,Tutup_Badan,Tutup_Kaki,Atribut,Lainnya',
            'description'    => 'nullable|string',
            'price'          => 'nullable|numeric|min:0',
            'unit'           => 'nullable|string|max:50',
            'invoice_group'  => 'nullable|string|max:255',
            'gender_specific'=> 'nullable|in:L,P',
            'is_active'      => 'boolean',
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->input('is_active') == '1';
        }

        $kaporItem->update($validated);

        return redirect()->back()->with('success', 'Item berhasil diperbarui');
    }

    public function destroy(KaporItem $kaporItem)
    {
        if ($kaporItem->submissions()->exists()) {
            return redirect()->back()->with('error', 'Item tidak dapat dihapus karena sudah ada data ukuran personel yang terkait.');
        }

        $kaporItem->sizes()->delete();
        $kaporItem->delete();

        return redirect()->back()->with('success', 'Item berhasil dihapus');
    }

    // ── Kelola Ukuran Per Item (AJAX) ──────────────────────────

    public function getSizes(KaporItem $kaporItem)
    {
        $sizes = $kaporItem->sizes()->orderBy('gender')->orderBy('sort_order')->get();

        return response()->json($sizes);
    }

    public function addSize(Request $request, KaporItem $kaporItem)
    {
        $validated = $request->validate([
            'size_label' => 'required|string|max:50',
            'gender'     => 'nullable|in:L,P',
        ]);

        // Cek duplikasi dalam item + gender yang sama
        $exists = $kaporItem->sizes()
            ->where('size_label', $validated['size_label'])
            ->where('gender', $validated['gender'] ?? null)
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Ukuran sudah ada untuk gender ini.'], 422);
        }

        // Auto sort_order berdasarkan yang sudah ada
        $max = $kaporItem->sizes()
            ->where('gender', $validated['gender'] ?? null)
            ->max('sort_order') ?? 0;

        $size = $kaporItem->sizes()->create([
            'size_label' => $validated['size_label'],
            'gender'     => $validated['gender'] ?? null,
            'sort_order' => $max + 1,
        ]);

        return response()->json($size, 201);
    }

    public function deleteSize(KaporItem $kaporItem, KaporSize $size)
    {
        if ($size->kapor_item_id !== $kaporItem->id) {
            return response()->json(['error' => 'Ukuran tidak ditemukan.'], 404);
        }

        $size->delete();

        return response()->json(['message' => 'Ukuran berhasil dihapus.']);
    }
}
