@extends('layouts.app')

@section('title', 'Analisis Duplikasi - ' . $budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-year', $budgetPackage->budgetYear) }}">{{ $budgetPackage->budgetYear->name }}</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}">{{ $budgetPackage->name }}</a>
    <span class="sep">/</span>
    <span class="current">Analisis Duplikasi</span>
@endsection

@section('content')
{{-- Hero Section --}}
<div class="recap-hero">
    <div class="recap-hero-inner">
        <a href="{{ route('admin.budget.show-package', $budgetPackage) }}" class="btn-back">
            <i class="ri-arrow-left-line"></i>
        </a>
        <div class="recap-hero-content">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div>
                    <h1 class="recap-title"><i class="ri-user-shared-line" style="margin-right: 8px;"></i>Analisis Duplikasi Personil</h1>
                    <p class="recap-desc">
                        Mendeteksi personil yang akan menerima lebih dari 1 barang dalam paket <strong>{{ $budgetPackage->name }}</strong>
                    </p>
                </div>
                <a href="{{ route('admin.budget.show-package', $budgetPackage) }}" class="btn-back-link">
                    <i class="ri-arrow-left-line"></i> Kembali ke Paket
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Statistik Ringkasan --}}
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #EFF6FF; color: #2563EB;">
            <i class="ri-shopping-bag-3-line"></i>
        </div>
        <div>
            <div class="stat-label">Total Barang</div>
            <div class="stat-number">{{ $totalItems }}</div>
        </div>
    </div>
    <div class="stat-card {{ $totalDuplicates > 0 ? 'warning-card' : 'success-card' }}">
        <div class="stat-icon" style="background: {{ $totalDuplicates > 0 ? '#FEF3C7' : '#D1FAE5' }}; color: {{ $totalDuplicates > 0 ? '#D97706' : '#059669' }};">
            <i class="{{ $totalDuplicates > 0 ? 'ri-alert-line' : 'ri-shield-check-line' }}"></i>
        </div>
        <div>
            <div class="stat-label">Personil Duplikat</div>
            <div class="stat-number" style="color: {{ $totalDuplicates > 0 ? '#D97706' : '#059669' }};">{{ number_format($totalDuplicates, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="stat-card grand-total-card">
        <div class="stat-icon" style="background: #FEF2F2; color: #C62828;">
            <i class="ri-money-dollar-circle-line"></i>
        </div>
        <div>
            <div class="stat-label">Total Anggaran</div>
            <div class="stat-number" style="color: #C62828;">Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

{{-- Panel Analisis Duplikasi --}}
<div class="analysis-section">
    <div class="section-header">
        <div class="section-header-left">
            <div class="section-icon {{ $totalDuplicates > 0 ? 'icon-warning' : 'icon-success' }}">
                <i class="{{ $totalDuplicates > 0 ? 'ri-user-shared-line' : 'ri-shield-check-line' }}"></i>
            </div>
            <div>
                <h2 class="section-title">Daftar Personil Duplikat</h2>
                <p class="section-subtitle">Personil yang akan menerima lebih dari 1 barang dalam paket ini</p>
            </div>
        </div>
        @if($totalDuplicates > 0)
        <div class="section-badge warning-badge">
            <i class="ri-alert-line"></i> {{ $totalDuplicates }} personil
        </div>
        @else
        <div class="section-badge success-badge">
            <i class="ri-check-line"></i> Tidak ada duplikasi
        </div>
        @endif
    </div>

    @if($totalDuplicates > 0)
    <div class="duplicate-list">
        @foreach($duplicates as $idx => $dup)
        <div class="duplicate-card" id="dup-{{ $idx }}">
            <div class="dup-header" onclick="document.getElementById('dup-{{ $idx }}').classList.toggle('expanded')">
                <div class="dup-info">
                    <div class="dup-number">{{ $idx + 1 }}</div>
                    <div class="dup-person">
                        <div class="dup-name">{{ $dup['personnel']->full_name }}</div>
                        <div class="dup-meta">
                            <span><i class="ri-id-card-line"></i> {{ $dup['personnel']->nrp ?? '-' }}</span>
                            <span><i class="ri-building-2-line"></i> {{ $dup['personnel']->satker->name ?? '-' }}</span>
                            <span><i class="ri-briefcase-line"></i> {{ $dup['personnel']->personnel_type }}</span>
                            @if($dup['personnel']->rank)
                            <span><i class="ri-medal-line"></i> {{ $dup['personnel']->rank->name }}</span>
                            @endif
                            @if($dup['personnel']->keterangan)
                            <span><i class="ri-information-line"></i> {{ $dup['personnel']->keterangan }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="dup-count-wrap">
                    <span class="dup-count-badge">{{ $dup['total_items'] }} barang</span>
                    <i class="ri-arrow-down-s-line dup-chevron"></i>
                </div>
            </div>
            <div class="dup-body">
                <div class="dup-items-table">
                    <div class="dup-items-header">
                        <span style="flex: 2;">Nama Barang</span>
                        <span style="flex: 1;">Kategori</span>
                        <span style="flex: 1.5;">Satker Penerima</span>
                        <span style="flex: 2;">Filter</span>
                        <span style="flex: 1; text-align: right;">Harga</span>
                    </div>
                    @foreach($dup['items'] as $itemDetail)
                    <div class="dup-items-row">
                        <span class="item-col" style="flex: 2;">
                            <i class="ri-box-3-line" style="color: #94A3B8; margin-right: 6px;"></i>
                            {{ $itemDetail['item_name'] }}
                        </span>
                        <span class="item-col" style="flex: 1;">
                            <span class="cat-badge">{{ $itemDetail['category'] }}</span>
                        </span>
                        <span class="item-col" style="flex: 1.5;">
                            <i class="ri-building-2-line" style="color: #94A3B8; margin-right: 4px;"></i>
                            {{ $itemDetail['satker_name'] }}
                        </span>
                        <span class="item-col" style="flex: 2;">
                            @if(count($itemDetail['filters']) > 0)
                                @foreach($itemDetail['filters'] as $fl)
                                <span class="filter-pill">{{ $fl }}</span>
                                @endforeach
                            @else
                                <span style="color: #94A3B8; font-size: 12px;">Semua</span>
                            @endif
                        </span>
                        <span class="item-col" style="flex: 1; text-align: right; font-weight: 600; color: #C62828;">
                            Rp {{ number_format($itemDetail['price'] ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <div class="empty-icon">
            <i class="ri-shield-check-fill"></i>
        </div>
        <h3>Tidak Ada Duplikasi</h3>
        <p>Semua personil dalam paket ini hanya menerima maksimal 1 barang. Data terlihat aman.</p>
    </div>
    @endif
</div>

{{-- Rincian Paket --}}
<div class="premium-card" style="margin-top: 24px;">
    <div class="card-header border-b">
        <div>
            <h3 class="card-title"><i class="ri-file-list-3-line" style="margin-right: 8px; color: #C62828;"></i>Rincian Paket</h3>
            <p class="card-subtitle">Daftar item dan estimasi kuantitas yang dibutuhkan</p>
        </div>
    </div>
    <div class="card-body flush">
        <div class="table-wrap">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">NO</th>
                        <th>NAMA BARANG</th>
                        <th>KATEGORI</th>
                        <th style="text-align: right;">HARGA</th>
                        <th style="text-align: center;">QTY</th>
                        <th style="text-align: right;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budgetPackage->items as $idx => $item)
                    <tr>
                        <td class="text-secondary">{{ $idx + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $item->kaporItem->item_name }}</div>
                            @if($item->recipients->count() > 0)
                            <div class="item-satker-list">
                                @foreach($item->recipients as $r)
                                    <span>{{ $r->satker->name }} ({{ $r->matched_count }})</span>{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td><span class="badge badge-neutral badge-sm">{{ $item->kaporItem->category }}</span></td>
                        <td style="text-align: right;" class="font-medium">{{ $item->formatted_price }}</td>
                        <td style="text-align: center;">
                            <span class="qty-badge">{{ number_format($item->calculated_qty) }}</span>
                        </td>
                        <td style="text-align: right;" class="text-red font-bold">{{ $item->formatted_total }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="grand-total-row">
                        <td colspan="4" class="text-right">GRAND TOTAL</td>
                        <td class="text-center">{{ number_format($totalRecipients) }}</td>
                        <td class="text-right">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@section('styles')
    /* ── Recap Hero ── */
    .recap-hero {
        background: #ffffff; border-radius: 16px; padding: 24px;
        margin-bottom: 24px; border: 1px solid #E2E8F0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        position: relative; overflow: hidden;
    }
    .recap-hero::before {
        content: ''; position: absolute; top: 0; left: 0;
        width: 100%; height: 4px;
        background: linear-gradient(90deg, #D97706, #F59E0B, #FBBF24);
    }
    .recap-hero-inner { display: flex; align-items: flex-start; gap: 16px; }
    .btn-back {
        display: flex; align-items: center; justify-content: center;
        width: 40px; height: 40px; background: #F8FAFC;
        border: 1px solid #E2E8F0; border-radius: 12px;
        color: #475569; font-size: 20px; transition: all 0.2s; text-decoration: none;
    }
    .btn-back:hover { background: #D97706; color: #fff; border-color: #D97706; transform: translateX(-2px); }
    .recap-hero-content { flex: 1; }
    .recap-title { font-size: 22px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; }
    .recap-desc { color: #64748B; font-size: 14px; margin: 6px 0 0; }
    .btn-back-link {
        display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
        border-radius: 8px; background: #F1F5F9; color: #475569;
        font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.2s;
    }
    .btn-back-link:hover { background: #E2E8F0; color: #0F172A; }

    /* ── Stat Grid ── */
    .stat-grid {
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 16px; margin-bottom: 24px;
    }
    .stat-card {
        background: #ffffff; border-radius: 16px; padding: 20px 24px;
        display: flex; align-items: center; gap: 16px;
        border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        transition: all 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03); }
    .stat-card.grand-total-card { border-color: #FECACA; background: #FEF2F2; }
    .stat-card.warning-card { border-color: #FDE68A; background: #FFFBEB; }
    .stat-card.success-card { border-color: #A7F3D0; background: #ECFDF5; }
    .stat-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; flex-shrink: 0;
    }
    .stat-label { font-size: 13px; font-weight: 600; color: #64748B; margin-bottom: 2px; }
    .stat-number { font-size: 22px; font-weight: 800; color: #0F172A; line-height: 1.2; }

    /* ── Analysis Section ── */
    .analysis-section {
        background: #fff; border: 1px solid #E2E8F0; border-radius: 16px;
        overflow: hidden;
    }
    .section-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 20px 24px; border-bottom: 1px solid #F1F5F9;
    }
    .section-header-left { display: flex; align-items: center; gap: 14px; }
    .section-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 20px;
    }
    .section-icon.icon-warning { background: #FEF3C7; color: #D97706; }
    .section-icon.icon-success { background: #D1FAE5; color: #059669; }
    .section-title { font-size: 16px; font-weight: 700; color: #0F172A; margin: 0; }
    .section-subtitle { font-size: 13px; color: #64748B; margin: 2px 0 0; }
    .section-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;
    }
    .warning-badge { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .success-badge { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }

    /* ── Duplicate List ── */
    .duplicate-list { padding: 16px 24px; display: flex; flex-direction: column; gap: 10px; }
    .duplicate-card {
        border: 1px solid #E2E8F0; border-radius: 12px;
        overflow: hidden; transition: all 0.2s;
    }
    .duplicate-card:hover { border-color: #CBD5E1; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
    .dup-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 14px 18px; cursor: pointer; user-select: none; background: #FAFBFC;
        transition: background 0.2s;
    }
    .dup-header:hover { background: #F1F5F9; }
    .dup-info { display: flex; align-items: center; gap: 14px; }
    .dup-number {
        width: 28px; height: 28px; border-radius: 8px;
        background: #F1F5F9; color: #64748B; font-size: 12px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .dup-name { font-size: 14px; font-weight: 700; color: #0F172A; }
    .dup-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 4px; }
    .dup-meta span { font-size: 11.5px; color: #64748B; display: inline-flex; align-items: center; gap: 4px; }
    .dup-meta i { font-size: 13px; color: #94A3B8; }
    .dup-count-wrap { display: flex; align-items: center; gap: 10px; }
    .dup-count-badge {
        background: #FEF3C7; color: #92400E; padding: 4px 12px;
        border-radius: 20px; font-size: 12px; font-weight: 700; border: 1px solid #FDE68A;
    }
    .dup-chevron { font-size: 18px; color: #94A3B8; transition: transform 0.2s; }
    .duplicate-card.expanded .dup-chevron { transform: rotate(180deg); }

    .dup-body { display: none; padding: 0; border-top: 1px solid #F1F5F9; }
    .duplicate-card.expanded .dup-body { display: block; }

    .dup-items-table { font-size: 12.5px; }
    .dup-items-header {
        display: flex; padding: 10px 18px; background: #F8FAFC;
        color: #64748B; font-weight: 600; font-size: 11px; text-transform: uppercase;
        letter-spacing: 0.5px; border-bottom: 1px solid #F1F5F9;
    }
    .dup-items-row {
        display: flex; padding: 12px 18px; align-items: center;
        border-bottom: 1px solid #F8FAFC; transition: background 0.15s;
    }
    .dup-items-row:last-child { border-bottom: none; }
    .dup-items-row:hover { background: #FAFBFC; }
    .item-col { font-size: 12.5px; color: #334155; font-weight: 500; display: flex; align-items: center; flex-wrap: wrap; gap: 4px; }
    .cat-badge {
        background: #F1F5F9; color: #475569; padding: 2px 8px;
        border-radius: 6px; font-size: 11px; font-weight: 600;
    }
    .filter-pill {
        background: #EFF6FF; color: #1D4ED8; padding: 2px 8px;
        border-radius: 12px; font-size: 10.5px; font-weight: 600;
    }

    /* ── Empty State ── */
    .empty-state { padding: 60px 24px; text-align: center; }
    .empty-icon {
        width: 72px; height: 72px; border-radius: 50%;
        background: #D1FAE5; color: #059669; font-size: 36px;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px;
    }
    .empty-state h3 { font-size: 18px; font-weight: 700; color: #0F172A; margin: 0 0 8px; }
    .empty-state p { font-size: 14px; color: #64748B; margin: 0; max-width: 400px; margin: 0 auto; }

    /* ── Premium Card & Table ── */
    .premium-card {
        background: #ffffff; border-radius: 16px;
        border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); overflow: hidden;
    }
    .card-header { padding: 20px 24px; }
    .card-header.border-b { border-bottom: 1px solid #F1F5F9; }
    .card-title { font-size: 16px; font-weight: 700; color: #0F172A; margin: 0 0 4px 0; }
    .card-subtitle { font-size: 13px; color: #64748B; margin: 0; }

    .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .modern-table th {
        background: #F8FAFC; color: #64748B; font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 24px;
        border-bottom: 1px solid #E2E8F0; text-align: left;
    }
    .modern-table td {
        padding: 16px 24px; font-size: 13px; color: #334155;
        border-bottom: 1px solid #F1F5F9; vertical-align: middle;
    }
    .modern-table tbody tr:hover { background: #F8FAFC; }
    .modern-table tbody tr:last-child td { border-bottom: none; }

    .item-name { font-weight: 600; color: #0F172A; font-size: 14px; }
    .item-satker-list { font-size: 11px; color: #94A3B8; margin-top: 4px; line-height: 1.4; }
    .qty-badge {
        display: inline-block; padding: 4px 12px; background: #FEF2F2; color: #C62828;
        border-radius: 20px; font-weight: 700; font-size: 12px;
    }
    .grand-total-row td {
        background: #FEF2F2; color: #C62828; font-weight: 800; font-size: 14px;
        border-top: 1px solid #FECACA; padding: 18px 24px;
    }

    /* Utilities */
    .text-secondary { color: #64748B; }
    .text-red { color: #C62828; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .font-medium { font-weight: 500; }
    .font-bold { font-weight: 700; }
    .badge-sm { padding: 2px 8px; font-size: 10px; border-radius: 6px; }
    .badge-neutral { background: #F3F4F6; color: #4B5563; }

    @media (max-width: 768px) {
        .stat-grid { grid-template-columns: 1fr; }
        .dup-items-header, .dup-items-row { flex-wrap: wrap; }
        .section-header { flex-direction: column; gap: 12px; align-items: flex-start; }
    }
@endsection
