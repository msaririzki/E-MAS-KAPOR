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

{{-- Filter --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('admin.warehouse-items.deletion-history') }}" class="filter-form" style="display:flex; gap:16px;">
        <div class="search-input-wrapper">
            <i class="ri-search-line search-icon"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama barang..." class="search-field" autocomplete="off">
        </div>
        <button type="submit" class="btn btn-primary" style="height:36px; padding:0 16px;">Cari</button>
        @if(request('search'))
            <a href="{{ route('admin.warehouse-items.deletion-history') }}" class="btn btn-outline" style="height:36px; padding:0 16px;">Reset</a>
        @endif
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
        <div class="table-container">
            <table class="user-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">NO</th>
                        <th style="width: 20%;">NAMA BARANG</th>
                        <th style="width: 10%;">SATUAN</th>
                        <th style="width: 12%;">STOK AKHIR</th>
                        <th style="width: 18%;">WAKTU DIHAPUS</th>
                        <th>ALASAN PENGHAPUSAN</th>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: #9CA3AF; padding: 40px;">
                                <i class="ri-inbox-line" style="font-size: 48px; color: #D1D5DB; display: block; margin-bottom: 12px;"></i>
                                Tidak ada riwayat penghapusan barang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($items->hasPages())
                <div style="padding: 16px 24px; border-top: 1px solid #F3F4F6;">
                    {{ $items->appends(request()->except('items_page'))->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Outflows Tab Content --}}
    <div id="outflows-tab" class="tab-content {{ request('outflows_page') ? 'active' : '' }}">
        <div class="table-container">
            <table class="user-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">NO</th>
                        <th style="width: 20%;">BARANG / SATKER</th>
                        <th style="width: 10%;">JUMLAH</th>
                        <th style="width: 15%;">PENERIMA</th>
                        <th style="width: 18%;">WAKTU DIHAPUS</th>
                        <th>ALASAN PENGHAPUSAN</th>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: #9CA3AF; padding: 40px;">
                                <i class="ri-inbox-line" style="font-size: 48px; color: #D1D5DB; display: block; margin-bottom: 12px;"></i>
                                Tidak ada riwayat penghapusan laporan pengeluaran.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($outflows->hasPages())
                <div style="padding: 16px 24px; border-top: 1px solid #F3F4F6;">
                    {{ $outflows->appends(request()->except('outflows_page'))->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        
        document.getElementById(tabId).classList.add('active');
        document.querySelector(`[onclick="switchTab('${tabId}')"]`).classList.add('active');
    }
</script>
@endsection

@section('styles')
<style>
    .page-title { font-size: 24px; font-weight: 700; color: #111827; }
    .page-subtitle { color: #6B7280; font-size: 14px; margin-top: 4px; }
    .page-header { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; }
    .page-header-row { display: flex; justify-content: space-between; width: 100%; align-items: center; }
    
    .table-container { background: #fff; border: 1px solid #E5E7EB; border-radius: 0 0 12px 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.02);}
    .user-table { width: 100%; border-collapse: collapse; }
    .user-table th { background: #F9FAFB; padding: 12px 24px; text-align: left; font-size: 12px; font-weight: 600; color: #6B7280; border-bottom: 1px solid #E5E7EB; }
    .user-table td { padding: 16px 24px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; color: #374151; font-size: 14px; }
    
    .tabs-header { display: flex; gap: 8px; margin-bottom: -1px; position: relative; z-index: 10; padding: 0 4px; }
    .tab-btn { padding: 12px 20px; font-size: 14px; font-weight: 600; color: #6B7280; background: #F9FAFB; border: 1px solid #E5E7EB; border-bottom: none; border-radius: 10px 10px 0 0; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .tab-btn i { font-size: 18px; }
    .tab-btn:hover { background: #F3F4F6; color: #374151; }
    .tab-btn.active { background: #fff; color: #059669; border: 1px solid #E5E7EB; border-bottom: 1px solid #fff; position: relative; }
    .tab-count { background: #F3F4F6; padding: 2px 8px; border-radius: 20px; font-size: 11px; color: #6B7280; }
    .tab-btn.active .tab-count { background: #ECFDF5; color: #059669; }
    
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .filter-bar { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .search-input-wrapper { flex: 1; position: relative; display: flex; align-items: center; max-width: 400px; }
    .search-icon { position: absolute; left: 14px; color: #9CA3AF; font-size: 18px; pointer-events: none; }
    .search-field { width: 100%; height: 36px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 16px 0 38px; font-size: 14px; color: #374151; outline: none; background: #fff; }
</style>
@endsection
