@extends('layouts.app')

@section('title', 'Statistik Review Item')
@section('breadcrumb', 'Statistik')



@section('content')
    <div class="admin-stats-wrapper">
        <div class="page-header">
            <div class="page-header-row" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Statistik Review Item</h1>
                    <p>Panel evaluasi kualitas layanan E-MAS KAPOR berdasarkan ulasan dan apresiasi personel yang login menggunakan
                        NRP/NIP.</p>
                </div>

                <div class="page-header-actions" style="display: flex; align-items: center; gap: 12px;">
                    <label class="export-comment-control">
                        <span>Komentar/bintang</span>
                        <input type="number" id="statisticsCommentLimit" min="1" max="50"
                            value="{{ request('comments_per_rating', 2) }}">
                    </label>
                    <a href="{{ route('superadmin.testimonials.export-pdf', request()->except('comments_per_rating')) }}"
                        class="btn-export-pdf" onclick="exportReviewPdf(event, this, 'statisticsCommentLimit')" style="height: 40px; padding: 0 16px; border-radius: 14px; font-family: 'Outfit', sans-serif; font-size: 14px;">
                        <i class="ri-file-pdf-2-line" style="font-size: 18px;"></i>
                        Unduh PDF
                    </a>
                    <a href="{{ route('superadmin.testimonials.export-word', request()->except('comments_per_rating')) }}"
                        class="btn-export-word" onclick="exportReviewPdf(event, this, 'statisticsCommentLimit')" style="height: 40px; padding: 0 16px; border-radius: 14px; font-family: 'Outfit', sans-serif; font-size: 14px;">
                        <i class="ri-file-word-2-line" style="font-size: 18px;"></i>
                        Unduh Word
                    </a>

                    {{-- Filter Tahun Anggaran --}}
                    <form method="GET" action="{{ route('superadmin.statistics') }}"
                        class="year-filter-form review-year-form">
                        @if(filled($distributionFilters['group']) && $distributionFilters['group'] !== 'all')
                            <input type="hidden" name="distribution_group" value="{{ $distributionFilters['group'] }}">
                        @endif
                        @if($distributionFilters['rating'] !== null)
                            <input type="hidden" name="distribution_rating" value="{{ $distributionFilters['rating'] }}">
                        @endif
                        @foreach($distributionFilters['compare_items'] as $itemId)
                            <input type="hidden" name="compare_items[]" value="{{ $itemId }}">
                        @endforeach
                        <div class="review-select-shell has-leading-icon">
                            <i class="ri-calendar-line" aria-hidden="true"></i>
                            <select name="year" class="review-modern-select" aria-label="Pilih tahun anggaran"
                                onchange="this.form.requestSubmit()">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $fiscal_year == $year ? 'selected' : '' }}>
                                        T.A. {{ $year }} {{ (string) $year === (string) $active_year ? '(Aktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="hero-panel">
            <div class="hero-grid">
                <div>
                    <div class="hero-score">
                        <div class="score-badge">
                            <strong>{{ $serviceScore }}</strong>
                            <span>Service Score</span>
                        </div>
                        <div>
                            <div
                                style="font-size: 12px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; color: var(--brand);">
                                Executive Pulse</div>
                            <div
                                style="font-size: 34px; font-weight: 800; color: var(--text-main); line-height: 1.1; margin-top: 6px;">
                                {{ number_format($averageRating, 1) }} / 5
                            </div>
                            <div class="hero-stars"
                                aria-label="Rata-rata rating {{ number_format($averageRating, 1) }} dari 5">
                                @for($star = 1; $star <= 5; $star++)
                                    <i class="{{ $averageRating >= $star ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                                @endfor
                            </div>
                            <div style="font-size: 15px; color: var(--text-muted); max-width: 620px;">
                                {{ $totalTestimonials > 0 ? 'Ringkasan ini memudahkan admin dan pimpinan melihat kualitas pengalaman pengguna secara cepat tanpa harus membuka data mentah satu per satu.' : 'Belum ada data masuk, tetapi panel ini sudah siap menjadi tempat evaluasi saat testimoni pertama mulai terkumpul.' }}
                            </div>
                        </div>
                    </div>

                    <div class="hero-meta">
                        <div class="hero-pill">
                            <i class="ri-message-3-line" style="color: var(--brand);"></i>
                            {{ number_format($totalTestimonials) }} testimoni total
                        </div>
                        <div class="hero-pill">
                            <i class="ri-time-line" style="color: var(--info);"></i>
                            {{ number_format($recentTestimonialsCount) }} ulasan 30 hari terakhir
                        </div>
                        <div class="hero-pill">
                            <i class="ri-checkbox-circle-line" style="color: var(--success);"></i>
                            {{ number_format($receivedRate, 1) }}% sudah diterima
                        </div>
                        <div class="hero-pill">
                            <i class="ri-calendar-check-line" style="color: var(--success);"></i>
                            {{ $lastSubmittedAt ? 'Ulasan terakhir ' . $lastSubmittedAt->format('d M Y, H:i') : 'Belum ada ulasan' }}
                        </div>
                    </div>
                </div>

                <div class="insight-panel">
                    <div>
                        <div class="insight-label"
                            style="background: {{ $serviceInsight['background'] }}; color: {{ $serviceInsight['tone'] }};">
                            <i class="ri-focus-3-line"></i> {{ $serviceInsight['label'] }}
                        </div>
                        <div style="font-size: 22px; font-weight: 800; color: var(--text-main); margin-top: 14px;">
                            Sinyal layanan untuk pimpinan
                        </div>
                        <p style="font-size: 14px; color: var(--text-muted); line-height: 1.7; margin-top: 10px;">
                            {{ $serviceInsight['message'] }}
                        </p>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                        <div
                            style="padding: 14px; border-radius: 14px; background: var(--bg-body); border: 1px solid var(--border-color);">
                            <div style="font-size: 12px; color: var(--text-muted);">Rerata 30 hari</div>
                            <div style="font-size: 22px; font-weight: 800; color: var(--text-main); margin-top: 6px;">
                                {{ number_format($recentAverageRating, 1) }}
                            </div>
                        </div>
                        <div
                            style="padding: 14px; border-radius: 14px; background: var(--bg-body); border: 1px solid var(--border-color);">
                            <div style="font-size: 12px; color: var(--text-muted);">Perlu atensi</div>
                            <div style="font-size: 22px; font-weight: 800; color: var(--danger); margin-top: 6px;">
                                {{ number_format($attentionCount) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($totalTestimonials === 0)
            <div class="empty-showcase">
                <div class="empty-icon">
                    <i class="ri-feedback-line"></i>
                </div>
                <h2 style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-bottom: 10px;">Belum ada testimoni
                    masuk</h2>
                <p style="max-width: 720px; margin: 0 auto; color: var(--text-muted); line-height: 1.8;">
                    Begitu personel mulai mengirim ulasan dari dashboard mereka, halaman ini otomatis berubah menjadi panel
                    statistik untuk admin dan para atasan: rata-rata bintang, distribusi kepuasan, satker paling aktif, dan
                    pesan
                    terbaru.
                </p>
            </div>
        @else
                <div class="stats-row stats-row-5" style="margin-bottom: 24px;">
                    <div class="stat-card premium">
                        <div class="stat-top">
                            <span class="stat-label">Total Testimoni</span>
                            <div class="stat-icon-sm" style="background: var(--brand-bg); color: var(--brand);">
                                <i class="ri-chat-quote-line"></i>
                            </div>
                        </div>
                        <div class="stat-value">{{ number_format($totalTestimonials) }}</div>
                        <div class="metric-subtext">Aduan, saran, dan apresiasi yang sudah terekam.</div>
                    </div>

                    <div class="stat-card premium">
                        <div class="stat-top">
                            <span class="stat-label">Rata-rata Bintang</span>
                            <div class="stat-icon-sm" style="background: #fff7ed; color: #f59e0b;">
                                <i class="ri-star-line"></i>
                            </div>
                        </div>
                        <div class="stat-value">{{ number_format($averageRating, 1) }}</div>
                        <div class="metric-subtext">Skor mutu layanan dari seluruh testimoni.</div>
                    </div>

                    <div class="stat-card premium">
                        <div class="stat-top">
                            <span class="stat-label">Sudah Diterima</span>
                            <div class="stat-icon-sm" style="background: var(--success-bg); color: var(--success);">
                                <i class="ri-checkbox-circle-line"></i>
                            </div>
                        </div>
                        <div class="stat-value">{{ number_format($receivedRate, 1) }}%</div>
                        <div class="metric-subtext">Proporsi item sudah diterima berdasarkan testimoni yang sudah diisi.</div>
                    </div>

                    <div class="stat-card premium">
                        <div class="stat-top">
                            <span class="stat-label">Belum Diterima</span>
                            <div class="stat-icon-sm" style="background: var(--warning-bg); color: var(--warning);">
                                <i class="ri-truck-line"></i>
                            </div>
                        </div>
                        <div class="stat-value" style="color: var(--warning);">{{ number_format($notReceivedCount) }}</div>
                        <div class="metric-subtext">Laporan personel yang menyatakan item belum sampai.</div>
                    </div>

                </div>


                {{-- Per-Category Rating Breakdown --}}
                @if(isset($categoryStats) && count($categoryStats) > 0)
                    <div class="category-stats-row">
                        @foreach($categoryStats as $catKey => $catStat)
                            <div class="category-stat-card" style="border-top: 0;">
                                <div style="position:absolute; top:0; left:0; right:0; height:4px; background: {{ $catStat['color'] }};">
                                </div>
                                <div class="category-stat-head">
                                    <div class="category-stat-label">
                                        <div class="category-stat-icon"
                                            style="background: {{ $catStat['bg'] }}; color: {{ $catStat['color'] }};">
                                            <i class="{{ $catStat['icon'] }}"></i>
                                        </div>
                                        {{ $catStat['label'] }}
                                    </div>
                                </div>
                                @if($catStat['type'] === 'report')
                                    <div class="category-stat-body">
                                        <div class="category-stat-value" style="color: {{ $catStat['color'] }};">
                                            {{ number_format($catStat['count']) }}
                                        </div>
                                    </div>
                                    <div class="category-stat-count">laporan belum menerima item</div>
                                @else
                                    <div class="category-stat-body">
                                        <div class="category-stat-value">{{ number_format($catStat['average_rating'], 1) }}</div>
                                        <div style="margin-left: 4px;">
                                            <div class="category-stat-stars">
                                                @for($star = 1; $star <= 5; $star++)
                                                    <i class="{{ $catStat['average_rating'] >= $star ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                    <div class="category-stat-count">{{ number_format($catStat['count']) }} total ulasan</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 24px;">

                    @if(isset($categoryStats))
                        @foreach($categoryStats as $catKey => $catStat)
                            <div>
                                <div
                                    style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px dashed var(--border-color);">
                                    <i class="{{ $catStat['icon'] }}" style="color: {{ $catStat['color'] }}; margin-right: 4px;"></i>
                                    {{ $catStat['label'] }}
                                </div>
                                <div class="breakdown-list">
                                    @foreach($catStat['ratingBreakdown'] as $bucket)
                                        <div class="breakdown-item" style="margin-bottom: 12px;">
                                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                                <div
                                                    style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: var(--text-main);">
                                                    <span>{{ $bucket['stars'] }} <i class="ri-star-fill" style="color: #f59e0b;"></i></span>
                                                </div>
                                                <div style="font-size: 12px; color: var(--text-muted);">
                                                    {{ number_format($bucket['count']) }} ulasan
                                                    <strong
                                                        style="color: var(--text-main); margin-left: 6px;">{{ number_format($bucket['percentage'], 1) }}%</strong>
                                                </div>
                                            </div>
                                            <div class="bar-track" style="height: 8px; margin-top: 6px;">
                                                <div class="bar-fill" style="width: {{ $bucket['percentage'] }}%;"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>


                <div class="card satker-recap-card">
                    <div class="card-head satker-recap-head">
                        <div>
                            <h3><i class="ri-building-2-line"></i> Rekap Ulasan Seluruh Satker</h3>
                            <p>{{ number_format($satkerStats->count()) }} satker terdaftar pada T.A. {{ $fiscal_year }}. Buka detail untuk melihat personel dan ulasannya.</p>
                        </div>
                        <div class="satker-recap-actions">
                            <label class="satker-search">
                                <i class="ri-search-line"></i>
                                <input type="search" id="satkerRecapSearch" placeholder="Cari satker..." autocomplete="off">
                            </label>
                            <a href="{{ route('superadmin.statistics.satkers.export-pdf', ['year' => $fiscal_year]) }}"
                                class="satker-pdf-button" target="_blank">
                                <i class="ri-file-pdf-2-line"></i>
                                PDF Rekap Satker
                            </a>
                        </div>
                    </div>
                    <div class="satker-recap-table-wrap">
                        <table class="satker-recap-table">
                            <thead>
                                <tr>
                                    <th>Satker</th>
                                    <th>Personel Merespons</th>
                                    <th>Total Ulasan</th>
                                    <th>Belum Menerima</th>
                                    <th>Rata-rata</th>
                                    <th><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody id="satkerRecapBody">
                                @foreach($satkerStats as $satker)
                                    <tr data-satker-row data-search="{{ Str::lower($satker['satker_name'].' '.$satker['satker_code']) }}">
                                        <td>
                                            <div class="satker-name-cell">
                                                <span class="satker-building-icon"><i class="ri-government-line"></i></span>
                                                <div>
                                                    <strong>{{ $satker['satker_name'] }}</strong>
                                                    <small>{{ $satker['satker_code'] }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><strong class="table-number">{{ number_format($satker['respondent_count']) }}</strong></td>
                                        <td><strong class="table-number">{{ number_format($satker['total_feedback']) }}</strong></td>
                                        <td>
                                            <span class="not-received-value {{ $satker['not_received_count'] > 0 ? 'has-report' : '' }}">
                                                {{ number_format($satker['not_received_count']) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($satker['average_rating'] !== null)
                                                <span class="satker-rating"><i class="ri-star-fill"></i> {{ number_format($satker['average_rating'], 1) }}</span>
                                            @else
                                                <span class="no-rating">Belum ada nilai</span>
                                            @endif
                                        </td>
                                        <td class="satker-action-cell">
                                            <a href="{{ route('superadmin.statistics.satkers.show', ['satker' => $satker['satker_id'], 'year' => $fiscal_year]) }}"
                                                class="satker-detail-button">
                                                Lihat Detail <i class="ri-arrow-right-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="satker-search-empty" id="satkerSearchEmpty" hidden>
                            <i class="ri-building-line"></i>
                            <strong>Satker tidak ditemukan</strong>
                            <span>Periksa kembali kata pencarian.</span>
                        </div>
                    </div>
                    <div class="satker-recap-foot">
                        <span id="satkerVisibleCount">Menampilkan {{ number_format($satkerStats->count()) }} satker</span>
                        <span><i class="ri-information-line"></i> Satker tanpa ulasan tetap ditampilkan.</span>
                    </div>
                </div>
            </div>



            <div class="card">
                <div class="card-head">
                    <h3><i class="ri-equalizer-line" style="margin-right: 8px; color: var(--brand);"></i>Perbandingan Distribusi
                        Bintang antar Item</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('superadmin.statistics') }}" id="comparisonFilterForm">
                        <input type="hidden" name="year" value="{{ $fiscal_year }}">
                        <input type="hidden" name="distribution_group" value="{{ $distributionFilters['group'] }}">
                        @if($distributionFilters['rating'] !== null)
                            <input type="hidden" name="distribution_rating" value="{{ $distributionFilters['rating'] }}">
                        @endif
                        <div class="review-comparison-selects">
                            @for($i = 0; $i < 3; $i++)
                                <div class="review-select-shell">
                                    <select name="compare_items[]" class="comparison-select" style="width: 100%;">
                                        <option value="">Pilih Item Kapor...</option>
                                        @php
                                            $groupedItems = $availableItems->groupBy('group');
                                        @endphp
                                        @foreach($groupedItems as $groupName => $items)
                                            <optgroup label="Tutup {{ $groupName }}">
                                                @foreach($items as $item)
                                                    <option value="{{ $item->id }}" {{ ($comparisonStats[$i]['id'] ?? null) == $item->id ? 'selected' : '' }} data-category="{{ $item->group }}">
                                                        {{ $item->item_name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            @endfor
                        </div>
                    </form>

                    <div class="review-comparison-results">
                        @foreach($comparisonStats as $stat)
                            <div style="padding: 24px; border-radius: 20px; background: #f8fafc; border: 1px solid #f1f5f9;">
                                <div style="margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #fff;">
                                    <div style="font-size: 16px; font-weight: 800; color: var(--text-main); line-height: 1.3;">
                                        {{ $stat['name'] }}
                                    </div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
                                        <div style="font-size: 24px; font-weight: 800; color: var(--text-main);">
                                            {{ $stat['average_rating'] }} <i class="ri-star-fill"
                                                style="color: #f59e0b; font-size: 20px;"></i>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted);">
                                                {{ number_format($stat['total_reviewed']) }} review
                                            </div>
                                            <div style="font-size: 12px; font-weight: 700; color: var(--danger); margin-top: 2px;">
                                                {{ number_format($stat['not_received_count']) }} belum diterima
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="breakdown-list">
                                    @foreach($stat['rating_breakdown'] as $bucket)
                                        <div class="breakdown-item" style="margin-bottom: 12px;">
                                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                                <div style="font-size: 13px; font-weight: 700; color: var(--text-main);">
                                                    {{ $bucket['stars'] }} <i class="ri-star-fill" style="color: #f59e0b;"></i>
                                                </div>
                                                <div style="font-size: 11px; color: var(--text-muted);">
                                                    {{ number_format($bucket['count']) }} masukan ({{ $bucket['percentage'] }}%)
                                                </div>
                                            </div>
                                            <div class="bar-track" style="height: 6px; margin-top: 4px;">
                                                <div class="bar-fill" style="width: {{ $bucket['percentage'] }}%;"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @for($i = count($comparisonStats); $i < 3; $i++)
                            <div
                                style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; border-radius: 20px; background: #fff; border: 2px dashed #e2e8f0; color: var(--text-muted); font-style: italic;">
                                <i class="ri-add-circle-line" style="font-size: 32px; margin-bottom: 12px;"></i>
                                <div style="font-size: 14px; text-align: center;">Pilih item di atas untuk mulai membandingkan data.
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        @endif
@endsection

@section('styles')
@include('components.review-select-styles')
<style>
        .review-comparison-selects,
        .review-comparison-results {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .review-comparison-selects {
            gap: 20px;
            margin-bottom: 32px;
        }

        .review-comparison-results {
            gap: 28px;
        }

        .comparison-select-dropdown .select2-results__option--selectable {
            height: 48px;
            margin: 2px 0;
            padding-top: 0;
            padding-bottom: 0;
            overflow: hidden;
        }

        .comparison-option {
            width: 100%;
            min-width: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 7px;
        }

        .comparison-option__name {
            min-width: 0;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            line-height: 1.35;
        }

        .comparison-option .badge {
            margin: 0 !important;
            flex: 0 0 auto;
            padding: 3px 6px;
            font-size: 9px;
            line-height: 1.2;
        }

        @media (max-width: 900px) {
            .review-comparison-selects,
            .review-comparison-results {
                grid-template-columns: 1fr;
            }
        }

        /* Google Fonts & Modern Variables */
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap');

        :root {
            --glass-bg: rgba(255, 255, 255, 0.65);
            --glass-border: rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);

            --brand-gradient: linear-gradient(135deg, #b91c1c, #7f1d1d);
            --gold-gradient: linear-gradient(135deg, #f59e0b, #b45309);
            --success-gradient: linear-gradient(135deg, #10b981, #047857);
            --info-gradient: linear-gradient(135deg, #3b82f6, #1d4ed8);
            --danger-gradient: linear-gradient(135deg, #ef4444, #b91c1c);

            --text-heading: #0f172a;
            --text-muted: #64748b;

            --radius-base: 16px;
            --radius-lg: 24px;
            --radius-xl: 32px;
            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .page-header h1 {
            font-family: 'Outfit', sans-serif !important;
            font-size: 28px !important;
            background: linear-gradient(135deg, #0f172a, #334155);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .page-header p {
            font-family: 'Outfit', sans-serif;
            color: var(--text-muted);
            font-size: 14px;
        }

        .btn-export-pdf {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 38px;
            padding: 0 14px;
            border-radius: 10px;
            background: #B91C1C;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            transition: all 0.2s;
        }

        .btn-export-pdf:hover {
            background: #991B1B;
            color: #fff;
        }

        .btn-export-word {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 38px;
            padding: 0 14px;
            border-radius: 10px;
            background: #0F766E;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            transition: all 0.2s;
        }

        .btn-export-word:hover {
            background: #115E59;
            color: #fff;
        }

        .export-comment-control {
            height: 40px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0 12px;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            background: #fff;
            color: var(--text-muted);
            font-family: 'Outfit', sans-serif;
            font-size: 12px;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .export-comment-control input {
            width: 58px;
            height: 28px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 0 8px;
            font-size: 13px;
            font-weight: 800;
            color: var(--text-main);
            outline: none;
        }

        .admin-stats-wrapper {
            width: 100%;
            max-width: 100%;
            position: relative;
        }

        @media (min-width: 1025px) {
            .admin-stats-wrapper {
                padding-left: 20px;
            }
        }



        /* Hero Panel with Glassmorphism */
        .hero-panel {
            position: relative;
            overflow: hidden;
            padding: 40px;
            border-radius: var(--radius-xl);
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            backdrop-filter: blur(20px);
            margin-bottom: 32px;
            font-family: 'Outfit', sans-serif;
        }

        .hero-panel::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 350px;
            height: 350px;
            background: rgba(220, 38, 38, 0.08);
            filter: blur(60px);
            border-radius: 50%;
            z-index: 0;
            animation: floatAura 8s infinite alternate ease-in-out;
        }

        @keyframes floatAura {
            0% {
                transform: translate(0, 0) scale(1);
            }

            100% {
                transform: translate(-40px, 40px) scale(1.1);
            }
        }

        .hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 32px;
            align-items: center;
        }

        .hero-score {
            display: flex;
            gap: 28px;
            align-items: center;
            flex-wrap: wrap;
        }

        .score-badge {
            width: 140px;
            height: 140px;
            border-radius: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--brand-gradient);
            color: #fff;
            box-shadow: 0 20px 40px -10px rgba(185, 28, 28, 0.4), inset 0 2px 0 rgba(255, 255, 255, 0.25);
            position: relative;
            overflow: hidden;
        }

        .score-badge::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: skewX(-20deg);
            animation: shinePass 6s infinite;
        }

        @keyframes shinePass {

            0%,
            50% {
                left: -100%;
            }

            100% {
                left: 200%;
            }
        }

        .score-badge strong {
            font-size: 56px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -2px;
        }

        .score-badge span {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-top: 4px;
            opacity: 0.9;
        }

        .hero-stars {
            display: flex;
            gap: 8px;
            margin: 16px 0 20px;
            color: #f59e0b;
            font-size: 28px;
            filter: drop-shadow(0 4px 6px rgba(245, 158, 11, 0.3));
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 99px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(255, 255, 255, 1);
            color: var(--text-heading);
            font-size: 13.5px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .hero-pill:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            background: #fff;
        }

        .insight-panel {
            padding: 32px;
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            backdrop-filter: blur(16px);
        }

        .insight-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            width: fit-content;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        /* Modern Grid Stats */
        .stats-row-5 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card.premium {
            position: relative;
            background: #fff;
            border: 1px solid #f1f5f9;
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.03);
            transition: var(--transition);
            overflow: hidden;
            z-index: 1;
            font-family: 'Outfit', sans-serif;
        }

        .stat-card.premium::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0), rgba(241, 245, 249, 0.6));
            z-index: -1;
        }

        .stat-card.premium:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
            border-color: #e2e8f0;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .stat-label {
            font-size: 14.5px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .stat-icon-sm {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.6), 0 6px 15px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 34px;
            font-weight: 800;
            color: var(--text-heading);
            line-height: 1;
            letter-spacing: -0.5px;
        }

        .metric-subtext {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 10px;
            line-height: 1.5;
        }

        /* Layout Grids */
        .feedback-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 28px;
            margin-bottom: 32px;
        }

        .card {
            background: #fff;
            border-radius: var(--radius-lg);
            border: 1px solid #f1f5f9;
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.03);
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 25px 50px -10px rgba(0, 0, 0, 0.06);
        }

        .card-head {
            padding: 24px 28px;
            border-bottom: 1px solid #f8fafc;
            background: transparent;
        }

        .card-head h3 {
            font-size: 19px;
            font-weight: 700;
            color: var(--text-heading);
            display: flex;
            align-items: center;
        }

        .card-body {
            padding: 28px;
        }

        .breakdown-item {
            margin-bottom: 18px;
        }

        .bar-track {
            width: 100%;
            height: 12px;
            border-radius: 999px;
            background: #f1f5f9;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.03);
            margin-top: 8px;
        }

        .bar-fill {
            height: 100%;
            border-radius: inherit;
            background: var(--gold-gradient);
            position: relative;
        }

        .bar-fill::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        }

        .sentiment-row {
            display: grid;
            gap: 16px;
            margin-top: 32px;
        }

        .sentiment-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 24px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04);
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .sentiment-chip:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        }

        .satker-recap-card { margin-bottom: 32px; border-radius: 8px; }
        .satker-recap-head { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 18px 20px; }
        .satker-recap-head h3 { gap: 8px; font-size: 18px; }
        .satker-recap-head h3 i { color: var(--brand); }
        .satker-recap-head p { margin: 5px 0 0; color: var(--text-muted); font-size: 12px; }
        .satker-recap-actions { display: flex; align-items: center; gap: 9px; flex: 0 0 auto; }
        .satker-search { width: 220px; height: 40px; display: flex; align-items: center; gap: 8px; padding: 0 11px; border: 1px solid #dce3ec; border-radius: 7px; color: #94a3b8; background: #fff; }
        .satker-search:focus-within { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220, 38, 38, .08); }
        .satker-search input { width: 100%; border: 0; outline: 0; color: #1e293b; font-size: 12px; }
        .satker-pdf-button { height: 40px; display: inline-flex; align-items: center; gap: 7px; padding: 0 12px; border-radius: 7px; background: #b91c1c; color: #fff; text-decoration: none; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .satker-pdf-button:hover { background: #991b1b; color: #fff; }
        .satker-recap-table-wrap { max-height: 590px; overflow: auto; }
        .satker-recap-table { width: 100%; min-width: 900px; border-collapse: collapse; }
        .satker-recap-table thead { position: sticky; top: 0; z-index: 2; }
        .satker-recap-table th { padding: 10px 14px; border-top: 1px solid #edf1f5; border-bottom: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; text-align: left; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .satker-recap-table th:not(:first-child) { text-align: center; }
        .satker-recap-table td { padding: 11px 14px; border-bottom: 1px solid #f0f3f7; color: #334155; text-align: center; font-size: 12px; }
        .satker-recap-table tbody tr:hover { background: #fbfcfe; }
        .satker-name-cell { display: flex; align-items: center; gap: 10px; min-width: 230px; text-align: left; }
        .satker-building-icon { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; border-radius: 7px; background: #fef2f2; color: #b91c1c; font-size: 17px; }
        .satker-name-cell div { display: grid; gap: 1px; }
        .satker-name-cell strong { color: #182235; font-size: 12px; }
        .satker-name-cell small { color: #94a3b8; font-size: 10px; }
        .table-number { color: #1e293b; font-size: 13px; }
        .not-received-value { min-width: 28px; display: inline-flex; justify-content: center; padding: 4px 7px; border-radius: 6px; background: #f1f5f9; color: #64748b; font-weight: 800; }
        .not-received-value.has-report { background: #fff7ed; color: #c2410c; }
        .satker-rating { color: #b45309; font-weight: 800; white-space: nowrap; }
        .no-rating { color: #94a3b8; font-size: 10px; white-space: nowrap; }
        .satker-action-cell { text-align: right !important; }
        .satker-detail-button { display: inline-flex; align-items: center; gap: 5px; color: #b91c1c; text-decoration: none; font-size: 11px; font-weight: 800; white-space: nowrap; }
        .satker-detail-button:hover { color: #7f1d1d; }
        .satker-search-empty { padding: 38px 20px; display: grid; justify-items: center; gap: 5px; color: #94a3b8; }
        .satker-search-empty[hidden] { display: none; }
        .satker-search-empty i { font-size: 28px; }
        .satker-search-empty strong { color: #475569; }
        .satker-search-empty span { font-size: 11px; }
        .satker-recap-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 16px; border-top: 1px solid #edf1f5; background: #fbfcfe; color: #7c899b; font-size: 10px; }

        /* Spotlights */
        .spotlight-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 28px;
            margin-bottom: 32px;
        }

        .spotlight-card {
            padding: 32px;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 24px;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            font-family: 'Outfit', sans-serif;
        }

        .spotlight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.09);
        }

        .spotlight-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
        }

        .spotlight-card[style*="success"]::before {
            background: var(--success-gradient);
        }

        .spotlight-card[style*="danger"]::before {
            background: var(--danger-gradient);
        }

        .spotlight-quote {
            font-size: 17px;
            line-height: 1.8;
            color: var(--text-heading);
            font-style: italic;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 26px;
            flex: 1;
            backdrop-filter: blur(10px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.03);
        }

        /* Testimonial Cards */
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 28px;
        }

        .testimonial-card {
            padding: 30px;
            border-radius: var(--radius-lg);
            background: #fff;
            border: 1px solid #f1f5f9;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            gap: 20px;
            transition: var(--transition);
            font-family: 'Outfit', sans-serif;
        }

        .testimonial-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 20px 45px -8px rgba(0, 0, 0, 0.08);
            border-color: #e2e8f0;
        }

        .testimonial-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .avatar-badge {
            width: 56px;
            height: 56px;
            border-radius: 20px;
            background: var(--brand-gradient);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
            box-shadow: 0 8px 16px -4px rgba(185, 28, 28, 0.4);
        }

        .testimonial-message {
            color: var(--text-heading);
            font-size: 15px;
            line-height: 1.8;
            background: #f8fafc;
            padding: 22px;
            border-radius: 18px;
            border: 1px solid #f1f5f9;
        }

        .empty-showcase {
            border: 2px dashed rgba(185, 28, 28, 0.2);
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            border-radius: var(--radius-xl);
            padding: 60px 30px;
            text-align: center;
            font-family: 'Outfit', sans-serif;
        }

        .empty-showcase .empty-icon {
            width: 100px;
            height: 100px;
            border-radius: 32px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--brand-gradient);
            color: #fff;
            font-size: 42px;
            box-shadow: 0 20px 40px rgba(185, 28, 28, 0.3);
        }

        /* Category Stats Row */
        .category-stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        .category-stat-card {
            position: relative;
            background: #fff;
            border: 1px solid #f1f5f9;
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.03);
            transition: var(--transition);
            overflow: hidden;
            font-family: 'Outfit', sans-serif;
        }

        .category-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
        }

        .category-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .category-stat-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .category-stat-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-heading);
        }

        .category-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .category-stat-body {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .category-stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-heading);
            line-height: 1;
        }

        .category-stat-stars {
            display: flex;
            gap: 3px;
            color: #f59e0b;
            font-size: 16px;
        }

        .category-stat-count {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 10px;
        }

        .distribution-filter-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .distribution-filter-form select {
            min-width: 180px;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: var(--text-heading);
            font-size: 13px;
            font-weight: 600;
        }

        .distribution-filter-form button,
        .distribution-filter-form a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: var(--text-heading);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .testimonial-category-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 6px;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {

            .hero-grid,
            .feedback-grid,
            .spotlight-grid,
            .category-stats-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .hero-panel {
                padding: 24px;
            }

            .score-badge {
                width: 110px;
                height: 110px;
                border-radius: 30px;
            }

            .score-badge strong {
                font-size: 40px;
            }

            .testimonial-grid {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .satker-recap-head,
            .satker-recap-foot {
                align-items: flex-start;
                flex-direction: column;
            }

            .satker-recap-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .satker-search {
                width: 100%;
            }

            .satker-pdf-button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endsection

@section('scripts')
@include('components.review-select-script')
<script>
        function exportReviewPdf(event, link, inputId) {
            event.preventDefault();

            const input = document.getElementById(inputId);
            const limit = Math.min(Math.max(parseInt(input?.value || '2', 10) || 2, 1), 50);
            const url = new URL(link.href, window.location.origin);
            url.searchParams.set('comments_per_rating', limit);

            window.open(url.toString(), '_blank');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const satkerSearch = document.getElementById('satkerRecapSearch');
            const satkerRows = Array.from(document.querySelectorAll('[data-satker-row]'));
            const satkerEmpty = document.getElementById('satkerSearchEmpty');
            const satkerVisibleCount = document.getElementById('satkerVisibleCount');

            if (satkerSearch && satkerRows.length) {
                satkerSearch.addEventListener('input', () => {
                    const query = satkerSearch.value.toLocaleLowerCase('id').trim();
                    let visible = 0;

                    satkerRows.forEach((row) => {
                        const matches = query === '' || (row.dataset.search || '').includes(query);
                        row.hidden = !matches;
                        if (matches) visible += 1;
                    });

                    if (satkerEmpty) satkerEmpty.hidden = visible !== 0;
                    if (satkerVisibleCount) satkerVisibleCount.textContent = `Menampilkan ${visible} satker`;
                });
            }

            const comparisonForm = document.getElementById('comparisonFilterForm');
            if (!comparisonForm) {
                return;
            }

            // Initialize Select2 for comparison dropdowns
            $('.comparison-select').select2({
                placeholder: 'Cari atau pilih item kapor...',
                allowClear: true,
                containerCssClass: 'modern-select2',
                dropdownCssClass: 'review-modern-select-dropdown comparison-select-dropdown',
                templateResult: formatItem,
                templateSelection: formatItemSelection
            }).on('change', function () {
                comparisonForm.submit();
            });

            function formatItem(item) {
                if (!item.id) return item.text;

                const category = $(item.element).data('category');
                let badgeClass = 'bg-secondary';
                if (category === 'Kepala') badgeClass = 'badge-info';
                if (category === 'Badan') badgeClass = 'badge-success';
                if (category === 'Kaki') badgeClass = 'badge-warning';
                if (category === 'Lainnya') badgeClass = 'badge-neutral';

                const $item = $('<span class="comparison-option"><span class="comparison-option__name"></span><span class="badge"></span></span>');
                $item.find('.comparison-option__name').text(item.text.trim());
                $item.find('.badge').addClass(badgeClass).text(category);
                return $item;
            };

            function formatItemSelection(item) {
                if (!item.id) return item.text;
                const category = $(item.element).data('category');
                return item.text + ' (' + category + ')';
            }
        });
    </script>
    <style>
        .select2-container--default .select2-selection--single {
            height: 48px;
            padding-top: 10px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: normal;
            padding-left: 15px;
        }
    </style>
@endsection
