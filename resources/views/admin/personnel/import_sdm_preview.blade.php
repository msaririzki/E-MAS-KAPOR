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
    .sdm-progress-overlay {
        position: fixed;
        inset: 0;
        z-index: 2600;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(6px);
    }
    .sdm-progress-card {
        width: min(100%, 460px);
        border-radius: 24px;
        padding: 28px;
        background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
        box-shadow: 0 24px 80px rgba(15, 23, 42, 0.22);
        border: 1px solid rgba(148, 163, 184, 0.22);
    }
    .sdm-progress-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 12px;
        border-radius: 999px;
        background: #D1FAE5;
        color: #047857;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .sdm-progress-track {
        position: relative;
        overflow: hidden;
        width: 100%;
        height: 14px;
        margin-top: 18px;
        border-radius: 999px;
        background: #E5E7EB;
    }
    .sdm-progress-fill {
        position: relative;
        height: 100%;
        width: 0;
        border-radius: inherit;
        background: linear-gradient(90deg, #059669 0%, #14B8A6 100%);
        transition: width 0.24s ease;
    }
    .sdm-progress-fill::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.4) 50%, transparent 100%);
        animation: sdmProgressShimmer 1.3s linear infinite;
    }
    .sdm-progress-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-top: 12px;
    }
    .sdm-progress-dots {
        display: inline-flex;
        gap: 6px;
        margin-top: 18px;
    }
    .sdm-progress-dots span {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #059669;
        opacity: 0.25;
        animation: sdmProgressPulse 1s infinite ease-in-out;
    }
    .sdm-progress-dots span:nth-child(2) { animation-delay: 0.18s; }
    .sdm-progress-dots span:nth-child(3) { animation-delay: 0.36s; }
    @keyframes sdmProgressShimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    @keyframes sdmProgressPulse {
        0%, 100% { opacity: 0.25; transform: translateY(0); }
        50% { opacity: 1; transform: translateY(-2px); }
    }
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
                <p style="font-size:12px; color:#6B7280; margin-top:4px;">
                    Unknown rank: <strong>{{ $stats['unknown_rank_count'] ?? 0 }}</strong>
                    <span style="color:#9CA3AF;">|</span>
                    Unresolved satker: <strong>{{ $stats['unresolved_satker_count'] ?? 0 }}</strong>
                    <span style="color:#9CA3AF;">|</span>
                    Duplicate NRP/NIP: <strong>{{ $stats['duplicate_count'] ?? 0 }}</strong>
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
            @if(!empty($importRun?->error_report_path))
            <a href="{{ route('admin.personnel.import-sdm-runs.error-report', $importRun) }}" class="btn btn-outline">
                <i class="ri-file-download-line"></i> Unduh Laporan Error
            </a>
            @endif
            <button type="submit" form="cancelForm" class="btn btn-outline">
                <i class="ri-close-line"></i> Batalkan
            </button>
            <button type="submit" class="btn btn-primary btn-submit-import" id="sdmConfirmSubmitBtn" style="background:#059669; border-color:#059669;">
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
                                data-override-type="rank"
                                data-row-index="{{ $i }}"
                                data-current-value="{{ $row['rank_id'] ?? '' }}"
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
                                data-override-type="satker"
                                data-row-index="{{ $i }}"
                                data-current-value="{{ $row['satker_id'] ?? '' }}"
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

<div id="sdmConfirmProgressOverlay" class="sdm-progress-overlay" aria-live="polite" aria-busy="true">
    <div class="sdm-progress-card">
        <div class="sdm-progress-chip">
            <i class="ri-loader-4-line"></i>
            <span id="sdmConfirmProgressBadge">Konfirmasi Import SDM</span>
        </div>
        <div style="margin-top: 18px;">
            <div id="sdmConfirmProgressTitle" style="font-size: 24px; font-weight: 800; color: #0F172A; line-height: 1.2;">Menyiapkan impor SDM</div>
            <div id="sdmConfirmProgressMessage" style="margin-top: 10px; font-size: 14px; color: #475569; line-height: 1.6;">Data preview akan disimpan ke database setelah semua pengecekan lolos.</div>
        </div>
        <div class="sdm-progress-track">
            <div id="sdmConfirmProgressBar" class="sdm-progress-fill"></div>
        </div>
        <div class="sdm-progress-meta">
            <strong id="sdmConfirmProgressPercent" style="font-size: 28px; color: #111827;">0%</strong>
            <span id="sdmConfirmProgressStep" style="font-size: 13px; color: #64748B; text-align: right;">Menunggu konfirmasi</span>
        </div>
        <div class="sdm-progress-dots" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</div>

