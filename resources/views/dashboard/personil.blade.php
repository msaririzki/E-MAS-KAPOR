@extends('layouts.personil')

@section('title', 'Data Kaporlap Personil')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .alert {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }

        .profile-row,
        .meta-row,
        .step-row,
        .link-row {
            display: grid;
            gap: 10px;
        }

        .profile-row {
            grid-template-columns: 48px 1fr;
            align-items: center;
        }

        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fef2f2;
            color: var(--brand);
            font-size: 16px;
            font-weight: 800;
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
            letter-spacing: 0.04em;
        }

        .meta-item span,
        .summary-item span {
            display: block;
            margin-top: 6px;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
        }

        .progress {
            height: 8px;
            border-radius: 999px;
            background: var(--slate-100);
            overflow: hidden;
            margin-top: 14px;
        }

        .progress>span {
            display: block;
            height: 100%;
            background: var(--brand);
        }

        .progress-38>span {
            width: 38%;
        }

        .progress-64>span {
            width: 64%;
        }

        .progress-82>span {
            width: 82%;
        }

        .progress-100>span {
            width: 100%;
        }

        .step-item {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
        }

        .step-item.active {
            color: var(--brand);
            border-color: #fecaca;
            background: #fef2f2;
        }

        .step-item.done {
            color: var(--success);
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .note {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--slate-50);
            border: 1px solid var(--border-color);
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .field-grid {
            display: grid;
            gap: 12px;
            margin-top: 14px;
        }

        .summary-grid {
            display: grid;
            gap: 12px;
            margin-top: 14px;
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
            min-height: 46px;
            padding: 0 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: #fff;
            color: var(--text-main);
        }

        input.control:focus,
        select.control:focus,
        textarea.control:focus {
            outline: none;
            border-color: #f87171;
            box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.12);
        }

        /* Tom Select Overrides for Premium Look */
        .ts-wrapper {
            margin-top: 0;
            cursor: pointer;
        }
        .ts-control {
            min-height: 46px;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: #fff;
            color: var(--text-main);
            box-shadow: none;
            font-size: 14px;
            font-family: inherit;
            display: flex;
            align-items: center;
        }
        .ts-control input {
            font-size: 14px;
            font-family: inherit;
        }
        .ts-control.focus {
            border-color: #f87171;
            box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.12);
        }
        .ts-dropdown {
            border-radius: 10px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.1);
            margin-top: 6px;
            font-size: 14px;
            font-family: inherit;
            padding: 6px;
        }
        .ts-dropdown.personil-floating-dropdown {
            z-index: 2000;
        }
        .ts-dropdown .option {
            padding: 10px 12px;
            border-radius: 6px;
            color: var(--text-main);
            transition: all 0.15s ease;
        }
        .ts-dropdown .option:hover,
        .ts-dropdown .option.active {
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
            min-height: 46px;
            padding: 0 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .button {
            width: 100%;
            background: var(--brand);
            color: #fff;
        }

        .button-secondary {
            background: #fff;
            border-color: var(--border-color);
            color: var(--text-main);
        }

        .alert {
            padding: 14px 16px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert.success {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: var(--success);
        }

        .alert.error {
            border-color: #fecaca;
            background: #fef2f2;
            color: var(--danger);
        }

        .hidden {
            display: none;
        }

        .link-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (min-width: 768px) {
            .field-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
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
        $identityStepLabel = $usesBagianDropdown ? '1. Jabatan + Bag/Fungsi' : '1. Jabatan';
        $showIdentityForm = !$identityReady || old('mode') === 'identity' || $errors->has('jabatan') || $errors->has('bagian');
        $showSizesForm = !$hasSubmitted || old('mode') === 'sizes';
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
    @endphp

    @if (!$personnel)
        <div class="alert error">Data personel belum tersedia. Hubungi admin sebelum mengisi kaporlap.</div>
    @else
        <div class="page">
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
                    <h2>Data tugas</h2>
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
                        </div>

                        <div style="margin-top: 12px;">
                            <button type="button" class="button-secondary" data-open-identity style="width: auto;">
                                <i class="ri-edit-line"></i> Edit Data Tugas
                            </button>
                        </div>
                    </div>
                @endif

                <form action="{{ route('personil.kapor.store') }}" method="POST" class="{{ $showIdentityForm ? '' : 'hidden' }}"
                    data-identity-form style="margin-top: 14px;">
                    @csrf
                    <input type="hidden" name="mode" value="identity">

                    <div class="field-grid">
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
                    </div>

                    <div class="note">{{ $usesBagianDropdown ? 'Simpan jabatan dan bag/fungsi dulu, lalu lanjut ke ukuran.' : 'Simpan jabatan dulu, lalu lanjut ke ukuran.' }}</div>

                        <div style="margin-top: 14px;">
                            <button type="submit" class="button">Simpan Data Tugas</button>
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

                            <div style="margin-top: 12px;">
                                <button type="button" class="button-secondary" data-open-sizes style="width: auto;">
                                    <i class="ri-edit-line"></i> Edit Ukuran
                                </button>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('personil.kapor.store') }}" method="POST" class="{{ $showSizesForm ? '' : 'hidden' }}"
                        data-sizes-form style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="mode" value="sizes">
                        <input type="hidden" name="jabatan" value="{{ old('jabatan', $personnel->jabatan ?? '') }}">
                        @if ($usesBagianDropdown)
                            <input type="hidden" name="bagian" value="{{ old('bagian', $personnel->bagian ?? '') }}">
                        @endif

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

                            <div style="margin-top: 14px;">
                                <button type="submit" class="button">Simpan Ukuran</button>
                            </div>
                        </form>
                    </div>
                </section>
            @else
                <section class="panel" id="ukuran-form">
                    <div class="panel-header">
                        <h2>Ukuran kaporlap</h2>
                        <p>Lengkapi dulu data tugas agar data ukuran aktif.</p>
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
                    style="color: var(--text-muted); font-size: 13px; font-weight: 600;">Halaman Testimoni</a>
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
                    onDropdownOpen() {
                        positionTomSelectDropdown(this);
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
        });
    </script>
@endsection
