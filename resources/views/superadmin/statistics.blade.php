@extends('layouts.app')

@section('title', 'Statistik Testimoni')
@section('breadcrumb', 'Statistik')

@section('styles')
<style>
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

    /* Overall Background */
    body {
        background-color: #f8fafc;
        background-image: 
            radial-gradient(at 0% 0%, rgba(239, 68, 68, 0.05) 0px, transparent 50%),
            radial-gradient(at 100% 0%, rgba(245, 158, 11, 0.05) 0px, transparent 50%);
        background-attachment: fixed;
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
        top: -100px; right: -100px;
        width: 350px; height: 350px;
        background: rgba(220, 38, 38, 0.08);
        filter: blur(60px);
        border-radius: 50%;
        z-index: 0;
        animation: floatAura 8s infinite alternate ease-in-out;
    }

    @keyframes floatAura {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(-40px, 40px) scale(1.1); }
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
        box-shadow: 0 20px 40px -10px rgba(185, 28, 28, 0.4), inset 0 2px 0 rgba(255,255,255,0.25);
        position: relative;
        overflow: hidden;
    }

    .score-badge::after {
        content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
        background: linear-gradient(to right, transparent, rgba(255,255,255,0.3), transparent);
        transform: skewX(-20deg);
        animation: shinePass 6s infinite;
    }

    @keyframes shinePass {
        0%, 50% { left: -100%; } 100% { left: 200%; }
    }

    .score-badge strong { font-size: 56px; line-height: 1; font-weight: 800; letter-spacing: -2px; }
    .score-badge span { font-size: 11px; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; margin-top: 4px; opacity: 0.9; }

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
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        transition: var(--transition);
        backdrop-filter: blur(10px);
    }

    .hero-pill:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        background: #fff;
    }

    .insight-panel {
        padding: 32px;
        border-radius: var(--radius-lg);
        background: rgba(255, 255, 255, 0.7);
        border: 1px solid rgba(255,255,255,0.9);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
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
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
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
        box-shadow: 0 10px 30px -5px rgba(0,0,0,0.03);
        transition: var(--transition);
        overflow: hidden;
        z-index: 1;
        font-family: 'Outfit', sans-serif;
    }

    .stat-card.premium::before {
        content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255,255,255,0), rgba(241, 245, 249, 0.6)); z-index: -1;
    }

    .stat-card.premium:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
        border-color: #e2e8f0;
    }

    .stat-top {
        display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;
    }

    .stat-label { font-size: 14.5px; font-weight: 600; color: var(--text-muted); }
    
    .stat-icon-sm {
        width: 48px; height: 48px; border-radius: 16px;
        display: flex; align-items: center; justify-content: center; font-size: 24px;
        box-shadow: inset 0 2px 4px rgba(255,255,255,0.6), 0 6px 15px rgba(0,0,0,0.05);
    }

    .stat-value { font-size: 34px; font-weight: 800; color: var(--text-heading); line-height: 1; letter-spacing: -0.5px; }
    .metric-subtext { font-size: 13px; color: var(--text-muted); margin-top: 10px; line-height: 1.5; }

    /* Layout Grids */
    .feedback-grid {
        display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 28px; margin-bottom: 32px;
    }

    .card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid #f1f5f9;
        box-shadow: 0 15px 35px -5px rgba(0,0,0,0.03);
        font-family: 'Outfit', sans-serif;
        overflow: hidden;
        transition: var(--transition);
    }
    
    .card:hover {
        box-shadow: 0 25px 50px -10px rgba(0,0,0,0.06);
    }

    .card-head { padding: 24px 28px; border-bottom: 1px solid #f8fafc; background: transparent; }
    .card-head h3 { font-size: 19px; font-weight: 700; color: var(--text-heading); display: flex; align-items: center; }
    .card-body { padding: 28px; }

    .breakdown-item { margin-bottom: 18px; }
    .bar-track { width: 100%; height: 12px; border-radius: 999px; background: #f1f5f9; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.03); margin-top: 8px;}
    .bar-fill { height: 100%; border-radius: inherit; background: var(--gold-gradient); position: relative; }
    .bar-fill::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent); }

    .sentiment-row { display: grid; gap: 16px; margin-top: 32px; }
    
    .sentiment-chip {
        display: flex; align-items: center; justify-content: space-between; gap: 16px;
        padding: 20px 24px; border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.6);
        box-shadow: 0 10px 25px rgba(0,0,0,0.04);
        transition: var(--transition);
        backdrop-filter: blur(10px);
    }
    .sentiment-chip:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }

    .satker-item { padding: 18px 0; border-bottom: 1px dashed #e2e8f0; transition: var(--transition); }
    .satker-item:hover { background-color: #f8fafc; border-radius: 12px; padding-left: 12px; padding-right: 12px; margin-left: -12px; margin-right: -12px; border-bottom-color: transparent;}
    .satker-item:last-child { border-bottom: none; padding-bottom: 0; }
    .satker-item:last-child:hover { border-bottom-color: transparent; padding-bottom: 12px; margin-bottom: -12px;} 

    /* Spotlights */
    .spotlight-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 28px; margin-bottom: 32px; }
    
    .spotlight-card {
        padding: 32px; border-radius: var(--radius-lg);
        border: 1px solid rgba(255,255,255,0.9);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05);
        display: flex; flex-direction: column; gap: 24px;
        position: relative; overflow: hidden;
        transition: var(--transition);
        font-family: 'Outfit', sans-serif;
    }
    .spotlight-card:hover { transform: translateY(-5px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.09); }

    .spotlight-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 6px; }
    .spotlight-card[style*="success"]::before { background: var(--success-gradient); }
    .spotlight-card[style*="danger"]::before { background: var(--danger-gradient); }

    .spotlight-quote {
        font-size: 17px; line-height: 1.8; color: var(--text-heading); font-style: italic;
        background: rgba(255, 255, 255, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.9);
        border-radius: 20px; padding: 26px; flex: 1;
        backdrop-filter: blur(10px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.03);
    }

    /* Testimonial Cards */
    .testimonial-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 28px; }
    
    .testimonial-card {
        padding: 30px; border-radius: var(--radius-lg);
        background: #fff; border: 1px solid #f1f5f9;
        box-shadow: 0 10px 30px -5px rgba(0,0,0,0.04);
        display: flex; flex-direction: column; gap: 20px;
        transition: var(--transition);
        font-family: 'Outfit', sans-serif;
    }
    .testimonial-card:hover { transform: translateY(-6px) scale(1.01); box-shadow: 0 20px 45px -8px rgba(0,0,0,0.08); border-color: #e2e8f0; }

    .testimonial-head { display: flex; justify-content: space-between; align-items: flex-start; }
    
    .avatar-badge {
        width: 56px; height: 56px; border-radius: 20px;
        background: var(--brand-gradient); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 20px;
        box-shadow: 0 8px 16px -4px rgba(185, 28, 28, 0.4);
    }

    .testimonial-message { color: var(--text-heading); font-size: 15px; line-height: 1.8; background: #f8fafc; padding: 22px; border-radius: 18px; border: 1px solid #f1f5f9; }

    .empty-showcase {
        border: 2px dashed rgba(185, 28, 28, 0.2);
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(12px);
        border-radius: var(--radius-xl);
        padding: 60px 30px; text-align: center;
        font-family: 'Outfit', sans-serif;
    }
    .empty-showcase .empty-icon {
        width: 100px; height: 100px; border-radius: 32px; margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;
        background: var(--brand-gradient); color: #fff; font-size: 42px; box-shadow: 0 20px 40px rgba(185, 28, 28, 0.3);
    }

    /* Category Stats Row */
    .category-stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 32px;
    }

    .category-stat-card {
        position: relative;
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: var(--radius-lg);
        padding: 24px;
        box-shadow: 0 10px 30px -5px rgba(0,0,0,0.03);
        transition: var(--transition);
        overflow: hidden;
        font-family: 'Outfit', sans-serif;
    }

    .category-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
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
    @media (max-width: 1024px) { .hero-grid, .feedback-grid, .spotlight-grid, .category-stats-row { grid-template-columns: 1fr; } }
    @media (max-width: 768px) {
        .hero-panel { padding: 24px; }
        .score-badge { width: 110px; height: 110px; border-radius: 30px; }
        .score-badge strong { font-size: 40px; }
        .testimonial-grid { grid-template-columns: 1fr; }
        .page-header h1 { font-size: 2rem; }
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Statistik Testimoni</h1>
            <p>Panel evaluasi kualitas layanan E-MAS KAPOR berdasarkan masukan personel yang login menggunakan NRP/NIP.</p>
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
                    <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; color: var(--brand);">Executive Pulse</div>
                    <div style="font-size: 34px; font-weight: 800; color: var(--text-main); line-height: 1.1; margin-top: 6px;">
                        {{ number_format($averageRating, 1) }} / 5
                    </div>
                    <div class="hero-stars" aria-label="Rata-rata rating {{ number_format($averageRating, 1) }} dari 5">
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
                    {{ number_format($recentTestimonialsCount) }} masukan 30 hari terakhir
                </div>
                <div class="hero-pill">
                    <i class="ri-star-smile-line" style="color: #f59e0b;"></i>
                    {{ number_format($fiveStarRate, 1) }}% memberi 5 bintang
                </div>
                <div class="hero-pill">
                    <i class="ri-calendar-check-line" style="color: var(--success);"></i>
                    {{ $lastSubmittedAt ? 'Masukan terakhir ' . $lastSubmittedAt->format('d M Y, H:i') : 'Belum ada waktu masukan' }}
                </div>
            </div>
        </div>

        <div class="insight-panel">
            <div>
                <div class="insight-label" style="background: {{ $serviceInsight['background'] }}; color: {{ $serviceInsight['tone'] }};">
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
                <div style="padding: 14px; border-radius: 14px; background: var(--bg-body); border: 1px solid var(--border-color);">
                    <div style="font-size: 12px; color: var(--text-muted);">Rerata 30 hari</div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--text-main); margin-top: 6px;">{{ number_format($recentAverageRating, 1) }}</div>
                </div>
                <div style="padding: 14px; border-radius: 14px; background: var(--bg-body); border: 1px solid var(--border-color);">
                    <div style="font-size: 12px; color: var(--text-muted);">Perlu atensi</div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--danger); margin-top: 6px;">{{ number_format($attentionCount) }}</div>
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
        <h2 style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-bottom: 10px;">Belum ada testimoni masuk</h2>
        <p style="max-width: 720px; margin: 0 auto; color: var(--text-muted); line-height: 1.8;">
            Begitu personel mulai mengirim masukan dari dashboard mereka, halaman ini otomatis berubah menjadi panel statistik untuk admin dan para atasan: rata-rata bintang, distribusi kepuasan, satker paling aktif, dan pesan terbaru.
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
                <span class="stat-label">5 Bintang</span>
                <div class="stat-icon-sm" style="background: var(--success-bg); color: var(--success);">
                    <i class="ri-medal-line"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($fiveStarRate, 1) }}%</div>
            <div class="metric-subtext">Proporsi testimoni yang benar-benar sangat puas.</div>
        </div>

        <div class="stat-card premium">
            <div class="stat-top">
                <span class="stat-label">Perlu Atensi</span>
                <div class="stat-icon-sm" style="background: var(--danger-bg); color: var(--danger);">
                    <i class="ri-alarm-warning-line"></i>
                </div>
            </div>
            <div class="stat-value" style="color: var(--danger);">{{ number_format($attentionCount) }}</div>
            <div class="metric-subtext">Masukan bernilai 1-2 bintang yang layak diprioritaskan.</div>
        </div>

        <div class="stat-card premium">
            <div class="stat-top">
                <span class="stat-label">30 Hari Terakhir</span>
                <div class="stat-icon-sm" style="background: var(--info-bg); color: var(--info);">
                    <i class="ri-pulse-line"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($recentTestimonialsCount) }}</div>
            <div class="metric-subtext">Menunjukkan apakah umpan balik pengguna masih aktif.</div>
        </div>
    </div>

    {{-- Per-Category Rating Breakdown --}}
    @if(isset($categoryStats) && count($categoryStats) > 0)
    <div class="category-stats-row">
        @foreach($categoryStats as $catKey => $catStat)
            <div class="category-stat-card" style="border-top: 0;">
                <div style="position:absolute; top:0; left:0; right:0; height:4px; background: {{ $catStat['color'] }};"></div>
                <div class="category-stat-head">
                    <div class="category-stat-label">
                        <div class="category-stat-icon" style="background: {{ $catStat['bg'] }}; color: {{ $catStat['color'] }};">
                            <i class="{{ $catStat['icon'] }}"></i>
                        </div>
                        {{ $catStat['label'] }}
                    </div>
                </div>
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
                <div class="category-stat-count">
                    {{ number_format($catStat['count']) }} testimoni
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <div class="feedback-grid">
        <div class="card">
            <div class="card-head">
                <h3><i class="ri-bar-chart-horizontal-line" style="margin-right: 8px; color: var(--brand);"></i>Distribusi Rating</h3>
            </div>
            <div class="card-body">
                <div class="breakdown-list">
                    @foreach($ratingBreakdown as $bucket)
                        <div class="breakdown-item">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                <div style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; color: var(--text-main);">
                                    <span>{{ $bucket['stars'] }} Bintang</span>
                                    <span style="display: inline-flex; gap: 2px; color: #f59e0b;">
                                        @for($star = 1; $star <= $bucket['stars']; $star++)
                                            <i class="ri-star-fill"></i>
                                        @endfor
                                    </span>
                                </div>
                                <div style="font-size: 13px; color: var(--text-muted);">
                                    {{ number_format($bucket['count']) }} masukan
                                    <strong style="color: var(--text-main); margin-left: 6px;">{{ number_format($bucket['percentage'], 1) }}%</strong>
                                </div>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: {{ $bucket['percentage'] }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="sentiment-row">
                    @foreach($sentimentBreakdown as $sentiment)
                        <div class="sentiment-chip" style="background: {{ $sentiment['background'] }};">
                            <div>
                                <div style="font-size: 13px; font-weight: 800; color: {{ $sentiment['color'] }};">{{ $sentiment['label'] }}</div>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">{{ number_format($sentiment['count']) }} testimoni</div>
                            </div>
                            <div style="font-size: 22px; font-weight: 800; color: {{ $sentiment['color'] }};">
                                {{ number_format($sentiment['percentage'], 1) }}%
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3><i class="ri-building-line" style="margin-right: 8px; color: var(--brand);"></i>Satker Paling Aktif Memberi Masukan</h3>
            </div>
            <div class="card-body">
                <div class="satker-list">
                    @forelse($topSatkers as $index => $satker)
                        <div class="satker-item">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 36px; height: 36px; border-radius: 12px; background: var(--bg-body); color: var(--brand); display: flex; align-items: center; justify-content: center; font-weight: 800;">
                                        {{ $index + 1 }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-main);">{{ $satker->satker_name }}</div>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">{{ number_format($satker->total_feedback) }} testimoni</div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 18px; font-weight: 800; color: var(--text-main);">{{ number_format((float) $satker->average_rating, 1) }}</div>
                                    <div style="font-size: 12px; color: #f59e0b;">rata-rata bintang</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 24px; text-align: center; color: var(--text-muted); background: var(--bg-body); border-radius: 16px; border: 1px dashed var(--border-color);">
                            Belum ada satker yang tercatat mengirim masukan.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="spotlight-grid">
        <div class="spotlight-card" style="background: linear-gradient(180deg, #ffffff, #f7fff8); border-color: rgba(16, 185, 129, 0.18);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <div>
                    <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--success);">Sorotan Positif</div>
                    <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 4px;">Suara terbaik dari lapangan</div>
                </div>
                <div style="width: 46px; height: 46px; border-radius: 14px; background: var(--success-bg); color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ri-thumb-up-line"></i>
                </div>
            </div>

            @if($latestPositive)
                <div class="spotlight-quote">
                    "{{ $latestPositive->message }}"
                </div>
                <div style="font-size: 13px; color: var(--text-muted);">
                    <strong style="color: var(--text-main);">{{ $latestPositive->user->name ?? 'Personel' }}</strong>
                    • {{ $latestPositive->user->satker->name ?? 'Tanpa Satker' }}
                    • {{ $latestPositive->created_at->format('d M Y, H:i') }}
                </div>
            @else
                <div class="spotlight-quote" style="display: flex; align-items: center; justify-content: center; text-align: center;">
                    Belum ada testimoni positif yang menonjol untuk ditampilkan.
                </div>
            @endif
        </div>

        <div class="spotlight-card" style="background: linear-gradient(180deg, #ffffff, #fff7f7); border-color: rgba(239, 68, 68, 0.18);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <div>
                    <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--danger);">Perlu Tindak Lanjut</div>
                    <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 4px;">Masukan yang layak diprioritaskan</div>
                </div>
                <div style="width: 46px; height: 46px; border-radius: 14px; background: var(--danger-bg); color: var(--danger); display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ri-error-warning-line"></i>
                </div>
            </div>

            @if($latestNeedsAttention)
                <div class="spotlight-quote">
                    "{{ $latestNeedsAttention->message }}"
                </div>
                <div style="font-size: 13px; color: var(--text-muted);">
                    <strong style="color: var(--text-main);">{{ $latestNeedsAttention->user->name ?? 'Personel' }}</strong>
                    • {{ $latestNeedsAttention->user->satker->name ?? 'Tanpa Satker' }}
                    • {{ $latestNeedsAttention->created_at->format('d M Y, H:i') }}
                </div>
            @else
                <div class="spotlight-quote" style="display: flex; align-items: center; justify-content: center; text-align: center; background: var(--success-bg); border-color: rgba(16, 185, 129, 0.18); color: var(--success);">
                    Belum ada testimoni kritis. Ini sinyal bagus untuk kualitas layanan saat ini.
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3><i class="ri-message-2-line" style="margin-right: 8px; color: var(--brand);"></i>Pesan Testimoni Terbaru</h3>
        </div>
        <div class="card-body">
            <div class="testimonial-grid">
                @php
                    $catIcons = [
                        'tutup_kepala' => ['icon' => 'ri-shield-user-line', 'bg' => '#eff6ff', 'color' => '#2563eb'],
                        'tutup_badan'  => ['icon' => 'ri-t-shirt-2-line', 'bg' => '#f0fdf4', 'color' => '#059669'],
                        'tutup_kaki'   => ['icon' => 'ri-footprint-line', 'bg' => '#fff7ed', 'color' => '#d97706'],
                    ];
                @endphp
                @foreach($latestBatches as $batch)
                    @php
                        $firstItem = $batch->first();
                        $user = $firstItem->user;
                        $avgRating = round($batch->avg('rating'), 1);
                    @endphp
                    <div class="testimonial-card">
                        <div class="testimonial-head">
                            <div style="display: flex; gap: 12px;">
                                <div class="avatar-badge">
                                    {{ strtoupper(substr($user->name ?? 'PN', 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 800; color: var(--text-main);">{{ $user->name ?? 'Personel' }}</div>
                                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                        {{ $user->nrp_nip ?? '-' }}
                                        @if($user->satker?->name)
                                            • {{ $user->satker->name }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 12px; color: var(--text-muted);">{{ $firstItem->created_at->format('d M Y, H:i') }}</div>
                            </div>
                        </div>

                        {{-- Category ratings inline --}}
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin: 14px 0;">
                            @foreach($batch as $item)
                                @php $meta = $catIcons[$item->category] ?? null; @endphp
                                @if($meta)
                                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 10px; background: {{ $meta['bg'] }}; border: 1px solid {{ $meta['color'] }}20;">
                                        <i class="{{ $meta['icon'] }}" style="color: {{ $meta['color'] }}; font-size: 14px;"></i>
                                        <span style="font-size: 12px; font-weight: 700; color: {{ $meta['color'] }};">{{ \App\Models\Testimonial::CATEGORIES[$item->category] ?? $item->category }}</span>
                                        <span style="display: inline-flex; gap: 1px; color: #f59e0b; font-size: 12px; margin-left: 2px;">
                                            @for($star = 1; $star <= 5; $star++)
                                                <i class="{{ ($item->rating ?? 5) >= $star ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                                            @endfor
                                        </span>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        @if(filled($firstItem->message))
                            <div class="testimonial-message">
                                {{ $firstItem->message }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
@endsection