<script>
const errorIndexes = @json(collect($preview)->where('status', 'error')->keys()->values());
const fatalErrorIndexes = @json(collect($preview)->filter(fn ($row) => !empty($row['fatal_error']))->keys()->values());
const requiresManualRank = @json(collect($preview)->filter(fn ($row) => !empty($row['requires_manual_rank']))->keys()->values());
const requiresManualSatker = @json(collect($preview)->filter(fn ($row) => !empty($row['requires_manual_satker']))->keys()->values());
const previewTotalRows = @json($stats['total']);
const sdmToastStorageKey = 'sdm-import-toast';
const rankOverridesState = {};
const satkerOverridesState = {};
let sdmConfirmProgressTimer = null;

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
    const normalizedValue = value === null || value === undefined ? '' : String(value);

    if (type === 'rank') {
        if (normalizedValue === '') {
            delete rankOverridesState[index];
        } else {
            rankOverridesState[index] = normalizedValue;
        }
        return;
    }

    if (type === 'satker') {
        if (normalizedValue === '') {
            delete satkerOverridesState[index];
        } else {
            satkerOverridesState[index] = normalizedValue;
        }
    }
}

function parseAjaxResponse(xhr) {
    if (xhr.response && typeof xhr.response === 'object') {
        return xhr.response;
    }

    try {
        return JSON.parse(xhr.responseText || '{}');
    } catch (error) {
        return {};
    }
}

function extractAjaxError(xhr, fallbackMessage) {
    const payload = parseAjaxResponse(xhr);
    if (payload.message) {
        return payload.message;
    }

    if (payload.errors) {
        const firstError = Object.values(payload.errors).flat()[0];
        if (firstError) {
            return firstError;
        }
    }

    return fallbackMessage;
}

function setStoredSdmToast(message, type = 'success') {
    sessionStorage.setItem(sdmToastStorageKey, JSON.stringify({ message, type }));
}

function setConfirmButtonState(isLoading) {
    const button = document.getElementById('sdmConfirmSubmitBtn');
    if (!button) {
        return;
    }

    button.disabled = isLoading;
    button.style.opacity = isLoading ? '0.7' : '1';
    button.style.cursor = isLoading ? 'wait' : 'pointer';
}

function setConfirmProgress(percent, title, message, step) {
    const safePercent = Math.max(0, Math.min(100, Math.round(percent)));
    document.getElementById('sdmConfirmProgressBar').style.width = safePercent + '%';
    document.getElementById('sdmConfirmProgressPercent').innerText = safePercent + '%';

    if (title) {
        document.getElementById('sdmConfirmProgressTitle').innerText = title;
    }
    if (message) {
        document.getElementById('sdmConfirmProgressMessage').innerText = message;
    }
    if (step) {
        document.getElementById('sdmConfirmProgressStep').innerText = step;
    }
}

function openConfirmProgressOverlay() {
    document.getElementById('sdmConfirmProgressOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => {
        setConfirmProgress(
            6,
            'Mengirim konfirmasi import',
            `Sistem sedang memproses ${previewTotalRows} baris preview SDM ke database.`,
            'Validasi koreksi manual',
        );
    });
}

function closeConfirmProgressOverlay() {
    document.getElementById('sdmConfirmProgressOverlay').style.display = 'none';
    document.body.style.overflow = '';

    if (sdmConfirmProgressTimer) {
        clearInterval(sdmConfirmProgressTimer);
        sdmConfirmProgressTimer = null;
    }
}

function animateConfirmProgress(maxPercent, stepMessage, options = {}) {
    if (sdmConfirmProgressTimer) {
        clearInterval(sdmConfirmProgressTimer);
    }

    const delay = options.interval || Math.max(180, Math.min(420, Math.round(previewTotalRows / 6)));
    const fastUntil = options.fastUntil || 45;
    const fastIncrement = options.fastIncrement || 4;
    const slowIncrement = options.slowIncrement || 1;

    sdmConfirmProgressTimer = setInterval(() => {
        const current = parseInt(document.getElementById('sdmConfirmProgressPercent').innerText, 10) || 0;
        if (current >= maxPercent) {
            clearInterval(sdmConfirmProgressTimer);
            sdmConfirmProgressTimer = null;
            return;
        }

        const increment = current < fastUntil ? fastIncrement : slowIncrement;
        setConfirmProgress(current + increment, null, null, stepMessage);
    }, delay);
}

