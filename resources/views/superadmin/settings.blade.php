@extends('layouts.app')

@section('title', 'Pengaturan Sistem')
@section('breadcrumb', 'Pengaturan')

@section('content')
@php
    $today = now()->toDateString();
    $isWithinInputPeriod = $today >= $settings['input_start_date'] && $today <= $settings['input_end_date'];
    $isWithinReviewPeriod = $today >= $settings['review_start_date'] && $today <= $settings['review_end_date'];
    $systemStatusLabel = $settings['is_system_locked']
        ? 'Terkunci paksa'
        : ($isWithinInputPeriod ? 'Periode input aktif' : 'Di luar periode input');
    $systemStatusClass = $settings['is_system_locked']
        ? 'danger'
        : ($isWithinInputPeriod ? 'success' : 'warning');
    $reviewStatusLabel = $settings['is_review_locked']
        ? 'Review ditutup paksa'
        : ($isWithinReviewPeriod ? 'Periode review aktif' : 'Di luar periode review');
    $reviewStatusClass = $settings['is_review_locked']
        ? 'danger'
        : ($isWithinReviewPeriod ? 'success' : 'warning');
    $personnelRequestModeLabel = $settings['personnel_request_mode'] === 'auto'
        ? 'Langsung aktif'
        : 'Perlu verifikasi superadmin';
    $periodSummary = \Illuminate\Support\Carbon::parse($settings['input_start_date'])->translatedFormat('d M Y')
        .' - '.
        \Illuminate\Support\Carbon::parse($settings['input_end_date'])->translatedFormat('d M Y');
    $reviewPeriodSummary = \Illuminate\Support\Carbon::parse($settings['review_start_date'])->translatedFormat('d M Y')
        .' - '.
        \Illuminate\Support\Carbon::parse($settings['review_end_date'])->translatedFormat('d M Y');
@endphp
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Pengaturan Sistem Superadmin</h1>
            <p>Pusat kontrol untuk mengatur perilaku sistem, master referensi, dan preferensi tampilan akun Anda.</p>
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

@if($errors->any())
<div class="alert alert-danger">
    <i class="ri-error-warning-line"></i>
    <div class="alert-content">
        <strong>Data belum tersimpan</strong>
        <span>{{ $errors->first() }}</span>
    </div>
</div>
@endif

