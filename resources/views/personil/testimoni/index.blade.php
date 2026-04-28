@extends('layouts.personil')

@section('title', 'Review Item Kapor')

@section('content')
    @php
        $defaultReviewTab = $pendingCards->isEmpty() && $reviewedCards->isNotEmpty()
            ? 'reviewed'
            : 'pending';
    @endphp

    <div class="page review-page">
        @if (session('success_testimoni'))
            <div class="toast-success" id="reviewToast" role="status" aria-live="polite">
                <div class="toast-success-body">
                    <i class="ri-checkbox-circle-fill"></i>
                    <span>{{ session('success_testimoni') }}</span>
                </div>
                <button type="button" class="toast-close" data-close-toast aria-label="Tutup notifikasi">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        @endif

        <div class="alert {{ $reviewPeriodStatus['tone'] }} status-banner" data-dismissible
            data-dismiss-key="personil-review-period-banner">
            <div class="dismiss-head">
                <strong>{{ $reviewPeriodStatus['title'] }}</strong>
                <button type="button" class="dismiss-btn" data-dismiss-trigger aria-label="Sembunyikan banner review">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <span>{{ $reviewPeriodStatus['message'] }}</span>
            <div class="status-meta">
                <i class="ri-calendar-line"></i>
                Periode review: {{ $reviewPeriodStatus['period_label'] }}
            </div>
        </div>

        <section class="panel hero-panel">
            <div class="panel-body hero-body">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
                    <div>
                        <div class="eyebrow">{{ $isHistoricalYear ? 'Arsip Review Personil' : 'Portal Review Personil' }}</div>
                        <h1>Review Item Kapor</h1>
                        <p>
                            @if ($isHistoricalYear)
                                Riwayat review item kapor untuk T.A. {{ $fiscalYear }} ditampilkan sebagai arsip baca-saja.
                            @else
                                Laporkan item yang belum diterima atau beri penilaian untuk item kapor yang sudah Anda terima
                                pada T.A. {{ $fiscalYear }}.
                            @endif
                        </p>
                    </div>

                    {{-- Filter Tahun --}}
                    <div class="year-filter-wrapper"
                        style="background: var(--slate-50); padding: 6px 14px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 8px;">
                        <i class="ri-calendar-line" style="color: var(--brand); font-size: 16px;"></i>
                        <select onchange="window.location.href='?year='+this.value"
                            style="border: none; background: transparent; font-size: 13px; font-weight: 700; color: var(--text-main); outline: none; cursor: pointer;">
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}" {{ $fiscalYear == $year ? 'selected' : '' }}>
                                    TA {{ $year }} {{ $year == $activeYear ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </section>

        @if (session('error_testimoni') || session('error'))
            <div class="alert error">
                <i class="ri-error-warning-fill"></i>
                <span>{{ session('error_testimoni') ?? session('error') }}</span>
            </div>
        @endif

        @if ($isHistoricalYear)
            <div class="alert info">
                <i class="ri-history-line"></i>
                <span>T.A. {{ $fiscalYear }} sudah tidak aktif. Anda masih bisa melihat hasil review lama, tetapi tidak bisa mengirim atau mengubah data lagi.</span>
            </div>
        @endif

        <section class="panel">
            <div class="panel-body toolbar-stack">
                <div class="compact-summary">
                    <strong>
                        @if ($isHistoricalYear)
                            {{ $allocationCards->count() }} item arsip untuk T.A. {{ $fiscalYear }}
                        @else
                            {{ $allocationCards->count() }} item eligible untuk Anda
                        @endif
                    </strong>
                    <span>
                        @if ($isHistoricalYear)
                            Daftar ini berasal dari snapshot paket yang sudah difinalkan pada tahun tersebut dan hanya bisa dibuka sebagai referensi.
                        @else
                            Daftar item di bawah ini berasal dari snapshot paket pengadaan yang sudah difinalkan untuk akun Anda.
                        @endif
                    </span>
                    <div class="eligible-chip-row">
                        @forelse ($allocationCards as $card)
                            <span class="eligible-chip">{{ $card['item_name'] }}</span>
                        @empty
                            <span class="eligible-chip muted">{{ $isHistoricalYear ? 'Tidak ada item arsip' : 'Belum ada item review' }}</span>
                        @endforelse
                    </div>
                </div>

                <div class="toolbar-row">
                    <label class="search-box compact-search" for="reviewSearch">
                        <i class="ri-search-line"></i>
                        <input type="search" id="reviewSearch" placeholder="Cari item...">
                    </label>
                </div>

                <div class="tab-row mobile-tabs">
                    <button type="button" class="tab-btn {{ $defaultReviewTab === 'pending' ? 'active' : '' }}" data-tab="pending">{{ $isHistoricalYear ? 'Belum Ada Respons' : 'Belum Direview' }}</button>
                    <button type="button" class="tab-btn {{ $defaultReviewTab === 'reviewed' ? 'active' : '' }}" data-tab="reviewed">{{ $isHistoricalYear ? 'Sudah Tersimpan' : 'Sudah Direview' }}</button>
                    <button type="button" class="tab-btn" data-tab="history">Riwayat Lain</button>
                </div>
            </div>
        </section>

        <section class="tab-panel {{ $defaultReviewTab === 'pending' ? 'active' : '' }}" data-panel="pending">
            @if ($pendingCards->isEmpty())
                <section class="panel">
                    <div class="panel-body empty-state">
                        <i class="ri-inbox-archive-line"></i>
                        <strong>{{ $isHistoricalYear ? 'Tidak ada item arsip tanpa respons' : 'Tidak ada item yang menunggu respons' }}</strong>
                        <span>
                            @if ($isHistoricalYear)
                                Semua item arsip pada tahun ini sudah memiliki hasil review, atau memang tidak ada data alokasi yang tersimpan.
                            @else
                                Jika ada item kapor baru yang difinalkan untuk Anda, item tersebut akan muncul di sini.
                            @endif
                        </span>
                    </div>
                </section>
            @endif

            <div class="review-list">
                @foreach ($pendingCards as $card)
                    @include('personil.testimoni.partials.review-card', ['card' => $card, 'reviewPeriodStatus' => $reviewPeriodStatus])
                @endforeach
            </div>
        </section>

        <section class="tab-panel {{ $defaultReviewTab === 'reviewed' ? 'active' : '' }}" data-panel="reviewed">
            @if ($reviewedCards->isEmpty())
                <section class="panel">
                    <div class="panel-body empty-state">
                        <i class="ri-chat-check-line"></i>
                        <strong>{{ $isHistoricalYear ? 'Belum ada hasil review tersimpan' : 'Belum ada review atau laporan penerimaan' }}</strong>
                        <span>
                            @if ($isHistoricalYear)
                                Jika tahun ini pernah memiliki review item, hasilnya akan tampil di sini sebagai arsip baca-saja.
                            @else
                                Item yang sudah Anda respons akan tampil di sini untuk memudahkan update selama periode review masih terbuka.
                            @endif
                        </span>
                    </div>
                </section>
            @endif

            <div class="review-list">
                @foreach ($reviewedCards as $card)
                    @include('personil.testimoni.partials.review-card', ['card' => $card, 'reviewPeriodStatus' => $reviewPeriodStatus])
                @endforeach
            </div>
        </section>

        <section class="tab-panel" data-panel="history">
            @if ($orphanReviews->isEmpty())
                <section class="panel">
                    <div class="panel-body empty-state compact">
                        <i class="ri-file-list-3-line"></i>
                        <strong>Tidak ada riwayat tambahan</strong>
                        <span>
                            @if ($isHistoricalYear)
                                Semua hasil review pada tahun ini masih terhubung dengan snapshot item yang tersedia.
                            @else
                                Semua review tahun ini masih terhubung dengan daftar item alokasi aktif Anda.
                            @endif
                        </span>
                    </div>
                </section>
            @else
                <div class="review-list">
                    @foreach ($orphanReviews as $review)
                        <section class="panel review-card-item"
                            data-searchable="{{ strtolower(($review->kaporItem?->item_name ?? 'item') . ' ' . ($review->kaporItem?->category ?? '') . ' ' . ($review->allocation?->budget_package_name_snapshot ?? '')) }}">
                            <div class="panel-body orphan-review-body">
                                <div class="review-head">
                                    <div>
                                        <strong class="review-item-name">{{ $review->item_name_snapshot }}</strong>
                                        <div class="review-meta">{{ $review->category_label }} •
                                            {{ $review->package_name_snapshot ?? 'Snapshot lama' }}
                                        </div>
                                    </div>
                                    <span
                                        class="status-badge {{ $review->response_status === \App\Models\ItemReview::STATUS_NOT_RECEIVED ? 'warning' : 'success' }}">{{ $review->response_label }}</span>
                                </div>
                                <p class="review-copy">{{ $review->display_message }}</p>
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        </section>

        <footer class="personil-footer-links">
            <a href="{{ route('dashboard') }}">Data Kapor</a>
            <span>•</span>
            <a href="{{ route('personil.kapor.history') }}">Riwayat Ukuran</a>
            <span>•</span>
            <a href="{{ route('personil.testimoni.index') }}">Review Item</a>
        </footer>
    </div>
@endsection

@section('styles')
    <style>
        .review-page {
            gap: 16px;
        }

        .hero-panel {
            background: #ffffff;
            border-color: var(--border-color);
        }

        .hero-body {
            display: grid;
            gap: 16px;
        }

        .eyebrow {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--brand);
        }

        .hero-body h1 {
            margin: 8px 0 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .hero-body p {
            margin: 10px 0 0;
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.7;
        }

        .tab-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid var(--border-color);
            background: #fff;
            color: var(--text-main);
            font-size: 13px;
            font-weight: 700;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            background: #fff;
            display: grid;
            gap: 10px;
        }

        .dismiss-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .dismiss-btn,
        .toast-close {
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.08);
            color: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex: 0 0 auto;
        }

        .toast-success {
            position: fixed;
            top: 18px;
            right: 16px;
            z-index: 60;
            width: min(420px, calc(100vw - 32px));
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.12);
        }

        .toast-success.hide {
            opacity: 0;
            transform: translateY(-8px);
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease;
        }

        .toast-success-body {
            display: flex;
            gap: 10px;
            line-height: 1.6;
            font-size: 13px;
            font-weight: 700;
            flex: 1;
        }

        .alert.success {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: var(--success);
        }

        .alert.info {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: var(--info);
        }

        .alert.warning {
            border-color: #fde68a;
            background: #fffbeb;
            color: var(--warning);
        }

        .alert.error {
            border-color: #fecaca;
            background: #fef2f2;
            color: var(--danger);
        }

        .status-banner strong,
        .summary-item strong {
            display: block;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .status-banner span,
        .summary-item span,
        .review-copy,
        .review-meta,
        .helper-copy {
            color: var(--text-muted);
            line-height: 1.6;
        }

        .status-meta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.78);
            font-size: 12px;
            font-weight: 800;
        }

        .summary-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .compact-summary {
            display: grid;
            gap: 6px;
        }

        .compact-summary strong {
            font-size: 14px;
            font-weight: 800;
            color: var(--text-main);
        }

        .compact-summary span {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .eligible-chip-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .eligible-chip {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            background: var(--slate-50);
            border: 1px solid var(--border-color);
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
        }

        .eligible-chip.muted {
            color: var(--text-muted);
            font-weight: 600;
        }

        .summary-item span {
            display: block;
            margin-top: 6px;
            font-size: 22px;
            font-weight: 800;
            color: var(--text-main);
        }

        .note {
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--slate-50);
            border: 1px solid var(--border-color);
            font-size: 13px;
            line-height: 1.6;
        }

        .toolbar-stack {
            display: grid;
            gap: 10px;
        }

        .toolbar-row {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 0 14px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: #fff;
        }

        .compact-search {
            width: 100%;
        }

        .search-box input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            font-size: 14px;
            color: var(--text-main);
        }

        .tab-row {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 2px;
            scrollbar-width: none;
        }

        .tab-row::-webkit-scrollbar {
            display: none;
        }

        .tab-btn.active {
            background: #111827;
            border-color: #111827;
            color: #fff;
        }

        .tab-panel {
            display: none;
            gap: 16px;
        }

        .tab-panel.active {
            display: grid;
        }

        .review-list {
            display: grid;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            display: grid;
            gap: 10px;
            color: var(--text-muted);
            justify-items: center;
            padding: 12px 0;
        }

        .empty-state i {
            font-size: 28px;
            color: var(--slate-400);
        }

        .orphan-review-body {
            display: grid;
            gap: 12px;
        }

        .review-head {
            display: grid;
            gap: 8px;
        }

        .review-item-name {
            display: block;
            font-size: 15px;
            font-weight: 800;
            color: var(--text-main);
        }

        .review-card-body,
        .review-form,
        .review-fieldset,
        .field-group {
            display: grid;
            gap: 10px;
        }

        .review-card-item .panel-body {
            padding: 14px 16px 16px;
        }

        .review-fieldset {
            border: 0;
            padding: 0;
            margin: 0;
            min-width: 0;
        }

        .review-form-grid {
            display: grid;
            gap: 10px;
        }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .field-control {
            width: 100%;
            min-height: 42px;
            padding: 0 10px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: #fff;
            color: var(--text-main);
            font-size: 13px;
        }

        .field-textarea {
            padding: 10px;
            min-height: 92px;
            resize: vertical;
        }

        .field-control:focus {
            outline: none;
            border-color: #f87171;
            box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.12);
        }

        .review-fieldset[disabled] .field-control {
            background: var(--slate-100);
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .status-choice-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .status-choice {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 12px;
            border: 1px solid var(--border-color);
            border-radius: 999px;
            background: #fff;
            cursor: pointer;
            transition: 0.18s ease;
            width: auto;
            flex: 0 0 auto;
        }

        .status-choice input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .status-choice.active {
            border-color: var(--brand);
            background: var(--brand);
            color: #fff;
        }

        .status-choice-title {
            font-size: 12px;
            font-weight: 800;
            color: inherit;
        }

        .review-fieldset[disabled] .status-choice {
            background: #7f1d1d;
            border-color: #7f1d1d;
            color: #fee2e2;
            cursor: not-allowed;
        }

        .review-fieldset[disabled] .status-choice.active {
            background: #991b1b;
            border-color: #991b1b;
            color: #fff;
        }

        .star-picker {
            display: inline-flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 2px;
        }

        .star-picker input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .star-picker label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--slate-300);
            font-size: 26px;
            transition: 0.16s ease;
            line-height: 1;
        }

        .star-picker label i {
            pointer-events: none;
        }

        .star-picker input:checked~label,
        .star-picker label:hover,
        .star-picker label:hover~label {
            color: #f59e0b;
        }

        .review-fieldset[disabled] .star-picker label {
            cursor: not-allowed;
        }

        .star-helper {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .review-actions {
            display: grid;
            gap: 8px;
        }

        .submit-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 14px;
            border: 0;
            border-radius: 12px;
            background: var(--brand);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .submit-button:disabled {
            background: var(--slate-300);
            cursor: not-allowed;
        }

        .submit-button.hidden {
            display: none;
        }

        .edit-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 14px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: #fff;
            color: var(--text-main);
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .status-badge.success {
            background: var(--success-soft);
            color: var(--success);
        }

        .status-badge.warning {
            background: var(--warning-soft);
            color: var(--warning);
        }

        .status-badge.info {
            background: var(--info-soft);
            color: var(--info);
        }

        .personil-footer-links {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid var(--border-color);
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
        }

        @media (min-width: 768px) {
            .hero-body {
                grid-template-columns: 1fr;
            }

            .review-form-grid,
            .review-actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                align-items: end;
            }

            .review-actions .helper-copy {
                align-self: center;
            }

            .tab-row {
                overflow: visible;
            }

            .review-head {
                grid-template-columns: 1fr auto;
                align-items: start;
            }
        }

        @media (max-width: 640px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .toolbar-row {
                justify-content: flex-start;
            }

            .review-head {
                grid-template-columns: 1fr;
            }

            .status-choice-grid {
                gap: 6px;
            }

            .status-choice {
                min-height: 38px;
                padding: 0 11px;
            }

            .toast-success {
                top: 14px;
                right: 12px;
                width: min(420px, calc(100vw - 24px));
            }
        }
    </style>
