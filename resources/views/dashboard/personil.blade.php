@extends('layouts.app')

@section('title', 'Dashboard Personil')
@section('page-title', 'Dashboard Personil')
@section('page-subtitle', 'Tahun Anggaran ' . $fiscalYear)

@section('content')
{{-- Profile Summary --}}
<div class="card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); overflow: hidden; position: relative;">
    {{-- Decorative Background Glow --}}
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
                    <span style="color: var(--slate-300);">•</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><i class="ri-medal-2-line" style="color: var(--slate-400);"></i> {{ $personnel->rank->name ?? '' }}</span>
                    <span style="color: var(--slate-300);">•</span>
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

{{-- Status Cards --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-top: 24px; margin-bottom: 24px;">
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

{{-- Action Button (CTA Belum Input) --}}
@if(!$hasSubmitted)
<div class="card" style="border: 2px dashed rgba(var(--brand-rgb, 198,40,40), 0.3); background: rgba(var(--brand-rgb, 198,40,40), 0.02); border-radius: var(--radius-lg); overflow: hidden; position: relative;">
    <div style="position: absolute; left: 0; right: 0; top: 0; height: 100%; background: linear-gradient(180deg, transparent 0%, rgba(var(--brand-rgb, 198,40,40), 0.03) 100%); pointer-events: none;"></div>
    <div class="card-body" style="text-align:center; padding: 60px 40px; position: relative; z-index: 1;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; background: #ffffff; border-radius: 50%; box-shadow: 0 8px 16px rgba(var(--brand-rgb, 198,40,40), 0.15); font-size:36px; color:var(--brand); margin-bottom:20px;">
            <i class="ri-shirt-fill"></i>
        </div>
        <h3 style="font-size:22px; font-weight:800; color: var(--text-main); margin-bottom:12px; letter-spacing: -0.5px;">Belum Ada Data Ukuran Anda</h3>
        <p style="font-size:15px; color: var(--text-muted); margin-bottom:32px; max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.6;">
            Aset data kaporlap Anda masih kosong. Pastikan Anda mengisi kelengkapan rekam ukur kapor untuk keperluan logistik Tahun Anggaran <strong>{{ $fiscalYear }}</strong> secara tepat.
        </p>
        <a href="{{ route('personil.kapor.index') }}" class="btn btn-primary" style="font-size:15px; font-weight: 700; padding:16px 36px; border-radius: 9999px; box-shadow: 0 4px 14px rgba(var(--brand-rgb, 198,40,40), 0.4); display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
            <i class="ri-edit-line"></i> Mulai Input Ukuran Sekarang
        </a>
    </div>
</div>
@endif

{{-- Submission History --}}
@if($hasSubmitted)
<div class="card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); overflow: hidden;">
    <div class="card-header" style="background: var(--bg-card); border-bottom: 1px solid var(--border-color); padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(var(--brand-rgb, 198,40,40), 0.1); color: var(--brand);">
                <i class="ri-shirt-line" style="font-size: 18px;"></i>
            </span>
            Rincian Ukuran Kapor Anda
        </h3>
        <span style="background: var(--info-bg); color: var(--info); font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 12px;">TA {{ $fiscalYear }}</span>
    </div>
    <div class="card-body flush">
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--hover-bg); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; width: 60px;">No</th>
                        <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Kategori</th>
                        <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Nama Item</th>
                        <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Ukuran Pilihan</th>
                        <th style="padding: 14px 24px; text-align: right; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Tanggal Input</th>
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
                                <td style="padding: 16px 24px; font-size: 13px; color: var(--text-muted); text-align: right;">{{ $personnel->updated_at->format('d M Y, H:i') }}</td>
                            </tr>
                        @endif
                    @endforeach
                    
                    @if($idx === 0)
                         <tr>
                            <td colspan="5" style="text-align:center; padding:40px 20px; color: var(--text-muted);">
                                <div style="font-size: 40px; margin-bottom: 12px; opacity: 0.5;"><i class="ri-inbox-line"></i></div>
                                Belum ada rincian data ukuran kaporlap yang terekap.
                            </td>
                         </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
