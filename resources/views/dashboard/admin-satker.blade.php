@extends('layouts.app')

@section('title', 'Dashboard Admin Satker')
@section('page-title', 'Dashboard — ' . $stats['satker_name'])
@section('page-subtitle', 'Tahun Anggaran ' . $stats['fiscal_year'])

@section('content')
    <div class="stats-row" style="grid-template-columns: repeat(5, 1fr);">
        {{-- Total POLRI --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total POLRI</span>
                <div class="stat-icon-sm" style="background:var(--success-bg);color:var(--success);"><i
                        class="ri-team-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_polri']) }}</div>
            <div class="stat-footer">Personil Aktif</div>
        </div>

        {{-- Total PNS --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total PNS/P3K</span>
                <div class="stat-icon-sm" style="background:var(--warning-bg);color:var(--warning);"><i
                        class="ri-user-star-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_pns']) }}</div>
            <div class="stat-footer">Personil Aktif</div>
        </div>

        {{-- Total Personil (Combined) --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Personil</span>
                <div class="stat-icon-sm" style="background:var(--brand-bg);color:var(--brand);"><i
                        class="ri-group-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_personnel']) }}</div>
            <div class="stat-footer">Polri + PNS</div>
        </div>

        {{-- Sudah Input --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Sudah Input</span>
                <div class="stat-icon-sm" style="background:var(--info-bg);color:var(--info);"><i
                        class="ri-check-double-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--info);">{{ number_format($stats['personnel_submitted']) }}</div>
            <div class="stat-footer"><span class="up"><i class="ri-arrow-up-s-fill"></i> {{ $stats['fill_rate'] }}%</span>
                progres</div>
        </div>

        {{-- Belum Input --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Belum Input</span>
                <div class="stat-icon-sm" style="background:#fef2f2;color:var(--danger);"><i class="ri-time-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--danger);">{{ number_format($stats['personnel_pending']) }}</div>
            <div class="stat-footer">Menunggu pengisian</div>
        </div>
    </div>

    {{-- Progress --}}
    <div class="card">
        <div class="card-header">
            <h3><i class="ri-bar-chart-box-line" style="margin-right:8px; color:var(--accent);"></i> Progres Pengisian
                {{ $stats['satker_name'] }}</h3>
        </div>
        <div class="card-body">
            @php $pct = $stats['fill_rate']; @endphp
            <div class="progress" style="height:14px; border-radius:7px;">
                <div class="progress-bar {{ $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red') }}"
                    style="width:{{ $pct }}%;"></div>
            </div>
            <div style="margin-top:12px; font-size:13px; color:#64748B;">
                {{ $stats['submitted'] }} dari {{ $stats['total_personnel'] }} personil telah mengisi data kapor.
            </div>
        </div>
    </div>

    {{-- Personil Belum Input --}}
    <div class="card">
        <div class="card-header">
            <h3><i class="ri-alert-line" style="margin-right:8px; color:var(--danger);"></i> Personil Belum Input (Maks. 20)
            </h3>
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
                            <tr>
                                <td colspan="5" style="text-align:center; color:var(--success); padding:24px;">
                                    <i class="ri-check-double-line" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                                    Semua personil sudah mengisi data kapor! 🎉
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection