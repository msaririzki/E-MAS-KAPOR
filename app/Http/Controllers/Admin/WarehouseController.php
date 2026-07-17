<?php

namespace App\Http\Controllers\Admin;

use App\Exports\WarehouseTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\WarehouseItem;
use App\Models\WarehouseItemSize;
use App\Models\WarehouseOutflow;
use App\Models\WarehouseSignatory;
use App\Services\WarehouseSppmExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        // Build standard query for view
        $viewQuery = WarehouseItem::with(['sizes' => function ($q) {
            $q->orderByRaw('CAST(size_label AS UNSIGNED) ASC, size_label ASC');
        }])->withSum('sizes', 'stock');

        if ($request->filled('search')) {
            $viewQuery->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('unit', 'like', "%{$request->search}%");
            });
        }
        if ($request->filled('sumber_pengadaan')) {
            $viewQuery->where('sumber_pengadaan', $request->sumber_pengadaan);
        }
        if ($request->filled('kategori_stok')) {
            $viewQuery->where('kategori_stok', $request->kategori_stok);
        }

        $perPage = $request->input('per_page', 10);
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
        if ($request->has('price')) {
            $priceStr = str_replace('.', '', $request->input('price'));
            $priceStr = str_replace(',', '.', $priceStr);
            $request->merge(['price' => $priceStr]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'sizes' => 'nullable|array',
            'sizes.*' => 'required|string|max:50',
            'quantities' => 'nullable|array',
            'quantities.*' => 'required|integer|min:0',
            'sumber_pengadaan' => 'required|string|in:Mabes Polri,Polda NTB',
            'kategori_stok' => 'required|string|in:Stok,Luar Stok',
        ]);

        $validated['unit'] = $validated['unit'] ?? 'PCS';
        $validated['price'] = $validated['price'] ?? 0;

        $item = WarehouseItem::create([
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'price' => $validated['price'],
            'sumber_pengadaan' => $validated['sumber_pengadaan'],
            'kategori_stok' => $validated['kategori_stok'],
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
        if ($request->has('price')) {
            $priceStr = str_replace('.', '', $request->input('price'));
            $priceStr = str_replace(',', '.', $priceStr);
            $request->merge(['price' => $priceStr]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'sumber_pengadaan' => 'required|string|in:Mabes Polri,Polda NTB',
            'kategori_stok' => 'required|string|in:Stok,Luar Stok',
        ]);

        $warehouse_item->update([
            'name' => $validated['name'],
            'price' => $validated['price'] ?? 0,
            'unit' => $validated['unit'] ?? 'PCS',
            'sumber_pengadaan' => $validated['sumber_pengadaan'],
            'kategori_stok' => $validated['kategori_stok'],
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
            'deleted_at_stock' => $totalStock,
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

    public function dispenseForm()
    {
        $items = WarehouseItem::with(['sizes' => function ($q) {
            $q->where('stock', '>', 0)->orderByRaw('CAST(size_label AS UNSIGNED) ASC, size_label ASC');
        }])->withSum('sizes', 'stock')->having('sizes_sum_stock', '>', 0)->orderBy('name')->get();

        $satkers = \App\Models\Satker::orderBy('name', 'asc')->get();

        return view('admin.warehouse.dispense', compact('items', 'satkers'));
    }

    public function getItemSizes($id)
    {
        $sizes = WarehouseItemSize::where('warehouse_item_id', $id)
            ->where('stock', '>', 0)
            ->orderByRaw('CAST(size_label AS UNSIGNED) ASC, size_label ASC')
            ->get(['id', 'size_label', 'stock']);

        return response()->json($sizes);
    }

    public function dispense(Request $request)
    {
        $method = $request->input('dispense_method', 'method_1');

        if ($method === 'method_1') {
            $request->validate([
                'outflow_date' => 'required|date',
                'satker_id' => 'required|exists:satkers,id',
                'recipient_name' => 'required|string|max:255',
                'letter_number' => 'nullable|string|max:255',
                'letter_date' => 'nullable|date',
                'items' => 'required|array|min:1',
                'items.*.sizes' => 'required|array|min:1',
                'items.*.sizes.*.warehouse_item_size_id' => 'required|exists:warehouse_item_sizes,id',
                'items.*.sizes.*.quantity' => 'required|integer|min:1',
            ]);
        } else {
            $request->validate([
                'outflow_date' => 'required|date',
                'letter_number' => 'nullable|string|max:255',
                'letter_date' => 'nullable|date',
                'selected_satkers' => 'required|array|min:1',
                'selected_satkers.*' => 'exists:satkers,id',
                'selected_items' => 'required|array|min:1',
                'selected_items.*' => 'exists:warehouse_items,id',
                'm2_mode' => 'nullable|in:acak,ukuran',
            ]);
        }

        DB::beginTransaction();
        try {
            $createdCount = 0;

            if ($method === 'method_1') {
                foreach ($request->items as $itemData) {
                    foreach ($itemData['sizes'] as $sizeData) {
                        $size = WarehouseItemSize::with('item')->findOrFail($sizeData['warehouse_item_size_id']);

                        if ($size->stock < $sizeData['quantity']) {
                            DB::rollBack();

                            return back()->withInput()->with('error', 'Stok ' . $size->item->name . ' ukuran ' . $size->size_label . ' tidak mencukupi. (Stok tersisa: ' . $size->stock . ')');
                        }

                        // Kurangi stok
                        $size->stock -= $sizeData['quantity'];
                        $size->save();

                        // Catat pengeluaran
                        WarehouseOutflow::create([
                            'warehouse_item_size_id' => $size->id,
                            'satker_id' => $request->satker_id,
                            'quantity' => $sizeData['quantity'],
                            'outflow_date' => $request->outflow_date,
                            'recipient_name' => $request->recipient_name,
                            'reference_note' => 'Belum Ada',
                            'letter_number' => $request->letter_number,
                            'letter_date' => $request->letter_date,
                        ]);

                        $createdCount++;
                    }
                }

            } else {
                // Method 2: Per Barang (Multiple Satkers & Items grid, No Size Selected ATAU by Ukuran)

                $mode = $request->m2_mode ?? 'acak';
                $totalsNeeded = [];
                $totalsNeededSize = [];
                
                // Calculate total needed per ITEM id
                foreach ($request->selected_satkers as $satkerId) {
                    foreach ($request->selected_items as $itemId) {
                        if ($mode === 'ukuran') {
                            if (isset($request->quantities_size[$satkerId][$itemId]) && is_array($request->quantities_size[$satkerId][$itemId])) {
                                foreach ($request->quantities_size[$satkerId][$itemId] as $sizeId => $qty) {
                                    $qty = (int)$qty;
                                    if ($qty > 0) {
                                        $totalsNeededSize[$itemId][$sizeId] = ($totalsNeededSize[$itemId][$sizeId] ?? 0) + $qty;
                                    }
                                }
                            }
                        } else {
                            $qty = isset($request->quantities[$satkerId][$itemId]) ? (int)$request->quantities[$satkerId][$itemId] : 0;
                            if ($qty > 0) {
                                $totalsNeeded[$itemId] = ($totalsNeeded[$itemId] ?? 0) + $qty;
                            }
                        }
                    }
                }

                if ($mode === 'ukuran' && empty($totalsNeededSize)) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Tidak ada barang dengan jumlah > 0 yang dibagikan ke satker mana pun.');
                }
                if ($mode === 'acak' && empty($totalsNeeded)) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Tidak ada barang dengan jumlah > 0 yang dibagikan ke satker mana pun.');
                }

                // Validate stocks
                if ($mode === 'ukuran') {
                    // Validasi per size
                    foreach ($totalsNeededSize as $itemId => $sizes) {
                        $item = \App\Models\WarehouseItem::findOrFail($itemId);
                        foreach ($sizes as $sizeId => $totalQty) {
                            $sizeObj = WarehouseItemSize::findOrFail($sizeId);
                            if ($sizeObj->stock < $totalQty) {
                                DB::rollBack();
                                return back()->withInput()->with('error', 'Total stok ' . $item->name . ' ukuran ' . $sizeObj->size_label . ' tidak mencukupi. (Dibutuhkan: ' . $totalQty . ', Stok: ' . $sizeObj->stock . ')');
                            }
                        }
                    }
                } else {
                    // Validasi total stok item
                    foreach ($totalsNeeded as $itemId => $totalQty) {
                        $totalStock = WarehouseItemSize::where('warehouse_item_id', $itemId)->sum('stock');
                        if ($totalStock < $totalQty) {
                            $item = \App\Models\WarehouseItem::findOrFail($itemId);
                            DB::rollBack();
                            return back()->withInput()->with('error', 'Total stok ' . $item->name . ' tidak mencukupi untuk total pembagian. (Dibutuhkan: ' . $totalQty . ', Stok tersisa: ' . $totalStock . ')');
                        }
                    }
                }

                // If sufficient, reduce stock and create outflow
                foreach ($request->selected_satkers as $satkerId) {
                    foreach ($request->selected_items as $itemId) {
                        
                        if ($mode === 'ukuran') {
                            if (isset($request->quantities_size[$satkerId][$itemId]) && is_array($request->quantities_size[$satkerId][$itemId])) {
                                foreach ($request->quantities_size[$satkerId][$itemId] as $sizeId => $qty) {
                                    $qtyRequired = (int)$qty;
                                    if ($qtyRequired > 0) {
                                        $sizeObj = WarehouseItemSize::findOrFail($sizeId);
                                        $sizeObj->stock -= $qtyRequired;
                                        $sizeObj->save();
                                        
                                        WarehouseOutflow::create([
                                            'warehouse_item_size_id' => $sizeObj->id,
                                            'satker_id' => $satkerId,
                                            'quantity' => $qtyRequired,
                                            'outflow_date' => $request->outflow_date,
                                            'recipient_name' => null, 
                                            'reference_note' => 'Belum Ada',
                                            'letter_number' => $request->letter_number,
                                            'letter_date' => $request->letter_date,
                                        ]);
                                        
                                        $createdCount++;
                                    }
                                }
                            }
                        } else {
                            // Acak FIFO
                            $qtyRequired = isset($request->quantities[$satkerId][$itemId]) ? (int)$request->quantities[$satkerId][$itemId] : 0;
                            if ($qtyRequired > 0) {
                                $sizes = WarehouseItemSize::where('warehouse_item_id', $itemId)
                                            ->where('stock', '>', 0)
                                            ->orderBy('id')
                                            ->get();
                                
                                $remaining = $qtyRequired;
                                
                                foreach ($sizes as $size) {
                                    if ($remaining <= 0) break;
                                    
                                    $take = min($size->stock, $remaining);
                                    
                                    $size->stock -= $take;
                                    $size->save();
                                    
                                    WarehouseOutflow::create([
                                        'warehouse_item_size_id' => $size->id,
                                        'satker_id' => $satkerId,
                                        'quantity' => $take,
                                        'outflow_date' => $request->outflow_date,
                                        'recipient_name' => null, 
                                        'reference_note' => 'Belum Ada',
                                        'letter_number' => $request->letter_number,
                                        'letter_date' => $request->letter_date,
                                    ]);
                                    
                                    $createdCount++;
                                    $remaining -= $take;
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.warehouse-items.reports')
                ->with('success', $createdCount.' barang berhasil dikeluarkan. Silakan klik tombol "Buat SPPM" pada Laporan Pengeluaran jika diperlukan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal mengeluarkan barang: '.$e->getMessage());
        }
    }

    public function downloadSppm($id)
    {
        $outflow = WarehouseOutflow::with(['itemSize.item', 'satker'])->findOrFail($id);

        $sppmService = app(WarehouseSppmExportService::class);
        $filePath = $sppmService->generate([
            'satker_name' => $outflow->satker ? $outflow->satker->name : '-',
            'outflow_date' => $outflow->outflow_date->format('Y-m-d'),
            'recipient_name' => $outflow->recipient_name ?? '-',
            'letter_number' => $outflow->letter_number ?? '-',
            'letter_date' => $outflow->letter_date ? $outflow->letter_date->format('Y-m-d') : null,
            'items' => [
                [
                    'item_name' => $outflow->itemSize->item->name ?? '-',
                    'unit' => $outflow->itemSize->item->unit ?? 'PCS',
                    'size_label' => $outflow->itemSize->size_label ?? '-',
                    'quantity' => $outflow->quantity,
                    'price' => $outflow->itemSize->item->price ?? 0,
                ],
            ],
        ]);

        $fileName = 'SPPM_'.str_replace(' ', '_', $outflow->itemSize->item->name ?? 'GUDANG').'_'.date('Ymd').'.docx';

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    public function sppm(Request $request)
    {
        $query = WarehouseOutflow::with(['satker'])
            ->select(
                'satker_id',
                'outflow_date',
                'recipient_name',
                'letter_number',
                'letter_date',
                DB::raw('COUNT(*) as item_count'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('MAX(id) as last_id'), // To identify the group
                DB::raw('GROUP_CONCAT(id) as group_ids') // Added for downloading
            );

        if ($request->filled('start_date')) {
            $query->whereDate('outflow_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('outflow_date', '<=', $request->end_date);
        }
        if ($request->filled('satker_id')) {
            $query->where('satker_id', $request->satker_id);
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($searchTerm) {
                $q->where('recipient_name', 'like', "%{$searchTerm}%")
                    ->orWhere('letter_number', 'like', "%{$searchTerm}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $groupedOutflows = $query->groupBy('satker_id', 'outflow_date', 'recipient_name', 'letter_number', 'letter_date')
            ->orderBy('outflow_date', 'desc')
            ->orderBy(DB::raw('MAX(id)'), 'desc')
            ->paginate($perPage)
            ->appends($request->query());

        $satkers = \App\Models\Satker::orderBy('name', 'asc')->get();

        return view('admin.warehouse.sppm', compact('groupedOutflows', 'satkers'));
    }

    public function downloadSppmGrouped(Request $request)
    {
        $request->validate([
            'group_ids' => 'required',
            'letter_number' => 'required',
            'letter_date' => 'required|date',
        ]);

        $ids = explode(',', $request->group_ids);
        $outflows = WarehouseOutflow::with(['itemSize.item', 'satker'])
            ->whereIn('id', $ids)
            ->get();

        if ($outflows->isEmpty()) {
            return back()->with('error', 'Data SPPM tidak ditemukan.');
        }

        // Perbarui letter number dan date di DB beserta status
        WarehouseOutflow::whereIn('id', $ids)->update([
            'letter_number' => $request->letter_number,
            'letter_date' => $request->letter_date,
            'reference_note' => 'Sudah Ada',
        ]);

        // Refresh data setelah diupdate
        $outflows = $outflows->fresh(['itemSize.item', 'satker']);

        $first = $outflows->first();
        $itemsDataMap = [];
        foreach ($outflows as $outflow) {
            $itemName = $outflow->itemSize->item->name ?? '-';
            if (isset($itemsDataMap[$itemName])) {
                $itemsDataMap[$itemName]['quantity'] += $outflow->quantity;
            } else {
                $itemsDataMap[$itemName] = [
                    'item_name' => $itemName,
                    'unit' => $outflow->itemSize->item->unit ?? 'PCS',
                    'size_label' => $outflow->itemSize->size_label ?? '-',
                    'quantity' => $outflow->quantity,
                    'price' => $outflow->itemSize->item->price ?? 0,
                ];
            }
        }
        $itemsData = array_values($itemsDataMap);

        $sppmService = app(WarehouseSppmExportService::class);
        $filePath = $sppmService->generate([
            'satker_name' => $first->satker ? $first->satker->name : '-',
            'outflow_date' => $first->outflow_date->format('Y-m-d'),
            'recipient_name' => $first->recipient_name ?? '-',
            'letter_number' => $first->letter_number ?? '-',
            'letter_date' => $first->letter_date ? $first->letter_date->format('Y-m-d') : null,
            'items' => $itemsData,
        ]);

        $fileName = 'SPPM_'.str_replace(' ', '_', $first->satker->name ?? 'GUDANG').'_'.$first->outflow_date->format('Ymd').'.docx';

        // Mark as "Sudah Ada" automatically when downloaded?
        // Let's do it to make it easier for user.
        WarehouseOutflow::whereIn('id', $outflows->pluck('id')->toArray())
            ->update(['reference_note' => 'Sudah Ada']);

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    public function saveSppmGrouped(Request $request)
    {
        $request->validate([
            'group_ids' => 'required',
            'letter_number' => 'required',
            'letter_date' => 'required|date',
        ]);

        $ids = explode(',', $request->group_ids);
        $outflows = WarehouseOutflow::whereIn('id', $ids)->get();

        if ($outflows->isEmpty()) {
            return back()->with('error', 'Data SPPM tidak ditemukan.');
        }

        WarehouseOutflow::whereIn('id', $ids)->update([
            'letter_number' => $request->letter_number,
            'letter_date' => $request->letter_date,
            'reference_note' => 'Sudah Ada',
        ]);

        return back()->with('success', 'Data SPPM berhasil disimpan. Anda dapat mengunduhnya melalui sub menu SPPM.');
    }

    public function updateSppm(Request $request, $id)
    {
        $request->validate([
            'reference_note' => 'required|string|in:Sudah Ada,Belum Ada',
        ]);

        $ids = explode(',', $id);
        WarehouseOutflow::whereIn('id', $ids)->update([
            'reference_note' => $request->reference_note,
        ]);

        return response()->json(['success' => true, 'message' => 'Status SPPM berhasil diperbarui.']);
    }

    public function cancelOutflow($id)
    {
        DB::beginTransaction();
        try {
            // Handle multiple IDs (grouped)
            $ids = explode(',', $id);
            $outflows_to_cancel = WarehouseOutflow::whereIn('id', $ids)->get();

            if ($outflows_to_cancel->isEmpty()) {
                return back()->with('error', 'Data pengeluaran tidak ditemukan.');
            }

            foreach ($outflows_to_cancel as $outflow) {
                if ($outflow->reference_note === 'Sudah Ada' || $outflow->reference_note === 'Ada') {
                    return back()->with('error', 'Gagal membatalkan: Salah satu barang dlm transaksi ini sudah memiliki SPPM.');
                }
                if ($outflow->itemSize) {
                    $outflow->itemSize->increment('stock', $outflow->quantity);
                }
                $outflow->delete();
            }

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
            'deletion_reason' => 'required|string|max:255',
        ]);

        $ids = explode(',', $id);
        $outflows = WarehouseOutflow::whereIn('id', $ids)->get();

        if ($outflows->isEmpty()) {
            return back()->with('error', 'Data pengeluaran tidak ditemukan.');
        }

        $satkerName = $outflows->first()->satker ? $outflows->first()->satker->name : 'Unknown';
        $totalQuantity = $outflows->sum('quantity');
        $itemCount = $outflows->count();

        \App\Services\AuditLogger::log('HAPUS_PENGELUARAN', "Riwayat pengeluaran dari {$satkerName} sejumlah {$itemCount} item (Total Qty: {$totalQuantity}) dihapus. Alasan: {$request->deletion_reason}");

        foreach ($outflows as $outflow) {
            $outflow->update(['deletion_reason' => $request->deletion_reason]);
            $outflow->delete();
        }

        return back()->with('success', 'Riwayat pengeluaran berhasil dihapus dan dipindahkan ke Riwayat Penghapusan.');
    }

    public function reports(Request $request)
    {
        $query = WarehouseOutflow::with(['satker'])
            ->select(
                'satker_id',
                'outflow_date',
                'recipient_name',
                'letter_number',
                'letter_date',
                DB::raw('COUNT(*) as item_count'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('GROUP_CONCAT(id) as group_ids'), // To identify group for actions
                DB::raw('MAX(reference_note) as group_status') // Aggregate status
            );

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
                $query->having('group_status', 'Sudah Ada');
            } elseif ($sppm === 'Belum Ada') {
                $query->havingRaw("group_status IS NULL OR group_status IN ('Belum Ada', 'Tidak')");
            }
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('recipient_name', 'like', "%{$searchTerm}%")
                    ->orWhere('letter_number', 'like', "%{$searchTerm}%");
            });
        }

        $perPage = $request->input('per_page', 15);

        // Finalize query for pagination
        $outflows = $query->groupBy('satker_id', 'outflow_date', 'recipient_name', 'letter_number', 'letter_date')
            ->orderByRaw("CASE WHEN MAX(reference_note) = 'Sudah Ada' THEN 1 ELSE 0 END")
            ->orderBy('outflow_date', 'desc')
            ->orderBy(DB::raw('MAX(id)'), 'desc')
            ->paginate($perPage)
            ->appends($request->query());

        // Attach item details manually to each group for display
        $outflows->getCollection()->each(function ($group) {
            $ids = explode(',', $group->group_ids);
            $items = WarehouseOutflow::whereIn('id', $ids)
                ->with(['itemSize' => fn ($q) => $q->withTrashed(), 'itemSize.item' => fn ($q) => $q->withTrashed()])
                ->get();

            $group->items_detail = $items;
            $group->items_json = $items->map(fn ($d) => [
                'name' => $d->itemSize->item->name ?? '-',
                'size' => $d->itemSize->size_label ?? '-',
                'qty' => $d->quantity,
                'unit' => $d->itemSize->item->unit ?? 'PCS',
            ])->toJson();
        });

        // Calculate total items out for the stat card (individual total)
        $totalItemsOut = WarehouseOutflow::query()
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('outflow_date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('outflow_date', '<=', $request->end_date))
            ->when($request->filled('satker_id'), fn ($q) => $q->where('satker_id', $request->satker_id))
            ->sum('quantity');

        $satkers = \App\Models\Satker::orderBy('name', 'asc')->get();

        return view('admin.warehouse.reports', compact('outflows', 'satkers', 'totalItemsOut'));
    }

    public function deletionHistory(Request $request)
    {
        $search = $request->search;

        // Tab Barang Terhapus
        $itemQuery = WarehouseItem::onlyTrashed()->with(['sizes' => fn ($q) => $q->withTrashed()]);
        if ($search) {
            $itemQuery->where('name', 'like', "%{$search}%");
        }
        $items = $itemQuery->latest('deleted_at')->paginate(15, ['*'], 'items_page')->withQueryString();

        // Tab Laporan Pengeluaran Terhapus
        $outflowQuery = WarehouseOutflow::onlyTrashed()->with(['itemSize.item' => fn ($q) => $q->withTrashed(), 'satker']);
        if ($search) {
            $outflowQuery->whereHas('itemSize.item', function ($q) use ($search) {
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

    // ── Penanda Tangan (Signatories) CRUD ──────────────────

    public function signatories()
    {
        $signatories = WarehouseSignatory::orderBy('created_at', 'desc')->get();

        return view('admin.warehouse.signatories', compact('signatories'));
    }

    public function storeSignatory(Request $request)
    {
        $validated = $request->validate([
            'satuan_kerja' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'pangkat' => 'nullable|string|max:255',
            'nrp' => 'nullable|string|max:100',
            'atribut' => 'nullable|string|max:255',
            'wakil' => 'nullable|string|max:255',
        ]);

        // Deactivate all others, set new as active
        WarehouseSignatory::query()->update(['is_active' => false]);

        WarehouseSignatory::create(array_merge($validated, ['is_active' => true]));

        return redirect()->back()->with('success', 'Penanda tangan berhasil ditambahkan.');
    }

    public function updateSignatory(Request $request, WarehouseSignatory $signatory)
    {
        $validated = $request->validate([
            'satuan_kerja' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'pangkat' => 'nullable|string|max:255',
            'nrp' => 'nullable|string|max:100',
            'atribut' => 'nullable|string|max:255',
            'wakil' => 'nullable|string|max:255',
        ]);

        $signatory->update($validated);

        return redirect()->back()->with('success', 'Penanda tangan berhasil diperbarui.');
    }

    public function deleteSignatory(WarehouseSignatory $signatory)
    {
        $signatory->delete();

        return redirect()->back()->with('success', 'Penanda tangan berhasil dihapus.');
    }

    public function toggleSignatoryActive(WarehouseSignatory $signatory)
    {
        // Deactivate all, activate this one
        WarehouseSignatory::query()->update(['is_active' => false]);
        $signatory->update(['is_active' => true]);

        return redirect()->back()->with('success', 'Penanda tangan aktif berhasil diubah.');
    }
}
