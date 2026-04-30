@extends('layouts.app')

@section('title', 'Preview Sinkronisasi Personel')
@section('breadcrumb', 'Preview Import Update')



@section('content')
<div class="pv-page">

@if(session('error'))
<div class="err-alert" style="margin-bottom:16px;">
    <i class="ri-error-warning-fill" style="color:#EF4444; font-size:20px; flex-shrink:0;"></i>
    <span style="color:#B91C1C; font-weight:600;">{{ session('error') }}</span>
</div>
@endif

{{-- Form aksi tersembunyi --}}
<form action="{{ route('admin.personnel.import-update-cancel') }}" method="POST" id="cancelForm">@csrf</form>
<form action="{{ route('admin.personnel.import-update-confirm') }}" method="POST" id="confirmForm" onsubmit="return doConfirm(event)">@csrf</form>

{{-- ═══ Page Header ════════════════════════════════════════════ --}}
<div style="margin-bottom:20px;">
    <h1 style="font-size:20px; font-weight:800; color:var(--slate-900); letter-spacing:-.3px;">Preview Sinkronisasi Personel</h1>
    <p style="font-size:13px; color:var(--slate-500); margin-top:3px;">
        Satker: <strong style="color:var(--slate-700);">{{ $satker->name }}</strong>
        <span style="margin: 0 8px; color:var(--slate-300);">·</span>
        {{ $stats['total'] }} baris dari file Excel
    </p>
</div>

{{-- ═══ Stat Cards ═════════════════════════════════════════════ --}}
<div class="pv-stats">
    @php $toProcess = ($stats['update'] ?? 0) + ($stats['new'] ?? 0) + ($stats['corrected'] ?? 0); @endphp
    @if($toProcess > 0)
    <div class="pv-stat update-process">
        <div class="pv-stat-num">{{ $toProcess }}</div>
        <div class="pv-stat-lbl">Akan Diproses</div>
    </div>
    @endif
    
    @if(($stats['update'] ?? 0) > 0)
    <div class="pv-stat update-only">
        <div class="pv-stat-num">{{ $stats['update'] }}</div>
        <div class="pv-stat-lbl">Diperbarui</div>
    </div>
    @endif
    
    @if(($stats['new'] ?? 0) > 0)
    <div class="pv-stat new-only">
        <div class="pv-stat-num">{{ $stats['new'] }}</div>
        <div class="pv-stat-lbl">Personel Baru</div>
    </div>
    @endif
    
    @if(($stats['corrected'] ?? 0) > 0)
    <div class="pv-stat update-only" style="border-style: solid; border-color: #FDE68A;">
        <div class="pv-stat-num">{{ $stats['corrected'] }}</div>
        <div class="pv-stat-lbl" style="color:#B45309;">Koreksi Pangkat</div>
    </div>
    @endif
    
    <div class="pv-stat same-only">
        <div class="pv-stat-num">{{ $stats['no_change'] ?? 0 }}</div>
        <div class="pv-stat-lbl">Tidak Berubah</div>
    </div>
    
    @if(($stats['error'] ?? 0) > 0)
    <div class="pv-stat err-only">
        <div class="pv-stat-num" style="color:#DC2626;">{{ $stats['error'] }}</div>
        <div class="pv-stat-lbl" style="color:#B91C1C;">Perlu Review</div>
    </div>
    @endif
</div>


