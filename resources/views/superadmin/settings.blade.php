@extends('layouts.app')

@section('title', 'Pengaturan Sistem')
@section('breadcrumb', 'Pengaturan')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Pengaturan Sistem</h1>
            <p>Kelola konfigurasi aplikasi dan transisi Tahun Anggaran.</p>
        </div>
    </div>
</div>

{{-- Flash Messages --}}
@if(session('success'))
<div class="alert alert-success" id="flashMsg">
    <i class="ri-check-line"></i> {{ session('success') }}
</div>
@endif

<div class="grid-2-1">
    {{-- Form Pengaturan Utama --}}
    <div class="card">
        <div class="card-head">
            <h3><i class="ri-settings-4-line" style="margin-right:6px;color:var(--brand);"></i> Konfigurasi Umum</h3>
        </div>
        <form method="POST" action="{{ route('superadmin.settings.update') }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label>Nama Aplikasi</label>
                    <input type="text" name="app_name" class="form-input" value="{{ $settings['app_name'] }}" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tahun Anggaran Aktif</label>
                        <input type="number" name="fiscal_year" class="form-input" value="{{ $settings['fiscal_year'] }}" required>
                        <p style="font-size:11px;color:var(--slate-400);margin-top:4px;">Tahun yang digunakan untuk Dashboard dan filter data.</p>
                    </div>
                </div>

                <div class="form-group" style="margin-top:10px;">
                    <label class="checkbox-container">
                        <input type="checkbox" name="is_system_locked" value="1" {{ $settings['is_system_locked'] ? 'checked' : '' }}>
                        <span class="checkmark"></span>
                        <span style="font-weight:600;color:var(--danger);">Kunci Sistem (Lock System)</span>
                    </label>
                    <p style="font-size:11px;color:var(--slate-500);padding-left:28px;">Jika dicentang, personil tidak dapat melakukan input data kapor baru.</p>
                </div>
            </div>
            <div class="card-footer" style="justify-content: flex-end;">
                <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>

    {{-- Transisi Tahun Anggaran --}}
    <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="card" style="border-left: 4px solid var(--warning);">
            <div class="card-head">
                <h3><i class="ri-refresh-line" style="margin-right:6px;color:var(--warning);"></i> Tutup Tahun Anggaran</h3>
            </div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--slate-600);margin-bottom:16px;">
                    Fitur ini digunakan saat periode Tahun Anggaran {{ $settings['fiscal_year'] }} sudah selesai. 
                </p>
                <div style="background:var(--warning-bg);padding:12px;border-radius:var(--radius-sm);margin-bottom:16px;border:1px dashed var(--warning);">
                    <ul style="font-size:12px;color:var(--warning-dark);padding-left:20px;">
                        <li>Sistem akan otomatis terkunci.</li>
                        <li>Tahun Anggaran akan beralih ke <strong>{{ $settings['fiscal_year'] + 1 }}</strong>.</li>
                        <li>Dashboard akan mulai menghitung dari nol untuk tahun baru.</li>
                        <li>Data tahun {{ $settings['fiscal_year'] }} tetap tersimpan sebagai arsip.</li>
                    </ul>
                </div>
                
                <form action="{{ route('superadmin.settings.next-year') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin MENUTUP Tahun Anggaran {{ $settings['fiscal_year'] }} dan lanjut ke {{ $settings['fiscal_year'] + 1 }}?')">
                    @csrf
                    <button type="submit" class="btn btn-outline" style="width:100%;border-color:var(--warning);color:var(--warning-dark);">
                        <i class="ri-checkbox-circle-line"></i> Selesaikan TA {{ $settings['fiscal_year'] }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Riwayat Data --}}
        <div class="card">
            <div class="card-head">
                <h3><i class="ri-history-line" style="margin-right:6px;color:var(--brand);"></i> Riwayat Recap</h3>
            </div>
            <div class="card-body flush">
                <div class="table-wrap">
                    <table style="font-size:12px;">
                        <thead>
                            <tr>
                                <th>Tahun</th>
                                <th style="text-align:center;">Total Recap</th>
                                <th style="text-align:right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($yearlyStats as $ys)
                            <tr style="{{ $ys->is_active ? 'background:var(--brand-bg);' : '' }}">
                                <td style="font-weight:700;">
                                    TA {{ $ys->fiscal_year }}
                                    @if($ys->is_active)
                                        <span class="badge badge-info" style="font-size:9px;padding:1px 4px;margin-left:4px;">AKTIF</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">{{ number_format($ys->total) }} input</td>
                                <td style="text-align:right;">
                                    <span class="badge {{ $ys->status == 'Aktif' ? 'badge-success' : 'badge-secondary' }}" style="font-size:10px;">
                                        {{ $ys->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" style="text-align:center;padding:12px;color:var(--slate-400);">Belum ada riwayat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .checkbox-container {
        display: block; position: relative; padding-left: 28px; cursor: pointer;
        user-select: none; font-size: 14px; line-height: 20px;
    }
    .checkbox-container input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
    .checkmark {
        position: absolute; top: 0; left: 0; height: 20px; width: 20px;
        background-color: var(--slate-100); border-radius: 4px; border: 1px solid var(--slate-300);
        transition: all .2s;
    }
    .checkbox-container:hover input ~ .checkmark { background-color: var(--slate-200); }
    .checkbox-container input:checked ~ .checkmark { background-color: var(--danger); border-color: var(--danger); }
    .checkmark:after {
        content: ""; position: absolute; display: none;
        left: 7px; top: 3px; width: 4px; height: 9px;
        border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg);
    }
    .checkbox-container input:checked ~ .checkmark:after { display: block; }

    .btn-outline:hover { background: var(--warning-bg); }

    .alert {
        padding: 10px 16px; border-radius: var(--radius-sm); font-size: 13px;
        display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
        animation: slideDown .3s ease;
        background: var(--success-bg); color: var(--success); border: 1px solid var(--success-border);
    }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection

@section('scripts')
<script>
    // Auto-hide flash message
    var flash = document.getElementById('flashMsg');
    if (flash) {
        setTimeout(function() {
            flash.style.opacity = '0';
            flash.style.transition = 'opacity .3s';
            setTimeout(function() { flash.remove(); }, 300);
        }, 3000);
    }
</script>
@endsection
