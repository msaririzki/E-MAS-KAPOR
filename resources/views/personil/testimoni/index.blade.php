@extends('layouts.personil')

@section('title', 'Review Item Kapor')

@section('content')
    @php
        $defaultReviewTab = $pendingCards->isEmpty() && $reviewedCards->isNotEmpty()
            ? 'reviewed'
            : 'pending';
    @endphp

    <div class="page review-page">
        {{-- ── HEADER TITLE ──────────────────────────────── --}}
        <div class="page-full reveal" style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
            <a href="{{ route('dashboard') }}" class="btn-outline" style="height: 40px; border-radius: 12px; padding: 0 16px;">
                <i class="ri-arrow-left-line"></i> Kembali
            </a>
            <h1 style="margin: 0; font-size: 20px; font-weight: 800; color: var(--text-main);">Review Item Kapor</h1>
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

        <section class="d-panel reveal" style="animation-delay: 0.2s;">
            <div class="d-panel-body toolbar-stack">
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
                <section class="d-panel">
                    <div class="d-panel-body empty-state">
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
                <section class="d-panel">
                    <div class="d-panel-body empty-state">
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
                <section class="d-panel">
                    <div class="d-panel-body empty-state compact">
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
                        <section class="d-panel review-card-item"
                            data-searchable="{{ strtolower(($review->kaporItem?->item_name ?? 'item') . ' ' . ($review->kaporItem?->category ?? '') . ' ' . ($review->allocation?->budget_package_name_snapshot ?? '')) }}">
                            <div class="d-panel-body orphan-review-body">
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
        font-size: 17px;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1.2;
    }
    .d-panel-subtitle {
        margin: 6px 0 0;
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.5;
    }
    .d-panel-body {
        padding: 24px;
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
            display: grid;
            gap: 10px;
            color: var(--text-muted);
            justify-items: center;
            text-align: center;
            padding: 12px 0;
        }

        .empty-state i {
            font-size: 28px;
            color: var(--slate-400);
        }

        .empty-state.compact {
            padding: 32px 20px;
        }

        .orphan-review-body {
            display: grid;
            gap: 12px;
        }

        .review-head {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .review-item-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--slate-50), #fff);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--brand);
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(15,23,42,0.04);
        }

        .review-head-content {
            flex: 1;
            display: grid;
            gap: 4px;
        }

        .review-head-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
            flex-shrink: 0;
        }

        .review-item-name {
            display: block;
            font-size: 16px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .review-card-body,
        .review-form,
        .review-fieldset,
        .field-group {
            display: grid;
            gap: 12px;
            transition: all 0.3s ease;
        }

        .review-card-item .d-panel-body {
            padding: 20px;
        }

        .review-fieldset {
            border: 0;
            padding: 0;
            margin: 0;
            min-width: 0;
        }

        .review-form-grid {
            display: grid;
            gap: 12px;
        }

        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }

        .field-control {
            width: 100%;
            min-height: 44px;
            padding: 0 14px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--slate-50);
            color: var(--text-main);
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .field-textarea {
            padding: 12px 14px;
            min-height: 100px;
            resize: vertical;
            line-height: 1.5;
        }

        .field-control:focus {
            outline: none;
            border-color: #fca5a5;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(248, 113, 113, 0.1);
        }

        .review-fieldset[disabled] .field-control {
            background: var(--slate-100);
            color: var(--text-muted);
            cursor: not-allowed;
            opacity: 0.8;
            border-color: transparent;
        }

        .status-choice-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .status-choice {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 16px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            width: auto;
            flex: 0 0 auto;
            box-shadow: 0 1px 2px rgba(15,23,42,0.02);
        }

        .status-choice:hover {
            border-color: var(--slate-300);
            background: var(--slate-50);
            transform: translateY(-2px);
        }

        .status-choice input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .status-choice.active {
            border-color: var(--brand);
            background: var(--brand-gradient);
            color: #fff;
            box-shadow: 0 4px 12px rgba(198,40,40,0.25);
            transform: translateY(-2px);
        }

        .status-choice-title {
            font-size: 13px;
            font-weight: 700;
            color: inherit;
        }

        .review-fieldset[disabled] .status-choice {
            background: var(--slate-100);
            border-color: var(--border-color);
            color: var(--slate-400);
            cursor: not-allowed;
            box-shadow: none;
        }

        .review-fieldset[disabled] .status-choice.active {
            background: var(--slate-300);
            border-color: var(--slate-300);
            color: #fff;
            transform: none;
        }

        .star-picker {
            display: inline-flex;
            flex-direction: row-reverse;
            justify-content: flex-start;
            gap: 4px;
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
            color: var(--slate-200);
            font-size: 32px;
            transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            line-height: 1;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.05));
        }

        .star-picker label i {
            pointer-events: none;
        }

        .star-picker input:checked~label,
        .star-picker label:hover,
        .star-picker label:hover~label {
            color: #fbbf24;
            transform: scale(1.15);
            filter: drop-shadow(0 4px 8px rgba(251, 191, 36, 0.4));
        }

        .review-fieldset[disabled] .star-picker label {
            cursor: not-allowed;
            transform: none !important;
            filter: none !important;
        }

        .star-helper {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .review-actions {
            display: grid;
            gap: 12px;
            margin-top: 4px;
        }

        .submit-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 20px;
            border: 0;
            border-radius: 12px;
            background: var(--brand-gradient);
            color: #fff;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(198,40,40,0.2);
            transition: all 0.2s ease;
        }
        
        .submit-button:hover {
            box-shadow: 0 6px 16px rgba(198,40,40,0.3);
        }

        .submit-button:active {
            transform: scale(0.98);
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
        }

        @media (max-width: 640px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .toolbar-row {
                justify-content: flex-start;
            }

            .review-head {
                display: grid;
                grid-template-columns: auto 1fr;
                gap: 12px;
            }

            .review-head-status {
                grid-column: 1 / -1;
                align-items: flex-start;
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
            if (needsRating) {
                ratingWrap.style.opacity = '1';
                ratingWrap.style.pointerEvents = 'auto';
                ratingWrap.style.filter = 'none';
            } else {
                ratingWrap.style.opacity = '0.35';
                ratingWrap.style.pointerEvents = 'none';
                ratingWrap.style.filter = 'grayscale(100%)';
            }
                 card.querySelectorAll('.status-choice').forEach((choice) => {                 const input = choice.querySelector('[data-response-input]');                 choice.classList.toggle('active', Boolean(input?.checked));             });
                 card.querySelectorAll('[data-rating-input]').forEach((input) => {                 input.required = needsRating;             });         }
             function toggleEditMode(card) {             const fieldset = card.querySelector('.review-fieldset');             const editToggle = card.querySelector('[data-edit-toggle]');             const submitButton = card.querySelector('.submit-button');             const isEditing = card.dataset.editing === 'true';
                 if (!fieldset || !editToggle || !submitButton) {                 return;             }
                 if (isEditing) {                 card.dataset.editing = 'false';                 fieldset.disabled = true;                 card.querySelector('form')?.reset();                 editToggle.innerHTML = '<i class="ri-edit-line"></i> Edit';                 submitButton.classList.add('hidden');                 toggleRatingField(card);
                     return;             }
                 card.dataset.editing = 'true';             fieldset.disabled = false;             editToggle.innerHTML = '<i class="ri-close-line"></i> Batal';             submitButton.classList.remove('hidden');             toggleRatingField(card);         }     });
    </script>
@endsection
