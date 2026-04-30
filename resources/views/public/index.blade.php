<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <title>E-MAS KAPOR | Biro Logistik Polda NTB</title>
    <meta name="description" content="E-MAS KAPOR adalah sistem informasi manajemen perlengkapan perorangan Biro Logistik Polda NTB untuk pendataan kapor, kebutuhan satker, pengadaan, gudang, distribusi, dan pelaporan.">
    <link rel="canonical" href="{{ url('/') }}">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="E-MAS KAPOR | Biro Logistik Polda NTB">
    <meta property="og:description" content="E-MAS KAPOR adalah sistem informasi manajemen perlengkapan perorangan Biro Logistik Polda NTB untuk pendataan kapor, kebutuhan satker, pengadaan, gudang, distribusi, dan pelaporan.">
    <meta property="og:image" content="{{ asset('e-mas-kapor.png') }}">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="{{ url('/') }}">
    <meta name="twitter:title" content="E-MAS KAPOR | Biro Logistik Polda NTB">
    <meta name="twitter:description" content="E-MAS KAPOR adalah sistem informasi manajemen perlengkapan perorangan Biro Logistik Polda NTB untuk pendataan kapor, kebutuhan satker, pengadaan, gudang, distribusi, dan pelaporan.">
    <meta name="twitter:image" content="{{ asset('e-mas-kapor.png') }}">
    
    <link rel="icon" href="{{ asset('e-mas-kapor.png') }}" type="image/png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    
    <!-- JSON-LD -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "GovernmentService",
      "name": "E-MAS KAPOR",
      "description": "Sistem informasi manajemen perlengkapan perorangan Biro Logistik Polda NTB untuk pendataan kapor, kebutuhan satker, pengadaan, gudang, distribusi, dan pelaporan.",
      "provider": {
        "@@type": "GovernmentOrganization",
        "name": "Biro Logistik Polda NTB",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('e-mas-kapor.png') }}"
      },
      "url": "{{ url('/') }}",
      "areaServed": "Nusa Tenggara Barat, Indonesia"
    }
    </script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        
        .hero-bg {
            background-image: linear-gradient(to bottom, rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.95)), url('{{ asset("bg_polda.jpg") }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .gold-accent {
            color: #eab308; /* Tailwind yellow-500 */
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased flex flex-col min-h-screen">

    <!-- Header / Navbar -->
    <header class="fixed w-full top-0 z-50 glass-panel border-b border-white/10 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('e-mas-kapor.png') }}" alt="Logo" class="h-10 w-auto drop-shadow-md">
                    <div>
                        <h1 class="text-white font-bold text-lg leading-tight tracking-wide">E-MAS KAPOR</h1>
                        <p class="text-slate-300 text-xs font-medium uppercase tracking-wider">Biro Logistik Polda NTB</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg font-semibold text-sm transition-all duration-200 bg-red-700 hover:bg-red-600 text-white shadow-lg shadow-red-900/50 border border-red-500/30">
                        Masuk
                        <i class="ri-login-circle-line text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative hero-bg min-h-[90vh] flex items-center pt-20 pb-16 overflow-hidden">
        <!-- Decorative elements -->
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-slate-900/50 to-slate-900 z-0"></div>
        <div class="absolute -top-[30%] -right-[10%] w-[70%] h-[70%] rounded-full bg-red-900/20 blur-[120px] pointer-events-none z-0"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full flex flex-col items-center text-center">
            
            <div class="mb-8 animate-fade-in-up">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-yellow-500/30 bg-yellow-500/10 text-yellow-400 text-xs font-semibold uppercase tracking-widest backdrop-blur-sm">
                    <i class="ri-lock-2-line"></i> Akses Terbatas
                </span>
            </div>
            
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white tracking-tight mb-6 drop-shadow-lg leading-tight">
                <span class="block">E-MAS KAPOR</span>
                <span class="block text-2xl md:text-3xl lg:text-4xl font-semibold text-slate-300 mt-2">Electronic Measurement <span class="gold-accent">Perlengkapan Perorangan</span></span>
            </h1>
            
            <p class="mt-4 text-base md:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed mb-10 drop-shadow">
                Sistem informasi manajemen perlengkapan perorangan Biro Logistik Polda NTB untuk pendataan, perencanaan, dan distribusi yang lebih terintegrasi.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-center">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl font-semibold text-base transition-all duration-300 bg-red-700 hover:bg-red-600 text-white shadow-xl shadow-red-900/40 border border-red-500/50 hover:-translate-y-0.5 w-full sm:w-auto">
                    Masuk ke Sistem
                    <i class="ri-arrow-right-line"></i>
                </a>
                <a href="#fitur" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl font-semibold text-base transition-all duration-300 bg-white/10 hover:bg-white/20 text-white border border-white/20 backdrop-blur-md w-full sm:w-auto">
                    Pelajari Fitur
                    <i class="ri-arrow-down-line"></i>
                </a>
            </div>
            
            <div class="mt-12">
                <p class="text-slate-400 text-sm flex items-center gap-2 justify-center">
                    <i class="ri-information-line"></i>
                    Khusus untuk personel dan operator terdaftar
                </p>
            </div>
        </div>
        
        <!-- Shape divider -->
        <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none z-10">
            <svg class="relative block w-full h-[50px] md:h-[80px]" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M1200 120L0 16.48 0 0 1200 0 1200 120z" class="fill-slate-900"></path>
                <path d="M1200 120L0 120 0 16.48 1200 120z" class="fill-slate-50"></path>
            </svg>
        </div>
    </section>

    <!-- Features Section -->
    <section id="fitur" class="py-20 bg-slate-50 flex-grow relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl font-bold text-slate-900 mb-4">Modul Utama Sistem</h2>
                <div class="h-1 w-20 bg-red-700 mx-auto rounded-full mb-6"></div>
                <p class="text-slate-600 text-lg">E-MAS KAPOR mengintegrasikan seluruh alur manajemen logistik dari perencanaan hingga distribusi kepada personel.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md transition-shadow group">
                    <div class="w-14 h-14 rounded-xl bg-red-50 text-red-700 flex items-center justify-center text-2xl mb-6 group-hover:bg-red-700 group-hover:text-white transition-colors duration-300">
                        <i class="ri-shirt-line"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Pendataan Ukuran Kapor</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Personel dapat memasukkan dan memperbarui data ukuran perlengkapan perorangan mereka secara mandiri dan akurat.</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md transition-shadow group">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-2xl mb-6 group-hover:bg-slate-700 group-hover:text-white transition-colors duration-300">
                        <i class="ri-git-repository-line"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Sinkronisasi Kebutuhan Satker</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Admin Satker dapat memantau dan memverifikasi identifikasi kebutuhan riil dari masing-masing satuan kerja.</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md transition-shadow group">
                    <div class="w-14 h-14 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mb-6 group-hover:bg-amber-500 group-hover:text-white transition-colors duration-300">
                        <i class="ri-pie-chart-box-line"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Perencanaan Pengadaan</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Rekapitulasi otomatis yang membantu Bagian Logistik dalam menyusun rencana pengadaan berbasis data akurat (data-driven).</p>
                </div>
                
                <!-- Feature 4 -->
                <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md transition-shadow group">
                    <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-2xl mb-6 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                        <i class="ri-truck-line"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Gudang & Distribusi</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Manajemen stok barang masuk dan pendistribusian langsung kepada personel secara transparan dan terekam.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 py-10 border-t border-slate-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-4">
                <img src="{{ asset('e-mas-kapor.png') }}" alt="Logo" class="h-12 w-auto grayscale opacity-70 hover:grayscale-0 hover:opacity-100 transition-all">
                <img src="{{ asset('logistik_polri.png') }}" alt="Logo Logistik" class="h-12 w-auto grayscale opacity-70 hover:grayscale-0 hover:opacity-100 transition-all">
            </div>
            
            <div class="text-center md:text-right">
                <p class="text-slate-400 font-medium">&copy; {{ date('Y') }} Biro Logistik Polda NTB.</p>
                <p class="text-slate-500 text-sm mt-1">Hak Cipta Dilindungi Undang-Undang.</p>
            </div>
        </div>
    </footer>

    <!-- Header scroll effect -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const header = document.querySelector('header');
            
            const handleScroll = () => {
                if (window.scrollY > 20) {
                    header.classList.remove('border-white/10', 'glass-panel');
                    header.classList.add('bg-slate-900/95', 'backdrop-blur-md', 'shadow-lg', 'border-slate-800');
                } else {
                    header.classList.add('border-white/10', 'glass-panel');
                    header.classList.remove('bg-slate-900/95', 'backdrop-blur-md', 'shadow-lg', 'border-slate-800');
                }
            };
            
            window.addEventListener('scroll', handleScroll, { passive: true });
            handleScroll(); // Initial check
        });
    </script>
</body>
</html>
