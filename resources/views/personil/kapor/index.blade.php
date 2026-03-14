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

    @php
        $hasSubmitted = !empty(array_filter((array)$kaporSizes));
        $s_head = range(54, 60);
        $s_shirt_m = ['14', '14,5', '15', '15,5', '16', '16,5', '17', '17,5', '18', '18,5', '19', '19,5', '20', '21', '22'];
        $s_wom = ['K', 'SD', 'B', 'EB', 'EEB', 'EEEB', 'EEEEB'];
        $s_pants_m = range(27, 50);
        $s_shoes = range(36, 48);
        $s_belt = range(36, 60, 2);
        $s_jilbab = ['K', 'SD', 'B'];
        
        $gender = optional(auth()->user()->personnel)->gender ?? 'L';
    @endphp

    <form action="{{ route('personil.kapor.store') }}" method="POST" id="kaporForm" style="display: {{ $hasSubmitted ? 'none' : 'block' }};">
        @csrf
        
        <div class="form-card">
            <h3 style="margin: 0 0 20px 0; font-size: 18px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                Tutup Badan & Pakaian
            </h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Kemeja</label>
                    <select name="kemeja" class="form-control select-control" required>
                        <option value="">— Pilih —</option>
                        @if($gender === 'L')
                            @foreach($s_shirt_m as $s)
                                <option value="{{ $s }}" {{ old('kemeja', $kaporSizes['kemeja'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        @else
                            @foreach($s_wom as $s)
                                <option value="{{ $s }}" {{ old('kemeja', $kaporSizes['kemeja'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Celana / Rok</label>
                    <select name="celana" class="form-control select-control" required>
                        <option value="">— Pilih —</option>
                        @if($gender === 'L')
                            @foreach($s_pants_m as $s)
                                <option value="{{ $s }}" {{ old('celana', $kaporSizes['celana'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        @else
                            @foreach($s_wom as $s)
                                <option value="{{ $s }}" {{ old('celana', $kaporSizes['celana'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">T.Shirt / Kaos Olahraga</label>
                    <select name="olahraga" class="form-control select-control" required>
                        <option value="">— Pilih —</option>
                        @foreach($s_wom as $s)
                            <option value="{{ $s }}" {{ old('olahraga', $kaporSizes['olahraga'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Jaket</label>
                    <select name="jaket" class="form-control select-control" required>
                        <option value="">— Pilih —</option>
                        @foreach($s_wom as $s)
                            <option value="{{ $s }}" {{ old('jaket', $kaporSizes['jaket'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
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
                    <select name="topi" class="form-control select-control" required>
                        <option value="">— Pilih —</option>
                        @foreach($s_head as $s)
                            <option value="{{ $s }}" {{ old('topi', $kaporSizes['topi'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Sabuk</label>
                    <select name="sabuk" class="form-control select-control" required>
                        <option value="">— Pilih —</option>
                        @foreach($s_belt as $s)
                            <option value="{{ $s }}" {{ old('sabuk', $kaporSizes['sabuk'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                
                @if($gender === 'P')
                <div class="form-group">
                    <label class="form-label">Jilbab <span style="color: var(--brand); font-size: 11px; margin-left: 4px;">(Khusus Polwan)</span></label>
                    <select name="jilbab" class="form-control select-control" required>
                        <option value="">— Pilih —</option>
                        @foreach($s_jilbab as $s)
                            <option value="{{ $s }}" {{ old('jilbab', $kaporSizes['jilbab'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
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
                    <select name="sepatu_dinas" class="form-control select-control" required>
                        <option value="">— Pilih —</option>
                        @foreach($s_shoes as $s)
                            <option value="{{ $s }}" {{ old('sepatu_dinas', $kaporSizes['sepatu_dinas'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Sepatu Olahraga</label>
                    <select name="sepatu_olahraga" class="form-control select-control" required>
                        <option value="">— Pilih —</option>
                        @foreach($s_shoes as $s)
                            <option value="{{ $s }}" {{ old('sepatu_olahraga', $kaporSizes['sepatu_olahraga'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 32px;">
            <button type="submit" class="btn-submit" style="max-width: 250px;">
                <i class="ri-save-line"></i> Simpan Data
            </button>
        </div>
    </form>

    @if($hasSubmitted)
    <div id="kaporSummaryCard" class="form-card" style="border: none; box-shadow: var(--shadow-md); overflow: hidden; margin-bottom: 24px;">
        <div class="card-header" style="background: var(--bg-card); border-bottom: 1px solid var(--border-color); padding-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: var(--success);">
                        <i class="ri-checkbox-circle-fill" style="font-size: 18px;"></i>
                    </span>
                    Ringkasan Ukuran Tersimpan
                </h3>
                <p style="margin: 8px 0 0 0; font-size: 13px; color: var(--text-muted);">Data ukuran kelengkapan kapor Anda telah terekam.</p>
            </div>
            <button type="button" onclick="document.getElementById('kaporForm').style.display='block'; document.getElementById('kaporSummaryCard').style.display='none';" class="btn" style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 8px; font-family: inherit; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s;">
                <i class="ri-edit-2-line"></i> Edit Data
            </button>
        </div>
        
        <div class="card-body" style="padding-top: 24px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
                @php
                    $summaryItems = [
                        'Kemeja' => $kaporSizes['kemeja'] ?? '-',
                        'Celana/Rok' => $kaporSizes['celana'] ?? '-',
                        'Olahraga' => $kaporSizes['olahraga'] ?? '-',
                        'Jaket' => $kaporSizes['jaket'] ?? '-',
                        'Tutup Kepala' => $kaporSizes['topi'] ?? '-',
                        'Sabuk' => $kaporSizes['sabuk'] ?? '-',
                        'Jilbab' => $kaporSizes['jilbab'] ?? '-',
                        'Sepatu Dinas' => $kaporSizes['sepatu_dinas'] ?? '-',
                        'Sepatu Olahraga' => $kaporSizes['sepatu_olahraga'] ?? '-',
                    ];
                @endphp
                @foreach($summaryItems as $label => $val)
                    @if($label === 'Jilbab' && $gender !== 'P')
                        @continue
                    @endif
                    <div style="background: var(--bg-body); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">{{ $label }}</div>
                        <div style="font-size: 15px; font-weight: 700; color: var(--text-main); margin-top: 4px;">{{ $val ?: '-' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    
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
