<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $siteUrl = rtrim(config('app.url') ?: request()->getSchemeAndHttpHost(), '/');
        $loginDescription = 'Halaman login resmi E-MAS KAPOR untuk personel dan operator Biro Logistik Polda NTB.';
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $loginDescription }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="{{ $siteUrl }}/login">
    <meta property="og:type" content="website">
    <meta property="og:description" content="{{ $loginDescription }}">
    <meta property="og:url" content="{{ $siteUrl }}/login">
    <meta property="og:image" content="{{ $siteUrl }}/e-mas-kapor.png">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:description" content="{{ $loginDescription }}">
    <meta name="twitter:image" content="{{ $siteUrl }}/e-mas-kapor.png">
    <title>Login E-MAS KAPOR | Biro Logistik Polda NTB</title>
    <link rel="icon" href="{{ asset('e-mas-kapor.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ===== FULL-SCREEN BACKGROUND ===== */
        .page-backdrop {
            position: fixed;
            inset: 0;
            z-index: 0;
        }
        .page-backdrop img {
            width: 100%; height: 100%;
            object-fit: cover;
            object-position: center 30%;
        }
        .page-backdrop::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(135deg,
                    rgba(8,2,2,.45) 0%,
                    rgba(20,6,6,.40) 50%,
                    rgba(10,3,3,.55) 100%);
        }

        /* ===== CENTERED WRAPPER ===== */
        .page-center {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 24px;
        }



        /* ===== THE MAIN CARD ===== */
        .login-card-split {
            display: flex;
            width: 100%;
            max-width: 980px;
            min-height: 560px;
            border-radius: 28px;
            overflow: hidden;
            box-shadow:
                0 40px 80px rgba(0,0,0,.35),
                0 0 0 1px rgba(255,255,255,.06);
            animation: cardPop .7s cubic-bezier(.22,1,.36,1) both;
        }

        /* ---- LEFT: DARK BRANDED PANEL ---- */
        .card-brand {
            flex: 1;
            position: relative;
            padding: 52px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            background-color: #7F1D1D; /* Solid dark red (Tailwind red-900) */
        }

        .card-brand-inner {
            position: relative;
            z-index: 2;
        }
        .brand-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
        }
        .card-logo {
            width: 72px; height: 72px;
            object-fit: contain;
            filter: drop-shadow(0 4px 16px rgba(0,0,0,.35));
            animation: float 5s ease-in-out infinite;
        }
        .brand-header-text h3 {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(0,0,0,.4);
        }
        .brand-header-text p {
            font-size: 11px;
            color: rgba(255,255,255,.7);
            font-weight: 500;
            letter-spacing: .5px;
        }
        .card-brand-inner h1 {
            font-size: 34px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 2px;
            line-height: 1.2;
            margin-bottom: 6px;
        }
        .card-brand-inner .org-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,.85);
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.15);
            padding: 4px 14px;
            border-radius: 20px;
            letter-spacing: .5px;
            margin-bottom: 28px;
            backdrop-filter: blur(6px);
        }

        /* Red glowing line */
        .glow-line {
            width: 50px; height: 3px;
            background: linear-gradient(90deg, #EF5350, #D32F2F);
            border-radius: 2px;
            margin-bottom: 24px;
            box-shadow: 0 0 16px rgba(229,57,53,.5);
        }

        .card-brand-inner .desc {
            font-size: 15px;
            color: rgba(255,255,255,.75);
            line-height: 1.75;
            font-weight: 300;
            margin-bottom: 36px;
            max-width: 380px;
        }

        /* Feature pills */
        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .pill {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 500;
            color: rgba(255,255,255,.75);
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.1);
            padding: 7px 14px;
            border-radius: 20px;
            backdrop-filter: blur(4px);
            transition: all .25s;
        }
        .pill:hover {
            background: rgba(255,255,255,.14);
            border-color: rgba(255,255,255,.2);
        }
        .pill i {
            font-size: 14px;
            color: #FF8A80;
        }

        /* ---- RIGHT: WHITE FORM PANEL ---- */
        .card-form {
            width: 420px;
            background: #fff;
            padding: 52px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 36px;
        }
        .form-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        .form-header p {
            font-size: 14px;
            color: #999;
            font-weight: 400;
            line-height: 1.6;
        }

        /* Input */
        .field { margin-bottom: 22px; }
        .field-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #444;
            margin-bottom: 8px;
            letter-spacing: .3px;
        }
        .field-wrap { position: relative; }
        .field-input {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid #E8E8E8;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            color: #1a1a2e;
            background: #FAFAFA;
            outline: none;
            transition: all .25s ease;
        }
        .field-input:focus {
            border-color: #D32F2F;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(211,47,47,.07);
        }
        .field-input::placeholder { color: #ccc; }
        .field-input.pr-icon { padding-right: 48px; }

        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }

        .toggle-pw {
            position: absolute;
            right: 16px; top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #bbb;
            transition: color .2s;
            user-select: none;
            z-index: 10;
        }
        .toggle-pw:hover { color: #D32F2F; }

        /* Remember */
        .remember-row {
            display: flex;
            align-items: center;
            margin-bottom: 28px;
        }
        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #888;
            cursor: pointer;
        }
        .remember-label input[type="checkbox"] {
            accent-color: #D32F2F;
            width: 16px; height: 16px;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #C62828, #E53935);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            letter-spacing: .3px;
            transition: all .3s ease;
            box-shadow: 0 4px 16px rgba(211,47,47,.3);
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #B71C1C, #D32F2F);
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(211,47,47,.4);
        }
        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(211,47,47,.25);
        }

        /* Help */
        .help-text {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #aaa;
            line-height: 1.6;
        }
        .help-text a { color: #D32F2F; text-decoration: none; font-weight: 600; }
        .help-text a:hover { text-decoration: underline; }

        /* Error */
        .error-msg {
            background: #FFF5F5;
            border: 1px solid #FED7D7;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #C53030;
            font-weight: 500;
        }
        .error-msg i { font-size: 18px; color: #E53E3E; }

        /* Form footer */
        .form-footer-text {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #f3f3f3;
            font-size: 10.5px;
            color: #ccc;
        }



        /* ===== ANIMATIONS ===== */
        @keyframes cardPop {
            from { opacity: 0; transform: scale(.96) translateY(30px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-8px); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: .5; }
            50%      { transform: scale(1.1); opacity: 1; }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .login-card-split { max-width: 860px; }
            .card-brand { padding: 44px 36px; }
            .card-form { width: 380px; padding: 44px 36px; }
            .card-brand-inner h1 { font-size: 30px; }
        }
        @media (max-width: 820px) {
            .login-card-split {
                flex-direction: column;
                max-width: 480px;
                min-height: auto;
            }
            .card-brand {
                padding: 36px 32px;
            }
            .card-brand-inner h1 { font-size: 26px; }
            .card-brand-inner .desc { font-size: 13px; margin-bottom: 20px; }
            .feature-pills { display: none; }
            .card-form { width: 100%; padding: 36px 32px; }

        }
        @media (max-width: 480px) {
            .page-center { padding: 16px 12px; padding-top: 80px; }
            .login-card-split { border-radius: 22px; }
            .card-brand { padding: 28px 24px; }
            .card-brand-inner h1 { font-size: 22px; }
            .card-logo { width: 64px; height: 64px; margin-bottom: 18px; }
            .glow-line { margin-bottom: 16px; }
            .card-brand-inner .desc { font-size: 12.5px; }
            .card-form { padding: 28px 22px; }
            .form-header h2 { font-size: 22px; }
        }
    </style>
</head>
<body>
    {{-- Full-screen background --}}
    <div class="page-backdrop">
        <img src="{{ asset('bg_polda.jpg') }}" alt="Markas Polda NTB">
    </div>

    <div class="page-center">


        {{-- ===== FLOATING SPLIT CARD ===== --}}
        <div class="login-card-split">
            {{-- Left — Branding --}}
            <div class="card-brand">
                <div class="card-brand-inner">
                    <div class="brand-header">
                        <img src="{{ asset('e-mas-kapor.png') }}" alt="Logo E-Mas Kapor" class="card-logo">
                        <div class="brand-header-text">
                            <h3>E-MAS KAPOR</h3>
                            <p>Biro Logistik Polda NTB</p>
                        </div>
                    </div>
                    <h1>Sistem Manajemen<br>Data Ukuran Kapor</h1>
                    <div class="glow-line" style="margin-top: 24px;"></div>
                    <p class="desc">
                        Platform pencatatan dan pengelolaan data perlengkapan perorangan untuk seluruh personel Polda NTB secara terpadu dan real-time.
                    </p>
                    <div class="feature-pills">
                        <div class="pill"><i class="ri-shield-check-line"></i> Multi-role Access</div>
                        <div class="pill"><i class="ri-team-line"></i> Sinkronisasi Satker</div>
                        <div class="pill"><i class="ri-bar-chart-box-line"></i> Statistik Real-time</div>
                        <div class="pill"><i class="ri-shirt-line"></i> Input Cepat</div>
                    </div>
                </div>
            </div>

            {{-- Right — Form --}}
            <div class="card-form">
                <div class="form-header">
                    <h2>Selamat Datang</h2>
                    <p>Superadmin dan admin login dengan Gmail, personil tetap menggunakan NRP/NIP.</p>
                </div>

                @if($errors->any())
                <div class="error-msg">
                    <i class="ri-error-warning-line"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="field">
                        <label class="field-label">Gmail / NRP / NIP</label>
                        <div class="field-wrap">
                            <input
                                type="text"
                                name="login"
                                class="field-input"
                                placeholder="Masukkan Gmail atau NRP/NIP"
                                value="{{ old('login') }}"
                                autofocus
                                required
                            >
                        </div>
                    </div>

                    <div class="field">
                        <label class="field-label">Password</label>
                        <div class="field-wrap">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="field-input pr-icon"
                                placeholder="••••••••"
                                required
                            >
                            <i class="ri-eye-line toggle-pw" id="togglePassword"></i>
                        </div>
                    </div>

                    <div class="remember-row">
                        <label class="remember-label">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            Ingat saya
                        </label>
                    </div>

                    <button type="submit" class="btn-login">
                        Masuk ke Sistem
                    </button>
                </form>

                <div class="help-text">
                    Tidak bisa login? Hubungi administrator Bagian Logistik atau kembali ke <a href="{{ route('home') }}">halaman informasi resmi</a>.
                </div>

                <div class="form-footer-text">
                    &copy; {{ date('Y') }} E-Mas Kapor — Polda Nusa Tenggara Barat
                </div>
            </div>
        </div>


    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            // toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // toggle the eye slash icon
            this.classList.toggle('ri-eye-line');
            this.classList.toggle('ri-eye-off-line');
        });
    </script>
</body>
</html>
