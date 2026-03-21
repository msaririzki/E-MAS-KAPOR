@extends('layouts.app')

@section('title', 'Dashboard Personil')
@section('page-title', 'Dashboard Personil')
@section('page-subtitle', 'Tahun Anggaran ' . $fiscalYear)

@section('styles')
<style>
    .form-card { padding: 20px; border: 1px solid var(--border-color); border-radius: var(--radius-lg); margin-bottom: 24px; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-main); font-size: 13px; }
    .form-control { width: 100%; padding: 10px 14px; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); font-family: inherit; transition: all 0.2s; }
    .form-control:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1); }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
    .btn-submit { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; background: var(--brand); color: #fff; border: none; border-radius: var(--radius-md); font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; width: 100%; }
    .btn-submit:hover { background: var(--brand-dark); transform: translateY(-1px); }
    .colored-toast.swal2-icon-success { background-color: var(--success-bg) !important; color: var(--success) !important; }
    .colored-toast .swal2-title { color: var(--success) !important; }
    
    /* Testimoni Rating */
    .rating-wrapper { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 8px; }
    .rating-wrapper input { display: none; }
    .rating-wrapper label { cursor: pointer; color: var(--slate-300); font-size: 24px; transition: color 0.2s; }
    .rating-wrapper label:hover, .rating-wrapper label:hover ~ label, .rating-wrapper input:checked ~ label { color: #F59E0B; }
    /* Modern Assessment Widget */
    .testimonial-widget {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 28px;
        box-shadow: 0 16px 40px -12px rgba(0,0,0,0.06);
        position: relative;
        overflow: hidden;
        transition: all 0.4s ease;
        margin-top: 32px;
    }
    .testimonial-widget:hover {
        transform: translateY(-4px);
        box-shadow: 0 25px 50px -16px rgba(0,0,0,0.08);
        border-color: rgba(203, 213, 225, 0.8);
    }
    .testimonial-widget::before {
        content: ''; position: absolute; top: 0; right: 0; 
        width: 300px; height: 300px; 
        background: radial-gradient(circle, rgba(14, 165, 233, 0.04) 0%, transparent 60%); 
        border-radius: 50%; pointer-events: none;
    }
    .testimonial-header {
        padding: 32px 32px 20px 32px;
        border-bottom: 1px solid rgba(241, 245, 249, 0.8);
    }
    .testimonial-body { padding: 24px 32px 32px 32px; }
    
    .rating-container {
        display: flex; gap: 4px; flex-direction: row-reverse; justify-content: flex-end;
        background: #f8fafc; padding: 16px 24px; border-radius: 20px; border: 1px solid #f1f5f9;
        width: fit-content;
    }
    .rating-container input { display: none; }
    .rating-container label { cursor: pointer; color: #cbd5e1; font-size: 32px; transition: all 0.2s cubic-bezier(0.18, 0.89, 0.32, 1.28); }
    .rating-container label:hover, .rating-container label:hover ~ label, .rating-container input:checked ~ label { 
        color: #f59e0b; transform: scale(1.15); filter: drop-shadow(0 4px 6px rgba(245, 158, 11, 0.3));
    }
    
    .testimonial-textarea {
        width: 100%; border: 2px solid #f1f5f9; border-radius: 20px; padding: 20px;
        font-family: inherit; font-size: 15px; color: #334155;
        transition: all 0.3s; background: #ffffff;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        margin-top: 12px;
    }
    .testimonial-textarea:focus {
        outline: none; border-color: rgba(56, 189, 248, 0.5); box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1), inset 0 2px 4px rgba(0,0,0,0.01);
        background: #fff;
    }
    
    .btn-submit-testimonial {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 16px 32px; border-radius: 100px;
        background: linear-gradient(135deg, #0284c7, #0369a1);
        color: white; font-weight: 700; font-size: 15.5px;
        border: none; cursor: pointer;
        box-shadow: 0 10px 20px -8px rgba(2, 132, 199, 0.5);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .btn-submit-testimonial:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 30px -10px rgba(2, 132, 199, 0.6);
        background: linear-gradient(135deg, #0369a1, #075985);
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div class="content">

{{-- Profile Summary --}}
<div class="card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); overflow: hidden; position: relative; margin-bottom: 24px;">
    <div style="position: absolute; right: -50px; top: -50px; width: 150px; height: 150px; background: var(--brand); opacity: 0.1; filter: blur(40px); border-radius: 50%; pointer-events: none;"></div>
    <div style="position: absolute; left: 20%; bottom: -40px; width: 100px; height: 100px; background: var(--accent); opacity: 0.05; filter: blur(30px); border-radius: 50%; pointer-events: none;"></div>

    <div class="card-body" style="display:flex; align-items:center; gap:24px; flex-wrap:wrap; padding: 24px 32px; background: var(--bg-card); position: relative; z-index: 1;">
        <div style="width:64px; height:64px; border-radius:16px; background:linear-gradient(135deg, var(--brand), var(--brand-light)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:24px; font-weight:700; flex-shrink:0; box-shadow: 0 4px 12px rgba(var(--brand-rgb, 198,40,40), 0.2);">
            {{ strtoupper(substr($user->name, 0, 2)) }}
        </div>
        <div style="flex:1; min-width:200px;">
            <div style="font-size:20px; font-weight:800; color: var(--text-main); letter-spacing: -0.2px;">{{ $user->name }}</div>
            <div style="font-size:14px; color: var(--text-muted); margin-top:4px; display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                <span style="display: inline-flex; align-items: center; gap: 4px;"><i class="ri-fingerprint-line" style="color: var(--slate-400);"></i> <strong>{{ $user->nrp_nip }}</strong></span>
                @if($personnel)
                    <span style="color: var(--slate-300);">???</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><i class="ri-medal-2-line" style="color: var(--slate-400);"></i> {{ $personnel->rank->name ?? '' }}</span>
                    <span style="color: var(--slate-300);">???</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><i class="ri-building-4-line" style="color: var(--slate-400);"></i> {{ $personnel->satker->name ?? '' }}</span>
                @endif
            </div>
        </div>
        <div>
            @if($hasSubmitted)
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 9999px; font-size: 13px; font-weight: 700; background: var(--success-bg); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); box-shadow: 0 2px 8px rgba(16, 185, 129, 0.1);">
                    <i class="ri-shield-check-fill" style="font-size: 16px;"></i> Sudah Input TA {{ $fiscalYear }}
                </span>
            @else
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 9999px; font-size: 13px; font-weight: 700; background: var(--warning-bg); color: #B45309; border: 1px solid var(--warning-border); box-shadow: 0 2px 8px rgba(245, 158, 11, 0.1);">
                    <i class="ri-error-warning-fill" style="font-size: 16px;"></i> Belum Input TA {{ $fiscalYear }}
                </span>
            @endif
        </div>
    </div>
</div>

{{-- FORM INPUT UTAMA (PRIORITAS DEPAN) --}}
<div class="card" style="border: none; box-shadow: var(--shadow-md); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 24px; background: var(--bg-card);">
    <div class="card-header" style="background: var(--bg-card); border-bottom: 1px solid var(--border-color); padding: 20px 24px;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(var(--brand-rgb, 198,40,40), 0.1); color: var(--brand);">
                <i class="ri-edit-box-line" style="font-size: 18px;"></i>
            </span>
            Form Biodata Kelengkapan Kaporlap
        </h3>
        <p style="margin: 8px 0 0 0; font-size: 13px; color: var(--text-muted);">Silakan lengkapi atau perbarui ukuran atribut lapangan Anda di bawah ini secara akurat.</p>
    </div>
    
    <div class="card-body" style="padding: 24px;">
        <form action="{{ route('personil.kapor.store') }}" method="POST">
            @csrf
            
            <div class="form-card">
                <h4 style="margin: 0 0 16px 0; font-size: 15px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Tutup Badan & Pakaian</h4>
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
                        <input type="text" name="olahraga" class="form-control" value="{{ old('olahraga', $kaporSizes['olahraga'] ?? '') }}" placeholder="Ketik ukuran (cth: K, SD, B...)" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jaket</label>
                        <input type="text" name="jaket" class="form-control" value="{{ old('jaket', $kaporSizes['jaket'] ?? '') }}" placeholder="Ketik ukuran (cth: M, L, XL...)" required>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <h4 style="margin: 0 0 16px 0; font-size: 15px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Perlengkapan Kepala & Sabuk</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Tutup Kepala (Topi/Baret)</label>
                        <input type="text" name="topi" class="form-control" value="{{ old('topi', $kaporSizes['topi'] ?? '') }}" placeholder="Ketik ukuran (cth: 56, 58...)" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sabuk</label>
                        <input type="text" name="sabuk" class="form-control" value="{{ old('sabuk', $kaporSizes['sabuk'] ?? '') }}" placeholder="Ketik ukuran (cth: 36, 38, 40...)" required>
                    </div>
                    @if(optional(auth()->user()->personnel)->gender === 'P')
                    <div class="form-group">
                        <label class="form-label">Jilbab <span style="color: var(--brand); font-size: 11px; margin-left: 4px;">(Khusus Polwan)</span></label>
                        <input type="text" name="jilbab" class="form-control" value="{{ old('jilbab', $kaporSizes['jilbab'] ?? '') }}" placeholder="Ketik ukuran (cth: K, SD, B...)" required>
                    </div>
                    @endif
                </div>
            </div>

            <div class="form-card" style="margin-bottom: 0;">
                <h4 style="margin: 0 0 16px 0; font-size: 15px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Tutup Kaki & Sepatu</h4>
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

            <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <button type="submit" class="btn-submit" style="max-width: 250px;">
                    <i class="ri-save-line"></i> Simpan Pilihan Form
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Status Cards (Dipindah ke Bawah Form) --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
    {{-- Card 1: Item Terisi --}}
    <div class="card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; padding: 32px 28px; background: var(--bg-card); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='var(--shadow-md)';" onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)';">
        <div style="flex: 1;">
            <p style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin: 0 0 12px 0;">Item Data Terisi</p>
            <div style="display: flex; align-items: baseline; gap: 8px;">
                <span style="font-size: 42px; font-weight: 800; color: var(--text-main); line-height: 1;">{{ is_array($kaporSizes) ? count(array_filter($kaporSizes)) : 0 }}</span>
                <span style="font-size: 16px; font-weight: 600; color: var(--text-muted);">/ 9 Total Barang</span>
            </div>
            <p style="font-size: 14px; font-weight: 500; color: {{ $hasSubmitted ? 'var(--success)' : 'var(--slate-400)' }}; margin: 16px 0 0 0; display: flex; align-items: center; gap: 8px;">
                <i class="ri-checkbox-circle-fill" style="font-size: 18px;"></i>
                {{ $hasSubmitted ? 'Kaporlap Anda sudah direkam.' : 'Masih ada data ukuran kosong.' }}
            </p>
        </div>
        <div style="width: 72px; height: 72px; border-radius: 50%; background: {{ $hasSubmitted ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)' }}; display: flex; align-items: center; justify-content: center; color: {{ $hasSubmitted ? 'var(--success)' : 'var(--warning)' }}; font-size: 32px; box-shadow: 0 4px 12px {{ $hasSubmitted ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)' }};">
            <i class="ri-{{ $hasSubmitted ? 'shopping-bag-3-fill' : 'edit-2-fill' }}"></i>
        </div>
    </div>

    {{-- Card 2: Tahun Anggaran --}}
    <div class="card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; padding: 32px 28px; background: var(--bg-card); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='var(--shadow-md)';" onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)';">
        <div style="flex: 1;">
            <p style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin: 0 0 12px 0;">Tahun Anggaran</p>
            <div style="display: flex; align-items: baseline; gap: 8px;">
                <span style="font-size: 42px; font-weight: 800; color: var(--text-main); line-height: 1;">{{ $fiscalYear }}</span>
            </div>
            <p style="font-size: 14px; font-weight: 500; color: var(--info); margin: 16px 0 0 0; display: flex; align-items: center; gap: 8px;">
                <i class="ri-time-fill" style="font-size: 18px;"></i>
                Data input masuk ke TA saat ini.
            </p>
        </div>
        <div style="width: 72px; height: 72px; border-radius: 50%; background: var(--info-bg); display: flex; align-items: center; justify-content: center; color: var(--info); font-size: 32px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);">
            <i class="ri-calendar-event-fill"></i>
        </div>
    </div>
</div>

{{-- Submission History Tab --}}
@if($hasSubmitted)
<div class="card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 24px;">
    <div class="card-header" style="background: var(--bg-card); border-bottom: 1px solid var(--border-color); padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(var(--brand-rgb, 198,40,40), 0.1); color: var(--brand);">
                <i class="ri-shirt-line" style="font-size: 18px;"></i>
            </span>
            Ringkasan Ukuran Tersimpan
        </h3>
        <span style="background: var(--info-bg); color: var(--info); font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 12px;">TA {{ $fiscalYear }}</span>
    </div>
    <div class="card-body flush">
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--hover-bg); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; width: 60px;">No</th>
                        <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Kategori</th>
                        <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Nama Item</th>
                        <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Ukuran Pilihan</th>
                        <th style="padding: 14px 24px; text-align: right; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Waktu Diperbarui</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $itemMap = [
                            'topi' => ['label' => 'TUTUP KEPALA', 'category' => 'Tutup Kepala', 'icon' => 'ri-spy-line'],
                            'jilbab' => ['label' => 'Jilbab', 'category' => 'Tutup Kepala', 'icon' => 'ri-spy-line'],
                            'kemeja' => ['label' => 'Kemeja (PDH/PDL)', 'category' => 'Tutup Badan', 'icon' => 'ri-t-shirt-line'],
                            'celana' => ['label' => 'Celana/Rok', 'category' => 'Tutup Badan', 'icon' => 'ri-t-shirt-line'],
                            'jaket' => ['label' => 'Jaket', 'category' => 'Tutup Badan', 'icon' => 'ri-t-shirt-line'],
                            'olahraga' => ['label' => 'T-Shirt/Olahraga', 'category' => 'Tutup Badan', 'icon' => 'ri-basketball-line'],
                            'sabuk' => ['label' => 'Sabuk', 'category' => 'Perlengkapan', 'icon' => 'ri-medal-line'],
                            'sepatu_dinas' => ['label' => 'Sepatu Dinas', 'category' => 'Tutup Kaki', 'icon' => 'ri-footprint-line'],
                            'sepatu_olahraga' => ['label' => 'Sepatu Olahraga', 'category' => 'Tutup Kaki', 'icon' => 'ri-footprint-line'],
                        ];
                        $idx = 0;
                    @endphp
                    @foreach($itemMap as $key => $meta)
                        @if(isset($kaporSizes[$key]) && !empty($kaporSizes[$key]))
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s;" onmouseover="this.style.background='var(--hover-bg)'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 16px 24px; font-size: 13px; color: var(--text-muted);">{{ ++$idx }}</td>
                                <td style="padding: 16px 24px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: var(--info-bg); color: var(--info);">
                                        <i class="{{ $meta['icon'] }}"></i> {{ $meta['category'] }}
                                    </span>
                                </td>
                                <td style="padding: 16px 24px; font-size: 14px; font-weight: 600; color: var(--text-main);">{{ $meta['label'] }}</td>
                                <td style="padding: 16px 24px;">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border-radius: 6px; background: rgba(var(--brand-rgb, 198,40,40), 0.08); border: 1px solid rgba(var(--brand-rgb, 198,40,40), 0.2); font-size: 14px; font-weight: 800; color: var(--brand);">
                                        {{ $kaporSizes[$key] }}
                                    </span>
                                </td>
                                <td style="padding: 16px 24px; font-size: 13px; color: var(--text-muted); text-align: right;">{{ optional($personnel->updated_at)->format('d M Y, H:i') ?? '-' }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- FORM TESTIMONI KOTAK SARAN --}}
