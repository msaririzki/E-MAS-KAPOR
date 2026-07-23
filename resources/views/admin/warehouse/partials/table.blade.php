@if($items->count() > 0)
    <table class="user-table">
        <thead>
            <tr>
                <th style="width: 50px;">NO</th>
                <th>NAMA BARANG</th>
                <th>SATUAN</th>
                <th>SUMBER</th>
                <th>KATEGORI</th>
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
                        @php
                            $sumber = $item->sumber_pengadaan ?? 'Mabes Polri';
                            $sumberBg = $sumber == 'Polda NTB' ? '#EEF2FF' : '#F3F4F6';
                            $sumberColor = $sumber == 'Polda NTB' ? '#4338CA' : '#4B5563';
                            $kategori = $item->kategori_stok ?? 'Stok';
                            $katBg = $kategori == 'Luar Stok' ? '#FEF2F2' : '#F0FDF4';
                            $katColor = $kategori == 'Luar Stok' ? '#B91C1C' : '#15803D';
                        @endphp
                        <span class="badge" style="background:{{ $sumberBg }}; color:{{ $sumberColor }}; font-weight:600;">{{ $sumber }}</span>
                    </td>
                    <td><span class="badge" style="background:{{ $katBg }}; color:{{ $katColor }}; font-weight:600;">{{ $kategori }}</span></td>
                    <td>
                        <span class="badge {{ $item->sizes_sum_stock > 0 ? 'badge-success' : 'badge-danger' }}">
                            {{ number_format($item->sizes_sum_stock ?? 0, 0, ',', '.') }} {{ $item->unit }}
                        </span>
                    </td>
                    <td>{{ $item->formatted_price }}</td>
                    <td>
                        @php
                            $totalPrice = ($item->sizes_sum_stock ?? 0) * $item->price;
                            $totalDecimals = floor($totalPrice) == $totalPrice ? 0 : 2;
                        @endphp
                        <strong>Rp {{ number_format($totalPrice, $totalDecimals, ',', '.') }}</strong>
                    </td>
                    <td style="text-align: right; padding: 10px 12px; white-space: nowrap;">
                        <div class="action-buttons">
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
                <td colspan="9">
                    <div style="display:flex; justify-content:space-between; align-items:center; padding: 10px 0;">
                        <div style="font-size: 13px; color: #6B7280;">
                            Menampilkan {{ $items->firstItem() }} sampai {{ $items->lastItem() }} dari {{ $items->total() }} entri
                        </div>
                        <div class="pagination">
                            @include('admin.warehouse.partials.pagination', ['paginator' => $items])
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
