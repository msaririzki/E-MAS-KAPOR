<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — SI-KAPOR Polda NTB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1C2E4A;
            --primary-light: #2A4365;
            --accent: #D4AF37;
            --accent-light: #E8C94A;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #0F1D30 0%, #1C2E4A 40%, #2A4365 100%);
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(212,175,55,.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(59,130,246,.06) 0%, transparent 40%),
                radial-gradient(circle at 60% 80%, rgba(212,175,55,.04) 0%, transparent 40%);
        }

        /* Grid pattern overlay */
        body::after {
            content: '';
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.02) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        .login-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Left panel - branding */
        .login-left {
            flex: 1;
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            padding: 60px;
            color: #fff;
        }
        .login-left .logo-wrap {
            text-align: center;
            margin-bottom: 48px;
        }
        .login-left .logo-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 36px; font-weight: 900;
            color: var(--primary);
            margin: 0 auto 20px;
            box-shadow: 0 8px 32px rgba(212,175,55,.3);
        }
        .login-left h1 {
            font-size: 32px; font-weight: 800;
            letter-spacing: 2px;
            background: linear-gradient(to right, #fff, rgba(255,255,255,.8));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .login-left .subtitle {
            font-size: 14px; opacity: .55;
            margin-top: 8px; letter-spacing: .5px;
        }
        .login-left .tagline {
            font-size: 15px; opacity: .7; line-height: 1.7;
            max-width: 380px; text-align: center;
        }
        .feature-list {
            margin-top: 40px; display: flex; flex-direction: column; gap: 16px;
        }
        .feature-item {
            display: flex; align-items: center; gap: 14px;
            font-size: 13.5px; opacity: .65;
        }
        .feature-item i {
            font-size: 20px; color: var(--accent); opacity: 1;
            width: 36px; height: 36px;
            background: rgba(212,175,55,.1);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }

        /* Right panel - form */
        .login-right {
            width: 480px;
            display: flex; align-items: center; justify-content: center;
            padding: 40px;
        }
        .login-card {
            width: 100%;
            background: rgba(255,255,255,.98);
            border-radius: 20px;
            padding: 44px 40px;
            box-shadow: 0 25px 60px rgba(0,0,0,.25);
            backdrop-filter: blur(20px);
        }
        .login-card h2 {
            font-size: 22px; font-weight: 800; color: var(--primary);
            margin-bottom: 4px;
        }
        .login-card .card-sub {
            font-size: 13.5px; color: #64748B; margin-bottom: 32px;
        }

        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block; font-size: 12.5px; font-weight: 600; color: #475569;
            margin-bottom: 8px; letter-spacing: .3px;
        }
        .input-wrap {
            position: relative;
        }
        .form-icon-left {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            font-size: 18px; color: #94A3B8;
            pointer-events: none;
        }
        .form-input {
            width: 100%; padding: 12px 14px 12px 44px;
            border: 2px solid #E2E8F0; border-radius: 10px;
            font-size: 14px; font-family: 'Inter', sans-serif;
            transition: all .2s; background: #F8FAFC;
            outline: none;
        }
        .form-input:focus {
            border-color: var(--accent); background: #fff;
            box-shadow: 0 0 0 4px rgba(212,175,55,.12);
        }
        .form-input::placeholder { color: #CBD5E1; }
        
        .password-toggle {
            position: absolute;
            right: 14px;
            left: auto;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #94A3B8;
            transition: color .2s;
            user-select: none;
            z-index: 10;
        }
        .password-toggle:hover {
            color: var(--accent);
        }

        .remember-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
        }
        .remember-label {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: #64748B; cursor: pointer;
        }
        .remember-label input[type="checkbox"] {
            accent-color: var(--accent); width: 16px; height: 16px;
        }

        .btn-login {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700; font-family: 'Inter', sans-serif;
            cursor: pointer; transition: all .2s;
            letter-spacing: .5px;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(28,46,74,.35);
        }

        .error-msg {
            background: #FEF2F2; border: 1px solid #FECACA;
            border-radius: 10px; padding: 12px 16px;
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
            font-size: 13px; color: #DC2626;
        }
        .error-msg i { font-size: 18px; }

        .login-footer {
            text-align: center; margin-top: 28px;
            font-size: 11.5px; color: #94A3B8;
        }

        @media (max-width: 900px) {
            .login-left { display: none; }
            .login-right { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="logo-wrap">
                <div class="logo-icon">SK</div>
                <h1>SI-KAPOR</h1>
                <p class="subtitle">Polda Nusa Tenggara Barat</p>
            </div>
            <p class="tagline">
                Sistem Manajemen Data Ukuran Kapor (Perlengkapan Perorangan) untuk seluruh personil Polda NTB.
            </p>
            <div class="feature-list">
                <div class="feature-item">
                    <i class="ri-shield-check-line"></i>
                    <span>Multi-role access control</span>
                </div>
                <div class="feature-item">
                    <i class="ri-team-line"></i>
                    <span>Data personil ter-sinkronisasi per Satker</span>
                </div>
                <div class="feature-item">
                    <i class="ri-bar-chart-box-line"></i>
                    <span>Laporan & rekap statistik real-time</span>
                </div>
                <div class="feature-item">
                    <i class="ri-shirt-line"></i>
                    <span>Input ukuran kapor mudah & cepat</span>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-card">
                <h2>Masuk ke SI-KAPOR</h2>
                <p class="card-sub">Gunakan NRP/NIP dan password Anda untuk login.</p>

                @if($errors->any())
                <div class="error-msg">
                    <i class="ri-error-warning-line"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">NRP / NIP</label>
                        <div class="input-wrap">
                            <i class="ri-id-card-line form-icon-left"></i>
                            <input
                                type="text"
                                name="nrp_nip"
                                class="form-input"
                                placeholder="Masukkan NRP atau NIP"
                                value="{{ old('nrp_nip') }}"
                                autofocus
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrap">
                            <i class="ri-lock-2-line form-icon-left"></i>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-input"
                                style="padding-right: 44px;"
                                placeholder="••••••••"
                                required
                            >
                            <i class="ri-eye-line password-toggle" id="togglePassword"></i>
                        </div>
                    </div>

                    <div class="remember-row">
                        <label class="remember-label">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            Ingat saya
                        </label>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="ri-login-box-line"></i> Masuk
                    </button>
                </form>

                <div class="login-footer">
                    © {{ date('Y') }} SI-KAPOR — Polda Nusa Tenggara Barat
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
