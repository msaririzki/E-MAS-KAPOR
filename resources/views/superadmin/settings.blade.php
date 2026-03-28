@extends('layouts.app')

@section('title', 'Pengaturan Sistem')
@section('breadcrumb', 'Pengaturan')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Pengaturan & Personalisasi</h1>
            <p>Kelola konfigurasi sistem E-MAS KAPOR dan preferensi tampilan Anda.</p>
        </div>
    </div>
</div>

{{-- Flash Messages --}}
@if(session('success'))
<div class="alert alert-success" id="flashMsg">
    <i class="ri-checkbox-circle-fill"></i>
    <div class="alert-content">
        <strong>Berhasil!</strong>
        <span>{{ session('success') }}</span>
    </div>
    <button type="button" class="alert-close" onclick="this.parentElement.remove()"><i class="ri-close-line"></i></button>
</div>
@endif

<div class="settings-container">

    {{-- 1. Konfigurasi Umum --}}
    <div class="settings-section">
        <div class="settings-info">
            <h3><i class="ri-settings-4-line"></i> Konfigurasi Umum</h3>
            <p>Atur nama aplikasi dan tentukan Tahun Anggaran yang sedang aktif. Pengaturan ini akan berdampak pada seluruh pengguna sistem E-MAS KAPOR.</p>
        </div>
        <div class="settings-card">
            <form method="POST" action="{{ route('superadmin.settings.update') }}">
                @csrf
                @method('PUT')
                
                <div class="modern-form-group">
                    <label>Nama Aplikasi <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="ri-window-line"></i>
                        <input type="text" name="app_name" class="modern-input" value="{{ $settings['app_name'] }}" required>
                    </div>
                </div>
                
                <div class="modern-form-group">
                    <label>Tahun Anggaran Aktif <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="ri-calendar-event-line"></i>
                        <input type="number" name="fiscal_year" class="modern-input" value="{{ $settings['fiscal_year'] }}" required>
                    </div>
                    <p class="help-text">Tahun yang digunakan untuk Dashboard dan perhitungan data saat ini.</p>
                </div>

                <div class="modern-toggle-group" style="padding-bottom: 12px; border-bottom: none;">
                    <div class="toggle-info">
                        <strong>Kunci Sistem Paksa (Force Lock System)</strong>
                        <span>Tombol Darurat: Jika diaktifkan, sistem akan SELALU TERKUNCI untuk input/perubahan tanpa memedulikan batas rentang tanggal masa pengisian di bawah.</span>
                    </div>
                    <label class="modern-toggle">
                        <input type="checkbox" name="is_system_locked" value="1" {{ $settings['is_system_locked'] ? 'checked' : '' }}>
                        <div class="toggle-slider"></div>
                    </label>
                </div>
                
                <div style="padding: 0 24px 20px 24px; border-bottom: 1px solid var(--border-color);">
                    <div style="background: var(--bg-body); border-radius: 8px; padding: 16px; border: 1px solid var(--border-color);">
                        <div style="margin-bottom: 12px;">
                            <strong style="font-size: 13px; color: var(--text-main); display: block;">Rentang Waktu Masa Pengisian Data</strong>
                            <span style="font-size: 12px; color: var(--text-muted);">Sistem akan <b>otomatis terkunci</b> apabila tanggal saat ini berada di luar rentang tanggal (Periode Input) ini.</span>
                        </div>
                        <div style="display: flex; gap: 16px;">
                            <div style="flex: 1;">
                                <label style="font-size: 11px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Tanggal Mulai</label>
                                <div class="input-with-icon">
                                    <i class="ri-calendar-event-line"></i>
                                    <input type="date" name="input_start_date" class="modern-input" value="{{ $settings['input_start_date'] }}" required>
                                </div>
                            </div>
                            <div style="flex: 1;">
                                <label style="font-size: 11px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Tanggal Ditutup</label>
                                <div class="input-with-icon">
                                    <i class="ri-calendar-close-line"></i>
                                    <input type="date" name="input_end_date" class="modern-input" value="{{ $settings['input_end_date'] }}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-action-bar">
                    <button type="submit" class="btn-primary-modern">
                        <i class="ri-save-3-line"></i> Simpan Konfigurasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <hr class="settings-divider">

    {{-- 2. Personalisasi Tampilan --}}
    <div class="settings-section">
        <div class="settings-info">
            <h3><i class="ri-palette-line"></i> Tampilan Antarmuka</h3>
            <p>Sesuaikan tema warna sidebar sistem. Pengaturan ini <strong>hanya berlaku untuk akun Anda sendiri</strong> dan tidak akan memengaruhi admin lainnya.</p>
        </div>
        <div class="settings-card">
            <form action="{{ route('profile.updateTheme') }}" method="POST" id="themeForm">
                @csrf
                <div class="theme-grid">
                    {{-- Default Navy --}}
                    <label class="theme-option {{ auth()->user()->theme == 'theme-default' || !auth()->user()->theme ? 'active' : '' }}">
                        <input type="radio" name="theme" value="theme-default" {{ auth()->user()->theme == 'theme-default' || !auth()->user()->theme ? 'checked' : '' }} onchange="document.getElementById('themeForm').submit()">
                        <div class="theme-visual" style="background:#111827;">
                            <div class="theme-accent" style="background:#C62828;"></div>
                        </div>
                        <div class="theme-texts">
                            <strong>Midnight Navy</strong>
                            <span>Klasik & Aman</span>
                        </div>
                        <i class="ri-checkbox-circle-fill check-icon"></i>
                    </label>
                    
                    {{-- Matcha --}}
                    <label class="theme-option {{ auth()->user()->theme == 'theme-matcha' ? 'active' : '' }}">
                        <input type="radio" name="theme" value="theme-matcha" {{ auth()->user()->theme == 'theme-matcha' ? 'checked' : '' }} onchange="document.getElementById('themeForm').submit()">
                        <div class="theme-visual" style="background:#FAFAF9; border: 1px solid rgba(0,0,0,0.1); box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                            <div class="theme-accent" style="background:#65a30d; box-shadow: 0 0 8px rgba(101,163,13,0.3);"></div>
                        </div>
                        <div class="theme-texts">
                            <strong>Matcha Minimalist</strong>
                            <span>Organik & Terang</span>
                        </div>
                        <i class="ri-checkbox-circle-fill check-icon"></i>
                    </label>

                    {{-- Cyber --}}
                    <label class="theme-option {{ auth()->user()->theme == 'theme-cyber' ? 'active' : '' }}">
                        <input type="radio" name="theme" value="theme-cyber" {{ auth()->user()->theme == 'theme-cyber' ? 'checked' : '' }} onchange="document.getElementById('themeForm').submit()">
                        <div class="theme-visual" style="background:#020617;">
                            <div class="theme-accent" style="background:#a855f7;"></div>
                        </div>
                        <div class="theme-texts">
                            <strong>Cyber Neon</strong>
                            <span>Gelap & Kontras</span>
                        </div>
                        <i class="ri-checkbox-circle-fill check-icon"></i>
                    </label>

                    {{-- Monochrome --}}
                    <label class="theme-option {{ auth()->user()->theme == 'theme-monochrome' ? 'active' : '' }}">
                        <input type="radio" name="theme" value="theme-monochrome" {{ auth()->user()->theme == 'theme-monochrome' ? 'checked' : '' }} onchange="document.getElementById('themeForm').submit()">
                        <div class="theme-visual" style="background:#000000;">
                            <div class="theme-accent" style="background:#ea580c;"></div>
                        </div>
                        <div class="theme-texts">
                            <strong>Monochrome Brutalist</strong>
                            <span>Hitam Pekat & Orange</span>
                        </div>
                        <i class="ri-checkbox-circle-fill check-icon"></i>
                    </label>

                    {{-- Twilight --}}
                    <label class="theme-option {{ auth()->user()->theme == 'theme-twilight' ? 'active' : '' }}">
                        <input type="radio" name="theme" value="theme-twilight" {{ auth()->user()->theme == 'theme-twilight' ? 'checked' : '' }} onchange="document.getElementById('themeForm').submit()">
                        <div class="theme-visual" style="background:linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border: 1px solid rgba(255,255,255,0.1);">
                            <div class="theme-accent" style="background:#8b5cf6;"></div>
                        </div>
                        <div class="theme-texts">
                            <strong>Twilight Lavender</strong>
                            <span>Ungu Estetik</span>
                        </div>
                        <i class="ri-checkbox-circle-fill check-icon"></i>
                    </label>

                    {{-- Ocean Breeze --}}
                    <label class="theme-option {{ auth()->user()->theme == 'theme-ocean' ? 'active' : '' }}">
                        <input type="radio" name="theme" value="theme-ocean" {{ auth()->user()->theme == 'theme-ocean' ? 'checked' : '' }} onchange="document.getElementById('themeForm').submit()">
                        <div class="theme-visual" style="background:#ffffff; border: 1px solid rgba(14,165,233,0.2); box-shadow: inset 0 4px 12px rgba(14,165,233,0.05);">
                            <div class="theme-accent" style="background:#0ea5e9; box-shadow: 0 0 10px rgba(14,165,233,0.4);"></div>
                        </div>
                        <div class="theme-texts">
                            <strong>Ocean Breeze</strong>
                            <span>Putih & Biru Segar</span>
                        </div>
                        <i class="ri-checkbox-circle-fill check-icon"></i>
                    </label>

                    {{-- Sunset Glow --}}
                    <label class="theme-option {{ auth()->user()->theme == 'theme-sunset' ? 'active' : '' }}">
                        <input type="radio" name="theme" value="theme-sunset" {{ auth()->user()->theme == 'theme-sunset' ? 'checked' : '' }} onchange="document.getElementById('themeForm').submit()">
                        <div class="theme-visual" style="background:linear-gradient(160deg, #2a0a18 0%, #17050f 100%);">
                            <div class="theme-accent" style="background:#f43f5e;"></div>
                        </div>
                        <div class="theme-texts">
                            <strong>Sunset Glow</strong>
                            <span>Gelap Senja Premium</span>
                        </div>
                        <i class="ri-checkbox-circle-fill check-icon"></i>
                    </label>

                    {{-- Forest Pine --}}
                    <label class="theme-option {{ auth()->user()->theme == 'theme-pine' ? 'active' : '' }}">
                        <input type="radio" name="theme" value="theme-pine" {{ auth()->user()->theme == 'theme-pine' ? 'checked' : '' }} onchange="document.getElementById('themeForm').submit()">
                        <div class="theme-visual" style="background:#064e3b;">
                            <div class="theme-accent" style="background:#10b981;"></div>
                        </div>
                        <div class="theme-texts">
                            <strong>Forest Pine</strong>
                            <span>Hijau Zamrud Elegan</span>
                        </div>
                        <i class="ri-checkbox-circle-fill check-icon"></i>
                    </label>

                    {{-- Sakura Pink --}}
                    <label class="theme-option {{ auth()->user()->theme == 'theme-sakura' ? 'active' : '' }}">
                        <input type="radio" name="theme" value="theme-sakura" {{ auth()->user()->theme == 'theme-sakura' ? 'checked' : '' }} onchange="document.getElementById('themeForm').submit()">
                        <div class="theme-visual" style="background:#fff1f2; border: 1px solid rgba(236,72,153,0.2); box-shadow: inset 0 2px 8px rgba(236,72,153,0.05);">
                            <div class="theme-accent" style="background:#ec4899; box-shadow: 0 0 10px rgba(236,72,153,0.4);"></div>
                        </div>
                        <div class="theme-texts">
                            <strong>Sakura Pink</strong>
                            <span>Lembut & Manis</span>
                        </div>
                        <i class="ri-checkbox-circle-fill check-icon"></i>
                    </label>
                </div>
            </form>
        </div>
    </div>

    <hr class="settings-divider">

    {{-- 3. Transisi Tahun Anggaran & Riwayat --}}
    <div class="settings-section">
        <div class="settings-info">
            <h3><i class="ri-history-line"></i> Riwayat & Transisi</h3>
            <p>Kelola perpindahan Tahun Anggaran. Saat satu siklus tahun sudah ditutup, Anda bisa melangkah ke siklus tahun berikutnya.</p>
        </div>
        <div class="settings-card transparent-card">
            
            {{-- Warning Box --}}
            <div class="danger-zone-box">
                <div class="danger-header">
                    <i class="ri-alert-line"></i>
                    <div>
                        <strong>Tutup Tahun Anggaran {{ $settings['fiscal_year'] }}</strong>
                        <p>Tindakan ini akan mengunci seluruh data pada tahun ini, mengarsipkan data, dan memulai periode pembukuan untuk TA {{ $settings['fiscal_year'] + 1 }}.</p>
                    </div>
                </div>
                <form action="{{ route('superadmin.settings.next-year') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin MENUTUP Tahun Anggaran {{ $settings['fiscal_year'] }} dan lanjut ke {{ $settings['fiscal_year'] + 1 }}? Data tahun ini tidak bisa diubah lagi.')">
                    @csrf
                    <button type="submit" class="btn-danger-modern">
                        Selesaikan Periode {{ $settings['fiscal_year'] }}
                    </button>
                </form>
            </div>

            {{-- History Table --}}
            <div class="table-card">
                <div class="table-card-head">
                    <h4>Riwayat Tahun Anggaran</h4>
                </div>
                <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%;">
                    <table class="modern-table" style="min-width: 500px;">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th style="text-align:right;">Total Item Direkap</th>
                                <th style="text-align:right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($yearlyStats as $ys)
                            <tr class="{{ $ys->is_active ? 'active-row' : '' }}">
                                <td>
                                    <div class="year-cell">
                                        <span class="year-text">TA {{ $ys->fiscal_year }}</span>
                                        @if($ys->is_active)
                                            <span class="pulse-badge">AKTIF</span>
                                        @endif
                                    </div>
                                </td>
                                <td align="right"><strong>{{ number_format($ys->total) }}</strong> entri</td>
                                <td align="right">
                                    <span class="status-pill {{ $ys->status == 'Aktif' ? 'success' : 'neutral' }}">
                                        {{ $ys->status == 'Aktif' ? 'Berjalan' : 'Arsip' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="empty-state">Belum ada riwayat data sebelumnya.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

@endsection

@section('styles')
<style>
    /* ── Layouting ── */
    .settings-container {
        display: flex;
        flex-direction: column;
        gap: 0;
        max-width: 1000px;
        margin: 0 auto;
        padding-bottom: 60px;
    }
    .settings-section {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 32px;
        padding: 40px 0;
    }
    @media (max-width: 900px) {
        .settings-section { grid-template-columns: 1fr; gap: 20px; padding: 30px 0; }
    }
    .settings-divider {
        border: none;
        border-top: 1px solid var(--slate-200);
        margin: 0;
    }

    /* ── Left Side Info ── */
    .settings-info h3 {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .settings-info h3 i {
        color: var(--brand);
        font-size: 18px;
    }
    .settings-info p {
        font-size: 13.5px;
        color: var(--text-muted);
        line-height: 1.6;
    }

    /* ── Right Side Cards ── */
    .settings-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    .transparent-card {
        background: transparent;
        border: none;
        box-shadow: none;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* ── Modern Form Elements ── */
    .modern-form-group {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
    }
    .modern-form-group:last-child { border-bottom: none; }
    .modern-form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 8px;
    }
    .required { color: var(--danger); margin-left: 2px; }
    
    .input-with-icon {
        position: relative;
    }
    .input-with-icon i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 16px;
    }
    .modern-input {
        width: 100%;
        padding: 10px 14px 10px 40px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 14px;
        color: var(--text-main);
        background: var(--input-bg);
        transition: all 0.2s;
    }
    .modern-input:focus {
        background: var(--bg-card);
        border-color: var(--brand-light);
        box-shadow: 0 0 0 4px rgba(229, 57, 53, 0.1);
        outline: none;
    }
    .help-text {
        font-size: 11.5px;
        color: var(--text-muted);
        margin-top: 6px;
    }

    /* ── Toggle Switch ── */
    .modern-toggle-group {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 24px;
    }
    .toggle-info strong {
        display: block;
        font-size: 14px;
        color: var(--text-main);
        margin-bottom: 4px;
    }
    .toggle-info span {
        font-size: 12px;
        color: var(--text-muted);
    }
    .modern-toggle {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }
    .modern-toggle input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: var(--slate-300);
        transition: .3s;
        border-radius: 24px;
    }
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px; width: 18px;
        left: 3px; bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .modern-toggle input:checked + .toggle-slider {
        background-color: var(--danger);
    }
    .modern-toggle input:checked + .toggle-slider:before {
        transform: translateX(20px);
    }

    /* ── Buttons ── */
    .settings-action-bar {
        background: var(--bg-body);
        padding: 16px 24px;
        display: flex;
        justify-content: flex-end;
        border-top: 1px solid var(--border-color);
    }
    .btn-primary-modern {
        background: var(--brand);
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(198, 40, 40, 0.2);
    }
    .btn-primary-modern:hover {
        background: #b71c1c;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(198, 40, 40, 0.3);
    }

    /* ── Theme Grid ── */
    .theme-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        padding: 24px;
    }
    @media (max-width: 600px) { .theme-grid { grid-template-columns: 1fr; } }
    
    .theme-option {
        border: 2px solid var(--border-color);
        border-radius: 10px;
        padding: 12px;
        display: flex;
        align-items: center;
        gap: 14px;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .theme-option:hover {
        border-color: var(--slate-400);
        background: var(--hover-bg);
    }
    .theme-option input { display: none; }
    .theme-visual {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        position: relative;
        overflow: hidden;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        flex-shrink: 0;
    }
    
    /* Simulate a mini sidebar layout */
    .theme-visual::after {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        right: 0;
        width: 14px;
        background: rgba(128, 128, 128, 0.08); 
        border-left: 1px solid rgba(128, 128, 128, 0.1);
    }

    .theme-accent {
        position: absolute;
        left: 0; 
        top: 10px; 
        bottom: 10px; 
        width: 4px;
        border-radius: 0 4px 4px 0;
        z-index: 10;
        box-shadow: 1px 0 4px rgba(0,0,0,0.2);
    }
    .theme-texts strong {
        display: block;
        font-size: 13.5px;
        color: var(--text-main);
        margin-bottom: 2px;
    }
    .theme-texts span {
        font-size: 11px;
        color: var(--text-muted);
    }
    .check-icon {
        position: absolute;
        right: 16px;
        color: var(--brand);
        font-size: 20px;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .theme-option.active {
        border-color: var(--brand);
        background: var(--brand-bg);
    }
    .theme-option.active .check-icon {
        opacity: 1;
        transform: scale(1);
    }

    /* ── Danger Zone ── */
    .danger-zone-box {
        border: 1px solid var(--warning-border);
        background: var(--warning-bg);
        border-radius: 12px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .danger-header {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    .danger-header i {
        font-size: 24px;
        color: #B45309; /* Dark orange */
        margin-top: -2px;
    }
    .danger-header strong {
        font-size: 15px;
        color: #92400E;
        display: block;
        margin-bottom: 6px;
    }
    .danger-header p {
        font-size: 13px;
        color: #B45309;
        line-height: 1.5;
        margin: 0;
    }
    .btn-danger-modern {
        background: var(--bg-card);
        border: 1px solid #F59E0B;
        color: #D97706;
        padding: 10px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
        display: block;
        text-align: center;
    }
    .btn-danger-modern:hover {
        background: #FEF3C7;
        color: #B45309;
        border-color: #D97706;
    }

    /* ── Table Card ── */
    .table-card {
        background: var(--bg-card);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        overflow: hidden;
    }
    .table-card-head {
        padding: 16px 24px;
        border-bottom: 1px solid var(--border-color);
        background: var(--bg-body);
    }
    .table-card-head h4 {
        margin: 0;
        font-size: 14px;
        color: var(--text-main);
        font-weight: 600;
    }
    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }
    .modern-table th {
        text-align: left;
        padding: 12px 24px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border-color);
    }
    .modern-table td {
        padding: 16px 24px;
        font-size: 13.5px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-main);
    }
    .modern-table tr:last-child td { border-bottom: none; }
    .modern-table tr:hover td { background: var(--hover-bg); }
    .active-row td { background: var(--hover-bg); }
    
    .year-cell { display: flex; align-items: center; gap: 10px; }
    .year-text { font-weight: 700; color: var(--text-main); }
    .pulse-badge {
        font-size: 10px;
        font-weight: 700;
        background: var(--info-bg);
        color: var(--info);
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid var(--info-border);
    }

    .status-pill {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-pill.success { background: var(--success-bg); color: #065F46; }
    .status-pill.neutral { background: var(--slate-100); color: var(--slate-600); }
    
    .empty-state { text-align: center; color: var(--slate-400); padding: 32px !important; font-style: italic; }

    /* ── Flash Alert Custom ── */
    .alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
        background: #F0FDF4;
        border: 1px solid #BBF7D0;
        box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.1);
        animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .alert > i {
        font-size: 24px;
        color: #16A34A;
    }
    .alert-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .alert-content strong { color: #166534; font-size: 14px; margin-bottom: 2px; }
    .alert-content span { color: #15803D; font-size: 13px; }
    .alert-close {
        background: none; border: none; font-size: 20px; color: #16A34A; cursor: pointer; opacity: 0.7; transition: 0.2s;
    }
    .alert-close:hover { opacity: 1; transform: scale(1.1); }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection

@section('scripts')
<script>
    // Auto-hide alert gracefully
    const alertBox = document.getElementById('flashMsg');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.opacity = '0';
            alertBox.style.transform = 'translateY(-10px)';
            alertBox.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            setTimeout(() => alertBox.remove(), 400);
        }, 4000);
    }
</script>
@endsection
