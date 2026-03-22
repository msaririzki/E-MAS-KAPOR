@if($items->count() > 0)
    <table class="user-table">
        <thead>
            <tr>
                <th>NO</th>
                <th>NAMA BARANG</th>
                <th>SATUAN</th>
                <th>KUANTITAS</th>
                <th>HARGA SATUAN</th>
                <th>JUMLAH HARGA</th>
                <th style="text-align: center;">AKSI</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                <tr>
                    <td>{{ $items->firstItem() + $index }}</td>
                    <td><span class="cell-name">{{ $item->name }}</span></td>
                    <td>{{ $item->unit }}</td>
                    <td>
                        <span class="badge {{ $item->sizes_sum_stock > 0 ? 'badge-success' : 'badge-danger' }}">
                            {{ number_format($item->sizes_sum_stock ?? 0, 0, ',', '.') }} {{ $item->unit }}
                        </span>
                    </td>
                    <td>{{ $item->formatted_price }}</td>
                    <td>
                        <strong>Rp {{ number_format(($item->sizes_sum_stock ?? 0) * $item->price, 0, ',', '.') }}</strong>
                    </td>
                    <td style="text-align: right; padding: 12px 24px;">
                        <div class="action-buttons">
                            <button class="btn-icon" onclick="openDispenseModal({{ $item->id }}, '{{ addslashes($item->name) }}')" title="Keluarkan Barang" style="color: #F59E0B;" onmouseover="this.style.background='#FEF3C7'" onmouseout="this.style.background=''">
                                <i class="ri-upload-cloud-2-line"></i>
                            </button>
                            <button onclick="openSizeModal({{ $item->id }}, '{{ addslashes($item->name) }}')" class="btn-icon" title="Kelola Ukuran & Stok">
                                <i class="ri-list-check"></i>
                            </button>
                            <button onclick="openEditModal({{ json_encode($item) }})" class="btn-icon blue" title="Edit Master">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button onclick="confirmDelete({{ $item->id }}, '{{ addslashes($item->name) }}')" class="btn-icon red" title="Hapus">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
        @if($items->hasPages())
        <tfoot>
            <tr>
                <td colspan="7">
                    <div style="display:flex; justify-content:space-between; align-items:center; padding: 10px 0;">
                        <div style="font-size: 13px; color: #6B7280;">
                            Menampilkan {{ $items->firstItem() }} sampai {{ $items->lastItem() }} dari {{ $items->total() }} entri
                        </div>
                        <div class="pagination">
                            {{ $items->links('vendor.pagination.bootstrap-4') }}
                        </div>
                    </div>
                </td>
            </tr>
        </tfoot>
        @endif
    </table>
@else
    <div style="padding: 40px; text-align: center;">
        <i class="ri-inbox-line" style="font-size: 48px; color: #9CA3AF;"></i>
        <h3 style="margin: 10px 0 5px; color: #4B5563;">Tidak Ada Item Gudang</h3>
        <p style="color: #6B7280; font-size: 14px;">Data belum tersedia atau tidak cocok dengan filter pencarian.</p>
    </div>
@endif
