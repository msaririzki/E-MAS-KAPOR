@extends('layouts.app')

@section('title', 'Pratinjau Unggah Personel')
@section('breadcrumb', 'Pratinjau Unggah')

@section('content')

{{-- Flash Messages: tampilkan error/warning dari controller --}}
@if(session('error'))
<div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:14px 18px; margin-bottom:16px; display:flex; gap:10px; align-items:flex-start;">
    <i class="ri-error-warning-fill" style="color:#EF4444; font-size:20px; flex-shrink:0;"></i>
    <div style="color:#B91C1C; font-size:14px; font-weight:600;">{{ session('error') }}</div>
</div>
@endif
@if(session('warning'))
<div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:10px; padding:14px 18px; margin-bottom:16px; display:flex; gap:10px; align-items:flex-start;">
    <i class="ri-alert-fill" style="color:#F59E0B; font-size:20px; flex-shrink:0;"></i>
    <div style="color:#92400E; font-size:14px; font-weight:600;">{{ session('warning') }}</div>
</div>
@endif


{{-- FORM BATALKAN — terpisah di luar form utama --}}
<form action="{{ route('admin.personnel.import-cancel') }}" method="POST" id="cancelForm">
    @csrf
</form>

{{-- FORM KONFIRMASI
     CATATAN: Hanya mengirim rank_overrides (baris yang diedit manual).
     Data lengkap 610+ baris dibaca dari SESSION di server.
     Ini menghindari batas max_input_vars PHP (default 1000). --}}
