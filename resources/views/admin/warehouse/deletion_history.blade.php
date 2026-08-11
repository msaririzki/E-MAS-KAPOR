@extends('layouts.app')

@section('title', 'Riwayat Penghapusan Barang')
@section('breadcrumb', 'Data Gudang / Riwayat Penghapusan')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Riwayat Penghapusan</h1>
            <p class="page-subtitle">Daftar barang dan laporan yang telah dihapus beserta alasannya</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin.warehouse-items.index') }}" class="btn btn-outline">
                <i class="ri-arrow-left-line"></i> Kembali ke Stok
            </a>
        </div>
    </div>
</div>

{{-- Filter & Content --}}
<div class="card">
    <div class="card-body">
        <div class="filter-bar-modern" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
            <form method="GET" action="{{ route('admin.warehouse-items.deletion-history') }}" class="filter-form" style="display:flex; flex-wrap: wrap; gap:12px; align-items: flex-end;">
                <div class="filter-group" style="flex: 1; min-width: 300px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase;">Pencarian Riwayat</label>
                    <div class="search-wrap">
                        <i class="ri-search-line"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama barang..." class="search-input" autocomplete="off" style="padding-left: 36px;">
                    </div>
                </div>

                <div class="filter-actions" style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary" style="height:36px; padding:0 20px;">
                        <i class="ri-search-2-line"></i> Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.warehouse-items.deletion-history') }}" class="btn btn-outline" style="height:36px; padding:0 16px;" title="Reset">
                            <i class="ri-refresh-line"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