{{-- ═══ Filter Inline Row + Butom Aksi ═══════════════════════ --}}
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; gap:8px; flex-wrap:wrap;">
    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <span style="font-size:12px; font-weight:700; color:var(--slate-400); letter-spacing:.5px;">TAMPILKAN:</span>
        <button type="button" class="fp fp-all active" onclick="setFilter('all')" id="pill-all">
            Semua <span class="fp-count">{{ $stats['total'] }}</span>
        </button>
        @if(($stats['update'] ?? 0) + ($stats['corrected'] ?? 0) > 0)
        <button type="button" class="fp fp-update" onclick="setFilter('update')" id="pill-update">
            <i class="ri-refresh-line"></i> Diperbarui
            <span class="fp-count">{{ ($stats['update'] ?? 0) + ($stats['corrected'] ?? 0) }}</span>
        </button>
        @endif
        @if(($stats['new'] ?? 0) > 0)
        <button type="button" class="fp fp-new" onclick="setFilter('new')" id="pill-new">
            <i class="ri-user-add-line"></i> Baru
            <span class="fp-count">{{ $stats['new'] }}</span>
        </button>
        @endif
        @if(($stats['no_change'] ?? 0) > 0)
        <button type="button" class="fp fp-same" onclick="setFilter('no_change')" id="pill-nochange">
            <i class="ri-equals-line"></i> Sama
            <span class="fp-count">{{ $stats['no_change'] }}</span>
        </button>
        @endif
        @if(($stats['error'] ?? 0) > 0)
        <button type="button" class="fp fp-error" onclick="setFilter('error')" id="pill-error">
            <i class="ri-error-warning-line"></i> Perlu Review
            <span class="fp-count">{{ $stats['error'] }}</span>
        </button>
        @endif
    </div>
    <div style="display:flex; gap:8px; align-items:center;">
        <button type="submit" form="cancelForm" class="btn-cancel">
            <i class="ri-close-line"></i> Batal
        </button>
        <button type="submit" form="confirmForm" class="btn-confirm">
            <i class="ri-check-double-line"></i> Konfirmasi Simpan
            @php $toProcess = ($stats['update'] ?? 0) + ($stats['new'] ?? 0) + ($stats['corrected'] ?? 0); @endphp
            <span class="badge-cnt">{{ $toProcess }}</span>
        </button>
    </div>
</div>

{{-- ═══ Info + Error Alerts ═══════════════════════════════════ --}}
<div class="info-banner" style="display:flex; align-items:center;">
    <i class="ri-information-fill" style="color:#3B82F6; font-size:18px; flex-shrink:0;"></i>
    <div style="font-size:12.5px; color:#1E3A8A; display:flex; align-items:center; gap:6px; flex-wrap:wrap; line-height:1.4;">
        <strong style="font-weight:700;">Pencocokan berlapis:</strong>
        <span class="st-badge st-update" style="padding:1px 6px; font-size:10px;">via NRP</span>
        <span style="color:#60A5FA;">cocok persis NRP/NIP di DB</span>
        <span style="color:#93C5FD; margin:0 2px;">·</span>
        <span class="st-badge" style="background:#F3E8FF; color:#7E22CE; padding:1px 6px; font-size:10px;">via Nama+NRP</span>
        <span style="color:#60A5FA;">nama sama di DB, NRP baru disimpan</span>
        <span style="color:#93C5FD; margin:0 2px;">·</span>
        <span class="st-badge" style="background:#FEF3C7; color:#B45309; padding:1px 6px; font-size:10px;">via Nama</span>
        <span style="color:#60A5FA;">NRP kosong, cocok dari nama persis</span>
    </div>
</div>

@if(($stats['error'] ?? 0) > 0)
<div class="err-alert">
    <i class="ri-error-warning-fill" style="color:#EF4444; font-size:18px; flex-shrink:0;"></i>
    <div style="color:#B91C1C; font-size:12.5px;">
        <strong>{{ $stats['error'] }} baris perlu review</strong> — ada pangkat tidak dikenali atau NRP duplikat.
        Klik filter <strong>Perlu Review</strong> di atas untuk menemukannya.
    </div>
</div>
@endif

