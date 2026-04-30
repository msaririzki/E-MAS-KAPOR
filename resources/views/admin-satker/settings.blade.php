@extends('layouts.app')

@section('title', 'Pengaturan Profil')
@section('breadcrumb', 'Pengaturan')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Pengaturan & Personalisasi</h1>
            <p>Sesuaikan preferensi tampilan panel Admin Satker Anda.</p>
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

    {{-- Penanda Tangan Export Satker --}}
    <div class="settings-section">
        <div class="settings-info">
            <h3><i class="ri-quill-pen-line"></i> Penanda Tangan Export</h3>
            <p>Atur identitas penanda tangan untuk semua export satker. Jika ada kolom kosong, sistem otomatis memakai setting global dari Superadmin.</p>
        </div>
        <div class="settings-card">
            <form method="POST" action="{{ route('admin-satker.settings.signatory.update') }}" class="form-block">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-group">
                        <label>Lokasi Tanda Tangan</label>
                        <input type="text" name="location" value="{{ $signatorySettings['location'] ?? '' }}" class="f-input" placeholder="Contoh: Mataram">
                    </div>
                    <div class="form-group">
                        <label>Atas Nama Organisasi</label>
                        <input type="text" name="organization_name" value="{{ $signatorySettings['organization_name'] ?? '' }}" class="f-input" placeholder="Contoh: KEPALA BAGIAN LOGISTIK">
                    </div>
                    <div class="form-group">
                        <label>Jabatan Penanda Tangan</label>
                        <input type="text" name="signatory_title" value="{{ $signatorySettings['signatory_title'] ?? '' }}" class="f-input" placeholder="Contoh: KASUBBAG RENMIN">
                    </div>
                    <div class="form-group">
                        <label>Nama Penanda Tangan</label>
                        <input type="text" name="signatory_name" value="{{ $signatorySettings['signatory_name'] ?? '' }}" class="f-input" placeholder="Contoh: KOMPOL NAMA PEJABAT">
                    </div>
                    <div class="form-group">
                        <label>Pangkat Penanda Tangan</label>
                        <input type="text" name="signatory_rank" value="{{ $signatorySettings['signatory_rank'] ?? '' }}" class="f-input" placeholder="Contoh: KOMPOL">
                    </div>
                    <div class="form-group">
                        <label>NRP / NIP Penanda Tangan</label>
                        <input type="text" name="signatory_nrp" value="{{ $signatorySettings['signatory_nrp'] ?? '' }}" class="f-input" placeholder="Masukkan NRP/NIP">
                    </div>
                </div>

                <div class="settings-action-bar">
                    <button type="submit" class="btn-primary-modern">
                        <i class="ri-save-3-line"></i> Simpan Penanda Tangan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <hr class="settings-divider">

    {{-- Personalisasi Tampilan --}}
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
                        <div class="theme-visual" style="background:#FAFAF9; border: 1px solid #e5e5e5;">
                            <div class="theme-accent" style="background:#65a30d;"></div>
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
                        <div class="theme-visual" style="background:linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
                            <div class="theme-accent" style="background:#8b5cf6;"></div>
                        </div>
                        <div class="theme-texts">
                            <strong>Twilight Lavender</strong>
                            <span>Ungu Estetik</span>
                        </div>
                        <i class="ri-checkbox-circle-fill check-icon"></i>
                    </label>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@section('styles')
<style>
    /* Layout */
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

    /* Left Side Info */
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

    /* Right Side Cards */
    .settings-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .form-block {
        padding: 20px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    @media (max-width: 700px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
    }
    .f-input {
        width: 100%;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--input-bg);
        color: var(--text-main);
        padding: 10px 12px;
        font-size: 13px;
    }
    .f-input:focus {
        outline: none;
        border-color: var(--brand-light);
        box-shadow: 0 0 0 4px rgba(229, 57, 53, 0.08);
    }
    .settings-action-bar {
        margin-top: 14px;
        display: flex;
        justify-content: flex-end;
    }
    .btn-primary-modern {
        background: var(--brand);
        color: #fff;
        border: none;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-primary-modern:hover {
        background: #b71c1c;
    }

    /* Theme Grid */
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
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    }
    .theme-accent {
        position: absolute;
        left: 0;
        top: 12px;
        bottom: 12px;
        width: 4px;
        border-radius: 0 4px 4px 0;
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

    /* Flash Alert Custom */
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
        background: none;
        border: none;
        font-size: 20px;
        color: #16A34A;
        cursor: pointer;
        opacity: 0.7;
        transition: 0.2s;
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
