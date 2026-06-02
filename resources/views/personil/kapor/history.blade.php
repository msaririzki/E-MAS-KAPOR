@extends('layouts.personil')

@section('title', 'Riwayat Ukuran Kaporlap')

@section('content')
    @php
        $itemMap = [
            'topi' => 'Topi / Baret',
            'jilbab' => 'Jilbab',
            'kemeja' => 'Kemeja',
            'celana' => 'Celana / Rok',
            'jaket' => 'Jaket',
            'olahraga' => 'Olahraga',
            'sabuk' => 'Sabuk',
            'sepatu_dinas' => 'Sepatu Dinas',
            'sepatu_olahraga' => 'Sepatu Olahraga',
        ];
    @endphp

    <div class="page">
        {{-- ── HEADER TITLE ──────────────────────────────── --}}
        <div class="page-full reveal" style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
            <a href="{{ route('dashboard') }}" class="btn-outline" style="height: 40px; border-radius: 12px; padding: 0 16px;">
                <i class="ri-arrow-left-line"></i> Kembali
            </a>
            <h1 style="margin: 0; font-size: 20px; font-weight: 800; color: var(--text-main);">Riwayat Ukuran Kaporlap</h1>
        </div>

        {{-- ── SIDEBAR (profil card) ────────────────────── --}}
        <div class="page-sidebar">
            <section class="profile-card reveal">
                <div class="profile-card-top">
                    <div class="profile-avatar">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                    <div class="profile-info">
                        <h2 class="profile-name">{{ $user->name }}</h2>
                        <p class="profile-nrp">
                            <i class="ri-fingerprint-line"></i>
                            {{ $user->nrp_nip }}
                        </p>
                    </div>
                </div>

                <div class="profile-stats">
                    <div class="profile-stat">
                        <span class="profile-stat-label"><i class="ri-building-4-line"></i> Satker</span>
                        <span class="profile-stat-value">{{ $personnel->satker->name ?? '-' }}</span>
                    </div>
                    <div class="profile-stat">
                        <span class="profile-stat-label"><i class="ri-calendar-line"></i> Tahun Anggaran</span>
                        <span class="profile-stat-value">{{ $fiscalYear }}</span>
                    </div>
                </div>

                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label">Progres Pengisian</span>
                        <span class="progress-pct" id="progressPct">{{ $progressPct }}%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: {{ $progressPct }}%;"></div>
                    </div>
                </div>

                <div class="stepper">
                    <div class="stepper-item {{ $identityReady ? 'done' : 'active' }}">
                        <div class="stepper-dot">
                            @if($identityReady) <i class="ri-check-line"></i> @else <span>1</span> @endif
                        </div>
                        <span class="stepper-label">{{ $identityStepLabel }}</span>
                    </div>
                    <div class="stepper-connector"></div>
                    <div class="stepper-item {{ !$identityReady ? '' : ($isComplete ? 'done' : 'active') }}">
                        <div class="stepper-dot">
                            @if($identityReady && $isComplete) <i class="ri-check-line"></i> @else <span>2</span> @endif
                        </div>
                        <span class="stepper-label">2. Ukuran</span>
                    </div>
                </div>
            </section>
        </div>

        {{-- ── MAIN CONTENT ──────────────────────────────── --}}
        <div class="page-main">

            <section class="d-panel reveal" style="margin-bottom: 24px;">
                <div class="d-panel-header">
                    <div class="d-panel-header-icon"><i class="ri-history-line"></i></div>
                    <div>
                        <h2 class="d-panel-title">Informasi Personel</h2>
                        <p class="d-panel-subtitle">Data profil Anda yang tertaut dengan ukuran.</p>
                    </div>
                </div>
                <div class="d-panel-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Jabatan</span>
                            <span class="info-value">{{ $personnel->jabatan ?? '-' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Bag/Fungsi</span>
                            <span class="info-value">{{ $personnel->bagian ?? '-' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Update Terakhir</span>
                            <span class="info-value">{{ $personnel?->updated_at?->format('d M Y, H:i') ?? '-' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status Profil</span>
                            <span class="info-value">
                                @if($isComplete)
                                    <span style="color: #16a34a; display: flex; align-items: center; gap: 4px;"><i class="ri-checkbox-circle-fill"></i> Lengkap</span>
                                @else
                                    <span style="color: #d97706; display: flex; align-items: center; gap: 4px;"><i class="ri-error-warning-fill"></i> Belum Lengkap</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="d-panel reveal" style="animation-delay: 0.1s;">
                <div class="d-panel-header">
                    <div class="d-panel-header-icon d-panel-header-icon--muted"><i class="ri-ruler-line"></i></div>
                    <div>
                        <h2 class="d-panel-title">{{ $hasSubmitted ? 'Ukuran Tersimpan' : 'Belum Ada Ukuran' }}</h2>
                        <p class="d-panel-subtitle">{{ $hasSubmitted ? 'Detail ukuran kaporlap terakhir Anda.' : 'Silakan lengkapi form ukuran terlebih dahulu di halaman Data.' }}</p>
                    </div>
                </div>
                
                @if ($hasSubmitted)
                    <div class="d-panel-body" style="padding-top: 0;">
                        <div class="sizes-grid">
                            @foreach ($itemMap as $key => $label)
                                @continue(empty($kaporSizes[$key]))
                                <div class="size-card">
                                    <span class="size-label">{{ $label }}</span>
                                    <span class="size-value">{{ $kaporSizes[$key] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

        </div>
    </div>
@endsection

@section('styles')
<style>
    /* ── CORE & REVEAL ──────────────────────────────────── */
    .page {
        padding: 24px 20px;
        min-height: calc(100vh - 64px);
    }
    @media (min-width: 1024px) {
        .page { padding: 40px; }
    }
    
    .reveal {
        opacity: 0;
        transform: translateY(20px);
        animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes fadeUp {
        to { opacity: 1; transform: translateY(0); }
    }

    /* ── PANELS ─────────────────────────────────────────── */
    .d-panel {
        background: #fff;
        border-radius: var(--dp-radius-xl, 20px);
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 24px rgba(15,23,42,0.07);
        overflow: hidden;
    }

    /* ── PROFILE CARD ───────────────────────────────────── */
    .profile-card {
        background: #fff;
        border-radius: var(--dp-radius-xl, 20px);
        border: 1px solid var(--border-color);
        padding: 24px;
        color: var(--text-main);
        box-shadow: 0 4px 24px rgba(15,23,42,0.07);
        position: relative;
        overflow: hidden;
    }
    .profile-card::before {
        content: ''; position: absolute; top: -60px; right: -60px; width: 200px; height: 200px; border-radius: 50%;
        background: radial-gradient(circle, rgba(198,40,40,0.05) 0%, transparent 70%); pointer-events: none;
    }
    .profile-card::after {
        content: ''; position: absolute; bottom: -40px; left: -40px; width: 160px; height: 160px; border-radius: 50%;
        background: radial-gradient(circle, rgba(15,23,42,0.02) 0%, transparent 70%); pointer-events: none;
    }

    .profile-card-top { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
    .profile-avatar { width: 60px; height: 60px; border-radius: 16px; background: linear-gradient(135deg, #c62828, #e53935); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 900; color: #fff; flex-shrink: 0; box-shadow: 0 4px 16px rgba(198,40,40,0.2); }
    .profile-name { margin: 0; font-size: 17px; font-weight: 800; color: var(--text-main); line-height: 1.2; }
    .profile-nrp { margin: 6px 0 0; font-size: 13px; color: var(--text-muted); font-weight: 600; display: flex; align-items: center; gap: 5px; }

    .profile-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
    .profile-stat { background: var(--slate-50); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 14px; }
    .profile-stat-label { display: flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; }
    .profile-stat-value { font-size: 14px; font-weight: 800; color: var(--text-main); line-height: 1.3;}

    .progress-section { margin-bottom: 16px; }
    .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .progress-label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
    .progress-pct { font-size: 13px; font-weight: 800; color: var(--text-main); }
    .progress-track { height: 6px; border-radius: 999px; background: var(--slate-200); overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #c62828, #f97316); box-shadow: 0 0 8px rgba(198,40,40,0.3); transition: width 0.8s ease; }

    .stepper { display: flex; align-items: center; background: var(--slate-50); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; }
    .stepper-item { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
    .stepper-dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; flex-shrink: 0; background: #fff; color: var(--text-muted); border: 2px solid var(--border-color); }
    .stepper-dot i { font-weight: normal; font-size: 15px; }
    .stepper-item.active .stepper-dot { background: var(--brand); border-color: var(--brand); color: #fff; box-shadow: 0 0 12px rgba(198,40,40,0.3); }
    .stepper-item.done .stepper-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
    .stepper-label { font-size: 12px; font-weight: 600; color: var(--text-muted); line-height: 1.3; }
    .stepper-item.active .stepper-label { color: var(--text-main); font-weight: 700; }
    .stepper-item.done .stepper-label { color: var(--text-main); }
    .stepper-connector { width: 24px; height: 2px; background: var(--border-color); flex-shrink: 0; margin: 0 8px; }

    .d-panel-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 24px;
        border-bottom: 1px solid rgba(0,0,0,0.04);
    }
    .d-panel-header-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        color: var(--brand);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }
    .d-panel-header-icon--muted {
        background: var(--slate-100);
        color: var(--slate-500);
    }
    .d-panel-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: var(--text-main);
        letter-spacing: -0.01em;
    }
    .d-panel-subtitle {
        margin: 4px 0 0;
        font-size: 13.5px;
        font-weight: 500;
        color: var(--text-muted);
    }
    .d-panel-body {
        padding: 24px;
    }

    /* ── INFO GRID ──────────────────────────────────────── */
    .info-grid {
        display: grid;
        gap: 16px;
        background: var(--slate-50);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 16px;
    }
    @media (min-width: 640px) {
        .info-grid { grid-template-columns: repeat(2, 1fr); padding: 20px; gap: 20px; }
    }
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .info-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .info-value {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-main);
    }

    /* ── SIZES GRID ─────────────────────────────────────── */
    .sizes-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(2, 1fr);
    }
    @media (min-width: 640px) {
        .sizes-grid { grid-template-columns: repeat(3, 1fr); gap: 16px; }
    }
    @media (min-width: 1024px) {
        .sizes-grid { grid-template-columns: repeat(4, 1fr); }
    }
    .size-card {
        background: #fff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    .size-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.06);
        border-color: #fca5a5;
    }
    .size-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }
    .size-value {
        display: block;
        font-size: 20px;
        font-weight: 900;
        color: var(--brand);
        line-height: 1;
    }

    /* ── BUTTONS ────────────────────────────────────────── */
    .btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid var(--border-color);
        color: var(--text-main);
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.18s ease;
        text-decoration: none;
    }
    .btn-outline:hover {
        border-color: #fca5a5;
        color: var(--brand);
        background: #fef2f2;
    }
</style>
@endsection
