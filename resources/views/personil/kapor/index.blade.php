@extends('layouts.app')

@section('title', 'Input Ukuran Kapor - SI-KAPOR')

@section('styles')
<style>
    .kapor-form-header {
        margin-bottom: 24px;
    }
    .kapor-form-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-main);
        margin: 0 0 8px 0;
    }
    .kapor-form-header p {
        color: var(--text-muted);
        margin: 0;
    }

    .form-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 24px;
        margin-bottom: 24px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--text-main);
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        color: var(--text-main);
        border-radius: var(--radius-md);
        font-family: inherit;
        transition: all 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1);
    }

    .select-control {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        padding-right: 40px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .btn-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        background: var(--brand);
        color: #fff;
        border: none;
        border-radius: var(--radius-md);
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
    }

    .btn-submit:hover {
        background: var(--brand-dark);
        transform: translateY(-1px);
    }
</style>
@endsection

@section('content')
<div class="content">
    <!-- SweetAlert2 Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <div class="kapor-form-header">
        <h1>Input Ukuran Kaporlap</h1>
        <p>Lengkapi spesifikasi ukuran perlengkapan lapangan Anda di bawah ini dengan akurat.</p>
    </div>

    <!-- Peringatan dummy alert telah dihapus karena form kini memiliki controller permanen. -->

    <form action="{{ route('personil.kapor.store') }}" method="POST">
        @csrf
        
        <div class="form-card">
            <h3 style="margin: 0 0 20px 0; font-size: 18px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                Tutup Badan & Pakaian
            </h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Kemeja</label>
                    <input type="text" name="kemeja" class="form-control" value="{{ old('kemeja', $kaporSizes['kemeja'] ?? '') }}" placeholder="Ketik ukuran (cth: M, L, XL...)" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Celana / Rok</label>
                    <input type="text" name="celana" class="form-control" value="{{ old('celana', $kaporSizes['celana'] ?? '') }}" placeholder="Ketik ukuran (cth: 32, 34...)" required>
                </div>
                <div class="form-group">
                    <label class="form-label">T.Shirt / Kaos Olahraga</label>
                    <input type="text" name="olahraga" class="form-control" value="{{ old('olahraga', $kaporSizes['olahraga'] ?? '') }}" placeholder="Ketik ukuran (cth: M, L, XL...)" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jaket</label>
                    <input type="text" name="jaket" class="form-control" value="{{ old('jaket', $kaporSizes['jaket'] ?? '') }}" placeholder="Ketik ukuran (cth: M, L, XL...)" required>
                </div>
            </div>
        </div>

        <div class="form-card">
            <h3 style="margin: 0 0 20px 0; font-size: 18px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                Perlengkapan Kepala & Sabuk
            </h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Tutup Kepala (Topi/Baret)</label>
                    <input type="text" name="topi" class="form-control" value="{{ old('topi', $kaporSizes['topi'] ?? '') }}" placeholder="Ketik ukuran (cth: 56, 58...)" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Sabuk</label>
                    <input type="text" name="sabuk" class="form-control" value="{{ old('sabuk', $kaporSizes['sabuk'] ?? '') }}" placeholder="Ketik ukuran (cth: 110, 120...)" required>
                </div>
                
                @if(optional(auth()->user()->personnel)->gender === 'P')
                <div class="form-group">
                    <label class="form-label">Jilbab <span style="color: var(--brand); font-size: 11px; margin-left: 4px;">(Khusus Polwan)</span></label>
                    <input type="text" name="jilbab" class="form-control" value="{{ old('jilbab', $kaporSizes['jilbab'] ?? '') }}" placeholder="Ketik ukuran (cth: SD, M...)" required>
                </div>
                @endif
            </div>
        </div>

        <div class="form-card">
            <h3 style="margin: 0 0 20px 0; font-size: 18px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                Tutup Kaki & Sepatu
            </h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Sepatu Dinas</label>
                    <input type="text" name="sepatu_dinas" class="form-control" value="{{ old('sepatu_dinas', $kaporSizes['sepatu_dinas'] ?? '') }}" placeholder="Ketik ukuran (cth: 42, 43...)" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Sepatu Olahraga</label>
                    <input type="text" name="sepatu_olahraga" class="form-control" value="{{ old('sepatu_olahraga', $kaporSizes['sepatu_olahraga'] ?? '') }}" placeholder="Ketik ukuran (cth: 42, 43...)" required>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 32px;">
            <button type="submit" class="btn-submit" style="max-width: 250px;">
                <i class="ri-save-line"></i> Simpan Data
            </button>
        </div>
    </form>
    
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Berhasil!',
                text: "{{ session('success') }}",
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: {
                    popup: 'colored-toast'
                }
            });
        });
    </script>
    @endif
    <style>
        .colored-toast.swal2-icon-success {
            background-color: var(--success-bg) !important;
            color: var(--success) !important;
        }
        .colored-toast .swal2-title {
            color: var(--success) !important;
        }
    </style>
</div>
@endsection
