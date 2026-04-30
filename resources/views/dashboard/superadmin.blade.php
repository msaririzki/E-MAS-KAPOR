@extends('layouts.app')

@section('title', 'Dashboard Superadmin')
@section('breadcrumb', 'Dashboard')

@section('content')
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-header-row">
            <div>
                <h1>Dashboard</h1>
                <p>Selamat datang kembali. Berikut ringkasan data E-MAS KAPOR TA {{ $stats['fiscal_year'] }}.</p>
            </div>
            <div class="page-header-actions">
                {{-- Filter Tahun Anggaran --}}
                <div
                    style="display:flex;align-items:center;gap:8px;margin-right:8px;background:var(--input-bg);padding:4px 10px;border-radius:var(--radius-sm);border:1px solid var(--border-color);">
                    <i class="ri-calendar-line" style="color:var(--brand);"></i>
                    <select onchange="window.location.href='?year='+this.value"
                        style="border:none;outline:none;font-size:13px;font-weight:600;color:var(--text-main);cursor:pointer;background:transparent;">
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}" {{ $fiscalYear == $year ? 'selected' : '' }}>
                                TA {{ $year }} {{ $year == $defaultYear ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($fiscalYear != $defaultYear)
                    <span class="badge badge-warning" style="margin-right:12px;padding:8px 12px;"><i
                            class="ri-history-line"></i> Mode Arsip</span>
                @endif
            </div>
        </div>
    </div>



    {{-- ═══ Stat Cards ═══ --}}
    <div class="stats-row stats-row-5">
        {{-- Total POLRI --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total POLRI</span>
                <div class="stat-icon-sm" style="background:var(--success-bg);color:var(--success);"><i
                        class="ri-team-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_polri']) }}</div>
            <div class="stat-footer">Data personel riil</div>
        </div>

        {{-- Total PNS --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total PNS/P3K</span>
                <div class="stat-icon-sm" style="background:var(--warning-bg);color:var(--warning);"><i
                        class="ri-user-star-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_pns']) }}</div>
            <div class="stat-footer">Data personel riil</div>
        </div>

        {{-- Total Personil (Combined) --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Personil</span>
                <div class="stat-icon-sm" style="background:var(--brand-bg);color:var(--brand);"><i
                        class="ri-group-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_personnel']) }}</div>
            <div class="stat-footer">Polri + PNS</div>
        </div>

        {{-- Sudah Isi Ukuran --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Sudah Isi Ukuran</span>
                <div class="stat-icon-sm" style="background:var(--info-bg);color:var(--info);"><i
                        class="ri-check-double-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--info);">{{ number_format($stats['personnel_submitted']) }}</div>
            <div class="stat-footer"><span class="up"><i class="ri-arrow-up-s-fill"></i> {{ $stats['fill_rate'] }}%</span>
                progres</div>
        </div>

        {{-- Belum Isi Ukuran --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Belum Isi Ukuran</span>
                <div class="stat-icon-sm" style="background:#fef2f2;color:var(--danger);"><i class="ri-time-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--danger);">{{ number_format($stats['personnel_pending']) }}</div>
            <div class="stat-footer">Menunggu ukuran wajib</div>
        </div>
    </div>

    {{-- ═══ Kepuasan & Testimoni (Horizontal Modern) ═══ --}}
    <div class="satisfaction-horizontal-wrapper">
        <div class="sat-main-stats" style="flex: 1; min-width: 0; max-width: none;">
            <div class="sat-overview-row" style="padding: 10px;">
                <div class="sat-score-circle" style="width: 80px; height: 80px; border-radius: 20px;">
                    <span class="score-val count-up" data-val="{{ $testimonialInsights['serviceScore'] }}"
                        style="font-size: 32px;">0</span>
                    <span class="score-lbl" style="font-size: 11px;">Skor</span>
                </div>
                <div class="sat-overview-details" style="margin-left: 20px;">
                    <div class="sat-stars-row" style="margin-bottom: 8px;">
                        <h2
                            style="font-size: 36px; font-weight: 800; margin: 0; display: flex; align-items: baseline; gap: 8px;">
                            <span class="count-up" data-val="{{ $testimonialInsights['averageRating'] }}"
                                data-decimals="1">0.0</span>
                            <small style="font-size: 16px; color: var(--text-muted); font-weight: 600;">/5.0</small>
                        </h2>
                    </div>
                    <div class="stars"
                        style="color: #f59e0b; font-size: 20px; margin-bottom: 12px; display: flex; gap: 4px;">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="{{ $testimonialInsights['averageRating'] >= $i ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                        @endfor
                    </div>
                    <div class="sat-total-reviews" style="font-size: 14px; color: var(--text-muted);">
                        Berdasarkan <strong
                            style="color: var(--text-main); font-size: 16px;">{{ number_format($testimonialInsights['totalTestimonials']) }}</strong>
                        testimoni & laporan masuk
                    </div>
                </div>
            </div>

            <div class="sentiment-bar-wrap" style="margin-top: 24px; padding: 0 10px;">
                <div
                    style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">
                    <span>Sentimen Review</span>
                    <span>{{ $testimonialInsights['totalTestimonials'] > 0 ? round(($testimonialInsights['sentimentBreakdown'][0]['count'] / $testimonialInsights['totalTestimonials']) * 100) : 0 }}%
                        Positif</span>
                </div>
                <div class="sentiment-bar"
                    style="height: 10px; background: var(--bg-body); border-radius: 10px; display: flex; overflow: hidden; border: 1px solid var(--border-color);">
                    @foreach($testimonialInsights['sentimentBreakdown'] as $sentiment)
                        <div style="width: {{ $sentiment['percentage'] }}%; background: {{ $sentiment['color'] }};"
                            title="{{ $sentiment['label'] }}: {{ $sentiment['count'] }}"></div>
                    @endforeach
                </div>
                <div style="display: flex; gap: 16px; margin-top: 12px;">
                    @foreach($testimonialInsights['sentimentBreakdown'] as $sentiment)
                        <div
                            style="display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: var(--text-muted);">
                            <span
                                style="width: 8px; height: 8px; border-radius: 2px; background: {{ $sentiment['color'] }};"></span>
                            {{ $sentiment['label'] }} ({{ $sentiment['count'] }})
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="sat-right-panel"
            style="display: flex; flex-direction: column; width: 100%; min-width: 0; justify-content: space-between;">
            <div class="sat-quotes-marquee" style="min-height: auto; align-items: flex-start; padding-top: 16px;">
                @if($testimonialInsights['dashboardQuotes']->isNotEmpty())
                    <div class="quotes-track">
                        @foreach($testimonialInsights['dashboardQuotes'] as $quote)
                            <div class="sat-modern-quote">
                                <i class="ri-double-quotes-l bg-quote-icon"></i>
                                <div class="quote-top-row">
                                    <div class="quote-status-badge"
                                        style="background: {{ $quote['background'] }}; color: {{ $quote['accent'] }};">
                                        <i
                                            class="{{ $quote['testimonial']->response_status === 'not_received' ? 'ri-truck-line' : 'ri-feedback-line' }}"></i>
                                        {{ $quote['type'] }}
                                    </div>
                                    @if(filled($quote['testimonial']->category_label ?? null))
                                        <div class="quote-category-badge">
                                            {{ $quote['testimonial']->category_label }}
                                        </div>
                                    @endif
                                </div>
                                <div class="quote-text">"{{ Str::limit($quote['testimonial']->display_message, 120) }}"</div>
                                <div class="quote-author">
                                    <div class="ava" style="background: {{ $quote['background'] }}; color: {{ $quote['accent'] }};">
                                        {{ strtoupper(substr($quote['testimonial']->user->name ?? 'PN', 0, 2)) }}
                                    </div>
                                    <div class="auth-info">
                                        <div class="name">
                                            {{ $quote['testimonial']->user->name ?? 'Personel' }}
                                        </div>
                                        <div class="satker">{{ $quote['testimonial']->item_name_snapshot }} •
                                            {{ $quote['testimonial']->user->satker->name ?? 'Tanpa Satker' }}
                                        </div>
                                        <div class="star-rating-full">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i
                                                    class="{{ ($quote['testimonial']->rating ?? 0) >= $i ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Duplicate for seamless scroll effect -->
                        @foreach($testimonialInsights['dashboardQuotes'] as $quote)
                            <div class="sat-modern-quote" aria-hidden="true">
                                <i class="ri-double-quotes-l bg-quote-icon"></i>
                                <div class="quote-top-row">
                                    <div class="quote-status-badge"
                                        style="background: {{ $quote['background'] }}; color: {{ $quote['accent'] }};">
                                        <i
                                            class="{{ $quote['testimonial']->response_status === 'not_received' ? 'ri-truck-line' : 'ri-feedback-line' }}"></i>
                                        {{ $quote['type'] }}
                                    </div>
                                    @if(filled($quote['testimonial']->category_label ?? null))
                                        <div class="quote-category-badge">
                                            {{ $quote['testimonial']->category_label }}
                                        </div>
                                    @endif
                                </div>
                                <div class="quote-text">"{{ Str::limit($quote['testimonial']->display_message, 120) }}"</div>
                                <div class="quote-author">
                                    <div class="ava" style="background: {{ $quote['background'] }}; color: {{ $quote['accent'] }};">
                                        {{ strtoupper(substr($quote['testimonial']->user->name ?? 'PN', 0, 2)) }}
                                    </div>
                                    <div class="auth-info">
                                        <div class="name">
                                            {{ $quote['testimonial']->user->name ?? 'Personel' }}
                                        </div>
                                        <div class="satker">{{ $quote['testimonial']->item_name_snapshot }} •
                                            {{ $quote['testimonial']->user->satker->name ?? 'Tanpa Satker' }}
                                        </div>
                                        <div class="star-rating-full">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i
                                                    class="{{ ($quote['testimonial']->rating ?? 0) >= $i ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-quotes">
                        <i class="ri-chat-smile-3-line"
                            style="font-size:24px; color:var(--text-muted); margin-bottom:8px; display:block;"></i>
                        Belum ada testimoni masuk
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ Chart Progres per Satker (Full Width) ═══ --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-head">
            <h3><i class="ri-bar-chart-horizontal-line" style="margin-right:6px;color:var(--brand);"></i> Grafik
                Progres per Satker</h3>
        </div>
        <div class="card-body" style="overflow-x: auto; max-height: 600px;">
            <div id="chartWrapper" style="position: relative; width: 100%; min-width: 500px;">
                <canvas id="satkerChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ═══ Secondary Widgets (Berbaris 3 Kolom) ═══ --}}
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">

        {{-- Perlu Perhatian (Smart Alerts) --}}
        <div class="card alert-card" style="height:100%; display:flex; flex-direction:column;">
            <div class="card-head"
                style="background:var(--warning-bg); border-bottom:1px solid var(--border-color); border-radius:var(--radius-md) var(--radius-md) 0 0;">
                <h3 style="color:var(--warning); display:flex; align-items:center;"><i class="ri-alarm-warning-fill"
                        style="margin-right:8px; animation: pulse 2s infinite;"></i> Perlu Perhatian</h3>
            </div>
            <div class="card-body" style="padding:16px;">
                @if($stats['personnel_pending'] > 0)
                    <div
                        style="background:var(--bg-body); border-left:4px solid var(--warning); padding:12px 16px; border-radius:4px; margin-bottom:16px; border:1px solid var(--border-color); border-left-width:4px;">
                        <div style="font-weight:700; color:var(--text-main); font-size:14px; margin-bottom:4px;">
                            {{ number_format($stats['personnel_pending']) }} Personil Belum Isi Ukuran
                        </div>
                        <div style="font-size:12px; color:var(--text-muted);">Daftar ini hanya menghitung ukuran kapor yang
                            wajib, tidak mencampur biodata umum.</div>
                    </div>

                    <div
                        style="font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase;">
                        Daftar Acak:</div>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        @foreach($incompletePersonnel as $ip)
                            <div
                                style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:var(--bg-body); border-radius:var(--radius-sm); border:1px solid var(--border-color);">
                                <div style="display:flex; flex-direction:column;">
                                    <span
                                        style="font-weight:600; font-size:13px; color:var(--text-main);">{{ $ip->full_name }}</span>
                                    <span
                                        style="font-size:11px; color:var(--text-muted);">{{ $ip->satker->name ?? 'Tanpa Satker' }}</span>
                                </div>
                                <a href="{{ route('admin.personnel.index', ['search' => $ip->nrp ?? $ip->full_name]) }}"
                                    class="btn btn-outline"
                                    style="padding:4px 8px; font-size:11px; color:var(--brand); border-color:var(--brand);">Lengkapi</a>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('admin.personnel.index', ['status' => 'incomplete', 'incomplete_scope' => 'size_only']) }}"
                        style="display:block; text-align:center; font-size:12px; font-weight:600; color:var(--brand); margin-top:16px; text-decoration:none;">Lihat
                        Semua Ukuran Bermasalah &rarr;</a>
                @else
                    <div style="text-align:center; padding:32px 16px;">
                        <i class="ri-checkbox-circle-fill"
                            style="font-size:48px; color:var(--success); margin-bottom:16px; display:inline-block;"></i>
                        <div style="font-weight:700; color:var(--text-main); font-size:16px;">Semua Data Lengkap!</div>
                        <div style="font-size:13px; color:var(--text-muted); margin-top:4px;">Tidak ada tindakan yang
                            diperlukan saat ini.</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Log Aktivitas Langsung (Live Timeline) --}}
        <div class="card" style="height:100%; margin-bottom:0; display:flex; flex-direction:column;">
            <div class="card-head">
                <h3><i class="ri-history-line" style="margin-right:6px;color:var(--brand);"></i> Aktivitas Terkini</h3>
            </div>
            <div class="card-body">
                <div style="position:relative; padding-left:24px;">
                    {{-- Garis Timeline --}}
                    <div style="position:absolute; left:7px; top:8px; bottom:0; width:2px; background:var(--border-color);">
                    </div>

                    @if(isset($activities) && count($activities) > 0)
                        @foreach($activities as $activity)
                            <div style="position:relative; margin-bottom:20px; padding-left:16px;">
                                {{-- Titik --}}
                                <div
                                    style="position:absolute; left:-22px; top:4px; width:12px; height:12px; border-radius:50%; background:var(--brand); border:2px solid var(--bg-card); box-shadow:0 0 0 1px var(--border-color);">
                                </div>

                                <div style="font-size:13px; color:var(--text-main); line-height:1.5;">
                                    <span style="font-weight:600;">{{ $activity->causer->name ?? 'System' }}</span>
                                    {{ $activity->description }}
                                    @if($activity->subject_type)
                                        <span
                                            style="color:var(--brand); font-weight:500;">({{ class_basename($activity->subject_type) }})</span>
                                    @endif
                                </div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                                    <i class="ri-time-line" style="vertical-align:middle;"></i>
                                    {{ $activity->created_at->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    @else
                        {{-- Mockup Data if no Spatie Activity --}}
                        <div style="position:relative; margin-bottom:20px; padding-left:16px;">
                            <div
                                style="position:absolute; left:-22px; top:4px; width:12px; height:12px; border-radius:50%; background:var(--brand); border:2px solid var(--bg-card); box-shadow:0 0 0 1px var(--border-color);">
                            </div>
                            <div style="font-size:13px; color:var(--text-main); line-height:1.5;">
                                <span style="font-weight:600;">Superadmin</span> baru saja login ke dalam sistem.
                            </div>
                            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;"><i class="ri-time-line"></i>
                                Baru saja</div>
                        </div>
                        <div style="position:relative; margin-bottom:20px; padding-left:16px;">
                            <div
                                style="position:absolute; left:-22px; top:4px; width:12px; height:12px; border-radius:50%; background:var(--info); border:2px solid var(--bg-card); box-shadow:0 0 0 1px var(--border-color);">
                            </div>
                            <div style="font-size:13px; color:var(--text-main); line-height:1.5;">
                                <span style="font-weight:600;">Bripda Rizky</span> berhasil mengimpor 150 data personil baru
                                untuk Polres Bima.
                            </div>
                            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;"><i class="ri-time-line"></i> 2
                                jam yang lalu</div>
                        </div>
                        <div style="position:relative; padding-left:16px;">
                            <div
                                style="position:absolute; left:-22px; top:4px; width:12px; height:12px; border-radius:50%; background:var(--success); border:2px solid var(--bg-card); box-shadow:0 0 0 1px var(--border-color);">
                            </div>
                            <div style="font-size:13px; color:var(--text-main); line-height:1.5;">
                                <span style="font-weight:600;">Kombes Pol Satria</span> membuat <strong>Paket Anggaran TA
                                    2026</strong>.
                            </div>
                            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;"><i class="ri-time-line"></i>
                                Kemarin</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection

@section('styles')
<style>
        .satisfaction-card {
            background:
                radial-gradient(circle at top right, rgba(212, 175, 55, 0.14), transparent 30%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 248, 244, 0.95));
            border: 1px solid rgba(198, 40, 40, 0.12);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .satisfaction-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            color: var(--brand);
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(198, 40, 40, 0.08);
        }

        .satisfaction-hero {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 20px;
            border-radius: 18px;
            border: 1px solid rgba(198, 40, 40, 0.12);
            background: linear-gradient(135deg, #fff7f5 0%, #ffffff 55%, #fffaf0 100%);
        }

        .satisfaction-score-ring {
            width: 84px;
            height: 84px;
            border-radius: 24px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--brand), #8b1e1e);
            color: #fff;
            box-shadow: 0 14px 28px rgba(198, 40, 40, 0.22);
        }

        .satisfaction-score-ring span {
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
        }

        .satisfaction-score-ring small {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.88;
            margin-top: 4px;
        }

        .satisfaction-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .satisfaction-rating-line {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 8px;
        }

        .satisfaction-rating-line strong {
            font-size: 28px;
            color: var(--text-main);
            letter-spacing: -0.03em;
        }

        .satisfaction-rating-line span {
            font-size: 12px;
            color: var(--text-muted);
        }

        .satisfaction-stars {
            display: flex;
            gap: 4px;
            color: #F59E0B;
            font-size: 18px;
            margin: 10px 0 14px;
        }

        .satisfaction-mini-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .mini-stat-box {
            padding: 10px 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--border-color);
        }

        .mini-stat-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .mini-stat-box strong {
            font-size: 18px;
            color: var(--text-main);
        }

        .satisfaction-summary-box {
            padding: 16px 18px;
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .summary-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .satisfaction-summary-box p {
            font-size: 12.5px;
            color: var(--text-main);
            line-height: 1.6;
        }

        .satisfaction-quotes {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .quote-card-modern {
            padding: 16px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.92);
            position: relative;
            overflow: hidden;
        }

        .quote-card-modern::after {
            content: '"';
            position: absolute;
            right: 12px;
            top: -8px;
            font-size: 56px;
            line-height: 1;
            color: rgba(15, 23, 42, 0.06);
            font-family: Georgia, serif;
        }

        .quote-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .quote-message {
            font-size: 13px;
            font-style: italic;
            color: var(--text-main);
            line-height: 1.6;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
        }

        .quote-user-row {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .quote-avatar {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .quote-user-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .quote-user-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .quote-rating {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 2px;
            font-weight: 800;
            font-size: 13px;
            flex-shrink: 0;
        }

        .satisfaction-empty-state {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            border-radius: 16px;
            border: 1px dashed rgba(198, 40, 40, 0.18);
            background: rgba(255, 255, 255, 0.85);
        }

        .satisfaction-empty-state i {
            font-size: 24px;
            color: var(--brand);
        }

        .satisfaction-empty-state strong {
            display: block;
            font-size: 13px;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .satisfaction-empty-state p {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .quick-action-btn {
            flex: 1;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 16px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .quick-action-btn:hover {
            border-color: var(--brand);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            /* very light shadow */
        }

        .quick-action-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .quick-action-text {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-main);
        }

        .alert-card {
            margin-bottom: 0;
            border: 1px solid var(--warning);
            box-shadow: 0 4px 6px -1px var(--warning-bg);
        }

        .satisfaction-horizontal-wrapper {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 20px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04), inset 0 1px 0 rgba(255, 255, 255, 1);
            overflow: hidden;
            position: relative;
        }

        .satisfaction-horizontal-wrapper::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 50% 0%, rgba(16, 185, 129, 0.05), transparent 50%), radial-gradient(circle at 100% 100%, rgba(245, 158, 11, 0.05), transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .sat-main-stats {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #fff;
            padding: 16px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.04);
            min-width: 370px;
            max-width: 455px;
        }

        .sat-overview-row {
            display: flex;
            gap: 14px;
            align-items: center;
            width: 100%;
        }

        @keyframes scorePopIn {
            0% {
                opacity: 0;
                transform: scale(0.6) rotate(-5deg);
                filter: blur(5px);
            }

            60% {
                opacity: 1;
                transform: scale(1.08) rotate(3deg);
                filter: blur(0);
            }

            100% {
                opacity: 1;
                transform: scale(1) rotate(0);
            }
        }

        @keyframes scorePulseInfinite {
            0% {
                box-shadow: 0 0 0 0 rgba(198, 40, 40, 0.4);
            }

            70% {
                box-shadow: 0 0 0 16px rgba(198, 40, 40, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(198, 40, 40, 0);
            }
        }

        .sat-score-circle {
            width: 76px;
            height: 76px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--brand), #8b0000);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            animation: scorePopIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards, scorePulseInfinite 2s ease-out infinite 0.8s;
        }

        .sat-score-circle .score-val {
            font-size: 28px;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 2px;
        }

        .sat-score-circle .score-lbl {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
        }

        .sat-stars-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 4px;
        }

        .sat-stars-row h2 {
            font-size: 24px;
            font-weight: 800;
            margin: 0;
            color: var(--text-main);
        }

        .sat-stars-row h2 small {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .sat-stars-row .stars {
            color: #f59e0b;
            font-size: 16px;
        }

        .sat-total-reviews {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .sat-total-reviews strong {
            color: var(--text-main);
            font-size: 15px;
            font-weight: 800;
        }

        .sat-category-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            width: 100%;
        }

        .sat-category-card {
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), #fff);
            min-width: 0;
            animation: statCardLift 0.55s ease both;
        }

        .sat-category-card:nth-child(2) {
            animation-delay: 0.08s;
        }

        .sat-category-card:nth-child(3) {
            animation-delay: 0.16s;
        }

        .sat-category-card-top {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .sat-category-icon {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }

        .sat-category-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.35;
        }

        .sat-category-rating {
            display: flex;
            align-items: baseline;
            gap: 4px;
            font-size: 18px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .sat-category-rating small {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .sat-category-stars {
            display: flex;
            gap: 2px;
            color: #f59e0b;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .sat-category-count {
            font-size: 10px;
            color: var(--text-muted);
        }

        .sat-category-count strong {
            color: var(--text-main);
            font-size: 12px;
        }

        .sat-rating-breakdown-wrap {
            width: 100%;
            padding-top: 10px;
            border-top: 1px dashed var(--border-color);
        }

        .sat-rating-breakdown-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .sat-rating-breakdown {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .breakdown-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
        }

        .breakdown-row .star-lbl {
            width: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .breakdown-row .star-lbl i {
            color: #f59e0b;
            font-size: 10px;
        }

        .breakdown-row .bar-track {
            flex: 1;
            height: 5px;
            background: var(--bg-body);
            border-radius: 3px;
            overflow: hidden;
        }

        .breakdown-row .bar-fill {
            height: 100%;
            background: #f59e0b;
            border-radius: 3px;
        }

        .breakdown-row .count-lbl {
            width: 16px;
            text-align: right;
        }

        .sat-insight-panel {
            position: relative;
            z-index: 1;
            padding: 24px;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .insight-header {
            font-size: 13px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sat-insight-panel p {
            font-size: 13px;
            line-height: 1.6;
            margin: 0 0 16px 0;
            font-weight: 500;
        }

        .btn-detail-sat {
            align-self: flex-start;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 20px;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .btn-detail-sat:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            filter: brightness(0.95);
        }

        .sat-quotes-marquee {
            position: relative;
            z-index: 1;
            overflow: hidden;
            display: flex;
            align-items: center;
            min-height: 220px;
            mask-image: linear-gradient(to right, transparent, black 2%, black 98%, transparent);
            -webkit-mask-image: linear-gradient(to right, transparent, black 2%, black 98%, transparent);
        }

        .quotes-track {
            display: flex;
            gap: 14px;
            align-items: center;
            animation: marquee 34s linear infinite;
            width: max-content;
            padding: 4px 0;
        }

        .quotes-track:hover {
            animation-play-state: paused;
        }

        @keyframes marquee {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(calc(-50% - 8px));
            }
        }

        @keyframes statCardLift {
            0% {
                opacity: 0;
                transform: translateY(14px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sat-modern-quote {
            width: 250px;
            background: #fff;
            border-radius: 16px;
            padding: 14px 16px 14px 16px;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.04);
            transition: transform 0.3s, box-shadow 0.3s;
            animation: statCardLift 0.5s ease both;
        }

        .sat-modern-quote:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .bg-quote-icon {
            position: absolute;
            top: 4px;
            right: 14px;
            font-size: 52px;
            color: rgba(15, 23, 42, 0.02);
            line-height: 1;
            font-family: Georgia, serif;
        }

        .quote-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .quote-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }

        .quote-text {
            font-size: 12px;
            font-style: italic;
            color: #475569;
            line-height: 1.55;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
            font-weight: 500;
            min-height: 56px;
        }

        .quote-category-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            background: var(--bg-body);
            padding: 3px 8px;
            border-radius: 6px;
            margin-bottom: 0;
            white-space: nowrap;
        }

        .quote-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quote-author .ava {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .quote-author .auth-info {
            flex: 1;
            min-width: 0;
        }

        .auth-info .name {
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
            letter-spacing: 0;
        }

        .auth-info .star-rating-full {
            color: #f59e0b;
            font-size: 13px;
            display: flex;
            gap: 2px;
            margin-top: 6px;
            filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.2));
        }

        .auth-info .satker {
            font-size: 10px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .empty-quotes {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            border: 1px dashed var(--border-color);
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }

        @media (max-width: 1200px) {
            .satisfaction-horizontal-wrapper {
                grid-template-columns: 1fr;
            }

            .sat-main-stats {
                max-width: none;
                min-width: 0;
            }

            .sat-quotes-marquee {
                min-height: 0;
            }

            .sat-quotes-marquee {
                overflow-x: auto;
                mask-image: none;
                -webkit-mask-image: none;
                display: block;
            }

            .quotes-track {
                animation: none;
                padding: 10px 4px;
            }

            .sat-modern-quote {
                flex-shrink: 0;
            }
        }

        @media (max-width: 768px) {
            .satisfaction-horizontal-wrapper {
                padding: 16px;
                border-radius: 16px;
            }

            .sat-main-stats {
                align-items: center;
                text-align: center;
                gap: 16px;
                padding: 16px 12px;
                min-width: 0;
            }

            .sat-overview-row {
                flex-direction: column;
                gap: 16px;
            }

            .sat-score-circle {
                margin: 0 auto;
                width: 64px;
                height: 64px;
            }

            .sat-score-circle .score-val {
                font-size: 24px;
            }

            .sat-rating-info {
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .sat-stars-row {
                justify-content: center;
            }

            .sat-total-reviews {
                text-align: center;
            }

            .sat-category-grid {
                grid-template-columns: 1fr;
            }

            .sat-category-card {
                text-align: left;
            }

            .quote-top-row {
                flex-wrap: wrap;
            }

            .sat-rating-breakdown-wrap {
                width: 100%;
            }

            #chartWrapper {
                min-width: 100% !important;
            }

            .card {
                min-width: 0;
            }

            /* Memperbaiki tinggi chart di mobile agar tetap enak dilihat bar-nya */
            .card-body canvas {
                min-height: 400px;
            }
        }
    </style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Register DataLabels plugin globally for this page
            Chart.register(ChartDataLabels);

            const satkerData = @json($satkerStats);

            const labels = satkerData.map(s => s.name);
            const dataPct = satkerData.map(s => {
                return s.total_personnel > 0 ? ((s.submitted_count / s.total_personnel) * 100).toFixed(1) : 0;
            });
            const rawSubmitted = satkerData.map(s => s.submitted_count);
            const rawTotal = satkerData.map(s => s.total_personnel);

            // Use dynamic colors based on threshold
            const backgroundColors = dataPct.map(pct => {
                if (pct >= 80) return '#10B981'; // green
                if (pct >= 50) return '#F59E0B'; // yellow
                return '#EF4444'; // red
            });

            const chartHeight = Math.max(300, satkerData.length * 30); // increased row height for better label visibility
            document.getElementById('chartWrapper').style.height = chartHeight + 'px';

            const ctx = document.getElementById('satkerChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Progres Pengisian',
                        data: dataPct,
                        backgroundColor: backgroundColors,
                        borderRadius: 4,
                        // Passing the raw data so the datalabels formatter can access it
                        rawSubmitted: rawSubmitted,
                        rawTotal: rawTotal
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Makes the bar chart horizontal
                    layout: {
                        padding: {
                            right: 90 // Add right padding to prevent long labels from cutting off
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const idx = context.dataIndex;
                                    const submitted = context.dataset.rawSubmitted[idx];
                                    const total = context.dataset.rawTotal[idx];
                                    return `Input: ${submitted} / Total: ${total} (${context.raw}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: function (context) {
                                // Always show label outside the bar with a dark text color for high contrast
                                return '#334155';
                            },
                            anchor: 'end',
                            align: 'right',
                            offset: 4,
                            font: {
                                weight: 'bold',
                                size: 11
                            },
                            formatter: function (value, context) {
                                const idx = context.dataIndex;
                                const submitted = context.dataset.rawSubmitted[idx];
                                const total = context.dataset.rawTotal[idx];
                                return `${submitted}/${total} (${value}%)`;
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function (value) {
                                    return value + '%';
                                }
                            }
                        },
                        y: {
                            ticks: {
                                font: { size: 11, weight: '500' }
                            }
                        }
                    }
                }
            });
        });

        // Count-up animation
        const counters = document.querySelectorAll('.count-up');
        counters.forEach(counter => {
            const target = parseFloat(counter.getAttribute('data-val'));
            const isDecimal = counter.hasAttribute('data-decimals');
            const duration = 2000; // 2 seconds
            const stepTime = 30;
            const steps = duration / stepTime;
            const increment = target / steps;
            let current = 0;

            if (target > 0) {
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    counter.innerText = isDecimal ? current.toFixed(1) : Math.round(current);
                }, stepTime);
            } else {
                counter.innerText = isDecimal ? "0.0" : "0";
            }
        });

    </script>
@endsection