{{-- ═══ Table ══════════════════════════════════════════════════ --}}
<div class="pv-wrap">
    <div class="pv-scroll">
        <table class="pv-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th style="width:110px;">STATUS</th>
                    <th style="width:180px;">NAMA</th>
                    <th style="width:160px;">PANGKAT</th>
                    <th style="width:50px;">GOL</th>
                    <th style="width:130px;">NRP / NIP</th>
                    <th>PERUBAHAN</th>
                </tr>
            </thead>
            <tbody id="previewBody">
            @foreach($preview as $i => $row)
            @php
                $hasDupe = !empty($row['db_duplicate']) || !empty($row['duplicate_nrp']);
                $fc = match(true) {
                    $hasDupe                       => 'duplicate',
                    $row['status'] === 'error'     => 'error',
                    $row['status'] === 'no_change' => 'no_change',
                    $row['action'] === 'new'        => 'new',
                    default                         => 'update',
                };
                $rowCls = match($fc) {
                    'new'      => 'row-new',
                    'no_change'=> 'row-same',
                    'duplicate'=> 'row-dup',
                    'error'    => 'row-error',
                    default    => ($row['status'] === 'corrected') ? 'row-corrected' : 'row-update',
                };
                $matchBy = $row['match_by'] ?? 'none';
            @endphp
            <tr class="{{ $rowCls }}" data-fc="{{ $fc }}" id="row-{{ $i }}">

                {{-- Hidden rank input --}}
                @if(in_array($row['status'], ['error','corrected']))
                <input type="hidden" name="rank_overrides[{{ $i }}]"
                       value="{{ $row['rank_id'] ?? '' }}" id="rank_id_{{ $i }}">
                @endif

                {{-- # --}}
                <td style="color:var(--slate-400); font-size:11px; padding-top:12px;">{{ $row['row_num'] }}</td>

                {{-- STATUS --}}
                <td style="padding-top:12px;">
                    @if($row['status'] === 'update')
                        <span class="st-badge st-update"><i class="ri-refresh-line"></i> UPDATE</span>
                        <div>
                            @if($matchBy === 'nrp')
                                <span class="mc mc-nrp" style="background:#DBEAFE; color:#1D4ED8;">via NRP</span>
                            @elseif($matchBy === 'name_add_nrp')
                                <span class="mc" style="background:#EDE9FE; color:#6D28D9;">via Nama+NRP</span>
                            @else
                                <span class="mc mc-name" style="background:#FEF3C7; color:#92400E;">via Nama</span>
                            @endif
                        </div>
                    @elseif($row['status'] === 'corrected')
                        <span class="st-badge st-corrected">✨ KOREKSI</span>
                        @if($row['action'] === 'update')
                            <div><span class="mc mc-nrp" style="background:#DBEAFE; color:#1D4ED8; margin-top:3px;">UPDATE</span></div>
                        @else
                            <div><span class="mc" style="background:var(--new-bg); color:var(--new-text); margin-top:3px;">BARU</span></div>
                        @endif
                    @elseif($row['status'] === 'new')
                        <span class="st-badge st-new"><i class="ri-user-add-line"></i> BARU</span>
                    @elseif($row['status'] === 'no_change')
                        <span class="st-badge st-same"><i class="ri-minus-line"></i> SAMA</span>
                    @else
                        @if($hasDupe)
                            <span class="st-badge st-dup" style="background:#FEE2E2; color:#991B1B;"><i class="ri-file-copy-line"></i> DUPLIKAT</span>
                        @else
                            <span class="st-badge st-error"><i class="ri-error-warning-line"></i> ERROR</span>
                        @endif
                    @endif
                </td>

                {{-- NAMA --}}
                <td style="padding-top:12px;">
                    <div style="font-weight:700; color:var(--slate-800); font-size:13px; line-height:1.3;">{{ $row['full_name'] }}</div>
                    @if(!empty($row['existing_name']) && $row['action'] === 'update' && $row['existing_name'] !== $row['full_name'])
                        <div style="font-size:10.5px; color:var(--slate-400); margin-top:2px;">DB: {{ $row['existing_name'] }}</div>
                    @endif
                </td>

                {{-- PANGKAT --}}
                <td style="padding-top:10px;">
                    @if($row['status'] === 'error' && ($row['error_type'] ?? '') === 'unknown_rank')
                        <div style="font-size:11px; color:#DC2626; font-weight:600; margin-bottom:4px;">
                            <i class="ri-error-warning-fill"></i> "{{ $row['rank_input'] }}"
                        </div>
                        <select onchange="updateOverride({{ $i }}, this)" class="rank-sel">
                            <option value="">— Pilih pangkat —</option>
                            @foreach($ranks as $rank)
                            <option value="{{ $rank->id }}" {{ ($row['rank_id'] == $rank->id) ? 'selected' : '' }}>
                                {{ $rank->name }}
                            </option>
                            @endforeach
                        </select>
                    @elseif($row['status'] === 'corrected' && $row['rank_corrected'])
                        <div style="font-size:10.5px; color:var(--slate-400); text-decoration:line-through; margin-bottom:2px;">{{ $row['rank_input'] }}</div>
                        <div style="font-weight:700; color:#059669; font-size:12.5px;">{{ $row['rank_name'] }}</div>
                        <div id="rank-edit-{{ $i }}" style="display:none; margin-top:4px;">
                            <select onchange="updateOverride({{ $i }}, this)" class="rank-sel rank-sel-warn" style="font-size:11px; width:100%;">
                                <option value="">— Pilih —</option>
                                @foreach($ranks as $rank)
                                <option value="{{ $rank->id }}" {{ ($row['rank_id'] == $rank->id) ? 'selected' : '' }}>{{ $rank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" onclick="toggleEdit({{ $i }})"
                            style="font-size:10px; color:var(--slate-400); background:none; border:none; cursor:pointer; padding:2px 0; margin-top:2px;">
                            <i class="ri-edit-line"></i> Ubah
                        </button>
                    @else
                        <span style="font-weight:600; color:{{ $row['rank_name'] ? 'var(--slate-700)' : 'var(--slate-400)' }}; font-size:12.5px;">
                            {{ $row['rank_name'] ?? '—' }}
                        </span>
                    @endif
                </td>

                {{-- GOL --}}
                <td style="color:var(--slate-500); font-size:12px; padding-top:12px;">{{ $row['golongan'] ?: '—' }}</td>

                {{-- NRP / NIP --}}
                <td style="padding-top:12px;">
                    @if(!empty($row['nrp']))
                        <span class="nrp-cell">{{ $row['nrp'] }}</span>
                        @if(!empty($row['db_duplicate']))
                        <span class="db-dupe-info">
                            <i class="ri-error-warning-fill"></i> NRP SUDAH ADA DI DB<br>
                            <strong>{{ $row['db_duplicate']['full_name'] }}</strong>
                            — {{ $row['db_duplicate']['same_satker'] ? 'Satker ini' : $row['db_duplicate']['satker_name'] }}
                        </span>
                        @elseif(!empty($row['duplicate_nrp']))
                        <span style="display:block; font-size:9px; background:#FED7AA; color:#9A3412; padding:1px 7px; border-radius:10px; font-weight:700; margin-top:2px; width:fit-content; font-family:sans-serif;">
                            <i class="ri-alert-line"></i> NRP DUPLIKAT DI FILE INI
                        </span>
                        @endif
                    @else
                        <span class="nrp-empty">Tanpa NRP</span>
                    @endif
                </td>

                {{-- PERUBAHAN --}}
                <td style="padding-top:10px;">
                    @if(in_array($row['status'], ['update','corrected']) && $row['action'] === 'update')
                        @if(!empty($row['diff']))
                        <div class="diff-list">
                            @foreach($row['diff'] as $lbl => $ch)
                            <div class="diff-row">
                                <span class="diff-lbl" title="{{ $lbl }}">{{ $lbl }}</span>
                                <span class="diff-from">{{ $ch['from'] }}</span>
                                <span class="diff-arr">→</span>
                                <span class="diff-to">{{ $ch['to'] }}</span>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <span style="font-size:11.5px; color:var(--slate-400); font-style:italic;">Data sudah sama</span>
                        @endif

                    @elseif($row['action'] === 'new' || ($row['status'] === 'corrected' && $row['action'] === 'new'))
                        <span style="font-size:12px; color:var(--new-text); font-weight:600;">
                            <i class="ri-user-add-line"></i>
                            {{ empty($row['nrp']) ? 'Ditambahkan tanpa NRP' : 'Personel baru' }}
                        </span>
                        @if(!empty($row['jabatan']))
                        <div style="margin-top:4px; font-size:11px; color:var(--slate-500);">{{ $row['jabatan'] }}</div>
                        @endif

                    @elseif($row['status'] === 'no_change')
                        <span style="font-size:11.5px; color:var(--slate-400);">
                            <i class="ri-check-line"></i> Tidak ada perubahan
                        </span>

                    @elseif($row['status'] === 'error')
                        <span style="font-size:11.5px; color:{{ ($row['error_type'] ?? '') === 'duplicate' ? '#B45309' : '#DC2626' }}; font-weight:600;">
                            {{ $row['skip_reason'] }}
                        </span>
                    @endif
                </td>
            </tr>
            @endforeach

            {{-- Empty state --}}
            <tr id="emptyRow" style="display:none;">
                <td colspan="7" class="pv-empty">
                    <i class="ri-inbox-line"></i>
                    <p>Tidak ada data pada kategori ini.</p>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ═══ Legend ══════════════════════════════════════════════════ --}}
<div class="pv-legend">
    <span><span class="leg-dot" style="background:#EFF6FF; border:1.5px solid #BFDBFE;"></span>Diperbarui</span>
    <span><span class="leg-dot" style="background:#ECFDF5; border:1.5px solid #A7F3D0;"></span>Baru</span>
    <span><span class="leg-dot" style="background:#FAFAFA; border:1.5px solid #E2E8F0;"></span>Sama (tidak diproses)</span>
    <span><span class="leg-dot" style="background:#FFFBEB; border:1.5px solid #FDE68A;"></span>Koreksi pangkat</span>
    <span><span class="leg-dot" style="background:#FEF2F2; border:1.5px solid #FECACA;"></span>Perlu review manual</span>
</div>

</form>
@endsection

@section('styles')
<style>
/* ─── Variables ────────────────────────────────────────────── */
:root {
    --update-bg: #EFF6FF; --update-border: #BFDBFE; --update-text: #1D4ED8;
    --new-bg:    #ECFDF5; --new-border:    #A7F3D0; --new-text:    #065F46;
    --same-bg:   #F8FAFC; --same-border:   #E2E8F0; --same-text:   #64748B;
    --corr-bg:   #FFFBEB; --corr-border:   #FDE68A; --corr-text:   #92400E;
    --err-bg:    #FEF2F2; --err-border:    #FECACA; --err-text:    #B91C1C;
    --dup-bg:    #FEF3C7; --dup-border:    #FDE68A; --dup-text:    #B45309;
}

/* ─── Page layout ───────────────────────────────────────────── */
.pv-page { max-width: 1280px; }

/* ─── Top bar (sticky action bar) ──────────────────────────── */
.pv-topbar {
    position: sticky; top: 56px; z-index: 40;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(12px) saturate(180%);
    -webkit-backdrop-filter: blur(12px) saturate(180%);
    border-bottom: 1px solid var(--slate-200);
    padding: 10px 0;
    margin: -24px -28px 24px;
    padding: 10px 28px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    flex-wrap: wrap;
}
.pv-topbar-left { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pv-topbar-right { display: flex; align-items: center; gap: 8px; }

/* ─── Stat grid ─────────────────────────────────────────────── */
.pv-stats {
    display: flex; gap: 14px; margin-bottom: 24px; flex-wrap: wrap;
}
.pv-stat {
    border-radius: 6px; padding: 14px 18px; min-width: 140px; text-align: left;
    background: #fff;
    border: 1px solid var(--slate-200);
}
.pv-stat-num  { font-size: 26px; font-weight: 800; letter-spacing: -.5px; line-height: 1; margin-bottom: 6px; color: var(--slate-800); }
.pv-stat-lbl  { font-size: 11.5px; font-weight: 700; color: var(--slate-500); }

/* Custom borders */
.pv-stat.update-process { border-left: 5px solid var(--slate-600); }
.pv-stat.update-only { border: 1.5px dashed var(--slate-400); }
.pv-stat.new-only { border-left: 5px solid #059669; }
.pv-stat.same-only { border-left: 5px solid var(--slate-400); opacity: 0.9; }
.pv-stat.err-only { border-left: 5px solid #DC2626; border-color:#FCA5A5; }


/* ─── Filter pills ──────────────────────────────────────────── */
.fp {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 30px;
    font-size: 12.5px; font-weight: 700; cursor: pointer;
    border: 2px solid transparent; transition: all .18s;
    user-select: none; white-space: nowrap;
    text-decoration: none;
}
/* ── Inactive (default) ── */
.fp-all    { background:#E2E8F0; color:#374151;   border-color:#CBD5E1; }
.fp-update { background:#DBEAFE; color:#1E40AF;   border-color:#93C5FD; }
.fp-new    { background:#D1FAE5; color:#065F46;   border-color:#6EE7B7; }
.fp-same   { background:#F1F5F9; color:#475569;   border-color:#CBD5E1; }
.fp-error  { background:#FEE2E2; color:#991B1B;   border-color:#FCA5A5; }
/* ── Active (selected) ── */
.fp-all.active    { background:#334155; color:#fff; border-color:#334155;
    box-shadow: 0 2px 10px rgba(51,65,85,.35); }
.fp-update.active { background:#2563EB; color:#fff; border-color:#2563EB;
    box-shadow: 0 2px 10px rgba(37,99,235,.4); }
.fp-new.active    { background:#059669; color:#fff; border-color:#059669;
    box-shadow: 0 2px 10px rgba(5,150,105,.4); }
.fp-same.active   { background:#64748B; color:#fff; border-color:#64748B;
    box-shadow: 0 2px 10px rgba(100,116,139,.35); }
.fp-error.active  { background:#DC2626; color:#fff; border-color:#DC2626;
    box-shadow: 0 2px 10px rgba(220,38,38,.4); }
.fp-count {
    background: rgba(0,0,0,.12); border-radius: 20px;
    padding: 1px 7px; font-size: 11px;
}
.fp.active .fp-count { background: rgba(255,255,255,.28); }

/* ─── Table ─────────────────────────────────────────────────── */
.pv-wrap { border-radius: 12px; border: 1px solid var(--slate-200); overflow: hidden; background:#fff; }
.pv-table { width: 100%; border-collapse: collapse; font-size: 12.5px; min-width: 900px; }
.pv-table thead th {
    background: var(--slate-50); color: var(--slate-500);
    font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px;
    padding: 10px 14px; border-bottom: 1px solid var(--slate-200); text-align: left;
    white-space: nowrap;
}
.pv-table tbody tr { border-bottom: 1px solid var(--slate-100); }
.pv-table tbody tr:last-child { border-bottom: none; }
.pv-table tbody td { padding: 10px 14px; vertical-align: top; }
.row-update   { }
.row-corrected{ background: #FFFDF0 !important; }
.row-new      { background: #F0FDF8 !important; }
.row-same     { background: #FAFAFA !important; opacity: .75; }
.row-error    { background: #FFF5F5 !important; }
.row-dup      { background: #FEE2E2 !important; border-left: 3px solid #EF4444; }

.db-dupe-info { font-size: 10px; background: #FEE2E2; color: #B91C1C; padding: 3px 8px; border-radius: 8px; font-weight: 600; margin-top: 3px; display: block; width: fit-content; font-family: sans-serif; line-height: 1.4; }
.db-dupe-info i { font-size: 11px; vertical-align: -1px; }

/* Status badge */
.st-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; border-radius: 20px; font-size: 10.5px; font-weight: 700;
    white-space: nowrap;
}
.st-update    { background: var(--update-bg); color: var(--update-text); }
.st-new       { background: var(--new-bg);    color: var(--new-text); }
.st-same      { background: var(--same-bg);   color: var(--same-text); }
.st-corrected { background: var(--corr-bg);   color: var(--corr-text); }
.st-error     { background: var(--err-bg);    color: var(--err-text); }
.st-dup       { background: var(--dup-bg);    color: var(--dup-text); }

/* Match chip */
.mc { font-size: 9px; padding: 1.5px 5px; border-radius: 4px; font-weight: 700; display: inline-block; margin-top: 3px; }
.mc-nrp  { background: #DBEAFE; color: #1D4ED8; }
.mc-name+nrp { background: #EDE9FE; color: #6D28D9; }
.mc-name { background: #FEF3C7; color: #92400E; }

/* Diff list */
.diff-list { display: flex; flex-direction: column; gap: 4px; }
.diff-row {
    display: grid; grid-template-columns: 90px 1fr 18px 1fr;
    gap: 4px; align-items: center; font-size: 11.5px; line-height: 1.3;
}
.diff-lbl  { color: var(--slate-500); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.diff-from { color: #DC2626; background: #FEF2F2; border-radius: 4px; padding: 1px 5px; text-decoration: line-through; font-size: 11px; word-break: break-word; }
.diff-arr  { color: var(--slate-400); text-align: center; font-size: 12px; }
.diff-to   { color: #059669; background: #F0FDF4; border-radius: 4px; padding: 1px 5px; font-weight: 700; font-size: 11px; word-break: break-word; }

/* NRP monospace */
.nrp-cell { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 11.5px; color: var(--slate-700); letter-spacing: .3px; }
.nrp-empty { color: var(--slate-400); font-style: italic; font-size: 11px; }

/* Empty state */
.pv-empty { text-align: center; padding: 60px 20px; }
.pv-empty i { font-size: 48px; color: var(--slate-300); display: block; margin-bottom: 12px; }
.pv-empty p { color: var(--slate-400); font-size: 13px; }

/* Info banner ─────── */
.info-banner {
    background: var(--info-bg); border: 1px solid var(--info-border);
    border-radius: 10px; padding: 12px 16px;
    display: flex; gap: 10px; align-items: flex-start;
    margin-bottom: 16px; font-size: 12.5px; color: #1E3A5F; line-height: 1.7;
}
.mc-inline {
    display: inline-flex; align-items: center; gap: 2px;
    font-size: 10px; padding: 1px 6px; border-radius: 4px; font-weight: 700;
    vertical-align: middle;
}

/* Error alert */
.err-alert {
    background: var(--err-bg); border: 1px solid var(--err-border);
    border-radius: 10px; padding: 10px 14px; margin-bottom: 14px;
    display: flex; gap: 10px; align-items: center; font-size: 12.5px;
}

/* Rank select */
.rank-sel {
    font-size: 11.5px; padding: 5px 8px; min-width: 160px;
    border: 1.5px solid #EF4444; border-radius: 6px;
    background: var(--err-bg); color: var(--err-text); font-weight: 600;
    width: 100%; margin-top: 4px; cursor: pointer;
}
.rank-sel-warn {
    border-color: #F59E0B; background: var(--warning-bg); color: #92400E;
}

/* Footer legend */
.pv-legend {
    margin-top: 14px; padding: 10px 14px;
    background: var(--slate-50); border-radius: 8px; border: 1px solid var(--slate-200);
    font-size: 11.5px; color: var(--slate-500);
    display: flex; gap: 16px; flex-wrap: wrap; align-items: center;
}
.leg-dot { display: inline-block; width: 8px; height: 8px; border-radius: 2px; margin-right: 4px; }

/* Scroll area */
.pv-scroll { overflow-x: auto; }

/* Btn variants for topbar */
.btn-cancel {
    background: #fff; border: 1.5px solid var(--slate-200);
    color: var(--slate-600); padding: 7px 14px; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex;
    align-items: center; gap: 6px; font-family: inherit; transition: all .15s;
}
.btn-cancel:hover { border-color: var(--slate-300); background: var(--slate-50); }
.btn-confirm {
    background: linear-gradient(135deg, #1D4ED8, #2563EB);
    color: #fff; padding: 8px 20px; border-radius: 8px;
    font-size: 13px; font-weight: 700; cursor: pointer; border: none;
    display: inline-flex; align-items: center; gap: 7px; font-family: inherit;
    transition: all .18s; box-shadow: 0 2px 8px rgba(37,99,235,.35);
}
.btn-confirm:hover { background: linear-gradient(135deg, #1E40AF, #1D4ED8); box-shadow: 0 4px 14px rgba(37,99,235,.45); transform: translateY(-1px); }
.badge-cnt {
    background: rgba(255,255,255,.25); border-radius: 20px;
    padding: 1px 8px; font-size: 12px;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    const errorIndexes = @json(
        collect($preview)
            ->filter(fn($r) => ($r['status'] === 'error') && (($r['error_type'] ?? '') === 'unknown_rank'))
            ->keys()
            ->values()
    );

    const PILL_MAP = {
        'all':'pill-all', 'update':'pill-update', 'new':'pill-new',
        'no_change':'pill-nochange', 'error':'pill-error'
    };

    window.setFilter = function(cat) {
        Object.values(PILL_MAP).forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.classList.remove('active');
        });
        const pillId = PILL_MAP[cat] || ('pill-' + cat);
        const active = document.getElementById(pillId);
        if (active) active.classList.add('active');

        let visible = 0;
        document.querySelectorAll('#previewBody tr[data-fc]').forEach(function(tr) {
            const fc = tr.getAttribute('data-fc');
            const show = (cat === 'all') || (fc === cat);
            tr.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        const emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.style.display = (visible === 0) ? '' : 'none';
    };

    window.updateOverride = function(i, sel) {
        const hidden = document.getElementById('rank_id_' + i);
        if (hidden) hidden.value = sel.value;
        if (sel.value) {
            const tr = document.getElementById('row-' + i);
            if (tr) tr.style.background = '#FEFCE8';
        }
    };

    window.toggleEdit = function(i) {
        const el = document.getElementById('rank-edit-' + i);
        if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
    };

    window.doConfirm = function(e) {
        const missing = [];
        errorIndexes.forEach(function(i) {
            const el = document.getElementById('rank_id_' + i);
            if (!el || !el.value) missing.push(i);
        });
        if (missing.length > 0) {
            e.preventDefault();
            setFilter('error');
            alert('Masih ada ' + missing.length + ' baris pangkat tidak dikenali yang belum dipilih secara manual.');
            return false;
        }
        showGlobalLoader('Sedang menyimpan update data... Hampir selesai!');
        return true;
    };

    // Sembunyikan emptyRow saat pertama load
    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) emptyRow.style.display = 'none';

    // Tampilkan semua saat load
    setFilter('all');
});
</script>

{{-- Global Loader --}}
<div id="fullScreenLoader" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.85); z-index:9999; align-items:center; justify-content:center; flex-direction:column; backdrop-filter: blur(4px);">
    <div style="width: 50px; height: 50px; border: 4px solid #E5E7EB; border-bottom-color: #059669; border-radius: 50%; animation: spin 1s linear infinite;"></div>
    <div style="margin-top: 16px; font-weight: 700; color: #111827; font-size: 16px;" id="loaderMsg">Memproses Data...</div>
    <div style="margin-top: 4px; font-size: 12px; color: #6B7280;">Mohon jangan tutup atau refresh halaman ini</div>
</div>
<style>
@keyframes spin { 100% { transform: rotate(360deg); } }
</style>
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
