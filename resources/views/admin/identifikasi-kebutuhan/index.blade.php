@extends('layouts.app')

@section('title', 'Identifikasi Kebutuhan')
@section('breadcrumb', 'Identifikasi Kebutuhan')

@section('content')

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Identifikasi Kebutuhan</h1>
            <p>Data pengajuan kebutuhan kapor dari seluruh satker.</p>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="{{ route('admin.identifikasi-kebutuhan.export-pdf') }}" class="btn-export-pdf">
                <i class="ri-file-pdf-2-line"></i>
                Export PDF
            </a>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Pengajuan</span>
            <div class="stat-icon-sm" style="background: var(--info-bg); color: var(--info);"><i class="ri-file-list-3-line"></i></div>
        </div>
        <div class="stat-value" title="Total seluruh dokumen pengajuan di sistem">{{ $stats['totalPengajuan'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Satker Mengajukan</span>
            <div class="stat-icon-sm" style="background: var(--success-bg); color: var(--success);"><i class="ri-building-line"></i></div>
        </div>
        <div class="stat-value" title="Jumlah satker berbeda yang sudah mengajukan kebutuhan">{{ $stats['totalSatker'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Item Diajukan</span>
            <div class="stat-icon-sm" style="background: var(--warning-bg); color: var(--warning);"><i class="ri-stack-line"></i></div>
        </div>
        <div class="stat-value" title="Total akumulasi item dari seluruh pengajuan">{{ $stats['totalItem'] }}</div>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
    <div style="background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; font-size: 13px;">
        <i class="ri-checkbox-circle-fill"></i> {{ session('success') }}
    </div>
@endif

{{-- Top item per kategori --}}
@if($itemStatsByCategory->count() > 0)
@php
    $catStyles = [
        'Tutup_Kepala' => ['icon' => 'ri-shirt-line', 'color' => '#3b82f6', 'bg' => '#dbeafe', 'barColor' => '#3b82f6'],
        'Tutup_Badan'  => ['icon' => 'ri-t-shirt-line', 'color' => '#f59e0b', 'bg' => '#fef3c7', 'barColor' => '#f59e0b'],
        'Tutup_Kaki'   => ['icon' => 'ri-footprint-line', 'color' => '#10b981', 'bg' => '#d1fae5', 'barColor' => '#10b981'],
    ];
    $defaultStyle = ['icon' => 'ri-box-3-line', 'color' => '#8b5cf6', 'bg' => '#ede9fe', 'barColor' => '#8b5cf6'];
@endphp
<div class="category-cards-grid">
    @foreach($itemStatsByCategory as $category => $items)
    @php $style = $catStyles[$category] ?? $defaultStyle; @endphp
    <div class="cat-top-card">
        <div class="cat-top-card-head">
            <div class="cat-icon" style="background: {{ $style['bg'] }}; color: {{ $style['color'] }};">
                <i class="{{ $style['icon'] }}"></i>
            </div>
            <div>
                <div class="cat-title">{{ str_replace('_', ' ', $category) }}</div>
            </div>
            <div class="cat-count" style="background: {{ $style['bg'] }}; color: {{ $style['color'] }};">
                Top {{ min(5, $items->count()) }}
            </div>
        </div>
        <div class="cat-top-card-body">
            @foreach($items->take(5) as $idx => $stat)
            <div class="top-item">
                <div class="top-rank {{ $idx === 0 ? 'gold' : ($idx === 1 ? 'silver' : ($idx === 2 ? 'bronze' : 'normal')) }}">
                    {{ $idx + 1 }}
                </div>
            <div class="top-info">
                <div class="top-name" title="{{ $stat['item_name'] }}">{{ $stat['item_name'] }}</div>
                <div class="top-count">{{ $stat['satker_count'] }} satker memilih</div>
            </div>
                <div class="top-percent-wrap" style="display: flex; align-items: center; gap: 8px;">
                    <div class="top-bar-mini"><div class="top-bar-mini-fill" style="width: {{ $stat['percentage'] }}%; background: {{ $style['barColor'] }};"></div></div>
                    <div class="top-pct" style="color: {{ $style['color'] }};">{{ $stat['percentage'] }}%</div>
                </div>
            </div>
            @endforeach

            @if($items->isEmpty())
            <div style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 12px;">
                <i class="ri-inbox-line" style="font-size: 24px; display: block; margin-bottom: 6px; opacity: 0.4;"></i>
                Belum ada data
            </div>
            @endif

            @if($items->count() > 0)
            <a href="javascript:void(0)" class="view-more-link" style="color: {{ $style['color'] }};" onclick="openDetailModal('{{ $category }}')">
                <i class="ri-eye-line"></i> Lihat Selengkapnya
            </a>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Detail Modal --}}
<div class="detail-modal-overlay" id="detailModalOverlay" onclick="if(event.target===this) closeDetailModal()">
    <div class="detail-modal">
        <div class="detail-modal-header">
            <div class="modal-icon" id="modalIcon"></div>
            <div>
                <div class="modal-title" id="modalTitle"></div>
                <div class="modal-subtitle" id="modalSubtitle"></div>
            </div>
            <button class="detail-modal-close" onclick="closeDetailModal()"><i class="ri-close-line"></i></button>
        </div>
        <div class="detail-modal-body" id="modalBody"></div>
    </div>
</div>
@endif

{{-- Filters --}}
<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="padding: 14px 20px;">
        <form method="GET" action="{{ route('admin.identifikasi-kebutuhan.index') }}" class="responsive-filter" id="filterForm" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Satker" class="search-input" style="width: 100%;">
            </div>
            <div style="width: 220px;">
                <select name="satker_id" class="filter-select">
                    <option value="">Semua Satker</option>
                    @foreach($satkers as $satker)
                        <option value="{{ $satker->id }}" @selected((string) request('satker_id') === (string) $satker->id)>
                            {{ $satker->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-search-line"></i> Filter</button>
            @if(request()->hasAny(['search', 'satker_id']))
                <a href="{{ route('admin.identifikasi-kebutuhan.index') }}" class="btn btn-ghost btn-sm"><i class="ri-refresh-line"></i> Reset</a>
            @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card" id="table-container">
    <div class="card-head"><h3>Daftar Pengajuan Kebutuhan</h3></div>
    <div class="card-body flush table-wrap">
        <table>
            <thead>
                <tr>
                    <th width="50" style="text-align: center;">No</th>
                    <th>Satker</th>
                    <th style="text-align: center;">Jumlah Item</th>
                    <th>Tanggal</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kebutuhans as $index => $k)
                <tr>
                    <td style="text-align: center;">{{ $kebutuhans->firstItem() + $index }}</td>
                    <td><span style="font-size: 12px; font-weight: 500;">{{ $k->satker->name ?? '-' }}</span></td>
                    <td style="text-align: center;"><span class="badge badge-neutral">{{ $k->items->count() }}</span></td>
                    <td style="font-size: 12px;">{{ $k->submitted_at ? $k->submitted_at->format('d/m/Y') : $k->created_at->format('d/m/Y') }}</td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 4px; justify-content: center;">
                            <a href="{{ route('admin.identifikasi-kebutuhan.show', $k) }}" class="btn btn-outline btn-xs" title="Lihat Detail">
                                <i class="ri-eye-line"></i>
                            </a>
                            @role('superadmin')
                            <form action="{{ route('admin.identifikasi-kebutuhan.destroy', $k) }}" method="POST" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-error btn-xs btn-delete-kebutuhan" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                            @endrole
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                        <i class="ri-file-list-3-line" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                        Belum ada pengajuan kebutuhan dari satker.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($kebutuhans->total() > 0)
        <div class="table-footer">
            <div class="footer-left">
                Menampilkan {{ $kebutuhans->firstItem() ?? 0 }} hingga {{ $kebutuhans->lastItem() ?? 0 }} dari {{ $kebutuhans->total() }} data
            </div>
            
            <div class="footer-right">
                <div class="pagination-controls">
                    <a href="{{ $kebutuhans->url(1) }}" class="page-btn {{ $kebutuhans->onFirstPage() ? 'disabled' : '' }}" title="Halaman Pertama">
                        <i class="ri-double-left-line"></i>
                    </a>
                    <a href="{{ $kebutuhans->previousPageUrl() }}" class="page-btn {{ $kebutuhans->onFirstPage() ? 'disabled' : '' }}" title="Halaman Sebelumnya">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                    <span class="page-info">Halaman <strong>{{ $kebutuhans->currentPage() }}</strong> dari <strong>{{ $kebutuhans->lastPage() }}</strong></span>
                    <a href="{{ $kebutuhans->nextPageUrl() }}" class="page-btn {{ !$kebutuhans->hasMorePages() ? 'disabled' : '' }}" title="Halaman Selanjutnya">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                    <a href="{{ $kebutuhans->url($kebutuhans->lastPage()) }}" class="page-btn {{ !$kebutuhans->hasMorePages() ? 'disabled' : '' }}" title="Halaman Terakhir">
                        <i class="ri-double-right-line"></i>
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Modern SweetAlert Custom Styles */
    .modern-swal-popup {
        border-radius: 16px !important;
        padding: 24px !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    }
    .modern-swal-title {
        font-size: 20px !important;
        font-weight: 700 !important;
        color: #111827 !important;
    }
    div:where(.swal2-container) div:where(.swal2-html-container) {
        color: #4B5563 !important;
        font-size: 15px !important;
        margin-top: 12px !important;
    }
    .modern-swal-actions {
        margin-top: 24px !important;
        gap: 12px;
    }
    .modern-swal-btn {
        border-radius: 8px !important;
        font-weight: 600 !important;
        padding: 10px 24px !important;
        font-size: 14px !important;
        letter-spacing: 0.3px;
        transition: all 0.2s;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .modern-swal-btn.btn-danger {
        background-color: #DC2626 !important;
        color: white !important;
        border: none !important;
        box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2) !important;
    }
    .modern-swal-btn.btn-danger:hover { background-color: #B91C1C !important; transform: translateY(-1px); }
    .modern-swal-btn.btn-secondary {
        background-color: #F3F4F6 !important;
        color: #374151 !important;
        border: 1px solid #E5E7EB !important;
    }
    .modern-swal-btn.btn-secondary:hover { background-color: #E5E7EB !important; }

    /* ── Custom Select UI ───────────────────── */
    .custom-select-wrapper { position: relative; width: 100%; }
    
    .custom-select {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        cursor: pointer;
        position: relative;
        transition: all 0.2s ease;
        display: flex; align-items: center;
    }
    .custom-select:hover { border-color: #D1D5DB; }
    
    .custom-select.active {
        border-color: #B91C1C;
        box-shadow: 0 0 0 4px #FEF2F2;
        background: #fff;
    }

    .select-trigger {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 500;
        color: #374151;
        font-size: 14px;
        padding: 0 16px;
    }
    .select-trigger i { 
        color: #9CA3AF; 
        font-size: 20px; 
        transition: transform 0.2s ease; 
    }
    .custom-select.active .select-trigger { color: #111827; }
    .custom-select.active .select-trigger i { 
        transform: rotate(180deg); 
        color: #B91C1C; 
    }

    .custom-options {
        position: absolute;
        top: calc(100% + 8px);
        left: 0; right: 0;
        background: #fff;
        border: 1px solid #F3F4F6;
        border-radius: 16px;
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1);
        z-index: 2000;
        display: none;
        padding: 8px;
        text-align: left;
    }
    
    .options-scroll {
        max-height: 240px;
        overflow-y: auto;
        padding-right: 2px;
    }
    
    .options-scroll::-webkit-scrollbar { width: 4px; }
    .options-scroll::-webkit-scrollbar-track { background: transparent; }
    .options-scroll::-webkit-scrollbar-thumb { background-color: #E5E7EB; border-radius: 10px; }
    .options-scroll::-webkit-scrollbar-thumb:hover { background-color: #D1D5DB; }
    
    .option {
        padding: 10px 12px;
        cursor: pointer;
        transition: all 0.1s;
        font-size: 13px;
        color: #4B5563;
        border-radius: 8px;
        margin-bottom: 2px;
        font-weight: 500;
        display: flex; align-items: center; justify-content: space-between;
    }
    .option:last-child { margin-bottom: 0; }
    
    .option:hover {
        background-color: #F9FAFB;
        color: #111827;
    }
    
    .option.selected {
        background-color: #FEF2F2;
        color: #B91C1C;
        font-weight: 600;
    }

    .filter-select {
        width: 100%;
        height: 38px;
        padding: 0 12px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        background: var(--input-bg);
        color: var(--text-main);
        font-size: 13px;
        font-weight: 500;
        outline: none;
    }

    .filter-select:focus {
        border-color: #B91C1C;
        box-shadow: 0 0 0 4px #FEF2F2;
        background: #fff;
    }

    /* ── Table Footer (Pagination) ────────────────────── */
    .table-footer {
        display: flex; justify-content: space-between; align-items: center;
        padding: 16px 20px; border-top: 1px solid #F3F4F6;
        background: #fff; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;
    }
    .footer-left { font-size: 13px; color: #6B7280; }
    .pagination-controls { display: flex; align-items: center; gap: 4px; }
    .page-btn {
        width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; color: #4B5563; text-decoration: none; transition: all 0.2s; font-size: 18px;
    }
    .page-btn:hover:not(.disabled) { background: #F3F4F6; color: #111827; }
    .page-btn.disabled { color: #D1D5DB; cursor: not-allowed; pointer-events: none; }
    .page-info {
        font-size: 13px; color: #4B5563; padding: 0 12px;
        display: flex; align-items: center; height: 32px; border-radius: 8px; background: #F9FAFB;
    }
    .page-info strong { color: #111827; font-weight: 600; margin: 0 4px; }
</style>

<style>
    /* ── Top Items List ── */
    .top-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border-color); }
    .top-item:last-child { border-bottom: none; }
    .top-rank { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; flex-shrink: 0; }
    .top-rank.gold { background: #fef3c7; color: #92400e; }
    .top-rank.silver { background: #e2e8f0; color: #475569; }
    .top-rank.bronze { background: #fed7aa; color: #9a3412; }
    .top-rank.normal { background: var(--slate-100); color: var(--text-muted); }
    .top-info { flex: 1; min-width: 0; }
    .top-name { font-size: 13px; font-weight: 600; color: var(--text-main); line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .top-count { font-size: 11px; color: var(--text-muted); margin-top: 2px; white-space: nowrap; }

    /* ── View More Link ── */
    .view-more-link {
        display: flex; align-items: center; justify-content: center; gap: 6px;
        padding: 10px 0 2px; margin-top: 4px; border-top: 1px dashed var(--border-color);
        font-size: 12px; font-weight: 600; cursor: pointer;
        text-decoration: none; transition: all .2s;
    }
    .view-more-link:hover { opacity: .8; }
    .view-more-link i { font-size: 14px; }

    /* ── Detail Modal ── */
    .detail-modal-overlay {
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,.45); backdrop-filter: blur(4px);
        display: none; align-items: center; justify-content: center; padding: 20px;
    }
    .detail-modal-overlay.active { display: flex; }
    .detail-modal {
        background: var(--bg-card); border-radius: 16px; width: 100%; max-width: 560px;
        max-height: 85vh; display: flex; flex-direction: column;
        box-shadow: 0 25px 50px rgba(0,0,0,.2); overflow: hidden;
    }
    .detail-modal-header {
        display: flex; align-items: center; gap: 12px; padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
    }
    .detail-modal-header .modal-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 20px;
    }
    .detail-modal-header .modal-title { font-size: 16px; font-weight: 700; color: var(--text-main); }
    .detail-modal-header .modal-subtitle { font-size: 12px; color: var(--text-muted); }
    .detail-modal-close {
        margin-left: auto; width: 32px; height: 32px; border-radius: 8px;
        border: 1px solid var(--border-color); background: transparent;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 18px; color: var(--text-muted); transition: all .2s;
    }
    .detail-modal-close:hover { background: var(--slate-100); color: var(--text-main); }
    .detail-modal-body { padding: 16px 24px; overflow-y: auto; flex: 1; }
    .detail-modal-body .modal-item {
        display: flex; align-items: center; gap: 12px; padding: 10px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .detail-modal-body .modal-item:last-child { border-bottom: none; }
    .detail-modal-body .modal-item-name { flex: 1; font-size: 13px; font-weight: 600; color: var(--text-main); line-height: 1.4; }
    .detail-modal-body .modal-item-pct { font-size: 14px; font-weight: 800; min-width: 55px; text-align: right; }
    .top-pct { font-size: 14px; font-weight: 800; min-width: 50px; text-align: right; }
    .top-bar-mini { width: 80px; height: 6px; background: var(--slate-100); border-radius: 99px; overflow: hidden; }
    .top-bar-mini-fill { height: 100%; border-radius: 99px; }

    /* ── Per-Category Cards Grid ── */
    .category-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }
    .cat-top-card { border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-card); overflow: hidden; }
    .cat-top-card-head {
        display: flex; align-items: center; gap: 10px; padding: 14px 20px;
        border-bottom: 1px solid var(--border-color);
    }
    .cat-top-card-head .cat-icon {
        width: 36px; height: 36px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 18px;
    }
    .cat-top-card-head .cat-title { font-size: 14px; font-weight: 700; color: var(--text-main); }
    .cat-top-card-head .cat-count { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 99px; margin-left: auto; }
    .cat-top-card-body { padding: 8px 20px 16px; }
    .btn-export-pdf {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 38px;
        padding: 0 14px;
        border-radius: 10px;
        background: #B91C1C;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
    }
    .btn-export-pdf:hover { background: #991B1B; color: #fff; }

    @media (max-width: 768px) {
        .stats-row { grid-template-columns: 1fr !important; }
        .category-cards-grid { grid-template-columns: 1fr !important; }
        .responsive-filter { flex-direction: column !important; align-items: stretch !important; }
        .responsive-filter > * { width: 100% !important; flex: none !important; margin-bottom: 8px; }
        .responsive-filter > .btn { justify-content: center; }
        .table-wrap { overflow-x: auto !important; -webkit-overflow-scrolling: touch; }
        .table-wrap table { min-width: 700px; }
        .top-item {
            display: grid !important;
            grid-template-columns: auto 1fr;
            gap: 4px 12px;
            padding: 12px 0;
            align-items: center;
        }
        .top-rank { grid-column: 1; grid-row: 1; align-self: flex-start; margin-top: 2px; }
        .top-info { grid-column: 2; grid-row: 1; min-width: 0; display: block; }
        .top-name { white-space: normal; line-height: 1.3; margin-bottom: 4px; display: block; overflow: visible; }
        .top-percent-wrap {
            grid-column: 2;
            grid-row: 2;
            width: 100%;
            justify-content: space-between !important;
            margin-top: 4px;
            background: transparent;
            padding: 0;
        }
        .top-bar-mini { flex: 1; margin-right: 12px; }
        .card { min-width: 0; }
    }
</style>
@endsection

@section('scripts')
<!-- SweetAlert2 Plugin -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ── Category detail data (from server) ──
    @php
        $modalData = [];
        foreach ($itemStatsByCategory as $cat => $catItems) {
            $modalData[$cat] = $catItems->map(function($stat, $idx) {
                return [
                    'rank' => $idx + 1,
                    'name' => $stat['item_name'],
                    'percentage' => $stat['percentage'],
                    'count' => $stat['satker_count'],
                ];
            })->values()->toArray();
        }
    @endphp
    const categoryData = @json($modalData);

    const catStyles = {
        'Tutup_Kepala': { icon: 'ri-shirt-line', color: '#3b82f6', bg: '#dbeafe' },
        'Tutup_Badan':  { icon: 'ri-t-shirt-line', color: '#f59e0b', bg: '#fef3c7' },
        'Tutup_Kaki':   { icon: 'ri-footprint-line', color: '#10b981', bg: '#d1fae5' },
    };
    const defaultCatStyle = { icon: 'ri-box-3-line', color: '#8b5cf6', bg: '#ede9fe' };

    function openDetailModal(category) {
        const items = categoryData[category] || [];
        const style = catStyles[category] || defaultCatStyle;
        const displayName = category.replace(/_/g, ' ');

        document.getElementById('modalIcon').innerHTML = `<i class="${style.icon}"></i>`;
        document.getElementById('modalIcon').style.background = style.bg;
        document.getElementById('modalIcon').style.color = style.color;
        document.getElementById('modalTitle').textContent = `Top ${items.length} — ${displayName}`;
        document.getElementById('modalSubtitle').textContent = `Persentase berdasarkan jumlah seluruh satker terdaftar`;

        let html = '';
        items.forEach(item => {
            const rankClass = item.rank === 1 ? 'gold' : (item.rank === 2 ? 'silver' : (item.rank === 3 ? 'bronze' : 'normal'));
            html += `
            <div class="modal-item">
                <div class="top-rank ${rankClass}">${item.rank}</div>
                <div class="modal-item-name">${item.name}<div style="font-size:11px;color:var(--text-muted);font-weight:500;margin-top:2px;">${item.count} satker memilih</div></div>
                <div class="modal-item-pct" style="color: ${style.color};">${item.percentage}%</div>
            </div>`;
        });
        document.getElementById('modalBody').innerHTML = html;
        document.getElementById('detailModalOverlay').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDetailModal() {
        document.getElementById('detailModalOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }

    // Close on Escape key
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDetailModal(); });

    // ── Dropdown Logic ──
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.custom-select')) {
            document.querySelectorAll('.custom-options').forEach(opt => opt.style.display = 'none');
            document.querySelectorAll('.custom-select').forEach(sel => sel.classList.remove('active'));
        }
    });

    function toggleDropdown(el) {
        const options = el.querySelector('.custom-options');
        const isOpen = options.style.display === 'block';

        document.querySelectorAll('.custom-options').forEach(opt => opt.style.display = 'none');
        document.querySelectorAll('.custom-select').forEach(sel => sel.classList.remove('active'));

        if (!isOpen) {
            options.style.display = 'block';
            el.classList.add('active');
        } 
        
        event.stopPropagation();
    }

    function selectOptionSearch(el, inputName, value, label) {
        const wrapper = el.closest('.custom-select-wrapper');
        const trigger = wrapper.querySelector('.select-trigger span');
        const input = wrapper.querySelector('input[type="hidden"]');
        
        trigger.innerText = label;
        input.value = value;
        
        document.getElementById('filterForm').submit();
    }

    // ── Delete confirmation ──
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.btn-delete-kebutuhan');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const form = this.closest('form');
                Swal.fire({
                    title: 'Hapus Pengajuan?',
                    text: 'Apakah Anda yakin ingin menghapus data pengajuan ini secara permanen?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DC2626',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: '<i class="ri-delete-bin-line" style="margin-right:4px;"></i> Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    customClass: {
                        popup: 'modern-swal-popup',
                        title: 'modern-swal-title',
                        confirmButton: 'modern-swal-btn btn-danger',
                        cancelButton: 'modern-swal-btn btn-secondary',
                        actions: 'modern-swal-actions'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endsection
