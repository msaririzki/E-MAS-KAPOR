<?php

namespace App\Http\Controllers\Admin;

use App\Exports\WarehouseTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\WarehouseItem;
use App\Models\WarehouseItemSize;
use App\Models\WarehouseOutflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseItem::withSum('sizes', 'stock')->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%'.$request->search.'%');
        }

        $perPage = $request->input('per_page', 10);
        $items = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total_items' => WarehouseItem::count(),
            'total_stock' => WarehouseItemSize::sum('stock'),
        ];

        $unitOptions = ['PCS' => 'PCS', 'STEL' => 'STEL', 'PASANG' => 'PASANG', 'SET' => 'SET', 'BUAH' => 'BUAH'];
        $satkers = \App\Models\Satker::orderBy('name', 'asc')->get();

        if ($request->ajax()) {
            return view('admin.warehouse.partials.table', compact('items'))->render();
        }

        return view('admin.warehouse.index', compact('items', 'stats', 'unitOptions', 'satkers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'sizes' => 'nullable|array',
            'sizes.*' => 'required|string|max:50',
            'quantities' => 'nullable|array',
            'quantities.*' => 'required|integer|min:0',
        ]);

        $validated['unit'] = $validated['unit'] ?? 'PCS';
        $validated['price'] = $validated['price'] ?? 0;

        $item = WarehouseItem::create([
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'price' => $validated['price'],
        ]);

        if (! empty($validated['sizes']) && ! empty($validated['quantities'])) {
            foreach ($validated['sizes'] as $index => $sizeLabel) {
                $qty = $validated['quantities'][$index] ?? 0;
                $item->sizes()->create([
                    'size_label' => $sizeLabel,
                    'stock' => $qty,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Data Gudang berhasil ditambahkan');
    }

    public function update(Request $request, WarehouseItem $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
        ]);

        $warehouse->update([
            'name' => $validated['name'],
            'price' => $validated['price'] ?? 0,
            'unit' => $validated['unit'] ?? 'PCS',
        ]);

        return redirect()->back()->with('success', 'Data Gudang berhasil diperbarui');
    }

    public function destroy(WarehouseItem $warehouse)
    {
        $warehouse->sizes()->delete();
        $warehouse->delete();

        return redirect()->back()->with('success', 'Data Gudang berhasil dihapus');
    }

    // ── Kelola Ukuran Per Item (AJAX) ──────────────────────────

    public function getSizes(WarehouseItem $warehouse)
    {
        $sizes = $warehouse->sizes()->orderBy('size_label')->get();

        return response()->json($sizes);
    }

    public function addSize(Request $request, WarehouseItem $warehouse)
    {
        $validated = $request->validate([
            'size_label' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
        ]);

        // Cek duplikasi ukuran
        $exists = $warehouse->sizes()
            ->where('size_label', $validated['size_label'])
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Ukuran ini sudah ada. Silakan update stoknya.'], 422);
        }

        $size = $warehouse->sizes()->create([
            'size_label' => $validated['size_label'],
            'stock' => $validated['stock'],
        ]);

        return response()->json($size, 201);
    }

    public function updateSize(Request $request, WarehouseItem $warehouse, WarehouseItemSize $size)
    {
        if ($size->warehouse_item_id !== $warehouse->id) {
            return response()->json(['error' => 'Ukuran tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $size->update(['stock' => $validated['stock']]);

        return response()->json(['message' => 'Stok ukuran berhasil diupdate.']);
    }

    public function deleteSize($warehouseId, $sizeId)
    {
        $size = WarehouseItemSize::findOrFail($sizeId);
        // Validasi opsional: apakah ada outflow terkait?
        $size->delete();

        return response()->json(['success' => true]);
    }

    public function dispense(Request $request)
    {
        $request->validate([
            'warehouse_item_size_id' => 'required|exists:warehouse_item_sizes,id',
            'quantity' => 'required|integer|min:1',
            'outflow_date' => 'required|date',
            'satker_id' => 'nullable|exists:satkers,id',
            'recipient_name' => 'nullable|string|max:255',
            'reference_note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $size = WarehouseItemSize::findOrFail($request->warehouse_item_size_id);

            if ($size->stock < $request->quantity) {
                return back()->with('error', 'Stok ukuran '.$size->size_label.' tidak mencukupi. (Stok tersisa: '.$size->stock.')');
            }

            // Kurangi stok
            $size->stock -= $request->quantity;
            $size->save();

            // Catat pengeluaran
            WarehouseOutflow::create([
                'warehouse_item_size_id' => $size->id,
                'satker_id' => $request->satker_id,
                'quantity' => $request->quantity,
                'outflow_date' => $request->outflow_date,
                'recipient_name' => $request->recipient_name,
                'reference_note' => $request->reference_note,
            ]);

            DB::commit();

            return back()->with('success', 'Barang berhasil dikeluarkan sebesar '.$request->quantity.' '.$size->item->unit.'.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal mengeluarkan barang: '.$e->getMessage());
        }
    }

    public function updateSppm(Request $request, $id)
    {
        $request->validate([
            'reference_note' => 'required|string|in:Sudah Ada,Belum Ada',
        ]);

        $outflow = WarehouseOutflow::findOrFail($id);
        $outflow->update([
            'reference_note' => $request->reference_note,
        ]);

        return response()->json(['success' => true, 'message' => 'Status SPPM berhasil diperbarui.']);
    }

    public function cancelOutflow($id)
    {
        DB::beginTransaction();
        try {
            $outflow = WarehouseOutflow::findOrFail($id);
            if ($outflow->reference_note === 'Sudah Ada' || $outflow->reference_note === 'Ada') {
                return back()->with('error', 'Gagal membatalkan: Barang ini sudah memiliki SPPM.');
            }
            if ($outflow->itemSize) {
                $outflow->itemSize->increment('stock', $outflow->quantity);
            }
            $outflow->delete();
            DB::commit();

            return back()->with('success', 'Pengeluaran barang berhasil dibatalkan dan stok telah dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal membatalkan pengeluaran: '.$e->getMessage());
        }
    }

    public function reports(Request $request)
    {
        $query = WarehouseOutflow::with(['itemSize.item', 'satker']);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('outflow_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('satker_id')) {
            $query->where('satker_id', $request->satker_id);
        }

        if ($request->filled('sppm_status')) {
            $query->where('reference_note', $request->sppm_status);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('itemSize.item', function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
            });
        }

        $outflows = $query->orderBy('outflow_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $satkers = \App\Models\Satker::orderBy('name', 'asc')->get();

        // Stats
        $totalItemsOut = $query->sum('quantity');

        return view('admin.warehouse.reports', compact('outflows', 'totalItemsOut', 'satkers'));
    }

    // ── Import / Export ──────────────────────────────────────

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\WarehouseImport, $request->file('file'));

            return redirect()->back()->with('success', 'Data Gudang berhasil diimport');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import: '.$e->getMessage());
        }
    }

    public function exportExcel()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\WarehouseExport, 'Data_Gudang.xlsx');
    }

    public function exportPdf()
    {
        $items = \App\Models\WarehouseItem::withSum('sizes', 'stock')->orderBy('name')->get();
        // Uses carlos-meneses/laravel-mpdf
        $pdf = \PDF::loadView('admin.warehouse.export_excel', compact('items'));

        return $pdf->download('Data_Gudang.pdf');
    }

    public function exportReportsPdf(Request $request)
    {
        $query = WarehouseOutflow::with('itemSize.item');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('outflow_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('itemSize.item', function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
            });
        }

        $outflows = $query->orderBy('outflow_date', 'desc')->get();
        $totalItemsOut = $query->sum('quantity');

        $pdf = \PDF::loadView('admin.warehouse.pdf-reports', compact('outflows', 'totalItemsOut'));

        return $pdf->download('Laporan_Pengeluaran_Gudang.pdf');
    }

    public function downloadTemplate()
    {
        return Excel::download(new WarehouseTemplateExport, 'template_import_gudang.xlsx');
    }
}
