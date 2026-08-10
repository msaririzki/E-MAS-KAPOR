<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\DispenseRequest;
use Illuminate\Support\Facades\DB;
use App\Models\WarehouseItemSize;
use App\Models\WarehouseOutflow;
use App\Services\AuditLogger;

class DispenseRequestController extends Controller
{
    public function approve($id)
    {
        $dispenseRequest = DispenseRequest::findOrFail($id);

        if ($dispenseRequest->status !== 'pending') {
            return back()->with('error', 'Permohonan ini sudah diproses sebelumnya.');
        }

        // Simulate Request to use the same validation and logic
        $fakeRequest = new Request();
        $fakeRequest->replace($dispenseRequest->payload);
        $fakeRequest->setMethod('POST');

        // We temporarily run the logic inside a transaction, but since the WarehouseController 
        // dispense method does its own transaction and redirects, we should probably 
        // replicate the actual stock deduction logic here to avoid redirecting in the middle.
        
        $method = $fakeRequest->input('dispense_method', 'method_1');
        $createdCount = 0;

        DB::beginTransaction();
        try {
            if ($method === 'method_1') {
                foreach ($fakeRequest->items as $itemData) {
                    foreach ($itemData['sizes'] as $sizeData) {
                        $size = WarehouseItemSize::with('item')->findOrFail($sizeData['warehouse_item_size_id']);
                        if ($size->stock < $sizeData['quantity']) {
                            DB::rollBack();
                            return back()->with('error', 'Stok '.$size->item->name.' ukuran '.$size->size_label.' tidak mencukupi untuk disetujui.');
                        }
                        $size->stock -= $sizeData['quantity'];
                        $size->save();

                        WarehouseOutflow::create([
                            'warehouse_item_size_id' => $size->id,
                            'satker_id' => $fakeRequest->satker_id,
                            'quantity' => $sizeData['quantity'],
                            'outflow_date' => $fakeRequest->outflow_date,
                            'recipient_name' => $fakeRequest->recipient_name,
                            'reference_note' => 'Belum Ada',
                            'letter_number' => $fakeRequest->letter_number,
                            'letter_date' => $fakeRequest->letter_date,
                        ]);
                        $createdCount++;
                    }
                }
            } else {
                $mode = $fakeRequest->m2_mode ?? 'acak';
                $totalsNeeded = [];
                $totalsNeededSize = [];

                foreach ($fakeRequest->selected_satkers as $satkerId) {
                    foreach ($fakeRequest->selected_items as $itemId) {
                        if ($mode === 'ukuran') {
                            if (isset($fakeRequest->quantities_size[$satkerId][$itemId]) && is_array($fakeRequest->quantities_size[$satkerId][$itemId])) {
                                foreach ($fakeRequest->quantities_size[$satkerId][$itemId] as $sizeId => $qty) {
                                    $qty = (int) $qty;
                                    if ($qty > 0) {
                                        $totalsNeededSize[$itemId][$sizeId] = ($totalsNeededSize[$itemId][$sizeId] ?? 0) + $qty;
                                    }
                                }
                            }
                        } else {
                            $qty = isset($fakeRequest->quantities[$satkerId][$itemId]) ? (int) $fakeRequest->quantities[$satkerId][$itemId] : 0;
                            if ($qty > 0) {
                                $totalsNeeded[$itemId] = ($totalsNeeded[$itemId] ?? 0) + $qty;
                            }
                        }
                    }
                }

                if ($mode === 'acak') {
                    foreach ($totalsNeeded as $itemId => $totalQty) {
                        $availableSizes = WarehouseItemSize::where('warehouse_item_id', $itemId)->where('stock', '>', 0)->get();
                        $sumStock = $availableSizes->sum('stock');
                        $itemObj = \App\Models\WarehouseItem::find($itemId);
                        if ($totalQty > $sumStock) {
                            DB::rollBack();
                            return back()->with('error', 'Total stok '.$itemObj->name.' tidak mencukupi untuk disetujui.');
                        }
                    }
                    foreach ($fakeRequest->selected_satkers as $satkerId) {
                        foreach ($fakeRequest->selected_items as $itemId) {
                            $qty = isset($fakeRequest->quantities[$satkerId][$itemId]) ? (int) $fakeRequest->quantities[$satkerId][$itemId] : 0;
                            if ($qty > 0) {
                                $sizes = WarehouseItemSize::where('warehouse_item_id', $itemId)->where('stock', '>', 0)->orderBy('stock', 'desc')->get();
                                $remainingToDeduct = $qty;
                                foreach ($sizes as $size) {
                                    if ($remainingToDeduct <= 0) break;
                                    $deduct = min($size->stock, $remainingToDeduct);
                                    $size->stock -= $deduct;
                                    $size->save();
                                    WarehouseOutflow::create([
                                        'warehouse_item_size_id' => $size->id,
                                        'satker_id' => $satkerId,
                                        'quantity' => $deduct,
                                        'outflow_date' => $fakeRequest->outflow_date,
                                        'recipient_name' => '-',
                                        'reference_note' => 'Belum Ada',
                                        'letter_number' => $fakeRequest->letter_number,
                                        'letter_date' => $fakeRequest->letter_date,
                                    ]);
                                    $remainingToDeduct -= $deduct;
                                    $createdCount++;
                                }
                            }
                        }
                    }
                } else {
                    foreach ($totalsNeededSize as $itemId => $sizesNeeded) {
                        $itemObj = \App\Models\WarehouseItem::find($itemId);
                        foreach ($sizesNeeded as $sizeId => $totalQty) {
                            $sizeObj = WarehouseItemSize::find($sizeId);
                            if ($totalQty > $sizeObj->stock) {
                                DB::rollBack();
                                return back()->with('error', 'Stok '.$itemObj->name.' ukuran '.$sizeObj->size_label.' tidak mencukupi.');
                            }
                        }
                    }
                    foreach ($fakeRequest->selected_satkers as $satkerId) {
                        foreach ($fakeRequest->selected_items as $itemId) {
                            if (isset($fakeRequest->quantities_size[$satkerId][$itemId]) && is_array($fakeRequest->quantities_size[$satkerId][$itemId])) {
                                foreach ($fakeRequest->quantities_size[$satkerId][$itemId] as $sizeId => $qty) {
                                    $qty = (int) $qty;
                                    if ($qty > 0) {
                                        $sizeObj = WarehouseItemSize::find($sizeId);
                                        $sizeObj->stock -= $qty;
                                        $sizeObj->save();
                                        WarehouseOutflow::create([
                                            'warehouse_item_size_id' => $sizeId,
                                            'satker_id' => $satkerId,
                                            'quantity' => $qty,
                                            'outflow_date' => $fakeRequest->outflow_date,
                                            'recipient_name' => '-',
                                            'reference_note' => 'Belum Ada',
                                            'letter_number' => $fakeRequest->letter_number,
                                            'letter_date' => $fakeRequest->letter_date,
                                        ]);
                                        $createdCount++;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $dispenseRequest->status = 'approved';
            $dispenseRequest->save();

            AuditLogger::log('Approve Dispense Request', "Menyetujui permohonan pengeluaran barang dengan {$createdCount} item.");

            DB::commit();

            return back()->with('success', "Permohonan pengeluaran berhasil disetujui, {$createdCount} data pengeluaran tersimpan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $dispenseRequest = DispenseRequest::findOrFail($id);

        if ($dispenseRequest->status !== 'pending') {
            return back()->with('error', 'Permohonan ini sudah diproses sebelumnya.');
        }

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $dispenseRequest->status = 'rejected';
        $dispenseRequest->reason = $request->reason;
        $dispenseRequest->save();

        AuditLogger::log('Reject Dispense Request', "Menolak permohonan pengeluaran barang. Alasan: " . $request->reason);

        return back()->with('success', 'Permohonan pengeluaran berhasil ditolak.');
    }
}
