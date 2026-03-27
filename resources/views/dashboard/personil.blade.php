@extends('layouts.app')

@section('title', 'Dashboard Personil')
@section('page-title', 'Dashboard Personil')
@section('breadcrumb', 'Dashboard')
@section('page-subtitle', 'Tahun Anggaran ' . $fiscalYear)

@section('styles')
<style>
    .form-card { padding: 20px; border: 1px solid var(--border-color); border-radius: var(--radius-lg); margin-bottom: 24px; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-main); font-size: 13px; }
    .form-control { width: 100%; padding: 10px 14px; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); font-family: inherit; transition: all 0.2s; }
    .form-control:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1); }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
    .btn-submit { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; background: var(--brand); color: #fff; border: none; border-radius: var(--radius-md); font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; width: 100%; }
    .btn-submit:hover { background: #B71C1C; transform: translateY(-1px); }
    .colored-toast.swal2-icon-success { background-color: var(--success-bg) !important; color: var(--success) !important; }
    .colored-toast .swal2-title { color: var(--success) !important; }
    
    /* Testimoni Rating */
    .rating-wrapper { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 8px; }
    .rating-wrapper input { display: none; }
    .rating-wrapper label { cursor: pointer; color: var(--slate-300); font-size: 24px; transition: color 0.2s; }
    .rating-wrapper label:hover, .rating-wrapper label:hover ~ label, .rating-wrapper input:checked ~ label { color: #F59E0B; }
    
    .btn-cancel:hover { background: var(--slate-100) !important; color: var(--danger) !important; border-color: var(--danger-border) !important; }

    /* ══ Mobile Responsive — Personnel Dashboard ══ */
    @media (max-width: 768px) {
        /* Profile card: stack vertical */
        .personil-profile-body {
            flex-direction: column !important;
            align-items: center !important;
            text-align: center;
            padding: 20px 16px !important;
            gap: 16px !important;
        }
        .personil-profile-body > div:nth-child(2) {
            min-width: unset !important;
        }
        .personil-profile-body .profile-meta {
            justify-content: center !important;
        }
        .personil-profile-body .profile-avatar {
            width: 52px !important;
            height: 52px !important;
            font-size: 20px !important;
            border-radius: 14px !important;
        }
        .personil-profile-body .profile-name {
            font-size: 17px !important;
        }

        /* Status badge: full width center */
        .personil-status-badge {
            width: 100%;
            justify-content: center !important;
        }

        /* Status cards grid: single column */
        .personil-stats-grid {
            grid-template-columns: 1fr !important;
            gap: 16px !important;
        }
        .personil-stat-card {
            padding: 20px 16px !important;
        }
        .personil-stat-card .stat-number {
            font-size: 28px !important;
        }
        .personil-stat-card .stat-suffix {
            font-size: 13px !important;
        }
        .personil-stat-card .stat-icon-circle {
            width: 56px !important;
            height: 56px !important;
            font-size: 26px !important;
        }
        .personil-stat-card .stat-desc {
            font-size: 13px !important;
        }

        /* Form card: tighter padding */
        .form-card {
            padding: 14px !important;
        }
        .form-grid {
            grid-template-columns: 1fr !important;
            gap: 14px !important;
        }

        /* Card header/body padding */
        .personil-card-header {
            padding: 16px !important;
        }
        .personil-card-body {
            padding: 16px !important;
        }
        .personil-card-header h3 {
            font-size: 15px !important;
        }

        /* Summary grid: 2 columns on mobile */
        .personil-summary-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 10px !important;
        }

        /* Table: compact cells */
        .personil-table th,
        .personil-table td {
            padding: 10px 12px !important;
            font-size: 12px !important;
        }
        .personil-table .cell-category {
            font-size: 10px !important;
            padding: 3px 6px !important;
        }
        .personil-table .cell-size-badge {
            min-width: 28px !important;
            height: 28px !important;
            font-size: 12px !important;
        }

        /* Buttons: full-width stacked */
        .personil-form-actions {
            flex-direction: column !important;
            gap: 10px !important;
        }
        .personil-form-actions .btn-submit,
        .personil-form-actions .btn-cancel {
            max-width: 100% !important;
            width: 100% !important;
        }

        /* Summary card header: stack if too narrow */
        .personil-summary-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 12px !important;
        }
        .personil-summary-header .btn {
            width: 100% !important;
            justify-content: center !important;
        }

        /* Testimoni: tighter */
        .personil-testimoni-body {
            padding: 16px !important;
        }
        .personil-testimoni-body .form-group textarea {
            font-size: 14px !important;
        }
        .personil-testimoni-actions {
            text-align: center !important;
        }
        .personil-testimoni-actions .btn {
            width: 100% !important;
            justify-content: center !important;
        }
    }

    @media (max-width: 480px) {
        /* Even smaller screens */
        .personil-profile-body .profile-avatar {
            width: 44px !important;
            height: 44px !important;
            font-size: 17px !important;
            border-radius: 12px !important;
        }
        .personil-profile-body .profile-name {
            font-size: 15px !important;
        }
        .personil-stat-card .stat-number {
            font-size: 24px !important;
        }
        .personil-stat-card .stat-icon-circle {
            width: 48px !important;
            height: 48px !important;
            font-size: 22px !important;
        }
        .personil-summary-grid {
            grid-template-columns: 1fr !important;
        }
        .personil-status-badge {
            padding: 8px 14px !important;
            font-size: 12px !important;
        }
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div class="content">

{{-- Profile Summary --}}
<div class="card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); overflow: hidden; position: relative; margin-bottom: 24px;">
    <div style="position: absolute; right: -50px; top: -50px; width: 150px; height: 150px; background: var(--brand); opacity: 0.1; filter: blur(40px); border-radius: 50%; pointer-events: none;"></div>
    <div style="position: absolute; left: 20%; bottom: -40px; width: 100px; height: 100px; background: var(--accent); opacity: 0.05; filter: blur(30px); border-radius: 50%; pointer-events: none;"></div>

    <div class="card-body personil-profile-body" style="display:flex; align-items:center; gap:24px; flex-wrap:wrap; padding: 24px 32px; background: var(--bg-card); position: relative; z-index: 1;">
        <div class="profile-avatar" style="width:64px; height:64px; border-radius:16px; background:linear-gradient(135deg, var(--brand), var(--brand-light)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:24px; font-weight:700; flex-shrink:0; box-shadow: 0 4px 12px rgba(var(--brand-rgb, 198,40,40), 0.2);">
            {{ strtoupper(substr($user->name, 0, 2)) }}
        </div>
        <div style="flex:1; min-width:200px;">
            <div class="profile-name" style="font-size:20px; font-weight:800; color: var(--text-main); letter-spacing: -0.2px;">{{ $user->name }}</div>
            <div class="profile-meta" style="font-size:14px; color: var(--text-muted); margin-top:4px; display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                <span style="display: inline-flex; align-items: center; gap: 4px;"><i class="ri-fingerprint-line" style="color: var(--slate-400);"></i> <strong>{{ $user->nrp_nip }}</strong></span>
                @if($personnel)
                    <span style="color: var(--slate-300);">•</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><i class="ri-medal-2-line" style="color: var(--slate-400);"></i> {{ $personnel->rank->name ?? '' }}</span>
                    <span style="color: var(--slate-300);">•</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><i class="ri-building-4-line" style="color: var(--slate-400);"></i> {{ $personnel->satker->name ?? '' }}</span>
                @endif
            </div>
        </div>
        <div>
            @if($hasSubmitted)
                <span class="personil-status-badge" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 9999px; font-size: 13px; font-weight: 700; background: var(--success-bg); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); box-shadow: 0 2px 8px rgba(16, 185, 129, 0.1);">
                    <i class="ri-shield-check-fill" style="font-size: 16px;"></i> Sudah Input TA {{ $fiscalYear }}
                </span>
            @else
                <span class="personil-status-badge" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 9999px; font-size: 13px; font-weight: 700; background: var(--warning-bg); color: #B45309; border: 1px solid var(--warning-border); box-shadow: 0 2px 8px rgba(245, 158, 11, 0.1);">
                    <i class="ri-error-warning-fill" style="font-size: 16px;"></i> Belum Input TA {{ $fiscalYear }}
                </span>
            @endif
        </div>
    </div>
</div>

{{-- FORM INPUT UTAMA (PRIORITAS DEPAN) --}}
<div id="kaporFormCard" class="card" style="border: none; box-shadow: var(--shadow-md); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 24px; background: var(--bg-card); display: {{ $hasSubmitted ? 'none' : 'block' }};">
    <div class="card-header" style="background: var(--bg-card); border-bottom: 1px solid var(--border-color); padding: 20px 24px;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(var(--brand-rgb, 198,40,40), 0.1); color: var(--brand);">
                <i class="ri-edit-box-line" style="font-size: 18px;"></i>
            </span>
            Form Biodata Kelengkapan Kaporlap
        </h3>
        <p style="margin: 8px 0 0 0; font-size: 13px; color: var(--text-muted);">Silakan lengkapi atau perbarui ukuran atribut lapangan Anda di bawah ini secara akurat.</p>
    </div>
    
    <div class="card-body" style="padding: 24px;">
        @php
            $s_head = range(54, 60);
            $s_shirt_m = ['14', '14,5', '15', '15,5', '16', '16,5', '17', '17,5', '18', '18,5', '19', '19,5', '20', '21', '22'];
            $s_wom = ['K', 'SD', 'B', 'EB', 'EEB', 'EEEB', 'EEEEB'];
            $s_pants_m = range(27, 50);
            $s_shoes = range(36, 48);
            $s_belt = range(36, 60, 2);
            $s_jilbab = ['K', 'SD', 'B'];
            
            $gender = optional(auth()->user()->personnel)->gender ?? 'L';
        @endphp

        <form action="{{ route('personil.kapor.store') }}" method="POST" id="kaporForm">
            @csrf
            
            <div class="form-card">
                <h4 style="margin: 0 0 16px 0; font-size: 15px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Tutup Badan & Pakaian</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Kemeja</label>
                        <select name="kemeja" class="form-control select-control" required>
                            <option value="">— Pilih —</option>
                            @if($gender === 'L')
                                @foreach($s_shirt_m as $s)
                                    <option value="{{ $s }}" {{ old('kemeja', $kaporSizes['kemeja'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            @else
                                @foreach($s_wom as $s)
                                    <option value="{{ $s }}" {{ old('kemeja', $kaporSizes['kemeja'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Celana / Rok</label>
                        <select name="celana" class="form-control select-control" required>
                            <option value="">— Pilih —</option>
                            @if($gender === 'L')
                                @foreach($s_pants_m as $s)
                                    <option value="{{ $s }}" {{ old('celana', $kaporSizes['celana'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            @else
                                @foreach($s_wom as $s)
                                    <option value="{{ $s }}" {{ old('celana', $kaporSizes['celana'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">T.Shirt / Kaos Olahraga</label>
                        <select name="olahraga" class="form-control select-control" required>
                            <option value="">— Pilih —</option>
                            @foreach($s_wom as $s)
                                <option value="{{ $s }}" {{ old('olahraga', $kaporSizes['olahraga'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jaket</label>
                        <select name="jaket" class="form-control select-control" required>
                            <option value="">— Pilih —</option>
                            @foreach($s_wom as $s)
                                <option value="{{ $s }}" {{ old('jaket', $kaporSizes['jaket'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <h4 style="margin: 0 0 16px 0; font-size: 15px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Perlengkapan Kepala & Sabuk</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Tutup Kepala (Topi/Baret)</label>
                        <select name="topi" class="form-control select-control" required>
                            <option value="">— Pilih —</option>
                            @foreach($s_head as $s)
                                <option value="{{ $s }}" {{ old('topi', $kaporSizes['topi'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sabuk</label>
                        <select name="sabuk" class="form-control select-control" required>
                            <option value="">— Pilih —</option>
                            @foreach($s_belt as $s)
                                <option value="{{ $s }}" {{ old('sabuk', $kaporSizes['sabuk'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($gender === 'P')
                    <div class="form-group">
                        <label class="form-label">Jilbab <span style="color: var(--brand); font-size: 11px; margin-left: 4px;">(Khusus Polwan)</span></label>
                        <select name="jilbab" class="form-control select-control" required>
                            <option value="">— Pilih —</option>
                            @foreach($s_jilbab as $s)
                                <option value="{{ $s }}" {{ old('jilbab', $kaporSizes['jilbab'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            </div>

            <div class="form-card" style="margin-bottom: 0;">
                <h4 style="margin: 0 0 16px 0; font-size: 15px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Tutup Kaki & Sepatu</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Sepatu Dinas</label>
                        <select name="sepatu_dinas" class="form-control select-control" required>
                            <option value="">— Pilih —</option>
                            @foreach($s_shoes as $s)
                                <option value="{{ $s }}" {{ old('sepatu_dinas', $kaporSizes['sepatu_dinas'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sepatu Olahraga</label>
                        <select name="sepatu_olahraga" class="form-control select-control" required>
                            <option value="">— Pilih —</option>
                            @foreach($s_shoes as $s)
                                <option value="{{ $s }}" {{ old('sepatu_olahraga', $kaporSizes['sepatu_olahraga'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="personil-form-actions" style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                @if($hasSubmitted)
                <button type="button" onclick="document.getElementById('kaporFormCard').style.display='none'; document.getElementById('kaporSummaryCard').style.display='block';" class="btn btn-cancel" style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); padding: 12px 24px; font-size: 15px; font-weight: 600; border-radius: var(--radius-md); font-family: inherit; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: all 0.2s;">
                    <i class="ri-close-line"></i> Batal
                </button>
                @endif
                <button type="submit" class="btn-submit" style="max-width: 250px;">
                    <i class="ri-save-line"></i> Simpan Pilihan Form
                </button>
            </div>
        </form>
    </div>
</div>

@if($hasSubmitted)
<div id="kaporSummaryCard" class="card" style="border: none; box-shadow: var(--shadow-md); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 24px; background: var(--bg-card);">
    <div class="card-header personil-card-header" style="background: var(--bg-card); border-bottom: 1px solid var(--border-color); padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: var(--success);">
                    <i class="ri-checkbox-circle-fill" style="font-size: 18px;"></i>
                </span>
                Form Biodata Kelengkapan Kaporlap
            </h3>
            <p style="margin: 8px 0 0 0; font-size: 13px; color: var(--text-muted);">Data Kaporlap Anda untuk TA {{ $fiscalYear }} telah terekam.</p>
        </div>
        <button type="button" onclick="document.getElementById('kaporFormCard').style.display='block'; document.getElementById('kaporSummaryCard').style.display='none';" class="btn" style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 8px; font-family: inherit; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s;">
            <i class="ri-edit-2-line"></i> Edit Data
        </button>
    </div>
    
    <div class="card-body personil-card-body" style="padding: 24px;">
        <div class="personil-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
            @php
                $summaryItems = [
                    'Kemeja' => $kaporSizes['kemeja'] ?? '-',
                    'Celana/Rok' => $kaporSizes['celana'] ?? '-',
                    'Olahraga' => $kaporSizes['olahraga'] ?? '-',
                    'Jaket' => $kaporSizes['jaket'] ?? '-',
                    'Tutup Kepala' => $kaporSizes['topi'] ?? '-',
                    'Sabuk' => $kaporSizes['sabuk'] ?? '-',
                    'Jilbab' => $kaporSizes['jilbab'] ?? '-',
                    'Sepatu Dinas' => $kaporSizes['sepatu_dinas'] ?? '-',
                    'Sepatu Olahraga' => $kaporSizes['sepatu_olahraga'] ?? '-',
                ];
            @endphp
            @foreach($summaryItems as $label => $val)
                @if($label === 'Jilbab' && $gender !== 'P')
                    @continue
                @endif
                <div style="background: var(--bg-body); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">{{ $label }}</div>
                    <div style="font-size: 15px; font-weight: 700; color: var(--text-main); margin-top: 4px;">{{ $val ?: '-' }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Status Cards (Dipindah ke Bawah Form) --}}
@php
    // Tentukan total item berdasarkan gender dan agama/hijab status
    $personnelGender = optional($personnel)->gender ?? 'L';
    $personnelReligion = strtoupper(trim(optional($personnel)->religion ?? ''));
    $isIslam = in_array($personnelReligion, ['ISLAM']);

    if ($personnelGender === 'L') {
        // Laki-laki: 8 item (tanpa jilbab)
        $totalItems = 8;
        $isJilbabRequired = false;
        $itemNote = '';
    } elseif ($personnelGender === 'P' && $isIslam) {
        // Perempuan Islam: 9 item (termasuk jilbab)
        $totalItems = 9;
        $isJilbabRequired = true;
        $itemNote = '';
    } else {
        // Perempuan non-Islam: 8 dari 9 item (jilbab tidak diwajibkan)
        $totalItems = 9;
        $isJilbabRequired = false;
        $itemNote = 'Item Jilbab tidak diwajibkan (non-Islam).';
    }

    // Hitung jumlah item yang sudah terisi
    $filledCount = 0;
    if (is_array($kaporSizes)) {
        foreach ($kaporSizes as $key => $val) {
            if (!empty($val)) {
                // Untuk laki-laki, skip jilbab dari hitungan
                if ($personnelGender === 'L' && $key === 'jilbab') continue;
                $filledCount++;
            }
        }
    }

    // Untuk perempuan non-Islam, max filled = 8 (jilbab tidak diminta)
    $maxExpected = ($personnelGender === 'P' && !$isIslam) ? 8 : $totalItems;
    $isComplete = $filledCount >= $maxExpected;
@endphp
<div class="personil-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
    {{-- Card 1: Item Terisi --}}
    <div class="card personil-stat-card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; padding: 32px 28px; background: var(--bg-card); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='var(--shadow-md)';" onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)';">
        <div style="flex: 1;">
            <p style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin: 0 0 12px 0;">Item Data Terisi</p>
            <div style="display: flex; align-items: baseline; gap: 8px;">
                <span class="stat-number" style="font-size: 42px; font-weight: 800; color: var(--text-main); line-height: 1;">{{ $filledCount }}</span>
                <span class="stat-suffix" style="font-size: 16px; font-weight: 600; color: var(--text-muted);">/ {{ $totalItems }} Total Barang</span>
            </div>
            <p class="stat-desc" style="font-size: 14px; font-weight: 500; color: {{ ($hasSubmitted && $isComplete) ? 'var(--success)' : 'var(--slate-400)' }}; margin: 16px 0 0 0; display: flex; align-items: center; gap: 8px;">
                <i class="ri-checkbox-circle-fill" style="font-size: 18px;"></i>
                @if($hasSubmitted && $isComplete)
                    Kaporlap Anda sudah direkam.
                @elseif($hasSubmitted && !$isComplete)
                    Kaporlap direkam, namun ada item belum terisi.
                @else
                    Masih ada data ukuran kosong.
                @endif
            </p>
            @if(!empty($itemNote) && $filledCount < $totalItems)
                <p style="font-size: 12px; font-weight: 500; color: var(--info); margin: 8px 0 0 0; display: flex; align-items: center; gap: 6px;">
                    <i class="ri-information-line" style="font-size: 14px;"></i>
                    {{ $itemNote }}
                </p>
            @endif
        </div>
        <div class="stat-icon-circle" style="width: 72px; height: 72px; border-radius: 50%; background: {{ ($hasSubmitted && $isComplete) ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)' }}; display: flex; align-items: center; justify-content: center; color: {{ ($hasSubmitted && $isComplete) ? 'var(--success)' : 'var(--warning)' }}; font-size: 32px; box-shadow: 0 4px 12px {{ ($hasSubmitted && $isComplete) ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)' }};">
            <i class="ri-{{ ($hasSubmitted && $isComplete) ? 'shopping-bag-3-fill' : 'edit-2-fill' }}"></i>
        </div>
    </div>

    {{-- Card 2: Tahun Anggaran --}}
    <div class="card personil-stat-card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; padding: 32px 28px; background: var(--bg-card); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='var(--shadow-md)';" onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)';">
        <div style="flex: 1;">
            <p style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin: 0 0 12px 0;">Tahun Anggaran</p>
            <div style="display: flex; align-items: baseline; gap: 8px;">
                <span class="stat-number" style="font-size: 42px; font-weight: 800; color: var(--text-main); line-height: 1;">{{ $fiscalYear }}</span>
            </div>
            <p class="stat-desc" style="font-size: 14px; font-weight: 500; color: var(--info); margin: 16px 0 0 0; display: flex; align-items: center; gap: 8px;">
                <i class="ri-time-fill" style="font-size: 18px;"></i>
                Data input masuk ke TA saat ini.
            </p>
        </div>
        <div class="stat-icon-circle" style="width: 72px; height: 72px; border-radius: 50%; background: var(--info-bg); display: flex; align-items: center; justify-content: center; color: var(--info); font-size: 32px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);">
            <i class="ri-calendar-event-fill"></i>
        </div>
    </div>
</div>



{{-- FORM TESTIMONI KOTAK SARAN --}}
<div class="card" style="border: none; box-shadow: var(--shadow-md); border-radius: var(--radius-lg); overflow: hidden; background: var(--bg-card); position: relative;">
    <div style="position: absolute; right: 0; top: 0; width: 200px; height: 100%; background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.03)); pointer-events: none;"></div>
    <div class="card-header" style="background: transparent; border-bottom: 1px solid var(--border-color); padding: 20px 24px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
            <i class="ri-feedback-line" style="color: var(--brand); font-size: 20px;"></i>
            Kotak Saran & Testimoni
        </h3>
    </div>
    <div class="card-body personil-testimoni-body" style="padding: 24px;">
        <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">Berikan masukan, kritik, atau pengalaman Anda dalam menggunakan Sistem E-Kaporlap untuk pengembangan kami ke depannya.</p>
        
        <form action="{{ route('personil.testimoni.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
                    Penilaian Kualitas
                </label>
                <div class="rating-wrapper" style="margin-top: 8px;">
                    <input type="radio" id="star5" name="rating" value="5" checked />
                    <label for="star5" title="Sangat Bagus"><i class="ri-star-fill"></i></label>
                    <input type="radio" id="star4" name="rating" value="4" />
                    <label for="star4" title="Bagus"><i class="ri-star-fill"></i></label>
                    <input type="radio" id="star3" name="rating" value="3" />
                    <label for="star3" title="Cukup"><i class="ri-star-fill"></i></label>
                    <input type="radio" id="star2" name="rating" value="2" />
                    <label for="star2" title="Buruk"><i class="ri-star-fill"></i></label>
                    <input type="radio" id="star1" name="rating" value="1" />
                    <label for="star1" title="Sangat Buruk"><i class="ri-star-fill"></i></label>
                </div>
            </div>
            
            <div class="form-group" style="margin-top:16px;">
                <label class="form-label">Tulis Pesan Anda</label>
                <textarea name="message" class="form-control" rows="4" placeholder="Ketik pandangan, harapan, atau kendala Anda di sini..." required style="resize: vertical;"></textarea>
            </div>
            
            <div class="personil-testimoni-actions" style="text-align: right; margin-top: 20px;">
                <button type="submit" class="btn" style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); padding: 10px 24px; border-radius: var(--radius-md); font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;" onmouseover="this.style.background='var(--hover-bg)'" onmouseout="this.style.background='var(--bg-body)'">
                    <i class="ri-send-plane-fill" style="color:var(--brand);"></i> Kirim Testimoni
                </button>
            </div>
        </form>
    </div>
</div>

</div>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            customClass: { popup: 'colored-toast' }
        });
    });
</script>
@endif

@if(session('success_testimoni'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Terkirim',
            text: "{{ session('success_testimoni') }}",
            icon: 'info',
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            customClass: { popup: 'colored-toast swal2-icon-info' }
        });
    });
</script>
<style>
    .colored-toast.swal2-icon-info { background-color: var(--info-bg) !important; color: var(--info) !important;}
    .colored-toast.swal2-icon-info .swal2-title { color: var(--info) !important; }
</style>
@endif

@endsection