@endsection

@section('scripts')
    <script>     document.addEventListener('DOMContentLoaded', () => {         const tabButtons = Array.from(document.querySelectorAll('.tab-btn'));         const panels = Array.from(document.querySelectorAll('.tab-panel'));         const searchInput = document.getElementById('reviewSearch');
             tabButtons.forEach((button) => {             button.addEventListener('click', () => {                 tabButtons.forEach((tab) => tab.classList.remove('active'));                 panels.forEach((panel) => panel.classList.remove('active'));
                     button.classList.add('active');                 document.querySelector(`[data-panel="${button.dataset.tab}"]`)?.classList.add('active');                 applySearch();             });         });
             document.querySelectorAll('.review-card-item').forEach((card) => {             card.querySelectorAll('[data-response-input]').forEach((input) => {                 input.addEventListener('change', () => toggleRatingField(card));             });
                 const editToggle = card.querySelector('[data-edit-toggle]');             if (editToggle) {                 editToggle.addEventListener('click', () => toggleEditMode(card));             }
                 toggleRatingField(card);         });
             document.querySelectorAll('[data-dismissible]').forEach((element) => {             element.querySelector('[data-dismiss-trigger]')?.addEventListener('click', () => {                 element.style.display = 'none';             });         });
             const toast = document.getElementById('reviewToast');         if (toast) {             const hideToast = () => {                 toast.classList.add('hide');                 window.setTimeout(() => toast.remove(), 180);             };
                 toast.querySelector('[data-close-toast]')?.addEventListener('click', hideToast);             window.setTimeout(hideToast, 4200);         }
             searchInput?.addEventListener('input', applySearch);
             function applySearch() {             const activePanel = document.querySelector('.tab-panel.active');             const term = (searchInput?.value || '').trim().toLowerCase();
                 activePanel?.querySelectorAll('.review-card-item').forEach((card) => {                 const haystack = String(card.dataset.searchable || '').toLowerCase();                 card.style.display = haystack.includes(term) ? '' : 'none';             });         }
             function toggleRatingField(card) {             const ratingWrap = card?.querySelector('[data-rating-wrap]');             const checkedResponse = card?.querySelector('[data-response-input]:checked');             const needsRating = checkedResponse?.value === '{{ \App\Models\ItemReview::STATUS_REVIEWED }}';
                 if (!ratingWrap) {                 return;             }
                 ratingWrap.style.display = needsRating ? '' : 'none';
                 card.querySelectorAll('.status-choice').forEach((choice) => {                 const input = choice.querySelector('[data-response-input]');                 choice.classList.toggle('active', Boolean(input?.checked));             });
                 card.querySelectorAll('[data-rating-input]').forEach((input) => {                 input.required = needsRating;             });         }
             function toggleEditMode(card) {             const fieldset = card.querySelector('.review-fieldset');             const editToggle = card.querySelector('[data-edit-toggle]');             const submitButton = card.querySelector('.submit-button');             const isEditing = card.dataset.editing === 'true';
                 if (!fieldset || !editToggle || !submitButton) {                 return;             }
                 if (isEditing) {                 card.dataset.editing = 'false';                 fieldset.disabled = true;                 card.querySelector('form')?.reset();                 editToggle.innerHTML = '<i class="ri-edit-line"></i> Edit';                 submitButton.classList.add('hidden');                 toggleRatingField(card);
                     return;             }
                 card.dataset.editing = 'true';             fieldset.disabled = false;             editToggle.innerHTML = '<i class="ri-close-line"></i> Batal';             submitButton.classList.remove('hidden');             toggleRatingField(card);         }     });
    </script>
@endsection
