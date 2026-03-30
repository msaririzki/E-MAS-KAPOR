@extends('layouts.app')

@section('title', 'Preview Import Data SDM')
@section('breadcrumb', 'Preview Import SDM')

@section('content')
@if(session('error'))
<div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:14px 18px; margin-bottom:16px; display:flex; gap:10px; align-items:flex-start;">
    <i class="ri-error-warning-fill" style="color:#EF4444; font-size:20px; flex-shrink:0;"></i>
    <div style="color:#B91C1C; font-size:14px; font-weight:600;">{{ session('error') }}</div>
</div>
@endif

<style>
    .filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 40px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        border: 2px solid transparent;
        transition: all 0.18s ease;
        user-select: none;
    }
    .filter-pill.all { background: #F3F4F6; color: #374151; border-color: #D1D5DB; }
    .filter-pill.ok { background: #D1FAE5; color: #065F46; border-color: #6EE7B7; }
    .filter-pill.warn { background: #FEF3C7; color: #92400E; border-color: #FDE68A; }
    .filter-pill.error { background: #FEE2E2; color: #B91C1C; border-color: #FECACA; }
    .filter-pill.active.all { background: #374151; color: #fff; border-color: #374151; }
    .filter-pill.active.ok { background: #059669; color: #fff; border-color: #059669; }
    .filter-pill.active.warn { background: #D97706; color: #fff; border-color: #D97706; }
    .filter-pill.active.error { background: #DC2626; color: #fff; border-color: #DC2626; }
    .filter-pill .badge {
        background: rgba(0,0,0,0.08);
        border-radius: 20px;
        padding: 2px 8px;
        font-size: 12px;
        font-weight: 700;
    }
    .filter-pill.active .badge {
        background: rgba(255,255,255,0.25);
    }
    .row-corrected { background: #FFFDF0 !important; }
    .row-error { background: #FFF5F5 !important; }
    .hidden-row { display: none !important; }
</style>

<form action="{{ route('admin.personnel.import-sdm-cancel') }}" method="POST" id="cancelForm">
    @csrf
</form>

<form action="{{ route('admin.personnel.import-sdm-confirm') }}" method="POST" id="importConfirmForm" onsubmit="return doConfirm(event)">
    @csrf

    <div class="page-header">
        <div class="page-header-row">
            <div>
                <h1 class="page-title">Preview Import Data SDM</h1>
                <p class="page-subtitle">
                    Total baris: <strong>{{ $stats['total'] }}</strong>
                    <span style="color:#6B7280;">| File: <strong>{{ $stats['file_count'] ?? 1 }}</strong></span>
                    <span style="color:#6B7280;">| Satker terdeteksi: <strong>{{ $stats['satker_count'] ?? 0 }}</strong></span>
                </p>
                <p style="font-size:12px; color:var(--brand); margin-top:4px;">
                    Baseline SDM: Nama, NRP/NIP, Pangkat, Jabatan, Jenis Kelamin, Agama, lalu satker ditentukan otomatis dari teks jabatan.
                </p>
            </div>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:16px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; flex:1;">
            <div class="filter-pill all active" onclick="setFilter('all')" id="pill-all">
                <i class="ri-list-check-2"></i> Semua <span class="badge">{{ $stats['total'] }}</span>
            </div>
            <div class="filter-pill ok" onclick="setFilter('ok')" id="pill-ok">
                <i class="ri-checkbox-circle-line"></i> Siap Import <span class="badge">{{ $stats['ok'] }}</span>
            </div>
            @if($stats['corrected'] > 0)
            <div class="filter-pill warn" onclick="setFilter('corrected')" id="pill-corrected">
                <i class="ri-edit-box-line"></i> Perlu Cek <span class="badge">{{ $stats['corrected'] }}</span>
            </div>
            @endif
            @if($stats['error'] > 0)
            <div class="filter-pill error" onclick="setFilter('error')" id="pill-error">
                <i class="ri-error-warning-line"></i> Error <span class="badge">{{ $stats['error'] }}</span>
            </div>
            @endif
        </div>

        <div style="display:flex; gap:12px; align-items:center;">
            <button type="submit" form="cancelForm" class="btn btn-outline">
                <i class="ri-close-line"></i> Batalkan
            </button>
            <button type="submit" class="btn btn-primary btn-submit-import" style="background:#059669; border-color:#059669;">
                <i class="ri-check-double-line"></i> Konfirmasi Import SDM
            </button>
        </div>
    </div>

    @if($stats['error'] > 0)
    <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; gap:10px; align-items:center;">
        <i class="ri-error-warning-fill" style="color:#EF4444; font-size:20px; flex-shrink:0;"></i>
        <div style="font-size:13px;">
            <strong style="color:#B91C1C;">Masih ada baris error.</strong>
            Pangkat dan satker bisa Anda koreksi manual di tabel. Error fatal seperti NRP duplikat dalam file harus diperbaiki dari file sumber.
        </div>
    </div>
    @endif

    <div class="table-container">
        <div class="table-responsive" style="overflow-x:auto;">
            <table class="user-table" style="font-size:12px; min-width:1500px;">
                <thead>
                    <tr>
                        <th style="width:50px; border-top-left-radius:12px;">#</th>
                        <th style="width:90px;">SHEET</th>
                        <th style="width:220px;">NAMA</th>
                        <th style="width:130px;">NRP / NIP</th>
                        <th style="width:230px;">PANGKAT</th>
                        <th style="width:90px;">GOL</th>
                        <th style="width:320px;">JABATAN</th>
                        <th style="width:230px;">SATKER HASIL BACA</th>
                        <th style="width:80px;">JK</th>
                        <th style="width:110px;">AGAMA</th>
                        <th style="border-top-right-radius:12px; min-width:230px;">CATATAN</th>
                    </tr>
                </thead>
                <tbody id="previewTableBody">
                @foreach($preview as $i => $row)
                @php
                    $trClass = match($row['status']) {
                        'corrected' => 'row-corrected',
                        'error' => 'row-error',
                        default => '',
                    };
                @endphp
                <tr class="{{ $trClass }}" id="row-{{ $i }}" data-status="{{ $row['status'] }}">
                    <input type="hidden" name="rank_overrides[{{ $i }}]" value="{{ $row['rank_id'] ?? '' }}" id="rank_id_{{ $i }}">
                    <input type="hidden" name="satker_overrides[{{ $i }}]" value="{{ $row['satker_id'] ?? '' }}" id="satker_id_{{ $i }}">

                    <td style="color:#9CA3AF; font-size:10px;">{{ $row['row_num'] }}</td>
                    <td style="color:#6B7280;">{{ $row['sheet_name'] ?? '0' }}</td>
                    <td style="font-weight:600; color:#111827;">{{ $row['full_name'] }}</td>
                    <td style="font-family:monospace; color:#374151;">
                        {{ $row['nrp'] ?: '—' }}
                        @if(!empty($row['duplicate_nrp']))
                        <span style="display:block; font-size:9px; background:#FECACA; color:#991B1B; padding:1px 7px; border-radius:10px; font-weight:700; margin-top:2px; width:fit-content; font-family:sans-serif;">
                            DUPLIKAT
                        </span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <div>
                                <div style="font-weight:600; color:#111827;">{{ $row['rank_name'] ?? '—' }}</div>
                                @if(!empty($row['rank_corrected']))
                                <div style="font-size:10px; color:#92400E;">
                                    Input: {{ $row['rank_input'] ?: '—' }} -> {{ $row['rank_corrected'] }}
                                </div>
                                @elseif(!empty($row['rank_input']) && empty($row['rank_name']))
                                <div style="font-size:10px; color:#B91C1C;">Input: {{ $row['rank_input'] }}</div>
                                @endif
                            </div>
                            @if($row['status'] === 'error' || !empty($row['requires_manual_rank']))
                            <select onchange="updateOverride('rank', {{ $i }}, this.value)"
                                style="font-size:11px; padding:6px 8px; border:1px solid #D1D5DB; border-radius:6px; background:#fff;">
                                <option value="">Pilih Pangkat...</option>
                                @foreach($ranks as $rank)
                                <option value="{{ $rank->id }}" {{ (string) ($row['rank_id'] ?? '') === (string) $rank->id ? 'selected' : '' }}>
                                    {{ $rank->name }} ({{ $rank->category }})
                                </option>
                                @endforeach
                            </select>
                            @endif
                        </div>
                    </td>
                    <td>{{ $row['golongan'] ?: '—' }}</td>
                    <td style="color:#374151;">{{ $row['jabatan'] ?: '—' }}</td>
                    <td>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <div>
                                <div style="font-weight:600; color:#111827;">{{ $row['satker_name'] ?? '—' }}</div>
                                @if(!empty($row['satker_match']))
                                <div style="font-size:10px; color:#6B7280;">Match: {{ $row['satker_match'] }}</div>
                                @endif
                            </div>
                            @if($row['status'] === 'error' || !empty($row['requires_manual_satker']))
                            <select onchange="updateOverride('satker', {{ $i }}, this.value)"
                                style="font-size:11px; padding:6px 8px; border:1px solid #D1D5DB; border-radius:6px; background:#fff;">
                                <option value="">Pilih Satker...</option>
                                @foreach($satkers as $satker)
                                <option value="{{ $satker->id }}" {{ (string) ($row['satker_id'] ?? '') === (string) $satker->id ? 'selected' : '' }}>
                                    {{ $satker->name }}
                                </option>
                                @endforeach
                            </select>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span style="font-size:11px; padding:2px 8px; border-radius:12px; background:{{ $row['gender'] === 'L' ? '#DBEAFE' : '#FCE7F3' }}; color:{{ $row['gender'] === 'L' ? '#1D4ED8' : '#BE185D' }}; font-weight:600;">
                            {{ $row['gender_raw'] ?: '—' }}
                        </span>
                    </td>
                    <td>{{ $row['religion'] ?: '—' }}</td>
                    <td>
                        @if(!empty($row['status_notes']))
                            @foreach($row['status_notes'] as $note)
                            <div style="font-size:11px; color:{{ $row['status'] === 'error' ? '#B91C1C' : '#92400E' }}; margin-bottom:4px;">• {{ $note }}</div>
                            @endforeach
                        @else
                            <span style="font-size:11px; color:#059669;">Siap import</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                <tr id="emptyRow" class="hidden-row">
                    <td colspan="11" style="text-align:center; padding:48px; color:#9CA3AF;">
                        <i class="ri-inbox-line" style="font-size:36px; display:block; margin-bottom:8px; opacity:.3;"></i>
                        Tidak ada data pada kategori ini.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</form>

<script>
const errorIndexes = @json(collect($preview)->where('status', 'error')->keys()->values());
const fatalErrorIndexes = @json(collect($preview)->filter(fn ($row) => !empty($row['fatal_error']))->keys()->values());
const requiresManualRank = @json(collect($preview)->filter(fn ($row) => !empty($row['requires_manual_rank']))->keys()->values());
const requiresManualSatker = @json(collect($preview)->filter(fn ($row) => !empty($row['requires_manual_satker']))->keys()->values());

function setFilter(status) {
    ['all', 'ok', 'corrected', 'error'].forEach(function(key) {
        const pill = document.getElementById('pill-' + key);
        if (pill) {
            pill.classList.toggle('active', key === status);
        }
    });

    let visible = 0;
    document.querySelectorAll('#previewTableBody tr[data-status]').forEach(function(tr) {
        const show = status === 'all' || tr.dataset.status === status;
        tr.classList.toggle('hidden-row', !show);
        if (show) visible++;
    });

    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) {
        emptyRow.classList.toggle('hidden-row', visible > 0);
    }
}

function updateOverride(type, index, value) {
    const id = type + '_id_' + index;
    const hidden = document.getElementById(id);
    if (hidden) {
        hidden.value = value;
    }
}

function doConfirm(event) {
    if (fatalErrorIndexes.length > 0) {
        event.preventDefault();
        setFilter('error');
        alert('Masih ada error fatal pada file SDM. Perbaiki file sumber lalu upload ulang.');
        return false;
    }

    const missingRank = requiresManualRank.filter(function(index) {
        const el = document.getElementById('rank_id_' + index);
        return !el || !el.value;
    });

    if (missingRank.length > 0) {
        event.preventDefault();
        setFilter('error');
        alert('Masih ada baris yang belum dipilih pangkatnya.');
        return false;
    }

    const missingSatker = requiresManualSatker.filter(function(index) {
        const el = document.getElementById('satker_id_' + index);
        return !el || !el.value;
    });

    if (missingSatker.length > 0) {
        event.preventDefault();
        setFilter('error');
        alert('Masih ada baris yang belum dipilih satkernya.');
        return false;
    }

    return true;
}

setFilter('all');
</script>
@endsection
