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
        // Build standard query for view
        $viewQuery = WarehouseItem::with(['sizes' => function ($q) {
            $q->orderByRaw("CAST(size_label AS UNSIGNED) ASC, size_label ASC");
        }])->withSum('sizes', 'stock');

        if ($request->filled('search')) {
            $viewQuery->where('name', 'like', "%{$request->search}%")
                      ->orWhere('unit', 'like', "%{$request->search}%");
        }

        $perPage = $request->input('per_page', 15);
        $items = $viewQuery->orderBy('name', 'asc')->paginate($perPage)->appends($request->query());

        // Basic stats for view
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

    public function update(Request $request, WarehouseItem $warehouse_item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
        ]);

        $warehouse_item->update([
            'name' => $validated['name'],
            'price' => $validated['price'] ?? 0,
            'unit' => $validated['unit'] ?? 'PCS',
        ]);

        return redirect()->back()->with('success', 'Data Gudang berhasil diperbarui');
    }

    public function destroy(Request $request, WarehouseItem $warehouse_item)
    {
        $request->validate([
            'deletion_reason' => 'required|string|max:1000',
        ]);

        $totalStock = $warehouse_item->sizes()->sum('stock');

        $warehouse_item->update([
            'deletion_reason' => $request->deletion_reason,
            'deleted_at_stock' => $totalStock
        ]);

        $warehouse_item->sizes()->delete();
        $warehouse_item->delete();

        return redirect()->back()->with('success', 'Data Gudang berhasil dihapus dan dipindahkan ke Riwayat Penghapusan.');
    }

    // ── Kelola Ukuran Per Item (AJAX) ──────────────────────────

    public function getSizes(WarehouseItem $warehouseItem)
    {
        $sizes = $warehouseItem->sizes()->orderBy('size_label')->get();
        return response()->json($sizes);
    }

    public function addSize(Request $request, WarehouseItem $warehouseItem)
    {
        $validated = $request->validate([
            'size_label' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
        ]);

        // Cek duplikasi ukuran
        $exists = $warehouseItem->sizes()
            ->where('size_label', $validated['size_label'])
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Ukuran ini sudah ada. Silakan update stoknya.'], 422);
        }

        $size = $warehouseItem->sizes()->create([
            'size_label' => $validated['size_label'],
            'stock' => $validated['stock'],
        ]);

        return response()->json($size, 201);
    }

    public function updateSize(Request $request, WarehouseItem $warehouseItem, WarehouseItemSize $size)
    {
        if ($size->warehouse_item_id !== $warehouseItem->id) {
            return response()->json(['error' => 'Ukuran tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $size->update(['stock' => $validated['stock']]);

        return response()->json(['message' => 'Stok ukuran berhasil diupdate.']);
    }

    public function deleteSize(WarehouseItem $warehouseItem, WarehouseItemSize $size)
    {
        if ($size->warehouse_item_id !== $warehouseItem->id) {
            return response()->json(['error' => 'Ukuran tidak ditemukan.'], 404);
        }
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

    public function destroyOutflow(Request $request, $id)
    {
        $request->validate([
            'deletion_reason' => 'required|string|max:255'
        ]);
        
        $outflow = WarehouseOutflow::findOrFail($id);
        
        $itemName = $outflow->itemSize ? ($outflow->itemSize->item ? $outflow->itemSize->item->name : 'Unknown') : 'Unknown';
        $satkerName = $outflow->satker ? $outflow->satker->name : 'Unknown';
        $quantity = $outflow->quantity;
        
        \App\Services\AuditLogger::log('HAPUS_PENGELUARAN', "Riwayat pengeluaran {$itemName} sejumlah {$quantity} untuk {$satkerName} dihapus. Alasan: {$request->deletion_reason}");
        
        $outflow->update(['deletion_reason' => $request->deletion_reason]);
        $outflow->delete();
        return back()->with('success', 'Riwayat pengeluaran berhasil dihapus dan dipindahkan ke Riwayat Penghapusan.');
    }

    public function reports(Request $request)
    {
        $query = WarehouseOutflow::with([
            'itemSize' => fn($q) => $q->withTrashed(),
            'itemSize.item' => fn($q) => $q->withTrashed(),
            'satker'
        ]);

        if ($request->filled('start_date')) {
            $query->whereDate('outflow_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('outflow_date', '<=', $request->end_date);
        }
        if ($request->filled('satker_id')) {
            $query->where('satker_id', $request->satker_id);
        }
        if ($request->filled('sppm_status')) {
            $sppm = $request->sppm_status;
            if ($sppm === 'Sudah Ada') {
                $query->whereIn('reference_note', ['Sudah Ada', 'Ada']);
            } elseif ($sppm === 'Belum Ada') {
                $query->where(function($q) {
                    $q->whereNull('reference_note')
                      ->orWhereIn('reference_note', ['Belum Ada', 'Tidak']);
                });
            }
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('itemSize.item', function ($q) use ($searchTerm) {
                $q->withTrashed()->where('name', 'like', "%{$searchTerm}%");
            })->orWhere('recipient_name', 'like', "%{$searchTerm}%");
        }

        $perPage = $request->input('per_page', 15);
        $totalItemsOut = (clone $query)->sum('quantity');
        $outflows = $query->latest()->paginate($perPage)->appends($request->query());
        $satkers = \App\Models\Satker::orderBy('name', 'asc')->get();

        return view('admin.warehouse.reports', compact('outflows', 'satkers', 'totalItemsOut'));
    }

    public function deletionHistory(Request $request)
    {
        $search = $request->search;
        
        // Tab Barang Terhapus
        $itemQuery = WarehouseItem::onlyTrashed()->with(['sizes' => fn($q) => $q->withTrashed()]);
        if ($search) {
            $itemQuery->where('name', 'like', "%{$search}%");
        }
        $items = $itemQuery->latest('deleted_at')->paginate(15, ['*'], 'items_page')->withQueryString();

        // Tab Laporan Pengeluaran Terhapus
        $outflowQuery = WarehouseOutflow::onlyTrashed()->with(['itemSize.item' => fn($q) => $q->withTrashed(), 'satker']);
        if ($search) {
            $outflowQuery->whereHas('itemSize.item', function($q) use ($search) {
                $q->withTrashed()->where('name', 'like', "%{$search}%");
            })->orWhere('recipient_name', 'like', "%{$search}%");
        }
        $outflows = $outflowQuery->latest('deleted_at')->paginate(15, ['*'], 'outflows_page')->withQueryString();
        
        return view('admin.warehouse.deletion_history', compact('items', 'outflows'));
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