<div class="settings-container">
    <div class="settings-overview-list">
        <div class="superadmin-stats-bar">
            <div class="ssb-item">
                <div class="ssb-icon"><i class="ri-calendar-event-line"></i></div>
                <div class="ssb-content">
                    <span class="ssb-label">Tahun Aktif</span>
                    <span class="ssb-val">TA {{ $settings['fiscal_year'] }}</span>
                </div>
            </div>
            <div class="ssb-divider"></div>
            <div class="ssb-item">
                <div class="ssb-icon"><i class="{{ $settings['is_system_locked'] ? 'ri-lock-line text-danger' : 'ri-time-line' }}"></i></div>
                <div class="ssb-content">
                    <span class="ssb-label">Sistem Input</span>
                    <span class="ssb-status {{ $systemStatusClass }}">{{ $systemStatusLabel }}</span>
                </div>
            </div>
            <div class="ssb-divider"></div>
            <div class="ssb-item">
                <div class="ssb-icon"><i class="{{ $settings['is_review_locked'] ? 'ri-lock-line text-danger' : 'ri-chat-check-line' }}"></i></div>
                <div class="ssb-content">
                    <span class="ssb-label">Review Item</span>
                    <span class="ssb-status {{ $reviewStatusClass }}">{{ $reviewStatusLabel }}</span>
                </div>
            </div>
            <div class="ssb-divider"></div>
            <div class="ssb-item">
                <div class="ssb-icon"><i class="ri-user-add-line"></i></div>
                <div class="ssb-content">
                    <span class="ssb-label">Personel Baru</span>
                    <strong class="ssb-val">{{ $personnelRequestModeLabel }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- 1. Konfigurasi Umum --}}
    <div class="settings-section">
        <div class="settings-info">
            <span class="settings-eyebrow">Pengaturan global</span>
            <h3><i class="ri-settings-4-line"></i> Konfigurasi Umum</h3>
            <p>Bagian ini memengaruhi seluruh pengguna. Gunakan untuk mengatur tahun berjalan, jadwal masa pengisian, dan cara usulan personel baru diproses.</p>
            <div class="impact-pill">Berlaku untuk seluruh sistem</div>
        </div>
        <div class="settings-card">
            <form method="POST" action="{{ route('superadmin.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="compact-guide-alert">
                    <i class="ri-lightbulb-flash-line"></i>
                    <div>
                        <strong>Panduan Singkat:</strong> Ubah Tahun Anggaran saat pindah siklus. Fitur <strong>'Kunci Paksa'</strong> dapat digunakan untuk menutup akses di tengah periode secara darurat.
                    </div>
                </div>

                <div class="form-grid-2">
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
                    </div>
                </div>

                <div class="modern-form-group" style="margin-bottom: 24px;">
                    <label>Penambahan Personel Baru (Admin Satker) <span class="required">*</span></label>
                    <select name="personnel_request_mode" class="modern-input" required>
                        <option value="auto" {{ $settings['personnel_request_mode'] === 'auto' ? 'selected' : '' }}>Langsung Aktif</option>
                        <option value="pending_verification" {{ $settings['personnel_request_mode'] === 'pending_verification' ? 'selected' : '' }}>Perlu Verifikasi Superadmin</option>
                    </select>
                </div>

                <div class="form-grid-2" style="margin-bottom: 24px;">
                    <div class="modern-toggle-group" style="border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px 16px;">
                        <div class="toggle-info">
                            <strong style="font-size:13px; color:#1e293b;">Kunci Sistem Paksa</strong>
                            <span style="font-size:11px;">Tutup semua input darurat</span>
                        </div>
                        <label class="modern-toggle">
                            <input type="checkbox" name="is_system_locked" value="1" {{ $settings['is_system_locked'] ? 'checked' : '' }}>
                            <div class="toggle-slider"></div>
                        </label>
                    </div>

                    <div class="modern-toggle-group" style="border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px 16px;">
                        <div class="toggle-info">
                            <strong style="font-size:13px; color:#1e293b;">Tutup Review Paksa</strong>
                            <span style="font-size:11px;">Hentikan review item kapor</span>
                        </div>
                        <label class="modern-toggle">
                            <input type="checkbox" name="is_review_locked" value="1" {{ $settings['is_review_locked'] ? 'checked' : '' }}>
                            <div class="toggle-slider"></div>
                        </label>
                    </div>
                </div>

                <div class="period-card" style="margin-bottom: 16px;">
                    <div class="period-card-head" style="margin-bottom: 12px;">
                        <strong style="font-size: 13px; color: #1e293b;"><i class="ri-calendar-check-line" style="color:#2563EB; margin-right:4px;"></i> Masa Pengisian Data Personel (Input)</strong>
                        <span class="compact-pill {{ $systemStatusClass }}" style="font-size:11px;">{{ $systemStatusLabel }}</span>
                    </div>
                    <div class="period-grid">
                        <div class="input-with-icon">
                            <i class="ri-calendar-event-line"></i>
                            <input type="date" name="input_start_date" class="modern-input" value="{{ $settings['input_start_date'] }}" title="Tanggal Mulai" required>
                        </div>
                        <div class="input-with-icon">
                            <i class="ri-calendar-close-line"></i>
                            <input type="date" name="input_end_date" class="modern-input" value="{{ $settings['input_end_date'] }}" title="Tanggal Ditutup" required>
                        </div>
                    </div>
                </div>

                <div class="period-card">
                    <div class="period-card-head" style="margin-bottom: 12px;">
                        <strong style="font-size: 13px; color: #1e293b;"><i class="ri-chat-check-line" style="color:#2563EB; margin-right:4px;"></i> Masa Evaluasi & Review Item Kapor</strong>
                        <span class="compact-pill {{ $reviewStatusClass }}" style="font-size:11px;">{{ $reviewStatusLabel }}</span>
                    </div>
                    <div class="period-grid">
                        <div class="input-with-icon">
                            <i class="ri-calendar-event-line"></i>
                            <input type="date" name="review_start_date" class="modern-input" value="{{ $settings['review_start_date'] }}" title="Tanggal Mulai Review" required>
                        </div>
                        <div class="input-with-icon">
                            <i class="ri-calendar-close-line"></i>
                            <input type="date" name="review_end_date" class="modern-input" value="{{ $settings['review_end_date'] }}" title="Tanggal Review Ditutup" required>
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
            <span class="settings-eyebrow">Khusus akun Anda</span>
            <h3><i class="ri-palette-line"></i> Tampilan Antarmuka</h3>
            <p>Sesuaikan tema warna sidebar sistem. Pengaturan ini <strong>hanya berlaku untuk akun Anda sendiri</strong> dan tidak akan memengaruhi admin lainnya.</p>
            <div class="impact-pill neutral">Tidak mengubah data sistem</div>
        </div>
        <div class="settings-card">
            <form action="{{ route('profile.updateTheme') }}" method="POST" id="themeForm">
                @csrf
                <div class="section-guide subtle">
                    <div class="guide-title">
                        <i class="ri-brush-line"></i>
                        <strong>Pilih tema yang paling nyaman</strong>
                    </div>
                    <p class="guide-text">Tema akan langsung diterapkan ke sidebar akun Anda setelah dipilih.</p>
                </div>
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

    {{-- 2. Penanda Tangan Export --}}
    <div class="settings-section">
        <div class="settings-info">
            <span class="settings-eyebrow">Default export</span>
            <h3><i class="ri-quill-pen-line"></i> Penanda Tangan Export</h3>
            <p>Konfigurasi ini dipakai sebagai default penanda tangan export pada akun superadmin dan menjadi fallback jika admin satker belum mengisi penanda tangan satkernya.</p>
            <div class="impact-pill">Dipakai pada dokumen export</div>
        </div>
        <div class="settings-card">
            <form method="POST" action="{{ route('superadmin.settings.signatory.update') }}">
                @csrf
                @method('PUT')

                <div class="compact-guide-alert">
                    <i class="ri-file-text-line"></i>
                    <div>
                        <strong>Format Dokumen Resmi:</strong> Data ini akan muncul sebagai penanda tangan bawaan ekspor. Pengaturan satker masing-masing tetap akan diprioritaskan jika tersedia.
                    </div>
                </div>

                <div class="form-grid-2" style="margin-bottom: 20px;">
                    <div class="modern-form-group" style="padding-bottom: 0; border: none;">
                        <label>Nama Penanda Tangan</label>
                        <div class="input-with-icon">
                            <i class="ri-user-3-line"></i>
                            <input type="text" name="signatory_name" class="modern-input" value="{{ $signatorySettings['signatory_name'] ?? '' }}" placeholder="Contoh: KOMISARIS BESAR POLISI NAMA">
                        </div>
                    </div>

                    <div class="modern-form-group" style="padding-bottom: 0; border: none;">
                        <label>Pangkat Penanda Tangan</label>
                        <div class="input-with-icon">
                            <i class="ri-shield-user-line"></i>
                            <input type="text" name="signatory_rank" class="modern-input" value="{{ $signatorySettings['signatory_rank'] ?? '' }}" placeholder="Contoh: KOMPOL">
                        </div>
                    </div>
                    
                    <div class="modern-form-group" style="padding-bottom: 0; border: none;">
                        <label>NRP / NIP Penanda Tangan</label>
                        <div class="input-with-icon">
                            <i class="ri-id-card-line"></i>
                            <input type="text" name="signatory_nrp" class="modern-input" value="{{ $signatorySettings['signatory_nrp'] ?? '' }}" placeholder="Masukkan NRP/NIP">
                        </div>
                    </div>

                    <div class="modern-form-group" style="padding-bottom: 0; border: none;">
                        <label>Jabatan Penanda Tangan</label>
                        <div class="input-with-icon">
                            <i class="ri-briefcase-4-line"></i>
                            <input type="text" name="signatory_title" class="modern-input" value="{{ $signatorySettings['signatory_title'] ?? '' }}" placeholder="Contoh: PEJABAT PEMBUAT KOMITMEN">
                        </div>
                    </div>

                    <div class="modern-form-group" style="padding-bottom: 0; border: none;">
                        <label>Atas Nama Organisasi</label>
                        <div class="input-with-icon">
                            <i class="ri-government-line"></i>
                            <input type="text" name="organization_name" class="modern-input" value="{{ $signatorySettings['organization_name'] ?? '' }}" placeholder="Contoh: KEPALA BIRO LOGISTIK POLDA NTB">
                        </div>
                    </div>

                    <div class="modern-form-group" style="padding-bottom: 0; border: none;">
                        <label>Lokasi Tanda Tangan</label>
                        <div class="input-with-icon">
                            <i class="ri-map-pin-line"></i>
                            <input type="text" name="location" class="modern-input" value="{{ $signatorySettings['location'] ?? '' }}" placeholder="Contoh: Mataram">
                        </div>
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

    {{-- 3. Master Bagian/Fungsi --}}
    <div class="settings-section">
        <div class="settings-info">
            <span class="settings-eyebrow">Referensi dropdown</span>
            <h3><i class="ri-list-check-3"></i> Master Bagian / Fungsi</h3>
            <p>Kelola daftar opsi `bagian/fungsi` yang dipakai sebagai dropdown pada form personel. Personel cukup memilih dari daftar ini agar input lebih konsisten.</p>
            <div class="impact-pill neutral">{{ number_format($bagianOptions->count()) }} opsi tersimpan</div>
        </div>
        <div class="settings-card transparent-card">
            <div class="table-card" style="margin-bottom: 20px;">
                <div class="table-card-head">
                    <h4>Tambah Opsi Baru</h4>
                </div>
                <form method="POST" action="{{ route('superadmin.bagian-options.store') }}" style="padding: 20px; display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
                    @csrf
                    <div style="flex: 1; min-width: 260px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px;">Nama Bagian / Fungsi</label>
                        <input type="text" name="name" class="modern-input" placeholder="Contoh: SAT RESKRIM" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()" required>
                    </div>
                    <button type="submit" class="btn-primary-modern">
                        <i class="ri-add-line"></i> Tambah
                    </button>
                </form>
            </div>

            <div class="table-card">
                <div class="table-card-head">
                    <h4>Daftar Master Bagian / Fungsi</h4>
                </div>
                <div class="dropdown-options-grid">
                    @forelse($bagianOptions as $option)
                        <div class="inline-edit-row grid-ier">
                            <form method="POST" action="{{ route('superadmin.bagian-options.update', $option) }}" class="ier-form" style="grid-template-columns: 1fr auto auto; padding: 10px 16px; min-height: 52px; gap: 12px;">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $option->name }}" class="ier-input" style="text-transform: uppercase; padding: 6px 10px; font-size: 13px;" oninput="this.value = this.value.toUpperCase()" required>
                                <label class="ier-toggle" style="margin:0;" title="{{ $option->is_active ? 'Aktif' : 'Nonaktif' }}">
                                    <input type="checkbox" name="is_active" value="1" {{ $option->is_active ? 'checked' : '' }}>
                                </label>
                                <div class="ier-actions" style="gap: 6px;">
                                    <button type="submit" class="btn-ier-save" style="padding: 4px 10px;">Simpan</button>
                                    <button type="submit" form="delete-bagian-{{ $option->id }}" class="btn-ier-delete" style="padding: 4px 10px;">Hapus</button>
                                </div>
                            </form>
                            <form id="delete-bagian-{{ $option->id }}" method="POST" action="{{ route('superadmin.bagian-options.destroy', $option) }}" onsubmit="return confirm('Hapus opsi {{ $option->name }}?')" style="display:none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @empty
                        <div class="empty-state" style="grid-column: 1 / -1; padding: 24px;">Belum ada master bagian/fungsi.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <hr class="settings-divider">

    {{-- 4. Alias Satker SDM --}}
    <div class="settings-section">
        <div class="settings-info">
            <span class="settings-eyebrow">Resolver import SDM</span>
            <h3><i class="ri-links-line"></i> Alias Satker SDM</h3>
            <p>Kelola alias tambahan yang dipakai resolver SDM saat membaca satker dari teks jabatan. Alias khusus di sini diprioritaskan lebih tinggi daripada alias turunan otomatis.</p>
            <div class="impact-pill neutral">{{ number_format($sdmSatkerAliases->count()) }} alias tersimpan</div>
        </div>
        <div class="settings-card transparent-card">
            <div class="table-card" style="margin-bottom: 20px;">
                <div class="table-card-head">
                    <h4>Tambah Alias Baru</h4>
                </div>
                <form method="POST" action="{{ route('superadmin.sdm-satker-aliases.store') }}" style="padding: 20px; display: grid; gap: 12px;">
                    @csrf
                    <div style="display:grid; grid-template-columns: 1.2fr 1fr 1fr auto; gap:12px; align-items:end;">
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px;">Alias Jabatan</label>
                            <input type="text" name="alias" class="modern-input" placeholder="Contoh: DEN GEGANA BRIMOB" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()" required>
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px;">Satker Tujuan</label>
                            <select name="satker_id" class="modern-input" required>
                                <option value="">Pilih Satker</option>
                                @foreach($satkers as $satker)
                                    <option value="{{ $satker->id }}">{{ $satker->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px;">Catatan</label>
                            <input type="text" name="notes" class="modern-input" placeholder="Opsional">
                        </div>
                        <button type="submit" class="btn-primary-modern">
                            <i class="ri-add-line"></i> Tambah
                        </button>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <div class="table-card-head">
                    <h4>Daftar Alias Satker SDM</h4>
                </div>
                <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%;">
                    <table class="modern-table" style="min-width: 860px;">
                        <thead>
                            <tr>
                                <th>Alias</th>
                                <th>Satker</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sdmSatkerAliases as $alias)
                            <tr>
                                <td colspan="5" style="padding:0; border:none;">
                                    <div class="inline-edit-row">
                                        <form method="POST" action="{{ route('superadmin.sdm-satker-aliases.update', $alias) }}" class="ier-form" style="grid-template-columns: 1.2fr 1fr 1fr auto auto;">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="alias" value="{{ $alias->alias }}" class="ier-input" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()" required>
                                            <select name="satker_id" class="ier-input" required>
                                                @foreach($satkers as $satker)
                                                    <option value="{{ $satker->id }}" {{ $alias->satker_id === $satker->id ? 'selected' : '' }}>{{ $satker->name }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="notes" value="{{ $alias->notes }}" class="ier-input" placeholder="Opsional">
                                            <label class="ier-toggle">
                                                <input type="checkbox" name="is_active" value="1" {{ $alias->is_active ? 'checked' : '' }}>
                                                <span>{{ $alias->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                            </label>
                                            <div class="ier-actions">
                                                <button type="submit" class="btn-ier-save">Simpan</button>
                                                <button type="submit" form="delete-alias-{{ $alias->id }}" class="btn-ier-delete">Hapus</button>
                                            </div>
                                        </form>
                                        <form id="delete-alias-{{ $alias->id }}" method="POST" action="{{ route('superadmin.sdm-satker-aliases.destroy', $alias) }}" onsubmit="return confirm('Hapus alias {{ $alias->alias }}?')" style="display:none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="empty-state">Belum ada alias satker SDM tambahan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <hr class="settings-divider">

    {{-- 5. Transisi Tahun Anggaran & Riwayat --}}
    <div class="settings-section">
        <div class="settings-info">
            <span class="settings-eyebrow">Aksi sensitif</span>
            <h3><i class="ri-history-line"></i> Riwayat & Transisi</h3>
            <p>Kelola perpindahan Tahun Anggaran. Saat satu siklus tahun sudah ditutup, Anda bisa melangkah ke siklus tahun berikutnya.</p>
            <div class="impact-pill warning">Perlu kehati-hatian tinggi</div>
        </div>
        <div class="settings-card transparent-card">
            
            {{-- Warning Box --}}
            <div class="danger-zone-box">
                <div class="danger-header">
                    <i class="ri-alert-line"></i>
                    <div>
                        <strong>Siapkan Tahun Anggaran {{ $settings['fiscal_year'] + 1 }}</strong>
                        <p>Tindakan ini akan mengunci sistem, mengarsipkan paket anggaran tahun {{ $settings['fiscal_year'] }}, membuat Tahun Anggaran {{ $settings['fiscal_year'] + 1 }} menjadi aktif, menonaktifkan akun personel, mengosongkan satker pada akun personel, lalu mereset dataset aktif personel agar siap diimport ulang dari SDM tanpa menghapus akun user.</p>
                    </div>
                </div>
                <div class="danger-checklist">
                    <span>Yang akan terjadi:</span>
                    <ul>
                        <li>Sistem langsung dikunci.</li>
                        <li>Paket anggaran tahun {{ $settings['fiscal_year'] }} diarsipkan.</li>
                        <li>Tahun Anggaran {{ $settings['fiscal_year'] + 1 }} menjadi aktif.</li>
                        <li>Akun personel dinonaktifkan, tetapi akun user tetap disimpan.</li>
                        <li>Satker pada akun user personel dikosongkan untuk persiapan import ulang SDM.</li>
                        <li>Dataset personel aktif dihapus agar tahun berikutnya dimulai dari baseline baru hasil import.</li>
                    </ul>
                </div>
                <form action="{{ route('superadmin.settings.next-year') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyiapkan Tahun Anggaran {{ $settings['fiscal_year'] + 1 }}? Sistem akan dikunci, paket anggaran tahun {{ $settings['fiscal_year'] }} diarsipkan, akun personel dinonaktifkan, satker akun personel dikosongkan, dan dataset aktif personel akan direset untuk import ulang SDM.')">
                    @csrf
                    <button type="submit" class="btn-danger-modern">
                        Siapkan Tahun Anggaran {{ $settings['fiscal_year'] + 1 }}
                    </button>
                </form>
            </div>

            {{-- History Table --}}
                <div class="table-card">
                    <div class="table-card-head">
                        <h4>Riwayat Tahun Anggaran</h4>
                    </div>
                    <div style="padding: 0 20px 14px; color: var(--text-muted); font-size: 13px;">
                        Tahun yang sudah selesai akan membaca <strong>snapshot arsip final</strong> yang tersimpan saat transisi tahun, jadi hasil lama tidak dihitung ulang dari data aktif sekarang.
                    </div>
                    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%;">
                    <table class="modern-table" style="min-width: 760px;">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th style="text-align:right;">Personel Final</th>
                                <th style="text-align:right;">Lengkap Ukuran</th>
                                <th style="text-align:right;">Entri Legacy</th>
                                <th style="text-align:right;">Arsip Final</th>
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
                                        <div style="margin-top: 4px; font-size: 12px; color: var(--text-muted);">
                                            {{ $ys->snapshot_source }}
                                        </div>
                                        @if(!$ys->is_active && $ys->has_budget_active_flag && (int) $ys->fiscal_year !== (int) $settings['fiscal_year'])
                                            <div style="margin-top: 2px; font-size: 11px; color: #B45309;">
                                                Flag budget aktif masih menyala, tetapi tahun ini bukan Tahun Sistem Aktif.
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td align="right"><strong>{{ number_format($ys->personnel_total) }}</strong> personel</td>
                                <td align="right"><strong>{{ number_format($ys->submitted_total) }}</strong> personel</td>
                                <td align="right">
                                    <strong>{{ number_format($ys->submission_total) }}</strong> entri
                                    <div style="margin-top: 2px; font-size: 11px; color: var(--text-muted);">
                                        KaporSubmission lama
                                    </div>
                                </td>
                                <td align="right">
                                    @if($ys->archive_files > 0)
                                        <strong>{{ number_format($ys->archive_files) }}</strong> file
                                    @else
                                        <span style="color: var(--text-muted);">Belum ada</span>
                                    @endif
                                </td>
                                <td align="right">
                                    <span class="status-pill {{ in_array($ys->status, ['Berjalan', 'Terkunci']) ? ($ys->status === 'Berjalan' ? 'success' : 'neutral') : 'warning' }}">
                                        {{ $ys->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="empty-state">Belum ada riwayat data sebelumnya.</td></tr>
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
        max-width: 1280px;
        margin: 0 auto;
        padding-bottom: 60px;
    }
    .settings-overview-list {
        margin-bottom: 8px;
    }
    .superadmin-stats-bar {
        background: #ffffff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px 20px;
        display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .ssb-item { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }
    .ssb-icon { width: 44px; height: 44px; border-radius: 10px; background: #EEF2FF; color: #4F46E5; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
    .ssb-icon .text-danger { color: #DC2626; background: #FEF2F2; }
    .ssb-content { display: flex; flex-direction: column; gap: 4px; overflow: hidden; }
    .ssb-label { font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; text-overflow: ellipsis; }
    .ssb-val { font-size: 14px; font-weight: 700; color: #0F172A; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; }
    .ssb-status { display: inline-flex; align-items: center; font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 6px; white-space: nowrap; max-width: fit-content; }
    .ssb-status.success { background: #DCFCE7; color: #166534; }
    .ssb-status.warning { background: #FEF9C3; color: #854D0E; }
    .ssb-status.danger { background: #FEE2E2; color: #991B1B; }
    .ssb-divider { width: 1px; height: 36px; background: #E2E8F0; flex-shrink: 0; }

    .compact-guide-alert { background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 12px 16px; display: flex; align-items: flex-start; gap: 12px; margin-bottom: 24px; }
    .compact-guide-alert i { font-size: 20px; color: #2563EB; margin-top: 2px; }
    .compact-guide-alert div { font-size: 13px; color: #1E3A8A; line-height: 1.5; }
    
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; min-width: 0; }
    .settings-section {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 32px;
        padding: 40px 0;
    }
    @media (max-width: 900px) {
        .settings-section { grid-template-columns: 1fr; gap: 20px; padding: 30px 0; }
        .superadmin-stats-bar { flex-direction: column; align-items: flex-start; }
        .ssb-divider { width: 100%; height: 1px; }
        .form-grid-2 { grid-template-columns: 1fr; }
    }

    .inline-edit-row { border-bottom: 1px solid #F1F5F9; transition: all 0.2s; }
    .inline-edit-row:hover { background: #F8FAFC; }
    .inline-edit-row:last-child { border-bottom: none; }
    .ier-form { display: grid; gap: 16px; align-items: center; padding: 12px 24px; min-height: 56px; width: 100%; }
    .ier-input { border: 1px solid transparent; background: transparent; padding: 8px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; color: #1E293B; width: 100%; transition: 0.2s; }
    .ier-input:hover { border-color: #E2E8F0; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .ier-input:focus { border-color: #3B82F6; background: #fff; outline:none; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
    .ier-toggle { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #64748B; cursor: pointer; white-space: nowrap; user-select: none; }
    .ier-actions { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
    .btn-ier-save { background: #F8FAFC; border: 1px solid #CBD5E1; color: #475569; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; white-space: nowrap; }
    .btn-ier-save:hover { background: #F1F5F9; color: #1E293B; border-color: #94A3B8; }
    .btn-ier-delete { background: #FEF2F2; border: 1px solid #FECACA; color: #DC2626; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; white-space: nowrap; }
    .btn-ier-delete:hover { background: #FEE2E2; color: #B91C1C; border-color: #F87171; }

    .dropdown-options-grid { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid #F1F5F9; }
    .grid-ier { border-bottom: 1px solid #F1F5F9; border-right: 1px solid #F1F5F9; }
    .grid-ier:nth-child(even) { border-right: none; }
    @media (max-width: 1024px) { .dropdown-options-grid { grid-template-columns: 1fr; } .grid-ier { border-right: none; } }

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
    .settings-eyebrow {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-muted);
        margin-bottom: 12px;
    }
    .impact-pill {
        margin-top: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: var(--brand-bg);
        border: 1px solid rgba(198, 40, 40, 0.14);
        color: var(--brand);
        font-size: 12px;
        font-weight: 700;
    }
    .impact-pill.neutral {
        background: var(--bg-body);
        border-color: var(--border-color);
        color: var(--text-muted);
    }
    .impact-pill.warning {
        background: #FFF7ED;
        border-color: #FED7AA;
        color: #C2410C;
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
    .section-guide {
        padding: 20px 24px 0;
    }
    .section-guide.subtle {
        padding-bottom: 4px;
    }
    .guide-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        color: var(--text-main);
    }
    .guide-title i {
        font-size: 18px;
        color: var(--brand);
    }
    .guide-title strong {
        font-size: 14px;
    }
    .guide-list {
        margin: 0;
        padding-left: 18px;
        color: var(--text-muted);
        font-size: 12.5px;
        line-height: 1.7;
    }
    .guide-text {
        margin: 0;
        color: var(--text-muted);
        font-size: 12.5px;
        line-height: 1.7;
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
    .status-banner {
        margin: 0 24px 20px;
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
    }
    .status-banner i {
        font-size: 20px;
        margin-top: 1px;
    }
    .status-banner strong {
        display: block;
        font-size: 13px;
        color: var(--text-main);
        margin-bottom: 4px;
    }
    .status-banner span {
        display: block;
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.6;
    }
    .status-banner.success {
        border-color: #BBF7D0;
        background: #F0FDF4;
    }
    .status-banner.success i,
    .status-banner.success strong {
        color: #166534;
    }
    .status-banner.warning {
        border-color: #FDE68A;
        background: #FFFBEB;
    }
    .status-banner.warning i,
    .status-banner.warning strong {
        color: #92400E;
    }
    .status-banner.danger {
        border-color: #FECACA;
        background: #FEF2F2;
    }
    .status-banner.danger i,
    .status-banner.danger strong {
        color: #B91C1C;
    }
    .period-card {
        margin: 0 24px 20px;
        padding: 16px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        background: var(--bg-body);
    }
    .period-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }
    .period-card-head strong {
        display: block;
        font-size: 13px;
        color: var(--text-main);
        margin-bottom: 4px;
    }
    .period-card-head span {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.6;
    }
    .compact-pill {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        font-size: 11px;
        font-weight: 700;
        color: var(--text-main);
    }
    .period-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
    .period-grid label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--text-muted);
        margin-bottom: 6px;
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
    .danger-checklist {
        border-top: 1px dashed rgba(180, 83, 9, 0.2);
        padding-top: 14px;
    }
    .danger-checklist > span {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #92400E;
        margin-bottom: 8px;
    }
    .danger-checklist ul {
        margin: 0;
        padding-left: 18px;
        color: #B45309;
        font-size: 12.5px;
        line-height: 1.7;
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
    .status-pill.warning { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }
    .status-pill.danger { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
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
    .alert.alert-danger {
        background: #FEF2F2;
        border-color: #FECACA;
        box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.08);
    }
    .alert > i {
        font-size: 24px;
        color: #16A34A;
    }
    .alert.alert-danger > i { color: #DC2626; }
    .alert-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .alert-content strong { color: #166534; font-size: 14px; margin-bottom: 2px; }
    .alert-content span { color: #15803D; font-size: 13px; }
    .alert.alert-danger .alert-content strong { color: #991B1B; }
    .alert.alert-danger .alert-content span { color: #B91C1C; }
    .alert-close {
        background: none; border: none; font-size: 20px; color: #16A34A; cursor: pointer; opacity: 0.7; transition: 0.2s;
    }
    .alert-close:hover { opacity: 1; transform: scale(1.1); }
    
    @media (max-width: 900px) {
        .settings-overview-list { gap: 10px; }
    }
    @media (max-width: 640px) {
        .theme-grid,
        .period-grid { grid-template-columns: 1fr; }
        .period-card-head,
        .modern-toggle-group { flex-direction: column; align-items: stretch; }
    }

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