<form action="{{ route('admin.personnel.import-confirm') }}" method="POST"
      id="importConfirmForm" onsubmit="return doConfirm(event)">
    @csrf

    {{-- Header --}}
    <div class="page-header">
        <div class="page-header-row">
            <div>
                <h1 class="page-title">Pratinjau Unggah Personel</h1>
                <p class="page-subtitle">Satker: <strong>{{ $satker->name }}</strong>
                    &mdash; <span style="color:#6B7280;">{{ $stats['total'] }} baris data</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Filter Pills & Aksi Import --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:16px;">
        
        {{-- Bagian Kiri: Filter --}}
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; flex:1;">
            @if($stats['error'] == 0)
            <span style="font-size:13px; font-weight:600; color:#6B7280; margin-right:4px;">TAMPILKAN:</span>
            @endif
            
            <div class="filter-pill all active" onclick="setFilter('all')" id="pill-all">
                <i class="ri-list-check-2"></i> Semua <span class="badge">{{ $stats['total'] }}</span>
            </div>
            <div class="filter-pill ok" onclick="setFilter('ok')" id="pill-ok">
                <i class="ri-checkbox-circle-line"></i> Siap Diunggah <span class="badge">{{ $stats['ok'] }}</span>
            </div>
            @if($stats['corrected'] > 0)
            <div class="filter-pill warn" onclick="setFilter('corrected')" id="pill-corrected">
                <i class="ri-edit-box-line"></i> Auto Koreksi <span class="badge">{{ $stats['corrected'] }}</span>
            </div>
            @endif
            @if($stats['error'] > 0)
            <div class="filter-pill error" onclick="setFilter('error')" id="pill-error">
                <i class="ri-error-warning-line"></i> Pilih Manual <span class="badge">{{ $stats['error'] }}</span>
            </div>
            @endif
            @php
                $dupeCount = collect($preview)->filter(fn($r) => !empty($r['db_duplicate']) || !empty($r['duplicate_nrp']))->count();
            @endphp
            @if($dupeCount > 0)
            <div class="filter-pill error" onclick="setFilter('duplicate')" id="pill-duplicate" style="background:#FEE2E2; color:#991B1B; border-color:#FECACA;">
                <i class="ri-file-copy-line"></i> NRP Duplikat <span class="badge">{{ $dupeCount }}</span>
            </div>
            @endif
        </div>

        {{-- Bagian Kanan: Aksi (Form Konfirmasi & Batal) - Tampil Berdasar Scroll --}}
        <div id="header-confirm" style="display:none; gap:12px; align-items:center; white-space:nowrap;">
            <button type="submit" form="cancelForm" class="btn btn-outline" style="padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 13px;">
                <i class="ri-close-line"></i> Batalkan
            </button>
            <button type="submit" class="btn btn-primary btn-submit-import" style="background:#059669; padding:8px 18px; border-radius:8px; font-weight:700; font-size:13px; box-shadow:0 4px 6px -1px rgba(5, 150, 105, 0.2), 0 2px 4px -1px rgba(5, 150, 105, 0.1);">
                <i class="ri-check-double-line" style="margin-right:6px;"></i> Konfirmasi
                <span style="background:rgba(255,255,255,0.25); margin-left:6px; padding:2px 8px; border-radius:12px; font-size:11px;">{{ $stats['total'] }}</span>
            </button>
        </div>
    </div>

    {{-- Alert untuk error --}}
    @if($stats['error'] > 0)
    <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; gap:10px; align-items:center;">
        <i class="ri-error-warning-fill" style="color:#EF4444; font-size:20px; flex-shrink:0;"></i>
        <div style="font-size:13px;">
            <strong style="color:#B91C1C;">{{ $stats['error'] }} baris dengan pangkat tidak dikenali.</strong>
            Klik <strong>"Perlu Pilih Manual"</strong> di atas untuk menemukan baris yang perlu diperbaiki.
        </div>
    </div>
    @endif

    {{-- Tabel Preview --}}
    <div class="table-container">
        <div class="table-responsive" style="overflow-x:auto;">
            <table class="user-table" style="font-size:12px; min-width:1050px;">
                <thead>
                    <tr>
                        <th style="width:40px; border-top-left-radius:12px;">#</th>
                        <th style="width:200px;">NAMA LENGKAP</th>
                        <th style="width:195px;">PANGKAT</th>
                        <th style="width:70px;">GOL</th>
                        <th style="width:130px;">NRP / NIP</th>
                        <th style="width:130px;">JABATAN</th>
                        <th style="width:110px;">BAGIAN</th>
                        <th style="width:55px;">JK</th>
                        <th style="border-top-right-radius:12px;">KET</th>
                    </tr>
                </thead>
                <tbody id="previewTableBody">
                @foreach($preview as $i => $row)
                @php
                    $hasDupe = !empty($row['db_duplicate']) || !empty($row['duplicate_nrp']);
                    $trClasses = collect([
                        $row['status'] === 'corrected' ? 'row-corrected' : null,
                        $row['status'] === 'error' ? 'row-error' : null,
                        $row['status'] === 'ok' ? 'row-ok' : null,
                        $hasDupe ? 'row-duplicate' : null,
                    ])->filter()->implode(' ');
                @endphp
                <tr class="{{ $trClasses }}" id="row-{{ $i }}" data-status="{{ $row['status'] }}" data-duplicate="{{ $hasDupe ? '1' : '0' }}">

                    {{-- HANYA kirim override rank_id untuk baris yang diubah manual.
                         Data lain (nama, nrp, dll) dibaca dari session di server.
                         Ini menghindari batas max_input_vars PHP. --}}
                    @if($row['status'] === 'error' || $row['status'] === 'corrected')
                    <input type="hidden"
                           name="rank_overrides[{{ $i }}]"
                           value="{{ $row['rank_id'] ?? '' }}"
                           id="rank_id_{{ $i }}">
                    @endif

                    <td style="color:#9CA3AF; font-size:10px;">{{ $row['row_num'] }}</td>
                    <td style="font-weight:600; color:#111827;">{{ $row['full_name'] }}</td>

                    {{-- Kolom Pangkat --}}
                    <td>
                    @if($row['status'] === 'error')
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <div style="font-size:11px; color:#DC2626; display:flex; align-items:center; gap:4px;">
                                <i class="ri-error-warning-fill"></i> N/A: <strong>{{ $row['rank_input'] }}</strong>
                            </div>
                            <select onchange="updateOverride({{ $i }}, this)"
                                style="font-size:12px; padding:6px 10px; border:1.5px solid #EF4444; border-radius:6px; background:#FEF2F2; min-width:148px; color:#B91C1C; font-weight:600; cursor:pointer;">
                                <option value="">Pilih Pangkat Manual...</option>
                                @foreach($ranks as $rank)
                                <option value="{{ $rank->id }}" {{ ($row['rank_id'] == $rank->id) ? 'selected' : '' }}>
                                    {{ $rank->name }} ({{ $rank->category }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                    @elseif($row['status'] === 'corrected')
                        <div style="display:flex; flex-direction:column; gap:3px;">
                            @if($row['rank_corrected'])
                            <span style="font-size:10px; color:#92400E;">
                                @if($row['rank_input'])
                                <s style="color:#9CA3AF;">{{ $row['rank_input'] }}</s>
                                → <strong>{{ $row['rank_name'] ?? '—' }}</strong>
                                @else
                                <strong>{{ $row['rank_name'] ?? '— (Pangkat Kosong)' }}</strong>
                                @endif
                            </span>
                            <span style="font-size:10px; background:#FDE68A; color:#92400E; padding:1px 7px; border-radius:10px; font-weight:700; display:inline-block; width:fit-content;">AUTO KOREKSI</span>
                            @else
                            <span style="font-weight:600; color:#059669;">{{ $row['rank_name'] }}</span>
                            @endif
                            @if(!empty($row['incomplete_fields']))
                            <span style="font-size:9px; background:#FED7AA; color:#9A3412; padding:1px 7px; border-radius:10px; font-weight:600; display:inline-block; width:fit-content;">
                                <i class="ri-alert-line"></i> {{ implode(', ', $row['incomplete_fields']) }} kosong
                            </span>
                            @endif
                            <button type="button" onclick="toggleEdit({{ $i }})"
                                style="font-size:10px; color:#6B7280; background:none; border:none; cursor:pointer; padding:0; text-align:left;">
                                <i class="ri-edit-line"></i> Edit
                            </button>
                            <div id="rank-edit-{{ $i }}" style="display:none; margin-top:2px;">
                                <select onchange="updateOverride({{ $i }}, this)"
                                    style="font-size:11px; padding:4px 6px; border:1.5px solid #F59E0B; border-radius:6px; background:#fff; min-width:148px;">
                                    <option value="">-- Pilih Pangkat --</option>
                                    @foreach($ranks as $rank)
                                    <option value="{{ $rank->id }}" {{ ($row['rank_id'] == $rank->id) ? 'selected' : '' }}>
                                        {{ $rank->name }} ({{ $rank->category }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    @else
                        <div style="display:flex; flex-direction:column; gap:3px;">
                            <span style="font-weight:600; color:#059669;">{{ $row['rank_name'] }}</span>
                            <button type="button" onclick="toggleEdit({{ $i }})"
                                style="font-size:10px; color:#9CA3AF; background:none; border:none; cursor:pointer; padding:0; text-align:left;">
                                <i class="ri-edit-line"></i> Edit
                            </button>
                            <div id="rank-edit-{{ $i }}" style="display:none; margin-top:2px;">
                                {{-- Untuk baris OK: simpan override hanya saat dipilih --}}
                                <input type="hidden" name="rank_overrides[{{ $i }}]"
                                       value="{{ $row['rank_id'] ?? '' }}" id="rank_id_{{ $i }}">
                                <select onchange="updateOverride({{ $i }}, this)"
                                    style="font-size:11px; padding:4px 6px; border:1.5px solid #D1D5DB; border-radius:6px; background:#fff; min-width:148px;">
                                    @foreach($ranks as $rank)
                                    <option value="{{ $rank->id }}" {{ ($row['rank_id'] == $rank->id) ? 'selected' : '' }}>
                                        {{ $rank->name }} ({{ $rank->category }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    </td>

                    <td style="color:#6B7280;">{{ $row['golongan'] ?: '—' }}</td>
                    <td style="font-family:monospace; color:#374151;">
                        {{ $row['nrp'] ?: '—' }}
                        @if(!empty($row['db_duplicate']))
                        <span class="db-dupe-info">
                            <i class="ri-error-warning-fill"></i> NRP SUDAH ADA DI DATABASE<br>
                            <strong>{{ $row['db_duplicate']['full_name'] }}</strong>
                            — {{ $row['db_duplicate']['same_satker'] ? 'Satker ini' : $row['db_duplicate']['satker_name'] }}
                        </span>
                        @elseif(!empty($row['duplicate_nrp']))
                        <span style="display:block; font-size:9px; background:#FED7AA; color:#9A3412; padding:1px 7px; border-radius:10px; font-weight:700; margin-top:2px; width:fit-content; font-family:sans-serif;">
                            <i class="ri-alert-line"></i> NRP DUPLIKAT DALAM FILE
                        </span>
                        @endif
                        
                        @if(!empty($row['db_duplicate']) || !empty($row['duplicate_nrp']))
                        <div style="margin-top: 6px;">
                            <select name="action_overrides[{{ $i }}]" onchange="updateDuplicateAction({{ $i }}, this)" style="font-size: 11px; padding: 4px; border: 1px solid #EF4444; border-radius: 4px; background: #fff; color: #B91C1C; cursor: pointer;">
                                <option value="import">Tetap Unggah</option>
                                <option value="skip">❌ Abaikan Baris Ini</option>
                            </select>
                        </div>
                        @endif
                    </td>
                    <td style="color:#4B5563;">{{ $row['jabatan'] ?: '—' }}</td>
                    <td style="color:#4B5563;">{{ $row['bagian'] ?: '—' }}</td>
                    <td>
                        <span style="font-size:11px; padding:2px 8px; border-radius:12px;
                            background:{{ $row['gender'] === 'L' ? '#DBEAFE' : '#FCE7F3' }};
                            color:{{ $row['gender'] === 'L' ? '#1D4ED8' : '#BE185D' }}; font-weight:600;">
                            {{ $row['gender_raw'] }}
                        </span>
                    </td>
                    <td style="color:#6B7280; font-size:11px;">{{ $row['keterangan'] ?: '—' }}</td>
                </tr>
                @endforeach
                <tr id="emptyRow" class="hidden-row">
                    <td colspan="9" style="text-align:center; padding:48px; color:#9CA3AF;">
                        <i class="ri-inbox-line" style="font-size:36px; display:block; margin-bottom:8px; opacity:.3;"></i>
                        Tidak ada data pada kategori ini.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Footer (Tombol Konfirmasi Bawah) --}}
    <div id="footer-confirm" style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding:16px 20px; background:#F9FAFB; border-radius:12px; border:1px solid #E5E7EB; flex-wrap:wrap; gap:12px;">
        <div style="font-size:12px; color:#6B7280;">
            <i class="ri-information-line" style="color:#6366F1;"></i>
            Baris <strong>kuning</strong> sudah otomatis diperbaiki. Periksa baris <strong style="color:#DC2626;">merah</strong> lalu konfirmasi di bawah ini.
        </div>
        <div style="display:flex; gap:12px; align-items:center;">
            <button type="submit" form="cancelForm" class="btn btn-outline" style="padding: 10px 16px; border-radius: 8px; font-weight: 600;">
                <i class="ri-close-line"></i> Batalkan
            </button>
            <button type="submit" class="btn btn-primary btn-submit-import" style="background:#059669; padding:10px 24px; border-radius:8px; font-weight:700; box-shadow:0 4px 6px -1px rgba(5, 150, 105, 0.2), 0 2px 4px -1px rgba(5, 150, 105, 0.1);">
                <i class="ri-check-double-line" style="margin-right:6px;"></i> Konfirmasi Unggah
                <span style="background:rgba(255,255,255,0.25); margin-left:8px; padding:2px 8px; border-radius:12px; font-size:12px;">{{ $stats['total'] }}</span>
            </button>
        </div>
    </div>

</form>



{{-- Global Loader --}}
<div id="fullScreenLoader" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.85); z-index:9999; align-items:center; justify-content:center; flex-direction:column; backdrop-filter: blur(4px);">
    <div style="width: 50px; height: 50px; border: 4px solid #E5E7EB; border-bottom-color: #059669; border-radius: 50%; animation: spin 1s linear infinite;"></div>
    <div style="margin-top: 16px; font-weight: 700; color: #111827; font-size: 16px;" id="loaderMsg">Memproses Data...</div>
    <div style="margin-top: 4px; font-size: 12px; color: #6B7280;">Mohon jangan tutup atau refresh halaman ini</div>
</div>
@endsection

@section('styles')
<style>
    .filter-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 40px; cursor: pointer;
        font-weight: 600; font-size: 13px; border: 2px solid transparent;
        transition: all 0.18s ease; user-select: none;
    }
    .filter-pill.all   { background: #F3F4F6; color: #374151; border-color: #D1D5DB; }
    .filter-pill.ok    { background: #D1FAE5; color: #065F46; border-color: #6EE7B7; }
    .filter-pill.warn  { background: #FEF3C7; color: #92400E; border-color: #FDE68A; }
    .filter-pill.error { background: #FEE2E2; color: #B91C1C; border-color: #FECACA; }
    .filter-pill.active.all   { background: #374151; color: #fff; border-color: #374151; }
    .filter-pill.active.ok    { background: #059669; color: #fff; border-color: #059669; }
    .filter-pill.active.warn  { background: #D97706; color: #fff; border-color: #D97706; }
    .filter-pill.active.error { background: #DC2626; color: #fff; border-color: #DC2626; }
    .filter-pill .badge {
        background: rgba(0,0,0,0.08); border-radius: 20px;
        padding: 2px 8px; font-size: 12px; font-weight: 700;
    }
    .filter-pill.active .badge { background: rgba(255,255,255,0.25); }
    .row-ok       { }
    .row-corrected{ background: #FFFDF0 !important; }
    .row-error    { background: #FFF5F5 !important; }
    .row-duplicate{ background: #FEE2E2 !important; border-left: 3px solid #EF4444; }
    .hidden-row   { display: none !important; }
    .db-dupe-info { font-size: 10px; background: #FEE2E2; color: #B91C1C; padding: 3px 8px; border-radius: 8px; font-weight: 600; margin-top: 3px; display: block; width: fit-content; font-family: sans-serif; line-height: 1.4; }
    .db-dupe-info i { font-size: 11px; vertical-align: -1px; }
</style>
<style>
@keyframes spin { 100% { transform: rotate(360deg); } }
</style>
@endsection

@section('scripts')
<script>
// Index baris yang berstatus 'error' (belum ada rank_id)
const errorIndexes = @json(collect($preview)->where('status', 'error')->keys()->values());

function setFilter(status) {
    ['all','ok','corrected','error','duplicate'].forEach(function(s) {
        const p = document.getElementById('pill-' + s);
        if (p) p.classList.toggle('active', s === status);
    });

    let visible = 0;
    document.querySelectorAll('#previewTableBody tr[data-status]').forEach(function(tr) {
        const show = status === 'all'
            || tr.dataset.status === status
            || (status === 'duplicate' && tr.dataset.duplicate === '1');
        tr.classList.toggle('hidden-row', !show);
        if (show) visible++;
    });

    const empty = document.getElementById('emptyRow');
    if (empty) empty.classList.toggle('hidden-row', visible > 0);

    const headerConfirm = document.getElementById('header-confirm');
    if (headerConfirm) {
        headerConfirm.style.display = visible > 10 ? 'flex' : 'none';
    }
    
    const footerConfirm = document.getElementById('footer-confirm');
    if (footerConfirm) {
        footerConfirm.style.display = visible > 0 ? 'flex' : 'none';
    }

    const first = document.querySelector('#previewTableBody tr[data-status]:not(.hidden-row)');
    if (first) setTimeout(function(){ first.scrollIntoView({ behavior:'smooth', block:'center' }); }, 80);
}

// Update nilai hidden input rank_overrides[i]
function updateOverride(i, sel) {
    const hidden = document.getElementById('rank_id_' + i);
    if (hidden) hidden.value = sel.value;

    if (sel.value) {
        const tr = document.getElementById('row-' + i);
        if (tr && tr.dataset.status === 'error') {
            tr.style.background = '#F0FDF4';
            const badge = document.getElementById('badge-' + i);
            if (badge) { badge.textContent = '✓ SUDAH DIPILIH'; badge.style.background = '#BBF7D0'; badge.style.color = '#065F46'; }
        }
    }
    recalcPending();
}

function toggleEdit(i) {
    const el = document.getElementById('rank-edit-' + i);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function updateDuplicateAction(i, sel) {
    const tr = document.getElementById('row-' + i);
    if (!tr) return;
    
    if (sel.value === 'skip') {
        tr.style.opacity = '0.5';
        tr.style.background = '#F3F4F6';
    } else {
        tr.style.opacity = '1';
        tr.style.background = '#FEE2E2';
    }
}

function recalcPending() {
    let count = 0;
    errorIndexes.forEach(function(i) {
        const el = document.getElementById('rank_id_' + i);
        if (!el || !el.value) count++;
    });
    const el = document.getElementById('remaining-count');
    if (el) el.textContent = count;
    const wrap = document.getElementById('remaining-info');
    if (wrap) wrap.style.display = count > 0 ? 'inline-flex' : 'none';
}

// Validasi sebelum submit: tidak pakai confirm() agar tidak diblokir browser
function doConfirm(e) {
    const missing = [];
    errorIndexes.forEach(function(i) {
        const el = document.getElementById('rank_id_' + i);
        if (!el || !el.value) missing.push(i);
    });

    if (missing.length > 0) {
        e.preventDefault();
        setFilter('error');
        alert('Masih ada ' + missing.length + ' baris yang belum dipilih pangkatnya.\nLihat baris merah di atas.');
        return false;
    }
    showGlobalLoader('Sedang menyimpan data... Hampir selesai!');
    return true;
}

recalcPending();

// Initialize UI
setFilter('all');
</script>
<script>
function showGlobalLoader(msg) {
    var loader = document.getElementById('fullScreenLoader');
    if(loader) {
        if(msg) document.getElementById('loaderMsg').innerText = msg;
        loader.style.display = 'flex';
    }
}
</script>
@endsection