{{-- Tabs --}}
<div class="tabs-container">
    <div class="tabs-header">
        <button class="tab-btn {{ !request('outflows_page') ? 'active' : '' }}" onclick="switchTab('items-tab')">
            <i class="ri-archive-line"></i> Master Barang 
            <span class="tab-count">{{ $items->total() }}</span>
        </button>
        <button class="tab-btn {{ request('outflows_page') ? 'active' : '' }}" onclick="switchTab('outflows-tab')">
            <i class="ri-file-list-3-line"></i> Laporan Pengeluaran
            <span class="tab-count">{{ $outflows->total() }}</span>
        </button>
    </div>

    {{-- Items Tab Content --}}
    <div id="items-tab" class="tab-content {{ !request('outflows_page') ? 'active' : '' }}">
            <div class="table-wrap">
                <table class="user-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">NO</th>
                        <th style="width: 20%;">NAMA BARANG</th>
                        <th style="width: 10%;">SATUAN</th>
                        <th style="width: 12%;">STOK AKHIR</th>
                        <th style="width: 18%;">WAKTU DIHAPUS</th>
                        <th>ALASAN PENGHAPUSAN</th>
                        @role('superadmin|kepala_gudang')
                        <th style="width: 10%; text-align: center;">AKSI</th>
                        @endrole
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $index => $item)
                        <tr>
                            <td>{{ $items->firstItem() + $index }}</td>
                            <td>
                                <strong style="color: #374151;">{{ $item->name }}</strong>
                            </td>
                            <td>
                                <span style="background: #F3F4F6; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; color:#374151;">
                                    {{ $item->unit }}
                                </span>
                            </td>
                            <td>
                                <strong style="color: #EF4444;">{{ number_format($item->deleted_at_stock ?? 0, 0, ',', '.') }}</strong>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px; color: #6B7280; font-size: 13px;">
                                    <i class="ri-time-line"></i>
                                    {{ $item->deleted_at->format('d/m/Y H:i') }}
                                </div>
                            </td>
                            <td>
                                <p style="color: #EF4444; margin: 0; font-size: 13px; line-height: 1.4;">
                                    {{ $item->deletion_reason ?? '-' }}
                                </p>
                            </td>
                            @role('superadmin|kepala_gudang')
                            <td style="text-align: center;">
                                <form action="{{ route('admin.warehouse-items.force-delete', $item->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmForceDelete(this.closest('form'), 'barang ini beserta seluruh ukuran dan stoknya')" class="btn-icon" style="background:#EF4444; color:white; border:none; width:34px; height:34px; border-radius:6px; cursor:pointer;" title="Hapus Permanen">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </td>
                            @endrole
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: #9CA3AF; padding: 40px;">
                                <i class="ri-inbox-line" style="font-size: 48px; color: #D1D5DB; display: block; margin-bottom: 12px;"></i>
                                Tidak ada riwayat penghapusan barang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($items->hasPages())
                <div class="pagination-footer">
                    <div class="pagination-info">
                        Menampilkan <strong>{{ $items->firstItem() }}</strong> - <strong>{{ $items->lastItem() }}</strong> dari <strong>{{ $items->total() }}</strong>
                    </div>
                    <div class="pagination-links">
                        {{ $items->appends(request()->except('items_page'))->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Outflows Tab Content --}}
    <div id="outflows-tab" class="tab-content {{ request('outflows_page') ? 'active' : '' }}">
        <div class="table-wrap">
            <table class="user-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">NO</th>
                        <th style="width: 20%;">BARANG / SATKER</th>
                        <th style="width: 10%;">JUMLAH</th>
                        <th style="width: 15%;">PENERIMA</th>
                        <th style="width: 18%;">WAKTU DIHAPUS</th>
                        <th>ALASAN PENGHAPUSAN</th>
                        @role('superadmin|kepala_gudang')
                        <th style="width: 10%; text-align: center;">AKSI</th>
                        @endrole
                    </tr>
                </thead>
                <tbody>
                    @forelse($outflows as $index => $outflow)
                        <tr>
                            <td>{{ $outflows->firstItem() + $index }}</td>
                            <td>
                                <div style="font-weight: 700; color: #1F2937; margin-bottom: 2px;">
                                    {{ $outflow->itemSize->item->name ?? '-' }}
                                </div>
                                <div style="font-size: 12px; color: #6B7280;">
                                    <i class="ri-building-line" style="margin-right: 2px;"></i> {{ $outflow->satker->name ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <div style="display:flex; flex-direction:column;">
                                    <strong style="color: #EF4444; font-size:15px;">{{ number_format($outflow->quantity, 0, ',', '.') }}</strong>
                                    <span style="font-size:11px; color:#9CA3AF; text-transform:uppercase;">{{ $outflow->itemSize->item->unit ?? '' }}</span>
                                </div>
                            </td>
                            <td>{{ $outflow->recipient_name ?: '-' }}</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px; color: #6B7280; font-size: 13px;">
                                    <i class="ri-time-line"></i>
                                    {{ $outflow->deleted_at->format('d/m/Y H:i') }}
                                </div>
                            </td>
                            <td>
                                <p style="color: #EF4444; margin: 0; font-size: 13px; line-height: 1.4;">
                                    {{ $outflow->deletion_reason ?? '-' }}
                                </p>
                            </td>
                            @role('superadmin|kepala_gudang')
                            <td style="text-align: center;">
                                <form action="{{ route('admin.warehouse-items.reports.force-delete', $outflow->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmForceDelete(this.closest('form'), 'laporan pengeluaran ini')" class="btn-icon" style="background:#EF4444; color:white; border:none; width:34px; height:34px; border-radius:6px; cursor:pointer;" title="Hapus Permanen">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </td>
                            @endrole
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: #9CA3AF; padding: 40px;">
                                <i class="ri-inbox-line" style="font-size: 48px; color: #D1D5DB; display: block; margin-bottom: 12px;"></i>
                                Tidak ada riwayat penghapusan laporan pengeluaran.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($outflows->hasPages())
                <div class="pagination-footer">
                    <div class="pagination-info">
                        Menampilkan <strong>{{ $outflows->firstItem() }}</strong> - <strong>{{ $outflows->lastItem() }}</strong> dari <strong>{{ $outflows->total() }}</strong>
                    </div>
                    <div class="pagination-links">
                        {{ $outflows->appends(request()->except('outflows_page'))->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
    </div>
</div>
</div>
</div>
@endsection

@section('styles')
<style>
    /* Style Overrides and Refinements */
    .user-table thead th {
        background: var(--slate-50);
        padding: 14px 20px;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        color: var(--slate-500);
        font-weight: 700;
        border-bottom: 2px solid var(--slate-100);
    }
    .user-table tbody td {
        padding: 14px 20px;
        border-bottom: 1px solid var(--slate-100);
    }
    
    /* Button Styles */
    .btn-icon { width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #E5E7EB; cursor: pointer; background: #fff; transition: all 0.2s; }
    .btn-icon:hover { background: #F9FAFB; }
    .btn-icon.red { color: #EF4444; }
    .btn-icon.red:hover { background: #EF4444; color: #fff; border-color: #EF4444; }
    
    /* Pagination Styling */
    .pagination-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .pagination-info {
        font-size: 13px;
        color: var(--slate-500);
    }
    .pagination-links .pagination {
        display: flex;
        list-style: none;
        gap: 4px;
        margin: 0;
        padding: 0;
    }
    .pagination-links .page-item .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: #fff;
        color: var(--slate-600);
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.2s;
    }
    .pagination-links .page-item.active .page-link {
        background: var(--brand);
        border-color: var(--brand);
        color: #fff;
    }

    .tabs-header { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 1px; }
    .tab-btn { padding: 10px 20px; font-size: 13px; font-weight: 700; color: var(--slate-500); background: transparent; border: none; border-bottom: 2px solid transparent; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; position: relative; }
    .tab-btn:hover { color: var(--brand); }
    .tab-btn.active { color: var(--brand); border-bottom-color: var(--brand); }
    .tab-count { background: var(--slate-100); padding: 2px 8px; border-radius: 20px; font-size: 10px; color: var(--slate-500); font-weight: 700; }
    .tab-btn.active .tab-count { background: var(--brand-bg); color: var(--brand); }
    
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.3s ease; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        
        document.getElementById(tabId).classList.add('active');
        document.querySelector(`[onclick="switchTab('${tabId}')"]`).classList.add('active');
    }

    function confirmForceDelete(form, itemNameText) {
        Swal.fire({
            title: 'Hapus Permanen?',
            html: `Apakah Anda yakin ingin menghapus permanen <strong>${itemNameText}</strong>?<br><small style="color:#EF4444; display:block; margin-top:8px;">Tindakan ini tidak dapat dibatalkan dan data tidak dapat dipulihkan.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Hapus Permanen',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>
@endsection
