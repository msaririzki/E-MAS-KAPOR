@extends('layouts.app')

@section('title', 'Preview Import Keterangan')
@section('breadcrumb', 'Preview Import Keterangan')

@section('styles')
<style>
    .ket-page { display: grid; gap: 20px; }
    .ket-hero, .ket-panel, .ket-group { background: #fff; border: 1px solid #E2E8F0; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.04); }
    .ket-hero { border-radius: 24px; padding: 28px; background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 52%, #EFF6FF 100%); }
    .ket-panel, .ket-group { border-radius: 20px; overflow: hidden; }
    .ket-hero-top, .ket-panel-head, .ket-group-head { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; align-items: flex-start; }
    .ket-filter-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(320px, 420px); gap: 18px; align-items: start; }
    .ket-eyebrow, .ket-chip, .ket-badge, .ket-status, .ket-pill { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; font-weight: 800; }
    .ket-eyebrow { padding: 7px 12px; background: rgba(37, 99, 235, 0.10); color: #1D4ED8; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 14px; }
    .ket-title { margin: 0; font-size: clamp(24px, 3vw, 32px); line-height: 1.1; font-weight: 900; letter-spacing: -.03em; color: #0F172A; }
    .ket-subtitle { max-width: 760px; margin: 12px 0 0; font-size: 14px; line-height: 1.75; color: #475569; }
    .ket-chip-row, .ket-badges, .ket-pills, .ket-legend { display: flex; gap: 10px; flex-wrap: wrap; }
    .ket-chip { padding: 10px 14px; background: rgba(255,255,255,.88); border: 1px solid rgba(148,163,184,.2); color: #334155; font-size: 12px; }
    .ket-chip strong { color: #0F172A; font-size: 13px; }
    .ket-hero-copy { flex: 1; min-width: min(100%, 520px); }
    .ket-actions { min-width: min(100%, 300px); width: 380px; padding: 18px; border-radius: 22px; background: rgba(255,255,255,.92); border: 1px solid rgba(148,163,184,.18); box-shadow: 0 16px 32px rgba(15, 23, 42, 0.05); align-self: stretch; }
    .ket-actions h2, .ket-panel-title, .ket-group-name { margin: 0; color: #0F172A; font-weight: 800; }
    .ket-actions h2 { font-size: 14px; }
    .ket-actions p, .ket-panel-subtitle, .ket-meta, .ket-empty, .ket-muted { color: #64748B; font-size: 12px; line-height: 1.7; }
    .ket-action-grid { display: grid; gap: 10px; margin-top: 14px; }
    .ket-action-grid .btn { min-height: 42px; justify-content: center; border-radius: 12px; font-weight: 800; }
    .ket-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; }
    .ket-stat { position: relative; padding: 18px 20px; border-radius: 20px; background: #fff; border: 1px solid #E2E8F0; box-shadow: 0 12px 28px rgba(15,23,42,.04); overflow: hidden; }
    .ket-stat::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 5px; background: #CBD5E1; }
    .ket-stat.update::before { background: #2563EB; } .ket-stat.same::before { background: #64748B; } .ket-stat.error::before { background: #DC2626; } .ket-stat.total::before { background: #0F172A; }
    .ket-stat-label { display: flex; align-items: center; gap: 8px; color: #64748B; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 12px; }
    .ket-stat-value { font-size: 34px; line-height: 1; font-weight: 900; letter-spacing: -.04em; color: #0F172A; }
    .ket-stat-help { margin-top: 8px; font-size: 12px; color: #94A3B8; }
    .ket-panel { padding: 22px; }
    .ket-panel-title { font-size: 18px; } .ket-panel-subtitle { margin: 5px 0 0; }
    .ket-ghost { min-height: 40px; padding: 0 14px; border: 1px solid #CBD5E1; border-radius: 12px; background: #fff; color: #334155; font-size: 12px; font-weight: 700; cursor: pointer; }
    .ket-filter-main { display: grid; gap: 14px; }
    .ket-filter-side { display: grid; gap: 12px; }
    .ket-pills { align-items: center; padding-top: 2px; }
    .ket-pill { padding: 9px 14px; border: 1px solid transparent; font-size: 12px; cursor: pointer; transition: .2s ease; }
    .ket-pill .count { padding: 2px 8px; border-radius: 999px; font-size: 11px; background: rgba(15,23,42,.08); }
    .ket-pill[data-filter="all"] { background: #F1F5F9; color: #334155; border-color: #CBD5E1; }
    .ket-pill[data-filter="update"] { background: #DBEAFE; color: #1D4ED8; border-color: #93C5FD; }
    .ket-pill[data-filter="no_change"] { background: #F3F4F6; color: #4B5563; border-color: #D1D5DB; }
    .ket-pill[data-filter="error"] { background: #FEE2E2; color: #B91C1C; border-color: #FCA5A5; }
    .ket-pill.active { transform: translateY(-1px); box-shadow: 0 10px 20px rgba(15,23,42,.10); color: #fff; }
    .ket-pill.active .count { background: rgba(255,255,255,.22); }
    .ket-pill[data-filter="all"].active { background: #0F172A; border-color: #0F172A; } .ket-pill[data-filter="update"].active { background: #2563EB; border-color: #2563EB; } .ket-pill[data-filter="no_change"].active { background: #475569; border-color: #475569; } .ket-pill[data-filter="error"].active { background: #DC2626; border-color: #DC2626; }
    .ket-search-wrap { position: relative; min-width: min(100%, 320px); flex: 1; max-width: 420px; }
    .ket-search-wrap i { position: absolute; top: 50%; left: 14px; transform: translateY(-50%); color: #94A3B8; }
    .ket-search { width: 100%; min-height: 44px; padding: 0 42px; border-radius: 14px; border: 1px solid #CBD5E1; background: #fff; color: #0F172A; font-size: 13px; }
    .ket-search:focus { outline: none; border-color: #60A5FA; box-shadow: 0 0 0 4px rgba(96,165,250,.16); }
    .ket-clear { position: absolute; top: 50%; right: 10px; transform: translateY(-50%); width: 28px; height: 28px; border: none; border-radius: 999px; background: #E2E8F0; color: #475569; display: none; cursor: pointer; }
    .ket-clear.show { display: inline-flex; align-items: center; justify-content: center; }
    .ket-tools { display: grid; gap: 12px; }
    .ket-select-wrap, .ket-search-wrap { width: 100%; }
    .ket-field { display: grid; gap: 7px; }
    .ket-field-label { font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #64748B; }
    .ket-select-wrap { min-width: 0; max-width: none; flex: initial; }
    .ket-select { width: 100%; min-height: 44px; padding: 0 14px; border-radius: 14px; border: 1px solid #CBD5E1; background: #fff; color: #0F172A; font-size: 13px; appearance: auto; }
    .ket-select:focus { outline: none; border-color: #60A5FA; box-shadow: 0 0 0 4px rgba(96,165,250,.16); }
    .ket-result { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: center; margin-top: 18px; padding-top: 16px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; }
    .ket-result strong { color: #0F172A; }
    .ket-dot { width: 10px; height: 10px; border-radius: 999px; display: inline-block; } .ket-dot.update { background: #2563EB; } .ket-dot.same { background: #64748B; } .ket-dot.error { background: #DC2626; }
    .ket-empty { display: none; padding: 18px 20px; border-radius: 18px; border: 1px dashed #CBD5E1; background: #fff; } .ket-empty.show { display: block; }
    .ket-group summary { list-style: none; cursor: pointer; } .ket-group summary::-webkit-details-marker { display: none; }
    .ket-group-head { padding: 18px 20px; background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%); border-bottom: 1px solid #F1F5F9; }
    .ket-group-name { font-size: 17px; } .ket-meta { margin-top: 6px; }
    .ket-badge { padding: 7px 10px; font-size: 11px; } .ket-badge.update { background: #DBEAFE; color: #1D4ED8; } .ket-badge.same { background: #F3F4F6; color: #4B5563; } .ket-badge.error { background: #FEE2E2; color: #B91C1C; }
    .ket-table-wrap { overflow-x: auto; }
    .ket-table { width: 100%; min-width: 1180px; border-collapse: collapse; }
    .ket-table thead th { padding: 13px 16px; background: #F8FAFC; border-bottom: 1px solid #E2E8F0; color: #64748B; font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
    .ket-table tbody tr { border-bottom: 1px solid #F1F5F9; } .ket-table tbody tr:last-child { border-bottom: none; } .ket-table tbody tr:hover { background: #F8FAFC; }
    .ket-table td { padding: 16px; vertical-align: top; font-size: 13px; color: #334155; }
    .ket-index { font-size: 12px; font-weight: 800; color: #94A3B8; }
    .ket-status { padding: 8px 11px; font-size: 11px; } .ket-status.update { background: #DBEAFE; color: #1D4ED8; } .ket-status.same { background: #F3F4F6; color: #4B5563; } .ket-status.error { background: #FEE2E2; color: #B91C1C; }
    .ket-ref { display: grid; gap: 10px; min-width: 270px; }
    .ket-ref-main { display: grid; gap: 4px; } .ket-ref-main strong { font-size: 15px; color: #0F172A; } .ket-ref-main span { font-size: 12px; color: #64748B; }
    .ket-ref-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .ket-ref-item { padding: 10px 12px; border-radius: 12px; background: #F8FAFC; border: 1px solid #E2E8F0; }
    .ket-ref-item .label { display: block; margin-bottom: 4px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #94A3B8; }
    .ket-ref-item .value { font-size: 12px; line-height: 1.5; color: #334155; word-break: break-word; }
    .ket-diff { display: grid; gap: 10px; min-width: 360px; } .ket-diff-row { display: grid; grid-template-columns: 110px 1fr 28px 1fr; gap: 8px; align-items: stretch; }
    .ket-diff-label { display: inline-flex; align-items: center; color: #64748B; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .ket-from, .ket-to, .ket-muted, .ket-note li { min-height: 42px; padding: 10px 12px; border-radius: 12px; font-size: 12px; line-height: 1.6; word-break: break-word; }
    .ket-from { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; } .ket-to { background: #ECFDF5; border: 1px solid #A7F3D0; color: #047857; font-weight: 700; }
    .ket-arrow { display: inline-flex; align-items: center; justify-content: center; color: #94A3B8; font-size: 16px; }
    .ket-note { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; min-width: 280px; }
    .ket-note li { display: flex; gap: 8px; align-items: flex-start; } .ket-note.warn li { background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; } .ket-note.error li { background: #FEF2F2; border: 1px solid #FECACA; color: #B91C1C; }
    .ket-hidden { display: none; }
    @media (max-width: 1100px) { .ket-filter-row { grid-template-columns: 1fr; } }
    @media (max-width: 900px) { .ket-hero, .ket-panel { padding: 18px; } .ket-group-head { padding: 16px; } .ket-ref-grid, .ket-diff-row { grid-template-columns: 1fr; } .ket-arrow { justify-content: flex-start; transform: rotate(90deg); } .ket-actions { width: 100%; } }
</style>
@endsection

@section('content')
@php
    $totalRows = (int) ($stats['total'] ?? count($preview));
    $updateCount = (int) ($stats['update'] ?? 0);
    $noChangeCount = (int) ($stats['no_change'] ?? 0);
    $errorCount = (int) ($stats['error'] ?? 0);
    $previewGroups = collect($preview)->groupBy(fn ($row) => $row['satker_name'] ?: 'Tanpa Satker / Error');
@endphp

<form action="{{ route('admin.personnel.import-keterangan-cancel') }}" method="POST" id="cancelForm">@csrf</form>
<form action="{{ route('admin.personnel.import-keterangan-confirm') }}" method="POST" id="confirmForm">@csrf</form>

<div class="ket-page">
    <section class="ket-hero">
        <div class="ket-hero-top">
            <div class="ket-hero-copy">
                <div class="ket-eyebrow"><i class="ri-file-search-line"></i> Preview Import Keterangan</div>
                <h1 class="ket-title">Review perubahan sebelum data diterapkan</h1>
                <p class="ket-subtitle">
                    Sistem hanya akan mengubah kolom <strong>`keterangan_2`</strong>, <strong>`keterangan_3`</strong>, dan <strong>`keterangan_4`</strong>.
                    Kolom lainnya ditampilkan agar proses review lebih aman dan lebih mudah discan.
                </p>
                <div class="ket-chip-row" style="margin-top:18px;">
                    <div class="ket-chip"><i class="ri-table-line"></i> Total <strong>{{ $totalRows }}</strong> baris</div>
                    <div class="ket-chip"><i class="ri-building-2-line"></i> <strong>{{ $previewGroups->count() }}</strong> satker</div>
                    <div class="ket-chip"><i class="ri-refresh-line"></i> Siap update <strong>{{ $updateCount }}</strong></div>
                    @if($errorCount > 0)
                        <div class="ket-chip"><i class="ri-error-warning-line"></i> Perlu dicek <strong>{{ $errorCount }}</strong></div>
                    @endif
                </div>
            </div>
            <div class="ket-actions">
                <h2>Aksi utama</h2>
                <p>Tinjau baris yang berubah, gunakan filter dan pencarian jika perlu, lalu konfirmasi hanya jika preview sudah sesuai.</p>
                <div class="ket-action-grid">
                    <button type="submit" form="confirmForm" class="btn btn-primary" @if($updateCount === 0) disabled @endif>
                        <i class="ri-check-double-line"></i> Konfirmasi Import{{ $updateCount > 0 ? ' ('.$updateCount.')' : '' }}
                    </button>
                    <button type="submit" form="cancelForm" class="btn btn-outline">
                        <i class="ri-close-line"></i> Batal dan Kembali
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="ket-stats">
        <article class="ket-stat update"><div class="ket-stat-label"><i class="ri-refresh-line"></i> Akan Diperbarui</div><div class="ket-stat-value">{{ $updateCount }}</div><div class="ket-stat-help">Baris dengan perubahan pada kolom target.</div></article>
        <article class="ket-stat same"><div class="ket-stat-label"><i class="ri-equal-line"></i> Tidak Berubah</div><div class="ket-stat-value">{{ $noChangeCount }}</div><div class="ket-stat-help">Baris valid tetapi nilainya tetap sama.</div></article>
        <article class="ket-stat error"><div class="ket-stat-label"><i class="ri-error-warning-line"></i> Error</div><div class="ket-stat-value">{{ $errorCount }}</div><div class="ket-stat-help">Baris belum bisa diterapkan.</div></article>
        <article class="ket-stat total"><div class="ket-stat-label"><i class="ri-list-check-3"></i> Total Baris</div><div class="ket-stat-value">{{ $totalRows }}</div><div class="ket-stat-help">Jumlah seluruh preview dari file impor.</div></article>
    </section>

    <section class="ket-panel">
        <div class="ket-panel-head">
            <div>
                <h2 class="ket-panel-title">Filter dan pencarian cepat</h2>
                <p class="ket-panel-subtitle">Cari ID, nama, NRP/NIP, satker, jabatan, warning, atau pesan error.</p>
            </div>
            <div class="ket-chip-row">
                <button type="button" class="ket-ghost" id="expandAllGroups"><i class="ri-add-circle-line"></i> Buka semua</button>
                <button type="button" class="ket-ghost" id="collapseAllGroups"><i class="ri-indeterminate-circle-line"></i> Tutup semua</button>
            </div>
        </div>
        <div class="ket-filter-row" style="margin-top:16px;">
            <div class="ket-filter-main">
                <div class="ket-field">
                    <span class="ket-field-label">Status baris</span>
                    <div class="ket-pills">
                        <button type="button" class="ket-pill active" data-filter="all"><i class="ri-list-check-2"></i> Semua <span class="count">{{ $totalRows }}</span></button>
                        <button type="button" class="ket-pill" data-filter="update"><i class="ri-loop-right-line"></i> Update <span class="count">{{ $updateCount }}</span></button>
                        <button type="button" class="ket-pill" data-filter="no_change"><i class="ri-equal-line"></i> Tidak berubah <span class="count">{{ $noChangeCount }}</span></button>
                        <button type="button" class="ket-pill" data-filter="error"><i class="ri-error-warning-line"></i> Error <span class="count">{{ $errorCount }}</span></button>
                    </div>
                </div>
            </div>
            <div class="ket-filter-side">
                <div class="ket-field">
                    <span class="ket-field-label">Pilih satker</span>
                    <div class="ket-select-wrap">
                        <select id="ketSatkerFilter" class="ket-select">
                            <option value="">Semua satker</option>
                            @foreach($previewGroups->keys()->sort()->values() as $groupName)
                                <option value="{{ \Illuminate\Support\Str::lower($groupName) }}">{{ $groupName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="ket-field">
                    <span class="ket-field-label">Cari cepat</span>
                    <div class="ket-search-wrap">
                        <i class="ri-search-line"></i>
                        <input type="text" id="ketSearch" class="ket-search" placeholder="Cari nama, ID, NRP/NIP, satker, atau catatan...">
                        <button type="button" id="ketClear" class="ket-clear" aria-label="Hapus pencarian"><i class="ri-close-line"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="ket-result">
            <div>Menampilkan <strong id="ketVisibleRows">{{ $totalRows }}</strong> dari <strong>{{ $totalRows }}</strong> baris preview.</div>
            <div class="ket-legend">
                <span><span class="ket-dot update"></span> Siap update</span>
                <span><span class="ket-dot same"></span> Tidak berubah</span>
                <span><span class="ket-dot error"></span> Gagal diproses</span>
            </div>
        </div>
    </section>

    <div class="ket-empty" id="ketEmpty">Tidak ada baris yang cocok dengan filter atau pencarian saat ini.</div>

    @forelse($previewGroups as $satkerName => $rows)
        @php
            $groupUpdateCount = $rows->where('status', 'update')->count();
            $groupSameCount = $rows->where('status', 'no_change')->count();
            $groupErrorCount = $rows->where('status', 'error')->count();
        @endphp
        <details class="ket-group js-group" data-satker="{{ \Illuminate\Support\Str::lower($satkerName) }}">
            <summary class="ket-group-head">
                <div>
                    <h2 class="ket-group-name">{{ $satkerName }}</h2>
                    <div class="ket-meta"><span class="js-group-visible">{{ $rows->count() }}</span> dari {{ $rows->count() }} baris tampil pada grup ini</div>
                </div>
                <div class="ket-badges">
                    <span class="ket-badge update"><i class="ri-refresh-line"></i> Update {{ $groupUpdateCount }}</span>
                    <span class="ket-badge same"><i class="ri-equal-line"></i> Tidak berubah {{ $groupSameCount }}</span>
                    @if($groupErrorCount > 0)
                        <span class="ket-badge error"><i class="ri-error-warning-line"></i> Error {{ $groupErrorCount }}</span>
                    @endif
                </div>
            </summary>
            <div class="ket-table-wrap">
                <table class="ket-table">
                    <thead>
                        <tr>
                            <th>Baris</th>
                            <th>Status</th>
                            <th>Referensi Personel</th>
                            <th>Perubahan</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php
                                $statusClass = match($row['status']) { 'update' => 'update', 'no_change' => 'same', default => 'error' };
                                $statusIcon = match($row['status']) { 'update' => 'ri-loop-right-line', 'no_change' => 'ri-equal-line', default => 'ri-error-warning-line' };
                                $statusLabel = match($row['status']) { 'update' => 'Siap update', 'no_change' => 'Tidak berubah', default => 'Error' };
                                $searchValue = strtolower(implode(' ', array_filter([
                                    (string) ($row['row_num'] ?? ''), (string) ($row['id'] ?? ''), (string) ($row['full_name'] ?? ''), (string) ($row['nrp_nip'] ?? ''),
                                    (string) ($row['satker_name'] ?? ''), (string) ($row['rank_name'] ?? ''), (string) ($row['jabatan'] ?? ''), (string) ($row['bagian'] ?? ''),
                                    (string) ($row['error_message'] ?? ''), collect($row['reference_warnings'] ?? [])->implode(' '),
                                ])));
                            @endphp
                            <tr class="js-row" data-status="{{ $row['status'] }}" data-search="{{ $searchValue }}">
                                <td><span class="ket-index">#{{ $row['row_num'] }}</span></td>
                                <td><span class="ket-status {{ $statusClass }}"><i class="{{ $statusIcon }}"></i> {{ $statusLabel }}</span></td>
                                <td>
                                    <div class="ket-ref">
                                        <div class="ket-ref-main">
                                            <strong>{{ $row['full_name'] ?: '-' }}</strong>
                                            <span>ID Personel: {{ $row['id'] ?: '-' }}</span>
                                        </div>
                                        <div class="ket-ref-grid">
                                            <div class="ket-ref-item"><span class="label">NRP / NIP</span><span class="value">{{ $row['nrp_nip'] ?: '-' }}</span></div>
                                            <div class="ket-ref-item"><span class="label">Satker</span><span class="value">{{ $row['satker_name'] ?: '-' }}</span></div>
                                            <div class="ket-ref-item"><span class="label">Pangkat</span><span class="value">{{ $row['rank_name'] ?: '-' }}</span></div>
                                            <div class="ket-ref-item"><span class="label">Jabatan / Bagian</span><span class="value">{{ $row['jabatan'] ?: '-' }}@if(!empty($row['bagian']))<br>{{ $row['bagian'] }}@endif</span></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($row['status'] === 'error')
                                        <div class="ket-muted"><i class="ri-information-line"></i> Baris ini tidak bisa diterapkan karena masih ada error.</div>
                                    @elseif($row['diff'] === [])
                                        <div class="ket-muted"><i class="ri-equal-line"></i> Nilai target sama dengan data yang sudah ada.</div>
                                    @else
                                        <div class="ket-diff">
                                            @foreach($row['diff'] as $field => $change)
                                                <div class="ket-diff-row">
                                                    <div class="ket-diff-label">{{ str_replace('_', ' ', strtoupper($field)) }}</div>
                                                    <div class="ket-from">{{ filled($change['from'] ?? null) ? $change['from'] : '-' }}</div>
                                                    <div class="ket-arrow"><i class="ri-arrow-right-line"></i></div>
                                                    <div class="ket-to">{{ filled($change['to'] ?? null) ? $change['to'] : '-' }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($row['status'] === 'error')
                                        <ul class="ket-note error"><li><i class="ri-close-circle-line"></i><span>{{ $row['error_message'] }}</span></li></ul>
                                    @elseif(!empty($row['reference_warnings']))
                                        <ul class="ket-note warn">
                                            @foreach($row['reference_warnings'] as $warning)
                                                <li><i class="ri-alert-line"></i><span>{{ $warning }}</span></li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="ket-muted"><i class="ri-checkbox-circle-line"></i> Tidak ada catatan tambahan pada baris ini.</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @empty
        <div class="ket-empty" style="display:block;">Tidak ada baris preview yang bisa ditampilkan.</div>
    @endforelse
</div>

<script>
    (() => {
        const pills = Array.from(document.querySelectorAll('.ket-pill'));
        const search = document.getElementById('ketSearch');
        const clear = document.getElementById('ketClear');
        const satkerFilter = document.getElementById('ketSatkerFilter');
        const visibleRowsText = document.getElementById('ketVisibleRows');
        const empty = document.getElementById('ketEmpty');
        const groups = Array.from(document.querySelectorAll('.js-group'));
        const expand = document.getElementById('expandAllGroups');
        const collapse = document.getElementById('collapseAllGroups');
        if (!pills.length || !search || !visibleRowsText || !groups.length || !satkerFilter) return;

        const state = { filter: 'all', query: '', satker: '' };
        const norm = (v) => (v || '').toString().toLowerCase().trim();

        const apply = () => {
            let totalVisible = 0;
            groups.forEach((group) => {
                const groupMatchesSatker = state.satker === '' || norm(group.dataset.satker) === state.satker;
                const rows = Array.from(group.querySelectorAll('.js-row'));
                let groupVisible = 0;
                rows.forEach((row) => {
                    const show = groupMatchesSatker &&
                        (state.filter === 'all' || row.dataset.status === state.filter) &&
                        (state.query === '' || norm(row.dataset.search).includes(state.query));
                    row.classList.toggle('ket-hidden', !show);
                    if (show) groupVisible += 1;
                });
                const label = group.querySelector('.js-group-visible');
                if (label) label.textContent = groupVisible;
                group.hidden = groupVisible === 0;
                if (!group.hidden && (state.filter !== 'all' || state.query !== '' || state.satker !== '')) {
                    group.open = true;
                }
                totalVisible += groupVisible;
            });
            visibleRowsText.textContent = totalVisible;
            empty.classList.toggle('show', totalVisible === 0);
            clear.classList.toggle('show', state.query !== '');
        };

        pills.forEach((pill) => {
            pill.addEventListener('click', () => {
                state.filter = pill.dataset.filter || 'all';
                pills.forEach((item) => item.classList.toggle('active', item === pill));
                apply();
            });
        });

        search.addEventListener('input', (event) => {
            state.query = norm(event.target.value);
            apply();
        });

        satkerFilter.addEventListener('change', (event) => {
            state.satker = norm(event.target.value);
            apply();
        });

        clear.addEventListener('click', () => {
            search.value = '';
            state.query = '';
            search.focus();
            apply();
        });

        if (expand) expand.addEventListener('click', () => groups.forEach((group) => { if (!group.hidden) group.open = true; }));
        if (collapse) collapse.addEventListener('click', () => groups.forEach((group) => { if (!group.hidden) group.open = false; }));

        apply();
    })();
</script>
@endsection