<div class="testimonial-widget">
    <div class="testimonial-header">
        <h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 12px; letter-spacing: -0.5px;">
            <div style="width: 42px; height: 42px; border-radius: 14px; background: linear-gradient(135deg, rgba(2, 132, 199, 0.1), rgba(14, 165, 233, 0.15)); display: flex; align-items: center; justify-content: center; color: #0284c7; font-size: 20px;">
                <i class="ri-feedback-fill"></i>
            </div>
            Suara Anda Penting Bagi Kami
        </h3>
        <p style="margin: 8px 0 0 54px; font-size: 14px; color: #64748b; line-height: 1.6;">Tingkatkan pengalaman sistem bersama. Berikan rating layanan dan jelaskan harapan, kritik, serta kendala yang Anda hadapi.</p>
    </div>
    
    <div class="testimonial-body">
        <form action="{{ route('personil.testimoni.store') }}" method="POST">
            @csrf
            <div style="display: flex; flex-wrap: wrap; gap: 32px; align-items: flex-start;">
                
                <div style="flex: 1; min-width: 280px;">
                    <label style="display:block; font-size:14px; font-weight:700; color:#334155; margin-bottom:12px;">Rating Pengalaman Anda</label>
                    <div class="rating-container">
                        <input type="radio" id="star5" name="rating" value="5" checked />
                        <label for="star5" title="Sangat Bagus (5)"><i class="ri-star-fill"></i></label>
                        <input type="radio" id="star4" name="rating" value="4" />
                        <label for="star4" title="Bagus (4)"><i class="ri-star-fill"></i></label>
                        <input type="radio" id="star3" name="rating" value="3" />
                        <label for="star3" title="Cukup (3)"><i class="ri-star-fill"></i></label>
                        <input type="radio" id="star2" name="rating" value="2" />
                        <label for="star2" title="Buruk (2)"><i class="ri-star-fill"></i></label>
                        <input type="radio" id="star1" name="rating" value="1" />
                        <label for="star1" title="Sangat Buruk (1)"><i class="ri-star-fill"></i></label>
                    </div>
                    
                    <div style="margin-top: 24px;">
                        <label style="display:block; font-size:14px; font-weight:700; color:#334155;">Pesan Testimoni / Saran</label>
                        <textarea name="message" class="testimonial-textarea" rows="4" placeholder="Ceritakan bagaimana performa aplikasi ini di lapangan..." required style="resize: vertical;"></textarea>
                    </div>
                </div>

                <div style="width: 100%; display: flex; justify-content: flex-end; align-items: center; border-top: 1px dashed #e2e8f0; padding-top: 24px; margin-top: -8px;">
                    <button type="submit" class="btn-submit-testimonial">
                        Kirim Kesan & Pesan <i class="ri-send-plane-fill" style="font-size: 18px;"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

