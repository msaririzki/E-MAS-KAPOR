<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <title>E-MAS KAPOR | Biro Logistik Polda NTB</title>
    <meta name="description" content="E-MAS KAPOR atau E Mas Kapor adalah sistem informasi manajemen perlengkapan perorangan Biro Logistik Polda NTB untuk pendataan kapor, kebutuhan satker, pengadaan, gudang, distribusi, dan pelaporan.">
    <link rel="canonical" href="{{ url('/') }}">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="E-MAS KAPOR | Biro Logistik Polda NTB">
    <meta property="og:description" content="E-MAS KAPOR atau E Mas Kapor adalah sistem informasi manajemen perlengkapan perorangan Biro Logistik Polda NTB untuk pendataan kapor, kebutuhan satker, pengadaan, gudang, distribusi, dan pelaporan.">
    <meta property="og:image" content="{{ asset('e-mas-kapor.png') }}">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="{{ url('/') }}">
    <meta name="twitter:title" content="E-MAS KAPOR | Biro Logistik Polda NTB">
    <meta name="twitter:description" content="E-MAS KAPOR atau E Mas Kapor adalah sistem informasi manajemen perlengkapan perorangan Biro Logistik Polda NTB untuk pendataan kapor, kebutuhan satker, pengadaan, gudang, distribusi, dan pelaporan.">
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
      "alternateName": [
        "EMAS KAPOR",
        "E Mas Kapor",
        "Kapor Logistik Polda NTB",
        "E-MAS KAPOR Biro Logistik Polda NTB"
      ],
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
        
        @-webkit-keyframes ken-burns {
            0% { -webkit-transform: scale(1); transform: scale(1); }
            50% { -webkit-transform: scale(1.15); transform: scale(1.15); }
            100% { -webkit-transform: scale(1); transform: scale(1); }
        }
        @keyframes ken-burns {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        
        .animate-ken-burns {
            -webkit-animation: ken-burns 12s ease-in-out infinite;
            animation: ken-burns 12s ease-in-out infinite;
            will-change: transform;
        }
        
        .hero-overlay {
            background: linear-gradient(to bottom, rgba(15, 23, 42, 0.70), rgba(15, 23, 42, 0.88));
        }
        
        .features-bg {
            background-image: linear-gradient(to bottom, rgba(248, 250, 252, 0.88), rgba(248, 250, 252, 0.94)), url('{{ asset("bg_polda.jpg") }}');
            background-size: cover;
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
        
        /* Reveal Animations */
        .reveal-item {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }
        .reveal-item.is-revealed {
            opacity: 1;
            transform: translateY(0);
        }
        .delay-100 { transition-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; }
        .delay-400 { transition-delay: 400ms; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased flex flex-col min-h-screen">

    <!-- Header / Navbar -->
    <header class="fixed w-full top-0 z-50 glass-panel border-b border-white/10 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-2 md:gap-3">
                    <img src="{{ asset('e-mas-kapor.png') }}" alt="Logo" class="h-8 md:h-10 w-auto drop-shadow-md">
                    <div>
                        <h1 class="text-white font-bold text-base md:text-lg leading-tight tracking-wide">E-MAS KAPOR</h1>
                        <p class="text-slate-300 text-[10px] md:text-xs font-medium uppercase tracking-wider">Biro Logistik Polda NTB</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 md:gap-2 px-4 md:px-5 py-2 md:py-2.5 rounded-lg font-semibold text-xs md:text-sm transition-all duration-200 bg-red-700 hover:bg-red-600 text-white shadow-lg shadow-red-900/50 border border-red-500/30">
                        Masuk
                        <i class="ri-login-circle-line text-base md:text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative bg-slate-900 min-h-[100dvh] flex items-center pt-20 pb-16 overflow-hidden">
        <!-- Animated Background Image -->
        <div class="absolute inset-0 z-0 overflow-hidden">
            <div class="absolute inset-0 bg-cover bg-[47.5%_top] md:bg-center origin-[47.5%_top] md:origin-center animate-ken-burns" style="background-image: url('{{ asset("bg_polda.jpg") }}');"></div>
            <div class="absolute inset-0 hero-overlay"></div>
        </div>
        <!-- Decorative elements -->
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-slate-900/50 to-slate-900 z-0"></div>
        <div class="absolute -top-[30%] -right-[10%] w-[70%] h-[70%] rounded-full bg-red-900/20 blur-[120px] pointer-events-none z-0"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full flex flex-col items-center text-center">
            
            <h1 class="reveal-item text-3xl sm:text-4xl md:text-6xl lg:text-7xl font-extrabold text-white tracking-tight mb-4 md:mb-6 drop-shadow-lg leading-tight mt-8 md:mt-0">
                <span class="block">E-MAS KAPOR</span>
                <span class="block text-lg sm:text-2xl md:text-3xl lg:text-4xl font-semibold text-slate-300 mt-2 md:mt-3">Electronic Measurement <span class="gold-accent block sm:inline mt-1 sm:mt-0">Perlengkapan Perorangan</span></span>
            </h1>
            
            <p class="reveal-item delay-100 mt-2 md:mt-4 text-sm md:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed mb-8 md:mb-10 drop-shadow px-2">
                Sistem informasi manajemen perlengkapan perorangan Biro Logistik Polda NTB untuk pendataan, perencanaan, dan distribusi yang lebih terintegrasi.
            </p>
            <div class="reveal-item delay-200 flex flex-col sm:flex-row gap-4 items-center justify-center">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl font-semibold text-base transition-all duration-300 bg-red-700 hover:bg-red-600 text-white shadow-xl shadow-red-900/40 border border-red-500/50 hover:-translate-y-0.5 w-full sm:w-auto">
                    Masuk ke Sistem
                    <i class="ri-arrow-right-line"></i>
                </a>
                <a href="#fitur" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl font-semibold text-base transition-all duration-300 bg-white/10 hover:bg-white/20 text-white border border-white/20 backdrop-blur-md w-full sm:w-auto">
                    Pelajari Fitur
                    <i class="ri-arrow-down-line"></i>
                </a>
            </div>
            
            <div class="reveal-item delay-300 mt-12">
                <p class="text-slate-400 text-sm flex items-center gap-2 justify-center">
                    <i class="ri-information-line"></i>
                    Khusus untuk personel dan operator terdaftar
                </p>
            </div>
        </div>
        
        <!-- Shape divider -->
        <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none z-10">
            <svg class="relative block w-full h-[50px] md:h-[80px]" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M1200,120 L0,120 L0,16 C400,16 800,120 1200,120 Z" class="fill-slate-50"></path>
            </svg>
        </div>
    </section>

    <!-- Features Section -->
    <section id="fitur" class="py-20 features-bg bg-[47.5%_top] md:bg-center flex-grow relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="reveal-item text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl font-bold text-slate-900 mb-4">Fitur Utama Sistem</h2>
                <div class="h-1 w-20 bg-red-700 mx-auto rounded-full mb-6"></div>
                <p class="text-slate-600 text-lg">E-MAS KAPOR mengintegrasikan seluruh alur manajemen logistik dari perencanaan hingga distribusi kepada personel.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="reveal-item bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md transition-shadow group">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 shrink-0 rounded-xl bg-red-50 text-red-700 flex items-center justify-center text-xl group-hover:bg-red-700 group-hover:text-white transition-colors duration-300">
                            <i class="ri-shirt-line"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 leading-tight">Pendataan Ukuran Kapor</h3>
                    </div>
                    <p class="text-slate-600 leading-relaxed text-sm">Personel dapat memasukkan dan memperbarui data ukuran perlengkapan perorangan mereka secara mandiri dan akurat.</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="reveal-item delay-100 bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md transition-shadow group">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 shrink-0 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl group-hover:bg-slate-700 group-hover:text-white transition-colors duration-300">
                            <i class="ri-git-repository-line"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 leading-tight">Sinkronisasi Kebutuhan Satker</h3>
                    </div>
                    <p class="text-slate-600 leading-relaxed text-sm">Admin Satker dapat memantau dan memverifikasi identifikasi kebutuhan riil dari masing-masing satuan kerja.</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="reveal-item delay-200 bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md transition-shadow group">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 shrink-0 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl group-hover:bg-amber-500 group-hover:text-white transition-colors duration-300">
                            <i class="ri-pie-chart-box-line"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 leading-tight">Perencanaan Pengadaan</h3>
                    </div>
                    <p class="text-slate-600 leading-relaxed text-sm">Rekapitulasi otomatis yang membantu Bagian Logistik dalam menyusun rencana pengadaan berbasis data akurat (data-driven).</p>
                </div>
                
                <!-- Feature 4 -->
                <div class="reveal-item delay-300 bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md transition-shadow group">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 shrink-0 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-xl group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                            <i class="ri-truck-line"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 leading-tight">Gudang & Distribusi</h3>
                    </div>
                    <p class="text-slate-600 leading-relaxed text-sm">Manajemen stok barang masuk dan pendistribusian langsung kepada personel secara transparan dan terekam.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 py-10 md:py-12 border-t border-slate-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-8">
            <!-- Left: Logo and Social -->
            <div class="flex flex-row items-center gap-5">
                <img src="{{ asset('e-mas-kapor.png') }}" alt="Logo" class="h-12 w-auto">
                <div class="h-8 w-px bg-slate-700"></div>
                <a href="https://www.instagram.com/birologistik_ntb/" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-gradient-to-tr hover:from-yellow-500 hover:via-red-500 hover:to-purple-500 hover:text-white transition-all duration-300" title="Instagram Biro Logistik Polda NTB">
                    <i class="ri-instagram-line text-xl"></i>
                </a>
            </div>

            <!-- Right: Copyright & SEO -->
            <div class="text-center md:text-right">
                <p class="text-slate-400 font-medium">&copy; {{ date('Y') }} Biro Logistik Polda NTB.</p>
                <p class="text-slate-500 text-sm mt-1">Hak Cipta Dilindungi Undang-Undang.</p>
                <p class="text-slate-600 text-xs mt-3 max-w-xl mx-auto md:ml-auto md:mr-0">
                    Portal resmi E Mas Kapor, EMAS KAPOR Logistik, Kapor Biro Logistik Polda NTB, dan E-MAS KAPOR Polda Nusa Tenggara Barat.
                </p>
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
            
            // Scroll Reveal Animation
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.15
            };

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.reveal-item').forEach((el) => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
