<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SizeEditRequest;
use App\Models\WarehouseItemSize;
use Illuminate\Support\Facades\DB;

class SizeEditRequestController extends Controller
{
    public function index()
    {
        $requests = SizeEditRequest::with(['itemSize.item', 'requestedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'edit_page');
            
        $dispenseRequests = \App\Models\DispenseRequest::with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'dispense_page');

        return view('admin.warehouse.edit_requests', compact('requests', 'dispenseRequests'));
    }

    public function monitor()
    {
        $requests = SizeEditRequest::with(['itemSize.item', 'requestedBy'])
            ->where('requested_by', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'edit_page');
            
        $dispenseRequests = \App\Models\DispenseRequest::with(['user'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'dispense_page');

        return view('admin.warehouse.monitor_requests', compact('requests', 'dispenseRequests'));
    }

    public function store(Request $request, $sizeId)
    {
        $size = WarehouseItemSize::findOrFail($sizeId);
        
        $validated = $request->validate([
            'requested_stock' => 'required|integer|min:0',
            'reason' => 'required|string|max:1000',
        ]);

        $editRequest = SizeEditRequest::create([
            'warehouse_item_size_id' => $size->id,
            'requested_by' => auth()->id(),
            'old_stock' => $size->stock,
            'requested_stock' => $validated['requested_stock'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Permohonan berhasil diajukan.']);
    }

    public function approve($id)
    {
        DB::beginTransaction();
        try {
            $editRequest = SizeEditRequest::findOrFail($id);
            if ($editRequest->status !== 'pending') {
                return redirect()->back()->with('error', 'Permohonan sudah diproses sebelumnya.');
            }

            $size = $editRequest->itemSize;
            $oldStock = $editRequest->old_stock;
            $newStock = $editRequest->requested_stock;
            $diff = $oldStock - $newStock;

            // Update stok belum dialokasi (ukuran '-') jika ada
            $noSize = $size->item->sizes()->whereIn('size_label', ['-', ''])->first();
            if ($noSize && $noSize->id !== $size->id) {
                if ($diff < 0 && $noSize->stock < abs($diff)) {
                    return redirect()->back()->with('error', 'Gagal disetujui. Stok belum dialokasi tidak mencukupi.');
                }
                $noSize->stock += $diff;
                $noSize->save();
            }

            $size->stock = $newStock;
            $size->save();

            $editRequest->status = 'approved';
            $editRequest->save();

            \App\Services\AuditLogger::log('SETUJUI_EDIT_STOK', "Permohonan edit stok untuk ukuran {$size->size_label} disetujui. Stok diubah dari {$oldStock} menjadi {$newStock}.");

            DB::commit();
            return redirect()->back()->with('success', 'Permohonan berhasil disetujui dan stok telah diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function reject($id)
    {
        $editRequest = SizeEditRequest::findOrFail($id);
        if ($editRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Permohonan sudah diproses sebelumnya.');
        }

        $editRequest->status = 'rejected';
        $editRequest->save();

        return redirect()->back()->with('success', 'Permohonan telah ditolak.');
    }

    public function destroy($id)
    {
        $editRequest = SizeEditRequest::findOrFail($id);
        $editRequest->delete();

        \App\Services\AuditLogger::log('DELETE_EDIT_STOK_REQUEST', "Menghapus data permohonan edit stok.");

        return redirect()->back()->with('success', 'Data pengajuan edit stok berhasil dihapus.');
    }
}
