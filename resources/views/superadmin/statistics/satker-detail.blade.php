@extends('layouts.app')

@section('title', 'Detail Ulasan ' . $satker->name)
@section('breadcrumb', 'Detail Ulasan Satker')

@section('content')
    <div class="satker-review-page">
        <header class="review-page-head">
            <div class="review-title-group">
                <a href="{{ route('superadmin.statistics', ['year' => $fiscalYear]) }}" class="back-button"
                    title="Kembali ke statistik">
                    <i class="ri-arrow-left-line"></i>
                </a>
                <div>
                    <div class="review-eyebrow">REKAP SATKER · T.A. {{ $fiscalYear }}</div>
                    <h1>{{ $satker->name }}</h1>
                    <p>Personel dan rincian review item kapor yang tercatat pada satker ini.</p>
                </div>
            </div>

            <div class="review-head-actions">
                <div class="review-select-shell has-leading-icon">
                    <i class="ri-calendar-line"></i>
                    <select class="review-modern-select" aria-label="Pilih tahun anggaran"
                        onchange="window.location.href = this.value">
                        @foreach($availableYears as $year)
                            <option value="{{ route('superadmin.statistics.satkers.show', ['satker' => $satker, 'year' => $year]) }}"
                                {{ (int) $year === $fiscalYear ? 'selected' : '' }}>
                                T.A. {{ $year }} {{ (int) $year === $activeYear ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ route('superadmin.statistics.satkers.detail.export-pdf', array_merge(['satker' => $satker, 'year' => $fiscalYear], array_filter($filters))) }}"
                    class="pdf-button" target="_blank">
                    <i class="ri-file-pdf-2-line"></i>
                    Unduh PDF
                </a>
            </div>
        </header>

        <section class="review-metrics" aria-label="Ringkasan ulasan satker">
            <article class="review-metric">
                <span class="metric-icon red"><i class="ri-chat-check-line"></i></span>
                <div>
                    <span>Total Ulasan</span>
                    <strong>{{ number_format(data_get($satkerSummary, 'total_feedback', 0)) }}</strong>
                </div>
            </article>
            <article class="review-metric">
                <span class="metric-icon blue"><i class="ri-group-line"></i></span>
                <div>
                    <span>Personel Merespons</span>
                    <strong>{{ number_format(data_get($satkerSummary, 'respondent_count', 0)) }}</strong>
                </div>
            </article>
            <article class="review-metric">
                <span class="metric-icon amber"><i class="ri-star-line"></i></span>
                <div>
                    <span>Rata-rata Bintang</span>
                    <strong>{{ data_get($satkerSummary, 'average_rating') !== null ? number_format(data_get($satkerSummary, 'average_rating'), 1) : '-' }}</strong>
                </div>
            </article>
            <article class="review-metric">
                <span class="metric-icon orange"><i class="ri-truck-line"></i></span>
                <div>
                    <span>Belum Menerima</span>
                    <strong>{{ number_format(data_get($satkerSummary, 'not_received_count', 0)) }}</strong>
                </div>
            </article>
        </section>

        <form method="GET" action="{{ route('superadmin.statistics.satkers.show', $satker) }}" class="review-filters">
            <input type="hidden" name="year" value="{{ $fiscalYear }}">
            <label class="search-control">
                <i class="ri-search-line"></i>
                <input type="search" name="search" value="{{ $filters['search'] }}"
                    placeholder="Cari nama, NRP/NIP, item, atau komentar">
            </label>
            <label class="review-filter-field">
                <span>Status</span>
                <div class="review-select-shell">
                    <select name="response_status" class="review-modern-select" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach(\App\Models\ItemReview::RESPONSE_STATUSES as $value => $label)
                            <option value="{{ $value }}" {{ $filters['response_status'] === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </label>
            <label class="review-filter-field">
                <span>Rating</span>
                <div class="review-select-shell">
                    <select name="rating" class="review-modern-select" aria-label="Filter rating">
                        <option value="">Semua rating</option>
                        @for($rating = 5; $rating >= 1; $rating--)
                            <option value="{{ $rating }}" {{ (int) $filters['rating'] === $rating ? 'selected' : '' }}>
                                {{ $rating }} bintang
                            </option>
                        @endfor
                    </select>
                </div>
            </label>
            <button type="submit" class="filter-button"><i class="ri-equalizer-2-line"></i> Terapkan</button>
            @if($filters['search'] !== '' || $filters['response_status'] !== '' || $filters['rating'] !== null)
                <a href="{{ route('superadmin.statistics.satkers.show', ['satker' => $satker, 'year' => $fiscalYear]) }}"
                    class="reset-button" title="Hapus filter"><i class="ri-close-line"></i></a>
            @endif
        </form>

        <section class="review-table-panel">
            <div class="table-panel-head">
                <div>
                    <h2>Daftar Personel dan Ulasan</h2>
                    <p>Menampilkan {{ number_format($reviews->total()) }} data sesuai filter.</p>
                </div>
            </div>

            <div class="review-table-scroll">
                <table class="review-table">
                    <thead>
                        <tr>
                            <th>Personel</th>
                            <th>Item Kapor</th>
                            <th>Status</th>
                            <th>Rating</th>
                            <th>Catatan</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $review)
                            @php
                                $personnelName = $review->user?->name ?? $review->allocation?->full_name_snapshot ?? 'Personel';
                                $nrp = $review->user?->nrp_nip ?? $review->allocation?->nrp_snapshot ?? '-';
                                $itemName = $review->allocation?->kapor_item_name_snapshot ?? $review->kaporItem?->item_name ?? 'Item Kapor';
                                $submittedAt = $review->submitted_at ?? $review->created_at;
                            @endphp
                            <tr>
                                <td>
                                    <div class="personnel-cell">
                                        <span class="personnel-avatar">{{ strtoupper(mb_substr($personnelName, 0, 1)) }}</span>
                                        <div>
                                            <strong>{{ $personnelName }}</strong>
                                            <small>{{ $nrp }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong class="item-name">{{ $itemName }}</strong>
                                    <small class="cell-muted">{{ $review->category_label }}</small>
                                </td>
                                <td>
                                    <span class="status-badge {{ $review->response_status === \App\Models\ItemReview::STATUS_REVIEWED ? 'received' : 'pending' }}">
                                        {{ $review->response_label }}
                                    </span>
                                </td>
                                <td>
                                    @if($review->rating)
                                        <span class="rating-value"><i class="ri-star-fill"></i> {{ $review->rating }}/5</span>
                                    @else
                                        <span class="cell-muted">-</span>
                                    @endif
                                </td>
                                <td><span class="comment-text">{{ $review->display_message }}</span></td>
                                <td><span class="date-value">{{ $submittedAt?->translatedFormat('d M Y') ?? '-' }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="review-empty">
                                        <i class="ri-search-eye-line"></i>
                                        <strong>Data tidak ditemukan</strong>
                                        <span>Ubah pencarian atau filter untuk melihat data lainnya.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($reviews->hasPages())
                <div class="review-pagination">
                    <span>Halaman {{ $reviews->currentPage() }} dari {{ $reviews->lastPage() }}</span>
                    <div>
                        <a href="{{ $reviews->previousPageUrl() ?? '#' }}" class="page-arrow {{ $reviews->onFirstPage() ? 'disabled' : '' }}"
                            title="Halaman sebelumnya"><i class="ri-arrow-left-s-line"></i></a>
                        <a href="{{ $reviews->nextPageUrl() ?? '#' }}" class="page-arrow {{ $reviews->hasMorePages() ? '' : 'disabled' }}"
                            title="Halaman berikutnya"><i class="ri-arrow-right-s-line"></i></a>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection

@section('styles')
    @include('components.review-select-styles')
    <style>
        .satker-review-page { display: grid; gap: 20px; font-family: 'Outfit', sans-serif; }
        .review-page-head { display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .review-title-group { display: flex; align-items: flex-start; gap: 14px; min-width: 0; }
        .back-button, .reset-button, .page-arrow { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; background: #fff; color: #334155; text-decoration: none; border-radius: 8px; flex: 0 0 auto; }
        .back-button:hover, .reset-button:hover, .page-arrow:hover { color: #b91c1c; border-color: #fecaca; background: #fff7f7; }
        .review-eyebrow { color: #b91c1c; font-size: 11px; font-weight: 800; letter-spacing: .08em; }
        .review-title-group h1 { margin: 4px 0 3px; color: #0f172a; font-size: 26px; line-height: 1.2; }
        .review-title-group p { margin: 0; color: #64748b; font-size: 14px; }
        .review-head-actions { display: flex; align-items: center; gap: 10px; }
        .pdf-button { height: 42px; display: inline-flex; align-items: center; gap: 8px; padding: 0 14px; border-radius: 8px; background: #b91c1c; color: #fff; text-decoration: none; font-size: 13px; font-weight: 700; }
        .pdf-button:hover { background: #991b1b; color: #fff; }
        .review-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .review-metric { display: flex; align-items: center; gap: 12px; padding: 16px; border: 1px solid #e5e7eb; background: #fff; border-radius: 8px; box-shadow: 0 4px 14px rgba(15, 23, 42, .035); }
        .review-metric div { display: grid; gap: 3px; }
        .review-metric span:not(.metric-icon) { color: #64748b; font-size: 12px; font-weight: 600; }
        .review-metric strong { color: #0f172a; font-size: 22px; }
        .metric-icon { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 19px; }
        .metric-icon.red { background: #fef2f2; color: #b91c1c; }
        .metric-icon.blue { background: #eff6ff; color: #2563eb; }
        .metric-icon.amber { background: #fffbeb; color: #d97706; }
        .metric-icon.orange { background: #fff7ed; color: #ea580c; }
        .review-filters { display: grid; grid-template-columns: minmax(260px, 1fr) 180px 160px auto auto; align-items: end; gap: 10px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
        .search-control { height: 42px; display: flex; align-items: center; gap: 9px; padding: 0 12px; border: 1px solid #dbe2ea; border-radius: 7px; color: #94a3b8; }
        .search-control:focus-within { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220, 38, 38, .08); }
        .search-control input { width: 100%; border: 0; outline: 0; color: #1e293b; font-size: 13px; }
        .filter-button { height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; border: 0; border-radius: 7px; padding: 0 14px; background: #1e293b; color: #fff; font-weight: 700; cursor: pointer; }
        .review-table-panel { border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; overflow: hidden; }
        .table-panel-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px; border-bottom: 1px solid #eef2f7; }
        .table-panel-head h2 { margin: 0; color: #0f172a; font-size: 17px; }
        .table-panel-head p { margin: 3px 0 0; color: #64748b; font-size: 12px; }
        .review-table-scroll { overflow-x: auto; }
        .review-table { width: 100%; min-width: 980px; border-collapse: collapse; }
        .review-table th { padding: 11px 14px; background: #f8fafc; color: #64748b; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 10px; text-transform: uppercase; }
        .review-table td { padding: 13px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 12px; vertical-align: middle; }
        .review-table tr:last-child td { border-bottom: 0; }
        .personnel-cell { display: flex; align-items: center; gap: 10px; min-width: 210px; }
        .personnel-avatar { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: #fef2f2; color: #b91c1c; font-weight: 800; }
        .personnel-cell div { display: grid; gap: 2px; }
        .personnel-cell strong, .item-name { color: #172033; font-size: 12px; }
        .personnel-cell small, .cell-muted { display: block; color: #8491a3; font-size: 11px; margin-top: 2px; }
        .status-badge { display: inline-flex; padding: 5px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; white-space: nowrap; }
        .status-badge.received { background: #ecfdf5; color: #047857; }
        .status-badge.pending { background: #fff7ed; color: #c2410c; }
        .rating-value { color: #b45309; font-weight: 800; white-space: nowrap; }
        .comment-text { display: block; max-width: 320px; line-height: 1.55; color: #475569; }
        .date-value { white-space: nowrap; color: #64748b; }
        .review-empty { display: grid; justify-items: center; gap: 5px; padding: 42px; color: #94a3b8; }
        .review-empty i { font-size: 28px; }
        .review-empty strong { color: #475569; }
        .review-pagination { display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; border-top: 1px solid #eef2f7; color: #64748b; font-size: 12px; }
        .review-pagination div { display: flex; gap: 6px; }
        .page-arrow { width: 34px; height: 34px; }
        .page-arrow.disabled { pointer-events: none; opacity: .4; }
        @media (max-width: 1100px) { .review-metrics { grid-template-columns: repeat(2, 1fr); } .review-filters { grid-template-columns: 1fr 1fr; } .search-control { grid-column: 1 / -1; } }
        @media (max-width: 700px) { .review-page-head { align-items: flex-start; flex-direction: column; } .review-head-actions { width: 100%; flex-wrap: wrap; } .review-metrics, .review-filters { grid-template-columns: 1fr; } .search-control { grid-column: auto; } .pdf-button { flex: 1; justify-content: center; } }
    </style>
@endsection

@section('scripts')
    @include('components.review-select-script')
@endsection
