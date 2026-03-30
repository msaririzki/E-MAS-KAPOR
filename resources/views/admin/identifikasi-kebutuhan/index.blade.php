@extends('layouts.app')

@section('title', 'Identifikasi Kebutuhan')
@section('breadcrumb', 'Identifikasi Kebutuhan')

@section('content')
<style>
    /* ── Category Summary Cards ── */
    .cat-card { padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-card); }
    .cat-card-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .cat-card-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .cat-card-title { font-size: 14px; font-weight: 700; color: var(--text-main); }
    .cat-card-subtitle { font-size: 11px; color: var(--text-muted); }
    .cat-bar { height: 8px; background: var(--slate-100); border-radius: 99px; overflow: hidden; margin-bottom: 8px; }
    .cat-bar-fill { height: 100%; border-radius: 99px; transition: width .8s ease; }
    .cat-card-stats { display: flex; justify-content: space-between; font-size: 12px; }
    .cat-card-stats .stat-num { font-weight: 700; color: var(--text-main); }
    .cat-card-stats .stat-lbl { color: var(--text-muted); }

    /* ── Top Items List ── */
    .top-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border-color); }
    .top-item:last-child { border-bottom: none; }
    .top-rank { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; flex-shrink: 0; }
    .top-rank.gold { background: #fef3c7; color: #92400e; }
    .top-rank.silver { background: #e2e8f0; color: #475569; }
    .top-rank.bronze { background: #fed7aa; color: #9a3412; }
    .top-rank.normal { background: var(--slate-100); color: var(--text-muted); }
    .top-info { flex: 1; min-width: 0; }
    .top-name { font-size: 13px; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .top-cat { font-size: 10px; padding: 1px 6px; border-radius: 3px; font-weight: 600; display: inline-block; margin-top: 2px; }
    .top-cat-Tutup_Kepala { background: #dbeafe; color: #1d4ed8; }
    .top-cat-Tutup_Badan { background: #fef3c7; color: #92400e; }
    .top-cat-Tutup_Kaki { background: #d1fae5; color: #065f46; }
    .top-pct { font-size: 14px; font-weight: 800; color: var(--brand); min-width: 50px; text-align: right; }
    .top-bar-mini { width: 80px; height: 6px; background: var(--slate-100); border-radius: 99px; overflow: hidden; }
    .top-bar-mini-fill { height: 100%; background: var(--brand); border-radius: 99px; }

    @media (max-width: 768px) {
        .stats-row { grid-template-columns: 1fr !important; }
        .responsive-filter { flex-direction: column !important; align-items: stretch !important; }
        .responsive-filter > * { width: 100% !important; flex: none !important; margin-bottom: 8px; }
        .responsive-filter > .btn { justify-content: center; }
        .table-wrap { overflow-x: auto !important; -webkit-overflow-scrolling: touch; }
        .table-wrap table { min-width: 700px; }
        
        /* Fix Top 10 Item agar padat namun tetap membungkus */
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
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Identifikasi Kebutuhan</h1>
            <p>Data pengajuan kebutuhan kapor dari seluruh satker.</p>
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
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Satker Mengajukan</span>
            <div class="stat-icon-sm" style="background: var(--success-bg); color: var(--success);"><i class="ri-building-line"></i></div>
        </div>
        <div class="stat-value">{{ $totalKebutuhans }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Item Diajukan</span>
            <div class="stat-icon-sm" style="background: var(--warning-bg); color: var(--warning);"><i class="ri-stack-line"></i></div>
        </div>
        <div class="stat-value">{{ \App\Models\KebutuhanItem::whereHas('kebutuhan', fn($q) => $q->whereIn('status', ['diajukan','disetujui']))->count() }}</div>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
    <div style="background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; font-size: 13px;">
        <i class="ri-checkbox-circle-fill"></i> {{ session('success') }}
    </div>
@endif

{{-- ═══ Compact Statistics Section ═══ --}}
@if($totalKebutuhans > 0)
<div class="grid-2" style="margin-bottom: 16px;">

    {{-- Category Coverage Cards --}}
    <div class="card">
        <div class="card-head"><h3><i class="ri-pie-chart-line" style="margin-right: 6px; color: var(--brand);"></i> Cakupan per Kategori</h3></div>
        <div class="card-body">
            @php
                $catColors = ['Tutup_Kepala' => '#3b82f6', 'Tutup_Badan' => '#f59e0b', 'Tutup_Kaki' => '#10b981'];
                $catIcons = ['Tutup_Kepala' => 'ri-shirt-line', 'Tutup_Badan' => 'ri-t-shirt-line', 'Tutup_Kaki' => 'ri-footprint-line'];
            @endphp
            <div style="display: flex; flex-direction: column; gap: 16px;">
                @foreach($categoryStats as $cat)
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="{{ $catIcons[$cat['category']] ?? 'ri-box-3-line' }}" style="font-size: 16px; color: {{ $catColors[$cat['category']] ?? '#64748b' }};"></i>
                            <span style="font-size: 13px; font-weight: 600;">{{ str_replace('_', ' ', $cat['category']) }}</span>
                        </div>
                        <span style="font-size: 13px; font-weight: 700; color: {{ $catColors[$cat['category']] ?? '#64748b' }};">
                            {{ $cat['unique_items'] }}/{{ $cat['total_in_category'] }} item
                        </span>
                    </div>
                    <div class="cat-bar">
                        <div class="cat-bar-fill" style="width: {{ $cat['coverage'] }}%; background: {{ $catColors[$cat['category']] ?? '#64748b' }};"></div>
                    </div>
                    <div style="font-size: 11px; color: var(--text-muted);">
                        {{ $cat['coverage'] }}% item dari kategori ini sudah diajukan · {{ $cat['total_requests'] }} total permintaan
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Top 10 Most Requested --}}
    <div class="card">
        <div class="card-head"><h3><i class="ri-trophy-line" style="margin-right: 6px; color: var(--brand);"></i> Top 10 Item Terpopuler</h3></div>
        <div class="card-body" style="padding-top: 4px;">
            @foreach($itemStats as $idx => $stat)
            <div class="top-item">
                <div class="top-rank {{ $idx === 0 ? 'gold' : ($idx === 1 ? 'silver' : ($idx === 2 ? 'bronze' : 'normal')) }}">
                    {{ $idx + 1 }}
                </div>
                <div class="top-info">
                    <div class="top-name" title="{{ $stat['item_name'] }}">{{ $stat['item_name'] }}</div>
                    <span class="top-cat top-cat-{{ $stat['category'] }}" style="margin-top: 4px;">{{ str_replace('_', ' ', $stat['category']) }}</span>
                </div>
                <div class="top-percent-wrap" style="display: flex; align-items: center; gap: 8px;">
                    <div class="top-bar-mini"><div class="top-bar-mini-fill" style="width: {{ $stat['percentage'] }}%;"></div></div>
                    <div class="top-pct">{{ $stat['percentage'] }}%</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Filters --}}
<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="padding: 14px 20px;">
        <form method="GET" action="{{ route('admin.identifikasi-kebutuhan.index') }}" class="responsive-filter" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul / satker..." class="search-input" style="width: 100%;">
            </div>
            <select name="satker_id" style="padding: 7px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 13px; font-family: inherit; background: var(--input-bg); color: var(--text-main); min-width: 160px;">
                <option value="">Semua Satker</option>
                @foreach($satkers as $satker)
                    <option value="{{ $satker->id }}" {{ request('satker_id') == $satker->id ? 'selected' : '' }}>{{ $satker->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-search-line"></i> Filter</button>
            @if(request()->hasAny(['search', 'satker_id']))
                <a href="{{ route('admin.identifikasi-kebutuhan.index') }}" class="btn btn-ghost btn-sm"><i class="ri-refresh-line"></i> Reset</a>
            @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-head"><h3>Daftar Pengajuan Kebutuhan</h3></div>
    <div class="card-body flush table-wrap">
        <table>
            <thead>
                <tr>
                    <th width="50" style="text-align: center;">No</th>
                    <th>Judul Pengajuan</th>
                    <th>Satker</th>
                    <th>Pengaju</th>
                    <th style="text-align: center;">Jumlah Item</th>
                    <th>Tanggal</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kebutuhans as $index => $k)
                <tr>
                    <td style="text-align: center;">{{ $kebutuhans->firstItem() + $index }}</td>
                    <td>
                        <a href="{{ route('admin.identifikasi-kebutuhan.show', $k) }}" style="text-decoration: none; color: inherit;">
                            <div class="cell-name" style="color: var(--brand);">{{ $k->title }}</div>
                        </a>
                        @if($k->notes)
                            <div class="cell-sub">{{ Str::limit($k->notes, 50) }}</div>
                        @endif
                    </td>
                    <td><span style="font-size: 12px;">{{ $k->satker->name ?? '-' }}</span></td>
                    <td><span style="font-size: 12px;">{{ $k->user->name ?? '-' }}</span></td>
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
                    <td colspan="7" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                        <i class="ri-file-list-3-line" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                        Belum ada pengajuan kebutuhan dari satker.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($kebutuhans->hasPages())
<div style="display: flex; justify-content: center; margin-top: 16px;">
    {{ $kebutuhans->links('pagination::simple-default') }}
</div>
@endif
@endsection

@section('scripts')
<!-- SweetAlert2 Plugin -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
</style>
@endsection
