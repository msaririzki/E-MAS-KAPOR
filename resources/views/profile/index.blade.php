@extends('layouts.app')

@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')

@section('content')
@php
    $user = auth()->user();
    $personnel = $user->personnel;
@endphp

<div class="card" style="border-left: 4px solid var(--accent);">
    <div class="card-body">
        <div style="display:flex; align-items:center; gap:24px; flex-wrap:wrap;">
            <div style="width:72px; height:72px; border-radius:50%; background:linear-gradient(135deg, var(--primary), var(--primary-light)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:28px; font-weight:700; flex-shrink:0;">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <h2 style="font-size:22px; font-weight:700;">{{ $user->name }}</h2>
                <p style="color:#64748B; font-size:14px;">
                    {{ ucfirst(str_replace('_', ' ', $user->getRoleNames()->first() ?? 'User')) }}
                    @if($user->satker) · {{ $user->satker->name }} @endif
                </p>
            </div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap:24px;">
    <div class="card">
        <div class="card-header"><h3><i class="ri-user-line" style="margin-right:8px; color:var(--accent);"></i> Informasi Akun</h3></div>
        <div class="card-body">
            <table style="width:100%;">
                <tr><td style="font-weight:600; width:140px; padding:10px 0; color:#64748B; font-size:13px; border:none;">NRP/NIP</td><td style="padding:10px 0; border:none;">{{ $user->nrp_nip }}</td></tr>
                <tr><td style="font-weight:600; padding:10px 0; color:#64748B; font-size:13px; border:none;">Nama</td><td style="padding:10px 0; border:none;">{{ $user->name }}</td></tr>
                <tr><td style="font-weight:600; padding:10px 0; color:#64748B; font-size:13px; border:none;">Email</td><td style="padding:10px 0; border:none;">{{ $user->email ?? '-' }}</td></tr>
                <tr><td style="font-weight:600; padding:10px 0; color:#64748B; font-size:13px; border:none;">Satker</td><td style="padding:10px 0; border:none;">{{ $user->satker->name ?? '-' }}</td></tr>
                <tr><td style="font-weight:600; padding:10px 0; color:#64748B; font-size:13px; border:none;">Status</td><td style="padding:10px 0; border:none;">
                    @if($user->is_active) <span class="badge badge-success">Aktif</span> @else <span class="badge badge-danger">Nonaktif</span> @endif
                </td></tr>
                <tr><td style="font-weight:600; padding:10px 0; color:#64748B; font-size:13px; border:none;">Role</td><td style="padding:10px 0; border:none;">
                    <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $user->getRoleNames()->first() ?? '-')) }}</span>
                </td></tr>
            </table>
        </div>
    </div>

    @if($personnel)
    <div class="card">
        <div class="card-header"><h3><i class="ri-id-card-line" style="margin-right:8px; color:var(--accent);"></i> Data Personil</h3></div>
        <div class="card-body">
            <table style="width:100%;">
                <tr><td style="font-weight:600; width:140px; padding:10px 0; color:#64748B; font-size:13px; border:none;">Nama Lengkap</td><td style="padding:10px 0; border:none;">{{ $personnel->full_name }}</td></tr>
                <tr><td style="font-weight:600; padding:10px 0; color:#64748B; font-size:13px; border:none;">Gender</td><td style="padding:10px 0; border:none;">{{ $personnel->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
                <tr><td style="font-weight:600; padding:10px 0; color:#64748B; font-size:13px; border:none;">Tipe</td><td style="padding:10px 0; border:none;">{{ $personnel->personnel_type }}</td></tr>
                <tr><td style="font-weight:600; padding:10px 0; color:#64748B; font-size:13px; border:none;">Pangkat</td><td style="padding:10px 0; border:none;">{{ $personnel->rank->name ?? '-' }}</td></tr>
                <tr><td style="font-weight:600; padding:10px 0; color:#64748B; font-size:13px; border:none;">Telepon</td><td style="padding:10px 0; border:none;">{{ $personnel->phone ?? '-' }}</td></tr>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
