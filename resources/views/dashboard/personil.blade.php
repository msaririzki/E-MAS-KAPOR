@extends('layouts.personil')

@section('title', 'Data Kaporlap Personil')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <style>
        /* Modern Utilities */
        :root {
            --ui-radius-lg: 20px;
            --ui-radius-md: 14px;
            --ui-radius-sm: 10px;
            --ui-shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.08);
            --ui-shadow-hover: 0 20px 40px -10px rgba(0,0,0,0.12);
        }

        .alert {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--ui-radius-md);
            box-shadow: var(--ui-shadow-soft);
        }

        .profile-row,
        .meta-row,
        .step-row,
        .link-row {
            display: grid;
            gap: 12px;
        }

        .profile-row {
            grid-template-columns: 56px 1fr;
            align-items: center;
        }

        .avatar {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: var(--brand);
            font-size: 18px;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.15);
        }

        .meta-row,
        .step-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .meta-item,
        .step-item,
        .summary-item {
            padding: 0;
            background: transparent;
            border: none;
        }

        .meta-item strong,
        .summary-item strong {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        .meta-item span,
        .summary-item span {
            display: block;
            margin-top: 6px;
            font-size: 15px;
            font-weight: 800;
            color: var(--text-main);
        }

        .progress {
            height: 8px;
            border-radius: 999px;
            background: var(--slate-100);
            overflow: hidden;
            margin-top: 18px;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
        }

        .progress>span {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, var(--brand), #fb923c);
            border-radius: 999px;
            transition: width 0.5s ease-out;
        }

        .progress-38>span { width: 38%; }
        .progress-64>span { width: 64%; }
        .progress-82>span { width: 82%; }
        .progress-100>span { width: 100%; }

        .step-item {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
        }

        .step-item.active {
            color: var(--brand);
        }

        .step-item.done {
            color: var(--success);
        }

        .note {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: var(--ui-radius-md);
            background: var(--slate-50);
            border: 1px solid var(--border-color);
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .field-grid, .identity-grid, .summary-grid {
            display: grid;
            gap: 16px;
            margin-top: 16px;
        }

        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field {
            padding: 0;
            background: transparent;
            border: none;
        }

        .label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--slate-700);
        }

        .hint {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .error {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            color: var(--danger);
            font-weight: 700;
        }

        input.control,
        select.control,
        textarea.control {
            width: 100%;
            min-height: 48px;
            padding: 0 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--ui-radius-sm);
            background: #fff;
            color: var(--text-main);
            transition: all 0.2s;
            font-size: 14px;
        }

        input.control:focus,
        select.control:focus,
        textarea.control:focus {
            outline: none;
            border-color: #fca5a5;
            box-shadow: 0 0 0 4px rgba(248, 113, 113, 0.1);
            background: #fff;
        }

        /* Tom Select Overrides */
        .ts-wrapper { margin-top: 0; cursor: pointer; }
        .ts-control {
            min-height: 48px;
            padding: 12px 14px;
            border-radius: var(--ui-radius-sm);
            border: 1px solid var(--border-color);
            background: #fff;
            color: var(--text-main);
            box-shadow: none;
            font-size: 14px;
            font-family: inherit;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        .ts-control input { font-size: 14px; font-family: inherit; }
        .ts-control.focus {
            border-color: #fca5a5;
            box-shadow: 0 0 0 4px rgba(248, 113, 113, 0.1);
        }
        .ts-dropdown {
            border-radius: var(--ui-radius-sm);
            border: 1px solid var(--border-color);
            box-shadow: var(--ui-shadow-soft);
            margin-top: 8px;
            font-size: 14px;
            font-family: inherit;
            padding: 8px;
        }
        .ts-dropdown.personil-floating-dropdown { z-index: 2000; }
        .ts-dropdown .option {
            padding: 10px 14px;
            border-radius: 8px;
            color: var(--text-main);
            transition: all 0.15s ease;
        }
        .ts-dropdown .option:hover, .ts-dropdown .option.active {
            background-color: var(--brand-soft) !important;
            color: var(--brand) !important;
            font-weight: 600;
        }
        .ts-wrapper.single .ts-control::after {
            border-color: var(--text-muted) transparent transparent transparent;
            border-width: 5px 5px 0 5px;
            right: 16px;
        }

        .button,
        .button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 48px;
            padding: 0 20px;
            border-radius: var(--ui-radius-sm);
            font-size: 14px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .button {
            width: 100%;
            background: linear-gradient(135deg, var(--brand) 0%, #e11d48 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
        }
        .button:hover {
            box-shadow: 0 6px 16px rgba(225, 29, 72, 0.35);
            transform: translateY(-1px);
        }

        .button-secondary {
            background: #fff;
            border-color: var(--border-color);
            color: var(--text-main);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .button-secondary:hover {
            background: var(--slate-50);
            border-color: var(--slate-300);
        }

        .alert {
            padding: 16px;
            font-size: 13.5px;
            font-weight: 600;
        }
        .alert.success { border-color: #bbf7d0; background: #f0fdf4; color: var(--success); }
        .alert.info { border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8; }
        .alert.warning { border-color: #fde68a; background: #fffbeb; color: #b45309; }
        .alert.error { border-color: #fecaca; background: #fef2f2; color: var(--danger); }

        .status-banner {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .dismiss-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .dismiss-btn {
            width: 32px;
            height: 32px;
            border: 0;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.06);
            color: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex: 0 0 auto;
            transition: all 0.2s;
        }
        .dismiss-btn:hover { background: rgba(15, 23, 42, 0.12); }

        .status-banner strong,
        .review-card strong {
            font-size: 15px;
            font-weight: 800;
            line-height: 1.3;
        }

        .status-banner span,
        .review-card p {
            font-size: 14px;
            line-height: 1.6;
        }

        .status-meta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            color: var(--text-main);
        }

        /* Modernized Review Card */
        .review-card {
            border: 1px solid rgba(255,255,255,0.4);
            border-radius: var(--ui-radius-lg);
            background: #fff;
            box-shadow: var(--ui-shadow-soft);
            overflow: hidden;
            position: relative;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--ui-shadow-hover);
        }

        .review-card.info {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-color: #bae6fd;
        }
        .review-card.warning {
            background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
            border-color: #fde68a;
        }
        .review-card.success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-color: #bbf7d0;
        }

        .review-card-body {
            display: grid;
            gap: 16px;
            padding: 20px;
        }

        .review-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }
        .review-card-head > div {
            flex: 1;
            min-width: 0;
        }
        .review-card-copy {
            color: var(--slate-700);
            margin-top: 6px;
            font-size: 14px;
            line-height: 1.5;
        }

        .review-card-title-row {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        @media (min-width: 640px) {
            .review-card-title-row {
                flex-direction: row;
                align-items: center;
                gap: 16px;
            }
        }

        .review-card.info .review-card-head strong { color: #0369a1; }
        .review-card.warning .review-card-head strong { color: #b45309; }
        .review-card.success .review-card-head strong { color: #15803d; }

        .review-eligible {
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: rgba(255,255,255,0.6);
            padding: 16px;
            border-radius: var(--ui-radius-md);
            border: none;
        }
        .review-eligible strong {
            display: block;
            font-size: 11px;
            color: var(--slate-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .review-inline-count {
            font-size: 14px;
            font-weight: 800;
            color: var(--text-main);
            background: rgba(255,255,255,0.8);
            padding: 2px 8px;
            border-radius: 999px;
            margin-left: 6px;
        }

        .item-chip-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .item-chip {
            display: inline-flex;
            align-items: center;
            min-height: 32px;
            padding: 0 14px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.05);
            font-size: 12.5px;
            font-weight: 700;
            color: var(--slate-700);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }
        .item-chip:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
            transform: translateY(-1px);
        }

        .review-action-row {
            display: flex;
            justify-content: center;
            margin-top: 4px;
            width: 100%;
        }

        .review-cta {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            min-height: 48px;
            width: 100%;
            padding: 0 24px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        @media (min-width: 640px) {
            .review-action-row { justify-content: flex-end; }
            .review-cta { width: auto; display: inline-flex; }
        }

        .review-card.info .review-cta {
            background: #0284c7;
            color: #fff;
            border: none;
        }
        .review-card.info .review-cta:hover { background: #0369a1; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.25); }

        .review-card.warning .review-cta {
            background: #d97706;
            color: #fff;
            border: none;
        }
        .review-card.warning .review-cta:hover { background: #b45309; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(217, 119, 6, 0.25); }

        .review-card.success .review-cta {
            background: #16a34a;
            color: #fff;
            border: none;
        }
        .review-card.success .review-cta:hover { background: #15803d; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(22, 163, 74, 0.25); }

        .hidden { display: none; }

        .link-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .panel {
            background: #fff;
            border-radius: var(--ui-radius-lg);
            box-shadow: var(--ui-shadow-soft);
            border: 1px solid var(--slate-100);
            padding: 24px;
            margin-bottom: 24px;
        }
        .panel-header h2 {
            font-size: 18px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 6px;
        }
        .panel-header p {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        .panel-body {
            /* clear structural paddings since parent has padding */
        }

        @media (min-width: 768px) {
            .field-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .identity-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .review-card-body { padding: 24px; }
        }

    </style>
@endsection

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
        $progressClass = !$identityReady ? 'progress-38' : ($isComplete ? 'progress-100' : ($hasSubmitted ? 'progress-82' : 'progress-64'));
        $summaryItems = [
            'Kemeja' => $kaporSizes['kemeja'] ?? '-',
            'Celana/Rok' => $kaporSizes['celana'] ?? '-',
            'Olahraga' => $kaporSizes['olahraga'] ?? '-',
            'Jaket' => $kaporSizes['jaket'] ?? '-',
            'Topi/Baret' => $kaporSizes['topi'] ?? '-',
            'Sabuk' => $kaporSizes['sabuk'] ?? '-',
            'Sepatu Dinas' => $kaporSizes['sepatu_dinas'] ?? '-',
            'Sepatu Olahraga' => $kaporSizes['sepatu_olahraga'] ?? '-',
        ];

        if ($requiresJilbab) {
            $summaryItems['Jilbab'] = $kaporSizes['jilbab'] ?? '-';
        }

        $showReviewHero = $reviewPeriodStatus['is_open'] ?? false;
    @endphp

    @if (!$personnel)
        <div class="alert error">Data personel belum tersedia. Hubungi admin sebelum mengisi kaporlap.</div>
    @else
        <div class="page">
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
                                    <span class="note" style="margin-top: 0;">Belum ada item review untuk akun Anda.</span>
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
                <div class="alert {{ $inputPeriodStatus['tone'] }} status-banner" data-dismissible data-dismiss-key="personil-dashboard-input-status">
                    <div class="dismiss-row">
                        <strong>{{ $inputPeriodStatus['title'] }}</strong>
                        <button type="button" class="dismiss-btn" data-dismiss-trigger aria-label="Sembunyikan status input">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                    <span>{{ $inputPeriodStatus['message'] }}</span>
                    <div class="status-meta">
                        <i class="ri-calendar-line"></i>
                        Periode aktif: {{ $inputPeriodStatus['period_label'] }}
                    </div>
                </div>
            @endif

            <section class="panel">
                <div class="panel-body">
                    <div class="profile-row">
                        <div class="avatar">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                        <div>
                            <h3>{{ $user->name }} • {{ $user->nrp_nip }}</h3>
                        </div>
                    </div>

                    <div class="meta-row" style="margin-top: 14px;">
                        <div class="meta-item"><strong>Satker</strong><span>{{ $personnel->satker->name ?? '-' }}</span></div>
                        <div class="meta-item"><strong>Tahun Anggaran</strong><span>{{ $fiscalYear }}</span></div>
                    </div>

                    <div class="progress {{ $progressClass }}"><span></span></div>

                    <div class="step-row" style="margin-top: 12px;">
                        <div class="step-item {{ $identityReady ? 'done' : 'active' }}">{{ $identityStepLabel }}</div>
                        <div class="step-item {{ !$identityReady ? '' : ($isComplete ? 'done' : 'active') }}">2. Ukuran Kaporlap
                        </div>
                    </div>
                </div>
            </section>

            @if (session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert error">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert error">Masih ada field yang perlu diperbaiki.</div>
            @endif

            <section class="panel">
                <div class="panel-header">
                    <h2>Data Personel</h2>
                    <p>Jabatan berasal dari import SDM Polda NTB. Perubahan akan dicatat di log audit.</p>
                </div>
                <div class="panel-body">
                    @if ($identityReady && !$showIdentityForm)
                    <div data-identity-summary>
                        <div class="summary-grid">
                            <div class="summary-item"><strong>Jabatan</strong><span>{{ $personnel->jabatan }}</span></div>
                            @if ($usesBagianDropdown)
                                <div class="summary-item"><strong>Bag/Fungsi</strong><span>{{ $personnel->bagian }}</span></div>
                            @endif
                            <div class="summary-item"><strong>No. HP (WhatsApp)</strong><span>{{ $currentPhone ?: '-' }}</span></div>
                        </div>

                        @if ($isInputOpen)
                            <div style="margin-top: 12px;">
                                <button type="button" class="button-secondary" data-open-identity style="width: auto;">
                                    <i class="ri-edit-line"></i> Edit Data Personel
                                </button>
                            </div>
                        @endif
                    </div>
                @endif

                <form action="{{ route('personil.kapor.store') }}" method="POST" class="{{ $showIdentityForm ? '' : 'hidden' }}"
                    data-identity-form style="margin-top: 14px;">
                    @csrf
                    <input type="hidden" name="mode" value="identity">

                    <fieldset @disabled(! $isInputOpen) style="border: 0; padding: 0; margin: 0; min-width: 0;">
                        <div class="identity-grid">
                            <div class="field">
                                <label class="label" for="jabatan">Jabatan</label>
                                <input id="jabatan" type="text" name="jabatan" class="control"
                                    value="{{ old('jabatan', $personnel->jabatan ?? '') }}" style="text-transform: uppercase;"
                                    oninput="this.value = this.value.toUpperCase()">
                                <span class="hint">Referensi SDM Polda NTB.</span>
                                @error('jabatan')<span class="error">{{ $message }}</span>@enderror
                            </div>

                            @if ($usesBagianDropdown)
                                <div class="field">
                                    <label class="label" for="bagian">Bag/Fungsi</label>
                                    <select id="bagian" name="bagian" class="control">
                                        <option value="">Pilih Bagian / Fungsi</option>
                                        @if ($selectedBagian && ! $bagianOptionsList->contains($selectedBagian))
                                            <option value="{{ $selectedBagian }}" selected>{{ $selectedBagian }}</option>
                                        @endif
                                        @foreach ($bagianOptionsList as $option)
                                            <option value="{{ $option }}" {{ $selectedBagian === $option ? 'selected' : '' }}>{{ $option }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="hint">Khusus satker kategori polres, bag/fungsi dipilih dari master superadmin.</span>
                                    @error('bagian')<span class="error">{{ $message }}</span>@enderror
                                </div>
                            @endif

                            <div class="field">
                                <label class="label" for="phone">No. HP (WhatsApp)</label>
                                <input id="phone" type="text" name="phone" class="control" inputmode="numeric" autocomplete="tel"
                                    placeholder="Contoh: 08123456789" value="{{ $currentPhone }}"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <span class="hint">Nomor ini dipakai admin untuk chat cepat via WhatsApp.</span>
                                @error('phone')<span class="error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </fieldset>

                    <div class="note">{{ $usesBagianDropdown ? 'Simpan jabatan, bag/fungsi, dan no. HP dulu, lalu lanjut ke Isi ukuran.' : 'Simpan jabatan dan no. HP dulu, lalu lanjut ke Isi ukuran.' }}</div>

                    @unless ($isInputOpen)
                        <div class="note" style="background: #fef2f2; border-color: #fecaca; color: #b91c1c;">
                            Periode pengisian sedang ditutup. Anda tidak dapat mengubah data personel untuk sementara waktu.
                        </div>
                    @endunless

                        <div style="margin-top: 14px;">
                            <button type="submit" class="button" @disabled(! $isInputOpen)>{{ $isInputOpen ? 'Simpan' : 'Input Ditutup' }}</button>
                        </div>
                    </form>
                </div>
            </section>

            @if ($identityReady)
                <section class="panel" id="ukuran-form">
                    <div class="panel-header">
                        <h2>Ukuran kaporlap</h2>
                        <p>Isi seperlunya. Jika sudah pernah menyimpan, data lama tetap tampil sebagai nilai awal.</p>
                    </div>
                    <div class="panel-body">
                        @if ($hasSubmitted && !$showSizesForm)
                        <div data-sizes-summary>
                            <div class="summary-grid">
                                @foreach ($summaryItems as $label => $value)
                                    <div class="summary-item"><strong>{{ $label }}</strong><span>{{ $value ?: '-' }}</span></div>
                                @endforeach
                            </div>

                            @if ($isInputOpen)
                                <div style="margin-top: 12px;">
                                    <button type="button" class="button-secondary" data-open-sizes style="width: auto;">
                                        <i class="ri-edit-line"></i> Edit Ukuran
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif

                    <form action="{{ route('personil.kapor.store') }}" method="POST" class="{{ $showSizesForm ? '' : 'hidden' }}"
                        data-sizes-form style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="mode" value="sizes">
                        <input type="hidden" name="jabatan" value="{{ old('jabatan', $personnel->jabatan ?? '') }}">
                        <input type="hidden" name="phone" value="{{ $currentPhone }}">
                        @if ($usesBagianDropdown)
                            <input type="hidden" name="bagian" value="{{ old('bagian', $personnel->bagian ?? '') }}">
                        @endif

                        <fieldset @disabled(! $isInputOpen) style="border: 0; padding: 0; margin: 0; min-width: 0;">

                        <div class="field-grid">
                            <div class="field">
                                <label class="label" for="kemeja">Kemeja</label>
                                <select id="kemeja" name="kemeja" class="control" required>
                                    <option value="">Pilih</option>
                                    @foreach ($gender === 'L' ? $sShirtMale : $sWomen as $size)
                                        <option value="{{ $size }}" {{ old('kemeja', $kaporSizes['kemeja'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                                @error('kemeja')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label class="label" for="celana">Celana / Rok</label>
                                <select id="celana" name="celana" class="control" required>
                                    <option value="">Pilih</option>
                                    @foreach ($gender === 'L' ? $sPantsMale : $sWomen as $size)
                                        <option value="{{ $size }}" {{ old('celana', $kaporSizes['celana'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                                @error('celana')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label class="label" for="olahraga">Olahraga</label>
                                <select id="olahraga" name="olahraga" class="control" required>
                                    <option value="">Pilih</option>
                                    @foreach ($sWomen as $size)
                                        <option value="{{ $size }}" {{ old('olahraga', $kaporSizes['olahraga'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                                @error('olahraga')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label class="label" for="jaket">Jaket</label>
                                <select id="jaket" name="jaket" class="control" required>
                                    <option value="">Pilih</option>
                                    @foreach ($sWomen as $size)
                                        <option value="{{ $size }}" {{ old('jaket', $kaporSizes['jaket'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                                @error('jaket')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label class="label" for="topi">Topi / Baret</label>
                                <select id="topi" name="topi" class="control" required>
                                    <option value="">Pilih</option>
                                    @foreach ($sHead as $size)
                                        <option value="{{ $size }}" {{ old('topi', $kaporSizes['topi'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                                @error('topi')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label class="label" for="sabuk">Sabuk</label>
                                <select id="sabuk" name="sabuk" class="control" required>
                                    <option value="">Pilih</option>
                                    @foreach ($sBelt as $size)
                                        <option value="{{ $size }}" {{ old('sabuk', $kaporSizes['sabuk'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                                @error('sabuk')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            @if ($requiresJilbab)
                                <div class="field">
                                    <label class="label" for="jilbab">Jilbab</label>
                                    <select id="jilbab" name="jilbab" class="control" required>
                                        <option value="">Pilih</option>
                                        @foreach ($sJilbab as $size)
                                            <option value="{{ $size }}" {{ old('jilbab', $kaporSizes['jilbab'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                        @endforeach
                                    </select>
                                    @error('jilbab')<span class="error">{{ $message }}</span>@enderror
                                </div>
                            @endif
                            <div class="field">
                                <label class="label" for="sepatu_dinas">Sepatu Dinas</label>
                                <select id="sepatu_dinas" name="sepatu_dinas" class="control" required>
                                    <option value="">Pilih</option>
                                    @foreach ($sShoes as $size)
                                        <option value="{{ $size }}" {{ old('sepatu_dinas', $kaporSizes['sepatu_dinas'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                                @error('sepatu_dinas')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label class="label" for="sepatu_olahraga">Sepatu Olahraga</label>
                                <select id="sepatu_olahraga" name="sepatu_olahraga" class="control" required>
                                    <option value="">Pilih</option>
                                    @foreach ($sShoes as $size)
                                        <option value="{{ $size }}" {{ old('sepatu_olahraga', $kaporSizes['sepatu_olahraga'] ?? '') == $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                                @error('sepatu_olahraga')<span class="error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        </fieldset>

                        @unless ($isInputOpen)
                            <div class="note" style="background: #fef2f2; border-color: #fecaca; color: #b91c1c;">
                                Periode pengisian sedang ditutup. Anda hanya bisa melihat ukuran yang ada tanpa bisa mengubahnya.
                            </div>
                        @endunless

                            <div style="margin-top: 14px;">
                                <button type="submit" class="button" @disabled(! $isInputOpen)>{{ $isInputOpen ? 'Simpan Ukuran' : 'Input Ditutup' }}</button>
                            </div>
                        </form>
                    </div>
                </section>
            @else
                <section class="panel" id="ukuran-form">
                    <div class="panel-header">
                        <h2>Ukuran kaporlap</h2>
                        <p>Lengkapi dulu data personel agar data ukuran aktif.</p>
                    </div>
                </section>
            @endif

        </div>

        <footer style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color); text-align: center;">
            <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                <a href="{{ route('personil.kapor.history') }}"
                    style="color: var(--text-muted); font-size: 13px; font-weight: 600;">Riwayat Ukuran</a>
                <span style="color: var(--slate-300);">•</span>
                <a href="{{ route('personil.testimoni.index') }}"
                    style="color: var(--text-muted); font-size: 13px; font-weight: 600;">Review Item</a>
            </div>
        </footer>
    @endif
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const positionTomSelectDropdown = (instance) => {
                const dropdown = instance.dropdown;
                const control = instance.control;

                if (!dropdown || !control) {
                    return;
                }

                const rect = control.getBoundingClientRect();
                const dropdownHeight = Math.min(dropdown.scrollHeight || 0, 320) || dropdown.offsetHeight || 0;
                const spaceBelow = window.innerHeight - rect.bottom;
                const spaceAbove = rect.top;
                const openUp = spaceBelow < dropdownHeight && spaceAbove > spaceBelow;

                dropdown.style.width = `${rect.width}px`;
                dropdown.style.left = `${window.scrollX + rect.left}px`;
                dropdown.style.top = openUp
                    ? `${window.scrollY + rect.top - dropdownHeight - 8}px`
                    : `${window.scrollY + rect.bottom + 8}px`;
            };

            // Apply Tom Select to all select elements with class 'control'
            document.querySelectorAll('select.control').forEach((el) => {
                const tom = new TomSelect(el, {
                    create: false,
                    sortField: null,
                    maxOptions: null,
                    searchField: ['text'],
                    dropdownParent: 'body',
                    dropdownClass: 'ts-dropdown personil-floating-dropdown',
                    controlInput: null,
                    openOnFocus: true,
                    onDropdownOpen() {
                        positionTomSelectDropdown(this);
                    },
                    onInitialize() {
                        if (this.control_input) {
                            this.control_input.setAttribute('readonly', 'readonly');
                            this.control_input.setAttribute('inputmode', 'none');
                            this.control_input.setAttribute('tabindex', '-1');
                        }
                    },
                });

                window.addEventListener('resize', () => positionTomSelectDropdown(tom));
                window.addEventListener('scroll', () => positionTomSelectDropdown(tom), true);
            });

            const openIdentityButton = document.querySelector('[data-open-identity]');
            const identityForm = document.querySelector('[data-identity-form]');
            const identitySummary = document.querySelector('[data-identity-summary]');

            if (openIdentityButton && identityForm) {
                openIdentityButton.addEventListener('click', function () {
                    identityForm.classList.remove('hidden');
                    if (identitySummary) identitySummary.classList.add('hidden');
                    else openIdentityButton.classList.add('hidden');
                });
            }

            const openSizesButton = document.querySelector('[data-open-sizes]');
            const sizesForm = document.querySelector('[data-sizes-form]');
            const sizesSummary = document.querySelector('[data-sizes-summary]');

            if (openSizesButton && sizesForm) {
                openSizesButton.addEventListener('click', function () {
                    sizesForm.classList.remove('hidden');
                    if (sizesSummary) sizesSummary.classList.add('hidden');
                    else openSizesButton.parentElement.classList.add('hidden');
                });
            }

            document.querySelectorAll('[data-dismissible]').forEach((element) => {
                element.querySelector('[data-dismiss-trigger]')?.addEventListener('click', () => {
                    element.style.display = 'none';
                });
            });
        });
    </script>
@endsection
