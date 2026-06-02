@extends('layouts.personil')

@section('title', 'Data Kaporlap Personil')



@section('content')
    @php
        $sHead = range(54, 60);
        $sShirtMale = ['14', '14,5', '15', '15,5', '16', '16,5', '17', '17,5', '18', '18,5', '19', '19,5', '20', '21', '22'];
        $sWomen = ['K', 'SD', 'B', 'EB', 'EEB', 'EEEB', 'EEEEB'];
        $sPantsMale = range(27, 50);
        $sShoes = range(36, 48);
        $sBelt = range(36, 60, 2);
        $sJilbab = ['K', 'SD', 'B'];
        $gender = $personnel?->gender ?? 'L';
        $religion = strtoupper(trim((string) ($personnel?->religion ?? '')));
        $requiresJilbab = $gender === 'P' && $religion === 'ISLAM';
        $selectedBagian = old('bagian', $personnel?->bagian ?? '');
        $bagianOptionsList = collect($bagianOptions ?? [])
            ->filter(fn ($option) => filled($option))
            ->values();

        if (filled($selectedBagian) && ! $bagianOptionsList->contains($selectedBagian)) {
            $bagianOptionsList = $bagianOptionsList->prepend($selectedBagian);
        }

        $usesBagianDropdown = $requiresBagian ?? false;
        $currentPhone = old('phone', $contactPhone ?? $personnel?->phone ?? $user->phone ?? '');
        $identityStepLabel = $usesBagianDropdown ? '1. Jabatan + Bag/Fungsi + No. WA' : '1. Jabatan + No. WA';
        $showIdentityForm = !$identityReady || old('mode') === 'identity' || $errors->has('jabatan') || $errors->has('bagian') || $errors->has('phone');
        $showSizesForm = !$hasSubmitted || old('mode') === 'sizes';
        $isInputOpen = $inputPeriodStatus['is_open'] ?? true;
        $progressPct = !$identityReady ? 33 : ($isComplete ? 100 : ($hasSubmitted ? 82 : 64));
        $summaryItems = [
            'Kemeja'          => $kaporSizes['kemeja'] ?? '-',
            'Celana/Rok'      => $kaporSizes['celana'] ?? '-',
            'Olahraga'        => $kaporSizes['olahraga'] ?? '-',
            'Jaket'           => $kaporSizes['jaket'] ?? '-',
            'Topi/Baret'      => $kaporSizes['topi'] ?? '-',
            'Sabuk'           => $kaporSizes['sabuk'] ?? '-',
            'Sepatu Dinas'    => $kaporSizes['sepatu_dinas'] ?? '-',
            'Sepatu Olahraga' => $kaporSizes['sepatu_olahraga'] ?? '-',
        ];

        if ($requiresJilbab) {
            $summaryItems['Jilbab'] = $kaporSizes['jilbab'] ?? '-';
        }

        $showReviewHero = $reviewPeriodStatus['is_open'] ?? false;

        $sizeIcons = [
            'kemeja'          => 'ri-shirt-line',
            'celana'          => 'ri-layout-bottom-2-line',
            'olahraga'        => 'ri-run-line',
            'jaket'           => 'ri-windy-line',
            'topi'            => 'ri-hat-line',
            'sabuk'           => 'ri-circle-line',
            'jilbab'          => 'ri-vip-crown-line',
            'sepatu_dinas'    => 'ri-footprint-line',
            'sepatu_olahraga' => 'ri-football-line',
        ];
    @endphp

    @if (!$personnel)
        <div class="d-alert d-alert--error">
            <i class="ri-error-warning-line"></i>
            Data personel belum tersedia. Hubungi admin sebelum mengisi kaporlap.
        </div>
    @else
        <div class="page">

            {{-- ── REVIEW / STATUS BANNER (full width) ──── --}}
            <div class="page-full">
            @if ($showReviewHero)
                <section class="review-card {{ $reviewPrompt['tone'] }}" data-dismissible data-dismiss-key="personil-dashboard-review-hero">
                    <div class="review-card-body">
                        <div class="review-card-head">
                            <div>
                                <div class="review-card-title-row">
                                    <strong>{{ $reviewPrompt['title'] }}</strong>
                                    <div class="status-meta">
                                        <i class="ri-calendar-check-line"></i>
                                        Review: {{ $reviewPeriodStatus['period_label'] }}
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="dismiss-btn" data-dismiss-trigger aria-label="Sembunyikan kartu review">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <p class="review-card-copy">{{ $reviewPrompt['message'] }}</p>
                        <div class="review-eligible">
                            <strong>Item Kaporlap Anda <span class="review-inline-count">({{ $eligibleItems }})</span></strong>
                            <div class="item-chip-row">
                                @forelse ($eligibleReviewItems as $itemName)
                                    <span class="item-chip">{{ $itemName }}</span>
                                @empty
                                    <span class="review-empty-note">Belum ada item review untuk akun Anda.</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="review-action-row">
                            <a href="{{ route('personil.testimoni.index') }}" class="review-cta">
                                {{ $reviewPrompt['action_label'] }}
                                <i class="ri-arrow-right-up-line"></i>
                            </a>
                        </div>
                    </div>
                </section>
            @else
                <div class="status-banner {{ $inputPeriodStatus['tone'] }}" data-dismissible data-dismiss-key="personil-dashboard-input-status">
                    <div class="status-banner-inner">
                        <div class="status-banner-icon">
                            @if($inputPeriodStatus['tone'] === 'success') <i class="ri-checkbox-circle-line"></i>
                            @elseif($inputPeriodStatus['tone'] === 'warning') <i class="ri-time-line"></i>
                            @elseif($inputPeriodStatus['tone'] === 'error') <i class="ri-lock-line"></i>
                            @else <i class="ri-information-line"></i>
                            @endif
                        </div>
                        <div class="status-banner-content">
                            <strong>{{ $inputPeriodStatus['title'] }}</strong>
                            <span>{{ $inputPeriodStatus['message'] }}</span>
                            <div class="status-meta" style="margin-top:6px;">
                                <i class="ri-calendar-line"></i>
                                Periode aktif: {{ $inputPeriodStatus['period_label'] }}
                            </div>
                        </div>
                        <button type="button" class="dismiss-btn" data-dismiss-trigger aria-label="Tutup">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                </div>
            @endif

            </div>{{-- /page-full --}}

            {{-- ── SESSION ALERTS (full width) ─────────────── --}}
            <div class="page-full">
            @if (session('success'))
                <div class="d-alert d-alert--success">
                    <i class="ri-checkbox-circle-line"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="d-alert d-alert--error">
                    <i class="ri-error-warning-line"></i>
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="d-alert d-alert--error">
                    <i class="ri-error-warning-line"></i>
                    Masih ada field yang perlu diperbaiki.
                </div>
            @endif

            </div>{{-- /page-full (alerts) --}}

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

                {{-- Progress --}}
                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label">Progres Pengisian</span>
                        <span class="progress-pct" id="progressPct">{{ $progressPct }}%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: {{ $progressPct }}%;"></div>
                    </div>
                </div>

                {{-- Stepper --}}
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
                        <span class="stepper-label">2. Ukuran Kaporlap</span>
                    </div>
                </div>
            </section>


                {{-- ── FOOTER (desktop: masuk sidebar bawah) ─ --}}
                <footer class="page-footer reveal">
                    <a href="{{ route('personil.kapor.history') }}" class="footer-pill">
                        <i class="ri-history-line"></i> Riwayat Ukuran
                    </a>
                    <a href="{{ route('personil.testimoni.index') }}" class="footer-pill">
                        <i class="ri-feedback-line"></i> Review Item
                    </a>
                </footer>

            </div>{{-- /page-sidebar --}}

            {{-- ── MAIN CONTENT ──────────────────────────────── --}}
            <div class="page-main">

            @if ($allocatedKaporItems->isNotEmpty())
                <section class="d-panel reveal">
                    <div class="d-panel-header">
                        <div class="d-panel-header-icon"><i class="ri-gift-line"></i></div>
                        <div>
                            <h2 class="d-panel-title">Barang yang Anda Dapatkan</h2>
                            <p class="d-panel-subtitle">Daftar ini muncul setelah superadmin memfinalkan paket pengadaan.</p>
                        </div>
                    </div>
                    <div class="d-panel-body">
                        <div class="allocation-grid">
                            @foreach ($allocatedKaporItems as $item)
                                <div class="allocation-card reveal-stagger">
                                    <div class="allocation-card-top">
                                        <strong class="allocation-name">{{ $item['item_name'] }}</strong>
                                        <span class="allocation-category">{{ $item['category'] }}</span>
                                    </div>
                                    <div class="allocation-badge-row">
                                        <span class="allocation-size-label">{{ $item['size_label'] }}</span>
                                        <div class="allocation-badge-group">
                                            @foreach(explode('/', $item['size_value']) as $val)
                                                <span class="allocation-size-badge">{{ trim($val) }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            {{-- ── DATA PERSONEL ─────────────────────────── --}}
            <section class="d-panel reveal">
                <div class="d-panel-header">
                    <div class="d-panel-header-icon"><i class="ri-user-line"></i></div>
                    <div>
                        <h2 class="d-panel-title">Data Personel</h2>
                        <p class="d-panel-subtitle">Jabatan dari SDM Polda NTB. Perubahan tercatat di log audit.</p>
                    </div>
                </div>
                <div class="d-panel-body">
                    @if ($identityReady && !$showIdentityForm)
                        <div data-identity-summary>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <span class="summary-label"><i class="ri-briefcase-line"></i> Jabatan</span>
                                    <span class="summary-value">{{ $personnel->jabatan }}</span>
                                </div>
                                @if ($usesBagianDropdown)
                                    <div class="summary-item">
                                        <span class="summary-label"><i class="ri-organization-chart"></i> Bag/Fungsi</span>
                                        <span class="summary-value">{{ $personnel->bagian }}</span>
                                    </div>
                                @endif
                                <div class="summary-item">
                                    <span class="summary-label"><i class="ri-phone-line"></i> No. HP (WhatsApp)</span>
                                    <span class="summary-value">{{ $currentPhone ?: '-' }}</span>
                                </div>
                            </div>
                            @if ($isInputOpen)
                                <div style="margin-top:16px;">
                                    <button type="button" class="btn-outline" data-open-identity>
                                        <i class="ri-edit-line"></i> Edit Data Personel
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif

                    <form action="{{ route('personil.kapor.store') }}" method="POST"
                          class="{{ $showIdentityForm ? '' : 'hidden' }}" data-identity-form style="margin-top:4px;">
                        @csrf
                        <input type="hidden" name="mode" value="identity">
                        <fieldset @disabled(! $isInputOpen) style="border:0;padding:0;margin:0;min-width:0;">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label class="form-label" for="jabatan">Jabatan</label>
                                    <input id="jabatan" type="text" name="jabatan" class="form-control"
                                           value="{{ old('jabatan', $personnel->jabatan ?? '') }}"
                                           style="text-transform:uppercase;"
                                           oninput="this.value = this.value.toUpperCase()">
                                    <span class="form-hint">Referensi SDM Polda NTB.</span>
                                    @error('jabatan')<span class="form-error">{{ $message }}</span>@enderror
                                </div>

                                @if ($usesBagianDropdown)
                                    <div class="form-field">
                                        <label class="form-label" for="bagian">Bag/Fungsi</label>
                                        <select id="bagian" name="bagian" class="form-control">
                                            <option value="">Pilih Bagian / Fungsi</option>
                                            @if ($selectedBagian && ! $bagianOptionsList->contains($selectedBagian))
                                                <option value="{{ $selectedBagian }}" selected>{{ $selectedBagian }}</option>
                                            @endif
                                            @foreach ($bagianOptionsList as $option)
                                                <option value="{{ $option }}" {{ $selectedBagian === $option ? 'selected' : '' }}>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        <span class="form-hint">Khusus satker polres.</span>
                                        @error('bagian')<span class="form-error">{{ $message }}</span>@enderror
                                    </div>
                                @endif

                                <div class="form-field">
                                    <label class="form-label" for="phone">No. HP (WhatsApp)</label>
                                    <input id="phone" type="text" name="phone" class="form-control"
                                           inputmode="numeric" autocomplete="tel"
                                           placeholder="Contoh: 08123456789"
                                           value="{{ $currentPhone }}"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    <span class="form-hint">Dipakai admin untuk chat via WhatsApp.</span>
                                    @error('phone')<span class="form-error">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </fieldset>

                        <div class="form-note">
                            {{ $usesBagianDropdown ? 'Simpan jabatan, bag/fungsi, dan no. HP dulu, lalu lanjut ke Isi ukuran.' : 'Simpan jabatan dan no. HP dulu, lalu lanjut ke Isi ukuran.' }}
                        </div>

                        @unless ($isInputOpen)
                            <div class="form-note form-note--danger">
                                Periode pengisian sedang ditutup. Anda tidak dapat mengubah data personel untuk sementara waktu.
                            </div>
                        @endunless

                        <div style="margin-top:16px;">
                            <button type="submit" class="btn-primary" @disabled(! $isInputOpen)>
                                {{ $isInputOpen ? 'Simpan Data' : 'Input Ditutup' }}
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- ── UKURAN KAPORLAP ───────────────────────── --}}
            @if ($identityReady)
                <section class="d-panel reveal" id="ukuran-form">
                    <div class="d-panel-header">
                        <div class="d-panel-header-icon"><i class="ri-ruler-line"></i></div>
                        <div>
                            <h2 class="d-panel-title">Ukuran Kaporlap</h2>
                            <p class="d-panel-subtitle">Isi seperlunya. Data lama tetap tampil sebagai nilai awal.</p>
                        </div>
                    </div>
                    <div class="d-panel-body">
                        @if ($hasSubmitted && !$showSizesForm)
                            <div data-sizes-summary>
                                <div class="sizes-badge-grid">
                                    @foreach ($summaryItems as $label => $value)
                                        <div class="sizes-badge-item">
                                            <span class="sizes-badge-label">{{ $label }}</span>
                                            <span class="sizes-badge-value {{ $value && $value !== '-' ? 'filled' : '' }}">{{ $value ?: '-' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($isInputOpen)
                                    <div style="margin-top:16px;">
                                        <button type="button" class="btn-outline" data-open-sizes>
                                            <i class="ri-edit-line"></i> Edit Ukuran
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <form action="{{ route('personil.kapor.store') }}" method="POST"
                              class="{{ $showSizesForm ? '' : 'hidden' }}" data-sizes-form style="margin-top:4px;">
                            @csrf
                            <input type="hidden" name="mode" value="sizes">
                            <input type="hidden" name="jabatan" value="{{ old('jabatan', $personnel->jabatan ?? '') }}">
                            <input type="hidden" name="phone" value="{{ $currentPhone }}">
                            @if ($usesBagianDropdown)
                                <input type="hidden" name="bagian" value="{{ old('bagian', $personnel->bagian ?? '') }}">
                            @endif

                            <fieldset @disabled(! $isInputOpen) style="border:0;padding:0;margin:0;min-width:0;">
                                <div class="sizes-grid">
                                    {{-- Kemeja --}}
                                    <div class="form-field">
                                        <label class="form-label" for="kemeja"><i class="ri-shirt-line"></i> Kemeja</label>
                                        <select id="kemeja" name="kemeja" class="form-control" required>
                                            <option value="">Pilih</option>
                                            @foreach ($gender === 'L' ? $sShirtMale : $sWomen as $size)
                                                <option value="{{ $size }}" {{ old('kemeja', $kaporSizes['kemeja'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                            @endforeach
                                        </select>
                                        @error('kemeja')<span class="form-error">{{ $message }}</span>@enderror
                                    </div>
                                    {{-- Celana --}}
                                    <div class="form-field">
                                        <label class="form-label" for="celana"><i class="ri-layout-bottom-2-line"></i> Celana / Rok</label>
                                        <select id="celana" name="celana" class="form-control" required>
                                            <option value="">Pilih</option>
                                            @foreach ($gender === 'L' ? $sPantsMale : $sWomen as $size)
                                                <option value="{{ $size }}" {{ old('celana', $kaporSizes['celana'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                            @endforeach
                                        </select>
                                        @error('celana')<span class="form-error">{{ $message }}</span>@enderror
                                    </div>
                                    {{-- Olahraga --}}
                                    <div class="form-field">
                                        <label class="form-label" for="olahraga"><i class="ri-run-line"></i> Olahraga</label>
                                        <select id="olahraga" name="olahraga" class="form-control" required>
                                            <option value="">Pilih</option>
                                            @foreach ($sWomen as $size)
                                                <option value="{{ $size }}" {{ old('olahraga', $kaporSizes['olahraga'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                            @endforeach
                                        </select>
                                        @error('olahraga')<span class="form-error">{{ $message }}</span>@enderror
                                    </div>
                                    {{-- Jaket --}}
                                    <div class="form-field">
                                        <label class="form-label" for="jaket"><i class="ri-windy-line"></i> Jaket</label>
                                        <select id="jaket" name="jaket" class="form-control" required>
                                            <option value="">Pilih</option>
                                            @foreach ($sWomen as $size)
                                                <option value="{{ $size }}" {{ old('jaket', $kaporSizes['jaket'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                            @endforeach
                                        </select>
                                        @error('jaket')<span class="form-error">{{ $message }}</span>@enderror
                                    </div>
                                    {{-- Topi --}}
                                    <div class="form-field">
                                        <label class="form-label" for="topi"><i class="ri-hat-line"></i> Topi / Baret</label>
                                        <select id="topi" name="topi" class="form-control" required>
                                            <option value="">Pilih</option>
                                            @foreach ($sHead as $size)
                                                <option value="{{ $size }}" {{ old('topi', $kaporSizes['topi'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                            @endforeach
                                        </select>
                                        @error('topi')<span class="form-error">{{ $message }}</span>@enderror
                                    </div>
                                    {{-- Sabuk --}}
                                    <div class="form-field">
                                        <label class="form-label" for="sabuk"><i class="ri-link-m"></i> Sabuk</label>
                                        <select id="sabuk" name="sabuk" class="form-control" required>
                                            <option value="">Pilih</option>
                                            @foreach ($sBelt as $size)
                                                <option value="{{ $size }}" {{ old('sabuk', $kaporSizes['sabuk'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                            @endforeach
                                        </select>
                                        @error('sabuk')<span class="form-error">{{ $message }}</span>@enderror
                                    </div>
                                    {{-- Jilbab (conditional) --}}
                                    @if ($requiresJilbab)
                                        <div class="form-field">
                                            <label class="form-label" for="jilbab"><i class="ri-vip-crown-line"></i> Jilbab</label>
                                            <select id="jilbab" name="jilbab" class="form-control" required>
                                                <option value="">Pilih</option>
                                                @foreach ($sJilbab as $size)
                                                    <option value="{{ $size }}" {{ old('jilbab', $kaporSizes['jilbab'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                                @endforeach
                                            </select>
                                            @error('jilbab')<span class="form-error">{{ $message }}</span>@enderror
                                        </div>
                                    @endif
                                    {{-- Sepatu Dinas --}}
                                    <div class="form-field">
                                        <label class="form-label" for="sepatu_dinas"><i class="ri-footprint-line"></i> Sepatu Dinas</label>
                                        <select id="sepatu_dinas" name="sepatu_dinas" class="form-control" required>
                                            <option value="">Pilih</option>
                                            @foreach ($sShoes as $size)
                                                <option value="{{ $size }}" {{ old('sepatu_dinas', $kaporSizes['sepatu_dinas'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                            @endforeach
                                        </select>
                                        @error('sepatu_dinas')<span class="form-error">{{ $message }}</span>@enderror
                                    </div>
                                    {{-- Sepatu Olahraga --}}
                                    <div class="form-field">
                                        <label class="form-label" for="sepatu_olahraga"><i class="ri-footprint-line"></i> Sepatu Olahraga</label>
                                        <select id="sepatu_olahraga" name="sepatu_olahraga" class="form-control" required>
                                            <option value="">Pilih</option>
                                            @foreach ($sShoes as $size)
                                                <option value="{{ $size }}" {{ old('sepatu_olahraga', $kaporSizes['sepatu_olahraga'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                            @endforeach
                                        </select>
                                        @error('sepatu_olahraga')<span class="form-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </fieldset>

                            @unless ($isInputOpen)
                                <div class="form-note form-note--danger" style="margin-top:16px;">
                                    Periode pengisian sedang ditutup. Anda hanya bisa melihat ukuran yang ada.
                                </div>
                            @endunless

                            <div style="margin-top:20px;">
                                <button type="submit" class="btn-primary" @disabled(! $isInputOpen)>
                                    {{ $isInputOpen ? 'Simpan Ukuran' : 'Input Ditutup' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @else
                <section class="d-panel reveal" id="ukuran-form">
                    <div class="d-panel-header">
                        <div class="d-panel-header-icon d-panel-header-icon--muted"><i class="ri-ruler-line"></i></div>
                        <div>
                            <h2 class="d-panel-title">Ukuran Kaporlap</h2>
                            <p class="d-panel-subtitle">Lengkapi dulu data personel agar formulir ukuran aktif.</p>
                        </div>
                    </div>
                </section>
            @endif

            </div>{{-- /page-main --}}

        </div>{{-- /page --}}
    @endif
@endsection

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
    /* ── DASHBOARD-SPECIFIC TOKENS ──────────────────────── */
    :root {
        --dp-radius-xl: 20px;
        --dp-radius-lg: 16px;
        --dp-radius-md: 12px;
        --dp-radius-sm: 8px;
        --dp-shadow: 0 4px 24px rgba(15,23,42,0.07);
        --dp-shadow-hover: 0 8px 32px rgba(15,23,42,0.12);
    }

    /* ── ALERTS ─────────────────────────────────────────── */
    .d-alert {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 20px;
        border-radius: var(--dp-radius-lg);
        font-size: 14px;
        font-weight: 600;
        border: 1px solid;
        background-color: #fff;
        box-shadow: var(--dp-shadow);
        animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .d-alert i {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        font-size: 20px;
        flex-shrink: 0;
    }
    .d-alert--success { border-color: #bbf7d0; color: #15803d; }
    .d-alert--success i { background: #dcfce7; color: #16a34a; }
    
    .d-alert--error { border-color: #fecaca; color: #b91c1c; }
    .d-alert--error i { background: #fee2e2; color: #dc2626; }
    
    .d-alert--info { border-color: #bfdbfe; color: #1d4ed8; }
    .d-alert--info i { background: #dbeafe; color: #2563eb; }
    
    .d-alert--warning { border-color: #fde68a; color: #b45309; }
    .d-alert--warning i { background: #fef3c7; color: #d97706; }

    /* ── STATUS BANNER ──────────────────────────────────── */
    .status-banner {
        border-radius: var(--dp-radius-xl);
        border: 1px solid;
        overflow: hidden;
        position: relative;
        box-shadow: var(--dp-shadow);
        animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .status-banner::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0.8;
        z-index: 0;
        pointer-events: none;
    }

    .status-banner.success { background: #fff; border-color: #bbf7d0; }
    .status-banner.success::before { background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); }

    .status-banner.warning { background: #fff; border-color: #fde68a; }
    .status-banner.warning::before { background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%); }

    .status-banner.error   { background: #fff; border-color: #fecaca; }
    .status-banner.error::before { background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%); }

    .status-banner.info    { background: #fff; border-color: #bfdbfe; }
    .status-banner.info::before { background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); }

    .status-banner-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 20px 24px;
    }

    .status-banner-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .status-banner.success .status-banner-icon { color: #16a34a; }
    .status-banner.warning .status-banner-icon { color: #d97706; }
    .status-banner.error   .status-banner-icon { color: #dc2626; }
    .status-banner.info    .status-banner-icon { color: #2563eb; }

    .status-banner-content {
        flex: 1;
        min-width: 0;
        padding-top: 2px;
    }
    .status-banner-content strong {
        display: block;
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -0.01em;
        line-height: 1.3;
        color: var(--text-main);
    }
    .status-banner-content span {
        display: block;
        font-size: 13.5px;
        font-weight: 500;
        color: var(--slate-600);
        margin-top: 4px;
        line-height: 1.5;
    }

    .status-meta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 999px;
        background: #fff;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-main);
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }

    .dismiss-btn {
        width: 30px;
        height: 30px;
        border: 0;
        border-radius: 8px;
        background: rgba(15,23,42,0.06);
        color: var(--text-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: all 0.18s;
        font-size: 16px;
    }
    .dismiss-btn:hover { background: rgba(15,23,42,0.12); color: var(--text-main); }

    /* ── REVIEW CARD ────────────────────────────────────── */
    .review-card {
        border-radius: var(--dp-radius-xl);
        border: 1px solid rgba(255,255,255,0.5);
        box-shadow: var(--dp-shadow);
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .review-card:hover { transform: translateY(-2px); box-shadow: var(--dp-shadow-hover); }
    .review-card.info    { background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border-color: #bae6fd; }
    .review-card.warning { background: linear-gradient(135deg, #fefce8, #fef3c7); border-color: #fde68a; }
    .review-card.success { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-color: #bbf7d0; }

    .review-card-body { display: grid; gap: 14px; padding: 20px; }

    .review-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .review-card-head > div { flex: 1; min-width: 0; }

    .review-card-title-row {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    @media (min-width: 640px) {
        .review-card-title-row { flex-direction: row; align-items: center; gap: 12px; }
    }
    .review-card.info    .review-card-head strong { color: #0369a1; font-size: 15px; font-weight: 800; }
    .review-card.warning .review-card-head strong { color: #b45309; font-size: 15px; font-weight: 800; }
    .review-card.success .review-card-head strong { color: #15803d; font-size: 15px; font-weight: 800; }

    .review-card-copy { font-size: 13.5px; color: var(--slate-700); line-height: 1.6; margin: 0; }

    .review-eligible {
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: rgba(255,255,255,0.55);
        padding: 14px;
        border-radius: var(--dp-radius-md);
        backdrop-filter: blur(4px);
    }
    .review-eligible strong {
        font-size: 11px;
        color: var(--slate-600);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 700;
    }
    .review-inline-count {
        font-size: 13px;
        font-weight: 800;
        color: var(--text-main);
        background: rgba(255,255,255,0.9);
        padding: 2px 8px;
        border-radius: 999px;
        margin-left: 4px;
    }

    .item-chip-row { display: flex; gap: 6px; flex-wrap: wrap; }
    .item-chip {
        display: inline-flex;
        align-items: center;
        height: 30px;
        padding: 0 12px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid rgba(0,0,0,0.06);
        font-size: 12px;
        font-weight: 700;
        color: var(--slate-700);
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        transition: all 0.18s;
    }
    .item-chip:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.08); }
    .review-empty-note { font-size: 13px; color: var(--text-muted); }

    .review-action-row { display: flex; justify-content: center; }
    @media (min-width: 640px) { .review-action-row { justify-content: flex-end; } }

    .review-cta {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 46px;
        padding: 0 24px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        width: 100%;
        justify-content: center;
    }
    @media (min-width: 640px) { .review-cta { width: auto; } }
    .review-card.info    .review-cta { background: #0284c7; color: #fff; }
    .review-card.info    .review-cta:hover { background: #0369a1; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(2,132,199,0.3); }
    .review-card.warning .review-cta { background: #d97706; color: #fff; }
    .review-card.warning .review-cta:hover { background: #b45309; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(217,119,6,0.3); }
    .review-card.success .review-cta { background: #16a34a; color: #fff; }
    .review-card.success .review-cta:hover { background: #15803d; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(22,163,74,0.3); }

    /* ── PROFILE CARD ───────────────────────────────────── */
    .profile-card {
        background: #fff;
        border-radius: var(--dp-radius-xl);
        border: 1px solid var(--border-color);
        padding: 24px;
        color: var(--text-main);
        box-shadow: var(--dp-shadow);
        position: relative;
        overflow: hidden;
    }
    .profile-card::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(198,40,40,0.05) 0%, transparent 70%);
        pointer-events: none;
    }
    .profile-card::after {
        content: '';
        position: absolute;
        bottom: -40px;
        left: -40px;
        width: 160px;
        height: 160px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(15,23,42,0.02) 0%, transparent 70%);
        pointer-events: none;
    }

    .profile-card-top {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }

    .profile-avatar {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        background: linear-gradient(135deg, #c62828, #e53935);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 900;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 4px 16px rgba(198,40,40,0.2);
        letter-spacing: -0.02em;
    }

    .profile-name {
        margin: 0;
        font-size: 17px;
        font-weight: 800;
        color: var(--text-main);
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .profile-nrp {
        margin: 6px 0 0;
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .profile-nrp i { font-size: 14px; }

    .profile-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 20px;
    }
    .profile-stat {
        background: var(--slate-50);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 12px 14px;
    }
    .profile-stat-label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 6px;
    }
    .profile-stat-label i { font-size: 12px; }
    .profile-stat-value {
        display: block;
        font-size: 14px;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1.3;
    }

    .progress-section { margin-bottom: 16px; }
    .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .progress-label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; }
    .progress-pct { font-size: 13px; font-weight: 800; color: var(--text-main); }
    .progress-track {
        height: 6px;
        border-radius: 999px;
        background: var(--slate-200);
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #c62828, #f97316);
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 0 8px rgba(198,40,40,0.3);
    }

    .stepper {
        display: flex;
        align-items: center;
        gap: 0;
        background: var(--slate-50);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 12px 16px;
    }
    .stepper-item {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        min-width: 0;
    }
    .stepper-dot {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        flex-shrink: 0;
        background: #fff;
        color: var(--text-muted);
        border: 2px solid var(--border-color);
        transition: all 0.3s;
    }
    .stepper-dot i { font-weight: normal; font-size: 15px; }
    .stepper-item.active .stepper-dot {
        background: var(--brand);
        border-color: var(--brand);
        color: #fff;
        box-shadow: 0 0 12px rgba(198,40,40,0.3);
    }
    .stepper-item.done .stepper-dot {
        background: #16a34a;
        border-color: #16a34a;
        color: #fff;
    }
    .stepper-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        line-height: 1.3;
    }
    .stepper-item.active .stepper-label { color: var(--text-main); font-weight: 700; }
    .stepper-item.done  .stepper-label { color: var(--text-main); }
    .stepper-connector {
        width: 24px;
        height: 2px;
        background: var(--border-color);
        flex-shrink: 0;
        margin: 0 8px;
    }

    /* ── PANEL ──────────────────────────────────────────── */
    .d-panel {
        background: #fff;
        border-radius: var(--dp-radius-xl);
        border: 1px solid var(--border-color);
        box-shadow: var(--dp-shadow);
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }
    .d-panel:hover { box-shadow: var(--dp-shadow-hover); }

    .d-panel-header {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 18px 20px 16px;
        border-bottom: 1px solid var(--border-color);
        background: linear-gradient(to bottom, #fafbfc, #fff);
    }
    .d-panel-header-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        color: var(--brand);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(198,40,40,0.12);
    }
    .d-panel-header-icon--muted {
        background: var(--slate-100);
        color: var(--text-muted);
        box-shadow: none;
    }
    .d-panel-title {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        color: var(--text-main);
        letter-spacing: -0.01em;
        line-height: 1.3;
    }
    .d-panel-subtitle {
        margin: 4px 0 0;
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.5;
    }
    .d-panel-body { padding: 20px; }

    /* ── ALLOCATION GRID ────────────────────────────────── */
    .allocation-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    @media (min-width: 540px) {
        .allocation-grid { grid-template-columns: repeat(3, 1fr); }
    }

    .allocation-card {
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: var(--dp-radius-md);
        padding: 14px;
        transition: all 0.2s ease;
    }
    .allocation-card:hover {
        border-color: #fecaca;
        background: #fff9f9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(198,40,40,0.08);
    }
    .allocation-card-top { margin-bottom: 10px; }
    .allocation-name {
        display: block;
        font-size: 13px;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1.3;
    }
    .allocation-category {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 3px;
    }
    .allocation-badge-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .allocation-size-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .allocation-badge-group {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .allocation-size-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 32px;
        padding: 0 10px;
        border-radius: 8px;
        background: linear-gradient(135deg, #c62828, #e53935);
        color: #fff;
        font-size: 14px;
        font-weight: 900;
        box-shadow: 0 2px 8px rgba(198,40,40,0.3);
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* ── SUMMARY GRID ───────────────────────────────────── */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .summary-item {
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: var(--dp-radius-md);
        padding: 12px 14px;
    }
    .summary-label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
    }
    .summary-label i { font-size: 12px; }
    .summary-value {
        display: block;
        font-size: 14px;
        font-weight: 800;
        color: var(--text-main);
    }

    /* ── SIZES BADGE GRID ───────────────────────────────── */
    .sizes-badge-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    @media (min-width: 540px) {
        .sizes-badge-grid { grid-template-columns: repeat(4, 1fr); }
    }

    .sizes-badge-item {
        text-align: center;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: var(--dp-radius-md);
        padding: 12px 8px;
        transition: all 0.18s;
    }
    .sizes-badge-item:hover { border-color: #fecaca; }
    .sizes-badge-label {
        display: block;
        font-size: 10.5px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
    }
    .sizes-badge-value {
        display: block;
        font-size: 18px;
        font-weight: 900;
        color: var(--slate-300);
        line-height: 1;
    }
    .sizes-badge-value.filled { color: var(--brand); }

    /* ── FORM ELEMENTS ──────────────────────────────────── */
    .form-grid {
        display: grid;
        gap: 16px;
    }
    @media (min-width: 540px) {
        .form-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 768px) {
        .form-grid { grid-template-columns: repeat(3, 1fr); }
    }

    .sizes-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }
    @media (min-width: 480px) {
        .sizes-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (min-width: 768px) {
        .sizes-grid { grid-template-columns: repeat(4, 1fr); }
    }

    .form-field { display: flex; flex-direction: column; }

    .form-label {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 700;
        color: var(--slate-700);
    }
    .form-label i { font-size: 14px; color: var(--text-muted); }

    .form-control {
        width: 100%;
        height: 48px;
        padding: 0 14px;
        border: 1.5px solid var(--border-color);
        border-radius: var(--dp-radius-md);
        background: #fff;
        color: var(--text-main);
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
        appearance: none;
    }
    .form-control:focus {
        outline: none;
        border-color: #fca5a5;
        box-shadow: 0 0 0 4px rgba(248,113,113,0.12);
    }
    .form-control:disabled { background: var(--slate-50); color: var(--text-muted); cursor: not-allowed; }

    .form-hint { font-size: 12px; color: var(--text-muted); margin-top: 5px; line-height: 1.5; }
    .form-error { font-size: 12px; color: var(--danger); font-weight: 700; margin-top: 5px; }

    .form-note {
        margin-top: 16px;
        padding: 12px 14px;
        border-radius: var(--dp-radius-md);
        background: var(--slate-50);
        border: 1px solid var(--border-color);
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.6;
    }
    .form-note--danger {
        background: #fef2f2;
        border-color: #fecaca;
        color: #b91c1c;
    }

    /* ── BUTTONS ────────────────────────────────────────── */
    .btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        height: 50px;
        padding: 0 24px;
        border-radius: var(--dp-radius-md);
        border: none;
        background: linear-gradient(135deg, #c62828, #e53935);
        color: #fff;
        font-size: 15px;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 16px rgba(198,40,40,0.3);
        letter-spacing: -0.01em;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(198,40,40,0.4); }
    .btn-primary:active { transform: translateY(0); }
    .btn-primary:disabled {
        background: var(--slate-200);
        color: var(--text-muted);
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    .btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 40px;
        padding: 0 16px;
        border-radius: 999px;
        border: 1.5px solid var(--border-color);
        background: #fff;
        color: var(--text-main);
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.18s ease;
    }
    .btn-outline:hover {
        border-color: #fca5a5;
        color: var(--brand);
        background: var(--brand-soft);
    }

    /* ── TOM SELECT OVERRIDES ───────────────────────────── */
    .ts-wrapper.form-control {
        border: none !important;
        padding: 0 !important;
        height: auto !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    .ts-wrapper { margin-top: 0; cursor: pointer; }
    .ts-control {
        min-height: 48px !important;
        padding: 0 14px !important;
        border-radius: var(--dp-radius-md) !important;
        border: 1.5px solid var(--border-color) !important;
        background: #fff !important;
        color: var(--text-main) !important;
        box-shadow: none !important;
        font-size: 14px !important;
        font-family: inherit !important;
        display: flex !important;
        align-items: center !important;
        transition: all 0.2s !important;
    }
    .ts-control input { font-size: 14px !important; font-family: inherit !important; }
    .ts-control.focus {
        border-color: #fca5a5 !important;
        box-shadow: 0 0 0 4px rgba(248,113,113,0.12) !important;
    }
    .ts-dropdown {
        border-radius: var(--dp-radius-md) !important;
        border: 1.5px solid var(--border-color) !important;
        box-shadow: var(--dp-shadow-hover) !important;
        margin-top: 6px !important;
        font-size: 14px !important;
        font-family: inherit !important;
        padding: 6px !important;
    }
    .ts-dropdown.personil-floating-dropdown { z-index: 2000; }
    .ts-dropdown .option {
        padding: 10px 12px !important;
        border-radius: 8px !important;
        color: var(--text-main) !important;
        transition: all 0.15s ease !important;
    }
    .ts-dropdown .option:hover,
    .ts-dropdown .option.active {
        background-color: var(--brand-soft) !important;
        color: var(--brand) !important;
        font-weight: 700 !important;
    }
    .ts-wrapper.single .ts-control::after {
        border-color: var(--text-muted) transparent transparent transparent !important;
        right: 14px !important;
    }

    /* ── FOOTER ─────────────────────────────────────────── */
    .page-footer {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
        padding: 8px 0 16px;
    }
    .footer-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 38px;
        padding: 0 16px;
        border-radius: 999px;
        border: 1.5px solid var(--border-color);
        background: #fff;
        color: var(--text-muted);
        font-size: 13px;
        font-weight: 700;
        transition: all 0.18s ease;
        box-shadow: 0 1px 4px rgba(15,23,42,0.04);
    }
    .footer-pill:hover {
        border-color: #fca5a5;
        color: var(--brand);
        background: var(--brand-soft);
        transform: translateY(-1px);
    }

    /* ── ANIMATIONS ─────────────────────────────────────── */
    .hidden { display: none !important; }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .reveal {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease, transform 0.5s ease;
    }
    .reveal.visible {
        opacity: 1;
        transform: translateY(0);
    }
    .reveal-stagger {
        opacity: 0;
        transform: translateY(16px);
        transition: opacity 0.4s ease, transform 0.4s ease;
    }
    .reveal-stagger.visible { opacity: 1; transform: translateY(0); }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Tom Select ────────────────────────────────────── */
    const positionDropdown = (instance) => {
        const dropdown = instance.dropdown;
        const control  = instance.control;
        if (!dropdown || !control) return;
        const rect = control.getBoundingClientRect();
        const dropH = Math.min(dropdown.scrollHeight || 0, 320) || dropdown.offsetHeight || 0;
        const below = window.innerHeight - rect.bottom;
        const above = rect.top;
        const openUp = below < dropH && above > below;
        dropdown.style.width = `${rect.width}px`;
        dropdown.style.left  = `${window.scrollX + rect.left}px`;
        dropdown.style.top   = openUp
            ? `${window.scrollY + rect.top - dropH - 8}px`
            : `${window.scrollY + rect.bottom + 8}px`;
    };

    document.querySelectorAll('select.form-control').forEach((el) => {
        const tom = new TomSelect(el, {
            create: false,
            sortField: null,
            maxOptions: null,
            searchField: ['text'],
            dropdownParent: 'body',
            dropdownClass: 'ts-dropdown personil-floating-dropdown',
            controlInput: null,
            openOnFocus: true,
            onDropdownOpen() { positionDropdown(this); },
            onInitialize() {
                if (this.control_input) {
                    this.control_input.setAttribute('readonly', 'readonly');
                    this.control_input.setAttribute('inputmode', 'none');
                    this.control_input.setAttribute('tabindex', '-1');
                }
            },
        });
        window.addEventListener('resize', () => positionDropdown(tom));
        window.addEventListener('scroll', () => positionDropdown(tom), true);
    });

    /* ── Identity Form Toggle ──────────────────────────── */
    const openIdentityBtn  = document.querySelector('[data-open-identity]');
    const identityForm     = document.querySelector('[data-identity-form]');
    const identitySummary  = document.querySelector('[data-identity-summary]');
    if (openIdentityBtn && identityForm) {
        openIdentityBtn.addEventListener('click', () => {
            identityForm.classList.remove('hidden');
            if (identitySummary) identitySummary.classList.add('hidden');
        });
    }

    /* ── Sizes Form Toggle ─────────────────────────────── */
    const openSizesBtn  = document.querySelector('[data-open-sizes]');
    const sizesForm     = document.querySelector('[data-sizes-form]');
    const sizesSummary  = document.querySelector('[data-sizes-summary]');
    if (openSizesBtn && sizesForm) {
        openSizesBtn.addEventListener('click', () => {
            sizesForm.classList.remove('hidden');
            if (sizesSummary) sizesSummary.classList.add('hidden');
        });
    }

    /* ── Dismiss Banners ───────────────────────────────── */
    document.querySelectorAll('[data-dismissible]').forEach((el) => {
        el.querySelector('[data-dismiss-trigger]')?.addEventListener('click', () => {
            el.style.opacity = '0';
            el.style.transform = 'scale(0.97)';
            el.style.transition = 'all 0.25s ease';
            setTimeout(() => el.style.display = 'none', 250);
        });
    });

    /* ── Scroll Reveal ─────────────────────────────────── */
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.reveal').forEach((el, i) => {
        el.style.transitionDelay = `${i * 60}ms`;
        revealObserver.observe(el);
    });

    /* ── Stagger Reveal ────────────────────────────────── */
    const staggerObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const siblings = entry.target.closest('.allocation-grid')
                    ?.querySelectorAll('.reveal-stagger') ?? [];
                siblings.forEach((el, i) => {
                    setTimeout(() => el.classList.add('visible'), i * 80);
                });
                staggerObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.reveal-stagger').forEach(el => staggerObserver.observe(el));
});
</script>
@endsection
