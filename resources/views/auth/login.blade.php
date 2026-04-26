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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 30%;
        }

        .page-backdrop::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(135deg,
                    rgba(8, 2, 2, .45) 0%,
                    rgba(20, 6, 6, .40) 50%,
                    rgba(10, 3, 3, .55) 100%);
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
            max-width: 1000px;
            min-height: 600px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
            animation: cardPop .7s cubic-bezier(.22, 1, .36, 1) both;
            background: #ffffff;
        }

        /* ---- LEFT: DARK BRANDED PANEL ---- */
        .card-brand {
            flex: 1;
            position: relative;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            background: url('{{ asset('logistik_polri_wide.png') }}') center/cover no-repeat;
        }

        .card-brand::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(120, 15, 15, 0.9) 0%, rgba(160, 30, 30, 0.7) 100%);
            z-index: 1;
        }

        .card-brand-inner {
            position: relative;
            z-index: 2;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
        }

        .card-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
        }

        .brand-header-text h3 {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 1px;
        }

        .brand-header-text p {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .card-brand-inner h1 {
            font-size: 36px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 1px;
            line-height: 1.25;
            margin-bottom: 8px;
        }

        .glow-line {
            width: 60px;
            height: 4px;
            background: #ef4444;
            border-radius: 2px;
            margin-bottom: 24px;
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.6);
        }

        .card-brand-inner .desc {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.7;
            font-weight: 400;
            margin-bottom: 40px;
            max-width: 400px;
        }

        /* Feature pills */
        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .pill {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 8px 16px;
            border-radius: 30px;
            backdrop-filter: blur(4px);
            transition: all .3s;
        }

        .pill:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .pill i {
            font-size: 14px;
            color: #fca5a5;
        }

        /* ---- RIGHT: WHITE FORM PANEL ---- */
        .card-form {
            width: 440px;
            background: #ffffff;
            padding: 60px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 36px;
        }

        .form-header h2 {
            font-size: 28px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 8px;
        }

        .form-header p {
            font-size: 14px;
            color: #4B5563;
            font-weight: 500;
            line-height: 1.6;
        }

        /* Input */
        .field {
            margin-bottom: 24px;
        }

        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
        }

        .field-wrap {
            position: relative;
        }

        .field-input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #E5E7EB;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            color: #111827;
            background: #F9FAFB;
            outline: none;
            transition: all .2s ease;
        }

        .field-input:focus {
            border-color: #DC2626;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
        }

        .field-input::placeholder {
            color: #9CA3AF;
        }

        .field-input.pr-icon {
            padding-right: 48px;
        }

        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }

        .toggle-pw {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #9CA3AF;
            transition: color .2s;
            user-select: none;
            z-index: 10;
        }

        .toggle-pw:hover {
            color: #DC2626;
        }

        /* Remember */
        .remember-row {
            display: flex;
            align-items: center;
            margin-bottom: 32px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            color: #4B5563;
            font-weight: 600;
            cursor: pointer;
        }

        .remember-label input[type="checkbox"] {
            accent-color: #DC2626;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 15px;
            background: #DC2626;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all .2s ease;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
        }

        .btn-login:hover {
            background: #B91C1C;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.2);
        }

        /* Help */
        .help-text {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: #6B7280;
            font-weight: 500;
            line-height: 1.6;
        }

        .help-text a {
            color: #DC2626;
            text-decoration: none;
            font-weight: 600;
        }

        .help-text a:hover {
            text-decoration: underline;
        }

        /* Error */
        .error-msg {
            background: #FEF2F2;
            border: 1px solid #FCA5A5;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            color: #B91C1C;
            font-weight: 600;
        }

        .error-msg i {
            font-size: 18px;
            color: #EF4444;
        }

        /* Form footer */
        .form-footer-text {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #F3F4F6;
            font-size: 11.5px;
            color: #9CA3AF;
            font-weight: 500;
        }



        /* ===== ANIMATIONS ===== */
        @keyframes cardPop {
            from {
                opacity: 0;
                transform: scale(.96) translateY(30px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: .5;
            }

            50% {
                transform: scale(1.1);
                opacity: 1;
            }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .login-card-split {
                max-width: 860px;
            }

            .card-brand {
                padding: 44px 36px;
            }

            .card-form {
                width: 380px;
                padding: 44px 36px;
            }

            .card-brand-inner h1 {
                font-size: 30px;
            }
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

            .card-brand-inner h1 {
                font-size: 26px;
            }

            .card-brand-inner .desc {
                font-size: 13px;
                margin-bottom: 20px;
            }

            .feature-pills {
                display: none;
            }

            .card-form {
                width: 100%;
                padding: 36px 32px;
            }

        }

        @media (max-width: 767px) {
            .page-backdrop {
                display: block;
            }

            .page-backdrop::after {
                background: linear-gradient(135deg, rgba(8, 2, 2, .7) 0%, rgba(20, 6, 6, .6) 50%, rgba(10, 3, 3, .8) 100%);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
            }

            body {
                background: transparent;
            }

            .page-center {
                padding: 12px 12px;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }

            .login-card-split {
                width: 100%;
                max-width: 420px;
                background: #ffffff;
                border-radius: 16px;
                box-shadow: 0 24px 60px rgba(0, 0, 0, .6), 0 0 0 1px rgba(255, 255, 255, .1);
                flex-direction: column;
                gap: 0;
                animation: cardPop .6s cubic-bezier(.22, 1, .36, 1) both;
                overflow: hidden;
                position: relative;
                z-index: 2;
            }

            .card-brand {
                display: flex;
                padding: 40px 24px 32px 24px;
                flex: none;
                min-height: auto;
                align-items: center;
                justify-content: center;
            }

            .card-brand-inner {
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .brand-header {
                display: flex;
                flex-direction: column;
                gap: 16px;
                margin-bottom: 0;
                align-items: center;
                text-align: center;
                width: 100%;
            }

            .card-logo {
                width: 76px;
                height: 76px;
                filter: drop-shadow(0 8px 16px rgba(127, 29, 29, .2));
                animation: none;
                margin: 0;
            }

            .brand-header-text h3 {
                font-size: 22px;
                color: #ffffff;
                text-shadow: none;
                letter-spacing: 0.5px;
            }

            .brand-header-text p {
                font-size: 12px;
                color: rgba(255, 255, 255, 0.85);
                font-weight: 500;
                text-transform: uppercase;
            }

            .card-brand-inner h1,
            .card-brand-inner .org-badge,
            .glow-line,
            .card-brand-inner .desc,
            .feature-pills {
                display: none;
            }

            .card-form {
                width: 100%;
                padding: 16px 28px 36px 28px;
                background: transparent;
            }

            .form-header {
                margin-bottom: 24px;
                text-align: center;
            }

            .form-header h2 {
                font-size: 24px;
                margin-bottom: 8px;
                color: #111827;
            }

            .form-header p {
                font-size: 13.5px;
                line-height: 1.6;
                color: #4B5563;
            }

            .field {
                margin-bottom: 20px;
            }

            .field-label {
                margin-bottom: 8px;
                font-size: 13px;
            }

            .field-input {
                padding: 14px 16px;
                border-radius: 12px;
                font-size: 14.5px;
                background: #fafafa;
                border-color: #E8E8E8;
            }

            .field-input:focus {
                background: #fff;
            }

            .field-input.pr-icon {
                padding-right: 46px;
            }

            .toggle-pw {
                right: 16px;
                font-size: 18px;
            }

            .error-msg {
                padding: 12px 14px;
                margin-bottom: 18px;
                font-size: 13px;
            }

            .remember-row {
                margin-bottom: 24px;
            }

            .remember-label {
                font-size: 13px;
            }

            .btn-login {
                padding: 15px;
                font-size: 15.5px;
                border-radius: 12px;
            }

            .help-text {
                margin-top: 24px;
                font-size: 12.5px;
            }

            .form-footer-text {
                margin-top: 24px;
                padding-top: 16px;
                font-size: 11px;
            }
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
                    <h1 style="font-size: 28px; letter-spacing: 1px; line-height: 1.4;">Electronic
                        Measurement<br>Perlengkapan Perorangan</h1>
                    <div class="glow-line" style="margin-top: 24px;"></div>
                    <p class="desc">
                        Platform pencatatan dan pengelolaan data perlengkapan perorangan untuk seluruh personel Polda
                        NTB secara terpadu dan real-time.
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
                    <p>Superadmin, Admin Satker, dan Admin Gudang login dengan Gmail. Personil login memakai NRP/NIP.
                    </p>
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
                            <input type="text" name="login" class="field-input"
                                placeholder="Masukkan Gmail atau NRP/NIP" value="{{ old('login') }}" autofocus required>
                        </div>
                    </div>

                    <div class="field">
                        <label class="field-label">Password</label>
                        <div class="field-wrap">
                            <input type="password" name="password" id="password" class="field-input pr-icon"
                                placeholder="••••••••" required>
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
                    Tidak bisa login? Hubungi administrator Bagian Logistik atau kembali ke <a
                        href="{{ route('home') }}">halaman informasi resmi</a>.
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