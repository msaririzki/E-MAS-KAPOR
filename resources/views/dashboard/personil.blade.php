@extends('layouts.app')

@section('title', 'Dashboard Personil')
@section('page-title', 'Dashboard Personil')
@section('page-subtitle', 'Tahun Anggaran ' . $fiscalYear)

@section('content')
{{-- Profile Summary --}}
<div class="card" style="border-left: 4px solid var(--accent);">
    <div class="card-body" style="display:flex; align-items:center; gap:24px; flex-wrap:wrap;">
        <div style="width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg, var(--primary), var(--primary-light)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; font-weight:700; flex-shrink:0;">
            {{ strtoupper(substr($user->name, 0, 2)) }}
        </div>
        <div style="flex:1; min-width:200px;">
            <div style="font-size:18px; font-weight:700;">{{ $user->name }}</div>
            <div style="font-size:13px; color:#64748B; margin-top:2px;">
                NRP/NIP: <strong>{{ $user->nrp_nip }}</strong>
                @if($personnel)
                    &nbsp;·&nbsp; {{ $personnel->rank->name ?? '' }}
                    &nbsp;·&nbsp; {{ $personnel->satker->name ?? '' }}
                @endif
            </div>
        </div>
        <div>
            @if($hasSubmitted)
                <span class="badge badge-success" style="font-size:13px; padding:8px 16px;">
                    <i class="ri-check-double-line"></i> Sudah Input TA {{ $fiscalYear }}
                </span>
            @else
                <span class="badge badge-warning" style="font-size:13px; padding:8px 16px;">
                    <i class="ri-alert-line"></i> Belum Input TA {{ $fiscalYear }}
                </span>
            @endif
        </div>
    </div>
</div>

{{-- Status Cards --}}
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card">
        <div class="stat-icon {{ $hasSubmitted ? 'green' : 'yellow' }}">
            <i class="ri-{{ $hasSubmitted ? 'check-double' : 'edit' }}-line"></i>
        </div>
        <div class="stat-value">{{ is_array($kaporSizes) ? count(array_filter($kaporSizes)) : 0 }}</div>
        <div class="stat-label">Item Terisi</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="ri-calendar-line"></i></div>
        <div class="stat-value">{{ $fiscalYear }}</div>
        <div class="stat-label">Tahun Anggaran</div>
    </div>
</div>

{{-- Action Button --}}
@if(!$hasSubmitted)
<div class="card" style="border: 2px dashed var(--accent); background: #FFFDF5;">
    <div class="card-body" style="text-align:center; padding:40px;">
        <div style="font-size:48px; color:var(--accent); margin-bottom:16px;"><i class="ri-shirt-line"></i></div>
        <h3 style="font-size:18px; font-weight:700; margin-bottom:8px;">Belum Ada Data Kapor</h3>
        <p style="font-size:14px; color:#64748B; margin-bottom:24px;">
            Silakan input ukuran kapor Anda untuk Tahun Anggaran {{ $fiscalYear }}.
        </p>
        <a href="{{ route('personil.kapor.index') }}" class="btn btn-accent" style="font-size:15px; padding:14px 32px;">
            <i class="ri-edit-line"></i> Mulai Input Ukuran Kapor
        </a>
    </div>
</div>
@endif

{{-- Submission History --}}
@if($hasSubmitted)
<div class="card">
    <div class="card-header">
        <h3><i class="ri-shirt-line" style="margin-right:8px; color:var(--accent);"></i> Data Ukuran Kapor Anda</h3>
        <span class="badge badge-info">TA {{ $fiscalYear }}</span>
    </div>
    <div class="card-body">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kategori</th>
                        <th>Item</th>
                        <th>Ukuran</th>
                        <th>Tanggal Input</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $itemMap = [
                            'topi' => ['label' => 'TUTUP KEPALA', 'category' => 'Tutup Kepala'],
                            'jilbab' => ['label' => 'Jilbab', 'category' => 'Tutup Kepala'],
                            'kemeja' => ['label' => 'Kemeja (PDH/PDL)', 'category' => 'Tutup Badan'],
                            'celana' => ['label' => 'Celana/Rok', 'category' => 'Tutup Badan'],
                            'jaket' => ['label' => 'Jaket', 'category' => 'Tutup Badan'],
                            'olahraga' => ['label' => 'T-Shirt/Olahraga', 'category' => 'Tutup Badan'],
                            'sabuk' => ['label' => 'Sabuk', 'category' => 'Perlengkapan'],
                            'sepatu_dinas' => ['label' => 'Sepatu Dinas', 'category' => 'Tutup Kaki'],
                            'sepatu_olahraga' => ['label' => 'Sepatu Olahraga', 'category' => 'Tutup Kaki'],
                        ];
                        $idx = 0;
                    @endphp

                    @foreach($itemMap as $key => $meta)
                        @if(isset($kaporSizes[$key]) && !empty($kaporSizes[$key]))
                            <tr>
                                <td>{{ ++$idx }}</td>
                                <td><span class="badge badge-info">{{ $meta['category'] }}</span></td>
                                <td style="font-weight:600;">{{ $meta['label'] }}</td>
                                <td><span style="font-weight:700; color:var(--primary);">{{ $kaporSizes[$key] }}</span></td>
                                <td>{{ $personnel->updated_at->format('d M Y, H:i') }}</td>
                            </tr>
                        @endif
                    @endforeach
                    
                    @if($idx === 0)
                         <tr><td colspan="5" style="text-align:center; padding:20px;">Belum ada data ukuran.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