</div>

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
            customClass: { popup: 'colored-toast' }
        });
    });
</script>
@endif

@if(session('success_testimoni'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Terkirim',
            text: "{{ session('success_testimoni') }}",
            icon: 'info',
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            customClass: { popup: 'colored-toast swal2-icon-info' }
        });
    });
</script>
<style>
    .colored-toast.swal2-icon-info { background-color: var(--info-bg) !important; color: var(--info) !important;}
    .colored-toast.swal2-icon-info .swal2-title { color: var(--info) !important; }
    /* Modern Assessment Widget */
    .testimonial-widget {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 28px;
        box-shadow: 0 16px 40px -12px rgba(0,0,0,0.06);
        position: relative;
        overflow: hidden;
        transition: all 0.4s ease;
        margin-top: 32px;
    }
    .testimonial-widget:hover {
        transform: translateY(-4px);
        box-shadow: 0 25px 50px -16px rgba(0,0,0,0.08);
        border-color: rgba(203, 213, 225, 0.8);
    }
    .testimonial-widget::before {
        content: ''; position: absolute; top: 0; right: 0; 
        width: 300px; height: 300px; 
        background: radial-gradient(circle, rgba(14, 165, 233, 0.04) 0%, transparent 60%); 
        border-radius: 50%; pointer-events: none;
    }
    .testimonial-header {
        padding: 32px 32px 20px 32px;
        border-bottom: 1px solid rgba(241, 245, 249, 0.8);
    }
    .testimonial-body { padding: 24px 32px 32px 32px; }
    
    .rating-container {
        display: flex; gap: 4px; flex-direction: row-reverse; justify-content: flex-end;
        background: #f8fafc; padding: 16px 24px; border-radius: 20px; border: 1px solid #f1f5f9;
        width: fit-content;
    }
    .rating-container input { display: none; }
    .rating-container label { cursor: pointer; color: #cbd5e1; font-size: 32px; transition: all 0.2s cubic-bezier(0.18, 0.89, 0.32, 1.28); }
    .rating-container label:hover, .rating-container label:hover ~ label, .rating-container input:checked ~ label { 
        color: #f59e0b; transform: scale(1.15); filter: drop-shadow(0 4px 6px rgba(245, 158, 11, 0.3));
    }
    
    .testimonial-textarea {
        width: 100%; border: 2px solid #f1f5f9; border-radius: 20px; padding: 20px;
        font-family: inherit; font-size: 15px; color: #334155;
        transition: all 0.3s; background: #ffffff;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        margin-top: 12px;
    }
    .testimonial-textarea:focus {
        outline: none; border-color: rgba(56, 189, 248, 0.5); box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1), inset 0 2px 4px rgba(0,0,0,0.01);
        background: #fff;
    }
    
    .btn-submit-testimonial {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 16px 32px; border-radius: 100px;
        background: linear-gradient(135deg, #0284c7, #0369a1);
        color: white; font-weight: 700; font-size: 15.5px;
        border: none; cursor: pointer;
        box-shadow: 0 10px 20px -8px rgba(2, 132, 199, 0.5);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .btn-submit-testimonial:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 30px -10px rgba(2, 132, 199, 0.6);
        background: linear-gradient(135deg, #0369a1, #075985);
    }
</style>
@endif

@endsection