function doConfirm(event) {
    if (fatalErrorIndexes.length > 0) {
        event.preventDefault();
        setFilter('error');
        alert('Masih ada error fatal pada file SDM. Perbaiki file sumber lalu upload ulang.');
        return false;
    }

    const missingRank = requiresManualRank.filter(function(index) {
        return !rankOverridesState[index];
    });

    if (missingRank.length > 0) {
        event.preventDefault();
        setFilter('error');
        alert('Masih ada baris yang belum dipilih pangkatnya.');
        return false;
    }

    const missingSatker = requiresManualSatker.filter(function(index) {
        return !satkerOverridesState[index];
    });

    if (missingSatker.length > 0) {
        event.preventDefault();
        setFilter('error');
        alert('Masih ada baris yang belum dipilih satkernya.');
        return false;
    }

    event.preventDefault();

    const form = document.getElementById('importConfirmForm');
    const formData = new FormData(form);
    Object.entries(rankOverridesState).forEach(function([index, value]) {
        formData.append(`rank_overrides[${index}]`, value);
    });
    Object.entries(satkerOverridesState).forEach(function([index, value]) {
        formData.append(`satker_overrides[${index}]`, value);
    });
    const xhr = new XMLHttpRequest();

    openConfirmProgressOverlay();
    setConfirmButtonState(true);
    animateConfirmProgress(22, 'Menyiapkan data konfirmasi', {
        interval: 180,
        fastUntil: 22,
        fastIncrement: 2,
        slowIncrement: 1,
    });

    xhr.open('POST', form.action, true);
    xhr.responseType = 'json';
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.upload.onprogress = function(progressEvent) {
        if (!progressEvent.lengthComputable) {
            return;
        }

        const percent = Math.max(10, Math.round((progressEvent.loaded / progressEvent.total) * 28));
        setConfirmProgress(
            percent,
            'Mengirim data konfirmasi',
            'Koreksi manual dan data preview sedang dikirim ke server.',
            'Upload data konfirmasi',
        );
    };

    xhr.upload.onload = function() {
        setConfirmProgress(
            32,
            'Menyimpan data personel SDM',
            `Server sedang menyimpan ${previewTotalRows} baris, membuat akun terkait, dan merapikan relasi satker.`,
            'Memproses penyimpanan ke database',
        );
        animateConfirmProgress(95, 'Menulis data ke database', {
            interval: Math.max(160, Math.min(320, Math.round(previewTotalRows / 7))),
            fastUntil: 74,
            fastIncrement: 3,
            slowIncrement: 1,
        });
    };

    xhr.onerror = function() {
        closeConfirmProgressOverlay();
        setConfirmButtonState(false);
        alert('Koneksi ke server terputus saat konfirmasi import SDM.');
    };

    xhr.onload = function() {
        const payload = parseAjaxResponse(xhr);

        if (xhr.status >= 200 && xhr.status < 300 && payload.redirect_url) {
            if (payload.notification && payload.notification.message) {
                setStoredSdmToast(payload.notification.message, payload.notification.type || 'success');
            }

            setConfirmProgress(
                100,
                'Import SDM selesai',
                payload.message || 'Seluruh proses import SDM selesai diproses.',
                'Mengalihkan ke halaman personel',
            );

            setTimeout(function() {
                window.location.href = payload.redirect_url;
            }, 550);

            return;
        }

        if (payload.redirect_url) {
            setStoredSdmToast(payload.message || 'Sesi preview SDM sudah tidak tersedia.', 'error');
            window.location.href = payload.redirect_url;
            return;
        }

        closeConfirmProgressOverlay();
        setConfirmButtonState(false);
        alert(extractAjaxError(xhr, 'Konfirmasi import SDM gagal diproses.'));
    };

    xhr.send(formData);

    return false;
}

setFilter('all');

document.querySelectorAll('select[data-override-type="rank"]').forEach(function(select) {
    updateOverride('rank', select.dataset.rowIndex, select.value || select.dataset.currentValue || '');
});

document.querySelectorAll('select[data-override-type="satker"]').forEach(function(select) {
    updateOverride('satker', select.dataset.rowIndex, select.value || select.dataset.currentValue || '');
});
</script>
@endsection
