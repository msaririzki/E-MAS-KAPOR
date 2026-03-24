<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="keywords" content="{{ $seo['keywords'] }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:image" content="{{ $seo['image'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    <meta name="twitter:image" content="{{ $seo['image'] }}">
    <title>{{ $seo['title'] }}</title>
    <link rel="icon" href="{{ asset('e-mas-kapor.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @foreach($structuredData as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
    @endforeach
    <style>
        :root { --bg:#f8f1e8; --card:rgba(255,250,244,.86); --ink:#201816; --muted:#6d635e; --brand:#8d1f1f; --brand-dark:#571212; --gold:#c79a2c; --line:rgba(93,57,42,.12); --shadow:0 24px 60px rgba(61,28,17,.12); }
        * { box-sizing:border-box; } html { scroll-behavior:smooth; } body { margin:0; font-family:'Sora',sans-serif; color:var(--ink); background:radial-gradient(circle at top left, rgba(199,154,44,.18), transparent 26%), radial-gradient(circle at 85% 10%, rgba(141,31,31,.18), transparent 22%), linear-gradient(180deg, #fbf7f2 0%, #f5eee4 100%); }
        a { color:inherit; text-decoration:none; } .wrap { width:min(1160px, calc(100% - 28px)); margin:0 auto; } .shell { padding:16px 0 40px; }
        .nav,.hero,.card,.banner,.footer { border:1px solid var(--line); background:var(--card); box-shadow:var(--shadow); }
        .nav { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:14px 18px; border-radius:999px; backdrop-filter:blur(16px); }
        .brand { display:flex; gap:12px; align-items:center; } .brand img { width:46px; height:46px; object-fit:contain; } .brand strong,.brand span { display:block; } .brand strong { font-size:14px; letter-spacing:.08em; text-transform:uppercase; } .brand span { font-size:12px; color:var(--muted); }
        .nav-links,.actions,.chips,.footer-actions,.stats,.features,.testimonials { display:flex; gap:12px; flex-wrap:wrap; } .nav-links { align-items:center; justify-content:flex-end; }
        .nav-links a { font-size:13px; color:var(--muted); }
        .btn,.chip { display:inline-flex; align-items:center; justify-content:center; gap:8px; border-radius:999px; font-size:12px; font-weight:700; }
        .btn { padding:12px 18px; border:1px solid transparent; } .btn-primary { color:#fff; background:linear-gradient(135deg, var(--brand), var(--brand-dark)); } .btn-secondary { border-color:rgba(87,18,18,.14); background:rgba(255,255,255,.7); color:var(--brand-dark); } .chip { padding:10px 14px; border:1px solid rgba(87,18,18,.1); background:rgba(255,255,255,.72); color:var(--brand-dark); }
        .hero { margin-top:22px; padding:26px; border-radius:32px; display:grid; grid-template-columns:minmax(0, 1.25fr) minmax(280px, .75fr); gap:20px; background:linear-gradient(140deg, rgba(255,250,243,.92), rgba(255,245,235,.82)), url('{{ asset('bg_polda.jpg') }}') center/cover no-repeat; overflow:hidden; }
        .eyebrow,.panel-tag { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px; font-size:12px; font-weight:700; } .eyebrow { color:var(--brand); text-transform:uppercase; letter-spacing:.12em; } .panel-tag { background:rgba(255,255,255,.08); color:#ffeec7; width:fit-content; }
        h1 { margin:18px 0 16px; font-size:clamp(34px, 6vw, 62px); line-height:1.02; max-width:12ch; } h2 { margin:0 0 10px; font-size:clamp(24px, 4vw, 36px); line-height:1.1; } h3 { margin:0 0 10px; font-size:18px; } p { margin:0; color:var(--muted); line-height:1.8; font-size:14px; }
        .actions,.chips { margin-top:22px; }
        .panel { padding:22px; border-radius:26px; color:#fff; background:linear-gradient(180deg, rgba(87,18,18,.94), rgba(39,11,11,.97)); position:relative; overflow:hidden; } .panel::before,.footer::before { content:''; position:absolute; inset:0; background:radial-gradient(circle at top right, rgba(199,154,44,.32), transparent 34%); pointer-events:none; } .panel > *, .footer > * { position:relative; z-index:1; } .panel p,.panel span { color:rgba(255,246,233,.82); }
        .panel-list { display:grid; gap:14px; margin-top:18px; } .panel-item { display:grid; grid-template-columns:36px 1fr; gap:12px; align-items:start; } .panel-icon { width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; background:rgba(255,255,255,.09); color:#ffe7a8; font-size:18px; }
        .banner { margin-top:18px; padding:16px 18px; border-radius:20px; display:flex; justify-content:space-between; gap:14px; align-items:center; background:linear-gradient(135deg, rgba(199,154,44,.12), rgba(255,255,255,.68)); }
        .section { padding-top:28px; } .section-head { display:flex; justify-content:space-between; align-items:end; gap:14px; margin-bottom:16px; flex-wrap:wrap; } .section-head p { max-width:58ch; }
        .stats,.features,.testimonials { display:grid; gap:16px; } .stats,.features { grid-template-columns:repeat(4, minmax(0, 1fr)); } .testimonials { grid-template-columns:repeat(3, minmax(0, 1fr)); }
        .card { padding:22px; border-radius:24px; backdrop-filter:blur(14px); } .metric { font-size:clamp(28px, 4vw, 42px); color:var(--brand-dark); line-height:1; margin-bottom:12px; }
        .story { display:grid; grid-template-columns:minmax(0, 1fr) minmax(300px, .9fr); gap:16px; } .story ul { list-style:none; padding:0; margin:18px 0 0; display:grid; gap:14px; } .story li { display:grid; grid-template-columns:28px 1fr; gap:12px; align-items:start; color:var(--muted); font-size:14px; line-height:1.8; } .story li i { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; background:rgba(141,31,31,.08); color:var(--brand); }
        .faq { display:grid; gap:12px; } details { border:1px solid var(--line); border-radius:18px; background:rgba(255,251,246,.84); box-shadow:0 18px 40px rgba(63,32,18,.06); } summary { list-style:none; cursor:pointer; padding:18px 20px; font-weight:700; display:flex; justify-content:space-between; gap:12px; } summary::-webkit-details-marker { display:none; } details div { padding:0 20px 18px; color:var(--muted); line-height:1.8; font-size:14px; }
        .badge { display:inline-flex; gap:8px; align-items:center; margin:0 8px 12px 0; padding:8px 12px; border-radius:999px; font-size:12px; font-weight:700; color:var(--brand); background:rgba(141,31,31,.08); }
        .footer { margin-top:28px; padding:22px; border-radius:30px; position:relative; color:#fff; background:linear-gradient(140deg, rgba(87,18,18,.96), rgba(39,11,11,.96)); overflow:hidden; } .footer p,.footer .note { color:rgba(255,244,235,.8); } .note { margin-top:14px; font-size:12px; }
        @media (max-width:1024px) { .hero,.story,.stats,.features { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
        @media (max-width:780px) { .nav,.banner,.footer,.section-head { flex-direction:column; align-items:stretch; } .nav-links,.footer-actions { justify-content:flex-start; } .hero,.story,.stats,.features,.testimonials { grid-template-columns:1fr; } .hero { padding:22px; } h1 { max-width:100%; } }
        @media (max-width:560px) { .btn,.chip { width:100%; } }
    </style>
</head>

<body>
    <div class="shell">
        <div class="wrap">
            <header class="nav">
                <a href="{{ route('home') }}" class="brand">
                    <img src="{{ asset('e-mas-kapor.png') }}" alt="Logo E-MAS KAPOR">
                    <div class="brand-text">
                        <strong>E-MAS KAPOR</strong>
                        <span>Biro Logistik Polda NTB</span>
                    </div>
                </a>
                <nav class="nav-links">
                    <a href="#fitur">Fitur</a>
                    <a href="#pencarian">Keyword</a>
                    <a href="#faq">FAQ</a>
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary">
                        <i class="ri-login-box-line"></i>{{ auth()->check() ? 'Buka Dashboard' : 'Masuk ke Sistem' }}
                    </a>
                </nav>
            </header>

            <section class="hero">
                <div>
                    <span class="eyebrow"><i class="ri-shield-star-line"></i>Domain Resmi {{ $seo['domainHost'] }}</span>
                    <h1>Sistem Informasi Manajemen Kapor Biro Logistik Polda NTB</h1>
                    <p>E-MAS KAPOR membantu pendataan ukuran kapor personel, rekap per satker, monitoring logistik, dan perencanaan distribusi yang lebih presisi. Halaman publik ini dibuat agar pencarian seperti <strong>Biro Logistik Polda NTB</strong>, <strong>E-MAS KAPOR</strong>, dan <strong>login E-MAS KAPOR</strong> lebih mudah menemukan domain resmi.</p>
                    <div class="actions">
                        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary"><i class="ri-arrow-right-up-line"></i>{{ auth()->check() ? 'Lanjut ke Dashboard' : 'Login Personel / Admin' }}</a>
                        <a href="#fitur" class="btn btn-secondary"><i class="ri-focus-3-line"></i>Pelajari Fitur</a>
                    </div>
                    <div class="chips">
                        @foreach(array_slice($searchIntents, 0, 4) as $intent)
                            <span class="chip"><i class="ri-search-line"></i>{{ $intent }}</span>
                        @endforeach
                    </div>
                </div>
                <aside class="panel">
                    <span class="panel-tag">Arah SEO yang benar</span>
                    <h3>Landing page publik menggantikan dominasi halaman login di hasil pencarian.</h3>
                    <p>Google butuh halaman yang menjelaskan siapa instansinya, apa fungsi sistemnya, dan kenapa domain ini relevan untuk pencarian logistik Polda NTB.</p>
                    <div class="panel-list">
                        <div class="panel-item"><span class="panel-icon"><i class="ri-global-line"></i></span><div><strong>Target brand search</strong><span>Pencarian nama instansi, singkatan biro, dan nama aplikasi diarahkan ke halaman utama.</span></div></div>
                        <div class="panel-item"><span class="panel-icon"><i class="ri-file-search-line"></i></span><div><strong>Konten yang bisa diindeks</strong><span>Penjelasan manfaat, fitur, FAQ, dan konteks logistik dibuat terbaca jelas oleh mesin pencari.</span></div></div>
                        <div class="panel-item"><span class="panel-icon"><i class="ri-route-line"></i></span><div><strong>Sinyal teknis rapi</strong><span>Canonical, sitemap, robots, dan schema.org ditambahkan agar crawler lebih mudah memahami situs.</span></div></div>
                    </div>
                </aside>
            </section>

            <div class="banner">
                <div>
                    <strong>Penguatan reputasi digital</strong>
                    <p>Inovasi digital E-MAS KAPOR juga sudah diberitakan secara publik sebagai bagian dari transformasi digital Biro Logistik Polda NTB.</p>
                </div>
                <a href="{{ $seo['awardArticleUrl'] }}" class="btn btn-secondary" target="_blank" rel="noopener noreferrer"><i class="ri-newspaper-line"></i>Buka Berita Penghargaan</a>
            </div>

            <section class="section" id="fitur">
                <div class="section-head">
                    <div><h2>Sinyal yang menjelaskan nilai sistem</h2></div>
                    <p>Bagian ini memakai kata-kata yang relevan dengan pencarian publik sekaligus tetap natural untuk pengguna manusia. Fokusnya bukan keyword stuffing, tetapi kejelasan fungsi layanan.</p>
                </div>
                <div class="stats">
                    @foreach($metricCards as $card)
                        <article class="card"><div class="metric">{{ $card['value'] }}</div><strong>{{ $card['label'] }}</strong><p>{{ $card['description'] }}</p></article>
                    @endforeach
                </div>
            </section>

            <section class="section">
                <div class="features">
                    @foreach($featureCards as $index => $feature)
                        <article class="card"><span class="badge">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $feature['title'] }}</h3><p>{{ $feature['description'] }}</p></article>
                    @endforeach
                </div>
            </section>

            <section class="section">
                <div class="story">
                    <article class="card">
                        <h2>Kenapa ini lebih kuat untuk SEO</h2>
                        <p>Sebelumnya root domain hanya mengarah ke login. Itu membuat mesin pencari melihat situs ini sebagai halaman form, bukan sebagai representasi resmi Biro Logistik Polda NTB. Sekarang halaman utama menjelaskan konteks sistem, manfaat layanan, dan topik yang paling sering dicari.</p>
                        <ul>
                            <li><i class="ri-check-line"></i><span>Nama instansi dan nama aplikasi muncul jelas pada judul, heading, deskripsi, dan schema.</span></li>
                            <li><i class="ri-check-line"></i><span>Halaman login tetap ada untuk akses pengguna, tetapi diberi sinyal <em>noindex</em> agar tidak menjadi target hasil pencarian.</span></li>
                            <li><i class="ri-check-line"></i><span>Domain utama bisa dibentuk menjadi sumber resmi untuk pencarian brand seperti <em>biro log polda ntb</em> dan <em>e-mas kapor polda ntb</em>.</span></li>
                        </ul>
                    </article>
                    <article class="card" id="pencarian">
                        <h2>Pencarian yang realistis di Google</h2>
                        <p>Ini adalah frasa yang paling masuk akal untuk diincar berdasarkan nama instansi, fungsi aplikasi, dan cara orang biasanya mencari akses resmi sistem internal.</p>
                        <div class="chips" style="margin-top:18px;">
                            @foreach($searchIntents as $intent)
                                <span class="chip">{{ $intent }}</span>
                            @endforeach
                        </div>
                    </article>
                </div>
            </section>

            @if($testimonials->isNotEmpty())
                <section class="section">
                    <div class="section-head">
                        <div><h2>Umpan balik yang memperkuat trust</h2></div>
                        <p>Testimoni aktual bisa membantu memperkaya konteks halaman dan memperlihatkan bahwa sistem ini benar-benar dipakai oleh lingkungan kerja yang relevan.</p>
                    </div>
                    <div class="testimonials">
                        @foreach($testimonials as $testimonial)
                            <article class="card">
                                <span class="badge"><i class="ri-star-fill"></i>{{ $testimonial->rating ?? 5 }}/5</span>
                                @if(optional($testimonial->user?->satker)->name)
                                    <span class="badge">{{ $testimonial->user->satker->name }}</span>
                                @endif
                                <p style="margin-top:8px;">{{ $testimonial->message }}</p>
                                <div style="margin-top:16px; font-size:13px;">
                                    <strong>{{ $testimonial->user?->name ?? 'Pengguna Sistem' }}</strong><br>
                                    <span style="color:var(--muted);">{{ $testimonial->created_at?->translatedFormat('d F Y') }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="section" id="faq">
                <div class="section-head">
                    <div><h2>FAQ yang menangkap intent pencarian</h2></div>
                    <p>FAQ dibuat untuk menjawab pertanyaan yang lazim muncul saat seseorang mencari informasi tentang E-MAS KAPOR atau Biro Logistik Polda NTB.</p>
                </div>
                <div class="faq">
                    @foreach($faqItems as $faq)
                        <details><summary><span>{{ $faq['question'] }}</span><i class="ri-arrow-down-s-line"></i></summary><div>{{ $faq['answer'] }}</div></details>
                    @endforeach
                </div>
            </section>

            <footer class="footer">
                <div>
                    <strong>E-MAS KAPOR untuk Biro Logistik Polda NTB</strong>
                    <p>Gunakan satu domain utama sebagai alamat resmi, lalu arahkan domain alternatif dengan 301 redirect ke domain utama agar sinyal SEO tidak terpecah.</p>
                    <div class="note">Canonical saat ini: {{ $seo['canonical'] }}</div>
                </div>
                <div class="footer-actions">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary"><i class="ri-login-box-line"></i>{{ auth()->check() ? 'Dashboard' : 'Login Resmi' }}</a>
                    <a href="{{ route('home') }}" class="btn btn-secondary"><i class="ri-home-5-line"></i>Halaman Utama</a>
                </div>
            </footer>
        </div>
    </div>
</body>

</html>
