@extends('layouts.app')

@section('title', 'Dashboard Admin Satker')
@section('page-title', 'Dashboard — ' . $stats['satker_name'])
@section('page-subtitle', 'Tahun Anggaran ' . $stats['fiscal_year'])

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="ri-team-line"></i></div>
        <div class="stat-value">{{ $stats['total_personnel'] }}</div>
        <div class="stat-label">Total Personil</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="ri-check-double-line"></i></div>
        <div class="stat-value">{{ $stats['submitted'] }}</div>
        <div class="stat-label">Sudah Input</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="ri-time-line"></i></div>
        <div class="stat-value">{{ $stats['pending'] }}</div>
        <div class="stat-label">Belum Input</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold"><i class="ri-percent-line"></i></div>
        <div class="stat-value">{{ $stats['fill_rate'] }}%</div>
        <div class="stat-label">Tingkat Pengisian</div>
    </div>
</div>

{{-- Progress --}}
<div class="card">
    <div class="card-header">
        <h3><i class="ri-bar-chart-box-line" style="margin-right:8px; color:var(--accent);"></i> Progres Pengisian {{ $stats['satker_name'] }}</h3>
    </div>
    <div class="card-body">
        @php $pct = $stats['fill_rate']; @endphp
        <div class="progress" style="height:14px; border-radius:7px;">
            <div class="progress-bar {{ $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red') }}" style="width:{{ $pct }}%;"></div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:#64748B;">
            {{ $stats['submitted'] }} dari {{ $stats['total_personnel'] }} personil telah mengisi data kapor.
        </div>
    </div>
</div>

{{-- Personil Belum Input --}}
<div class="card">
    <div class="card-header">
        <h3><i class="ri-alert-line" style="margin-right:8px; color:var(--danger);"></i> Personil Belum Input (Maks. 20)</h3>
        <a href="{{ route('admin-satker.monitor') }}" class="btn btn-sm btn-outline">Lihat Semua</a>
    </div>
    <div class="card-body">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>NRP/NIP</th>
                        <th>Pangkat</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingPersonnel as $idx => $p)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td style="font-weight:600;">{{ $p->full_name }}</td>
                        <td>{{ $p->user->nrp_nip ?? '-' }}</td>
                        <td>{{ $p->rank->name ?? '-' }}</td>
                        <td><span class="badge badge-warning"><i class="ri-time-line"></i> Belum Input</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center; color:var(--success); padding:24px;">
                        <i class="ri-check-double-line" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        Semua personil sudah mengisi data kapor! 🎉
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
