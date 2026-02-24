@extends('layouts.app')

@section('title', 'Dashboard Superadmin')
@section('breadcrumb', 'Dashboard')

@section('content')
{{-- Page Header --}}
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Dashboard</h1>
            <p>Selamat datang kembali. Berikut ringkasan data SI-KAPOR TA {{ $stats['fiscal_year'] }}.</p>
        </div>
        <div class="page-header-actions">
            {{-- Filter Tahun Anggaran --}}
            <div style="display:flex;align-items:center;gap:8px;margin-right:8px;background:#fff;padding:4px 10px;border-radius:var(--radius-sm);border:1px solid var(--slate-200);">
                <i class="ri-calendar-line" style="color:var(--brand);"></i>
                <select onchange="window.location.href='?year='+this.value" style="border:none;outline:none;font-size:13px;font-weight:600;color:var(--slate-700);cursor:pointer;background:transparent;">
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}" {{ $fiscalYear == $year ? 'selected' : '' }}>
                            TA {{ $year }} {{ $year == $defaultYear ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($fiscalYear != $defaultYear)
                <span class="badge badge-warning" style="margin-right:12px;padding:8px 12px;"><i class="ri-history-line"></i> Mode Arsip</span>
            @endif

            @if($stats['is_locked'])
                <span class="btn btn-outline" style="cursor:default;"><span class="status-dot red"></span> Sistem Terkunci</span>
            @else
                <span class="btn btn-outline" style="cursor:default;"><span class="status-dot green"></span> Sistem Aktif</span>
            @endif
            <a href="{{ route('superadmin.settings.index') }}" class="btn btn-primary"><i class="ri-settings-3-line"></i> Pengaturan</a>
        </div>
    </div>
</div>

{{-- ═══ Stat Cards ═══ --}}
<div class="stats-row" style="grid-template-columns: repeat(5, 1fr);">
    {{-- Total POLRI --}}
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total POLRI</span>
            <div class="stat-icon-sm" style="background:var(--success-bg);color:var(--success);"><i class="ri-team-line"></i></div>
        </div>
        <div class="stat-value">{{ number_format($stats['total_polri']) }}</div>
        <div class="stat-footer">Personil Aktif</div>
    </div>

    {{-- Total PNS --}}
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total PNS/P3K</span>
            <div class="stat-icon-sm" style="background:var(--warning-bg);color:var(--warning);"><i class="ri-user-star-line"></i></div>
        </div>
        <div class="stat-value">{{ number_format($stats['total_pns']) }}</div>
        <div class="stat-footer">Personil Aktif</div>
    </div>

    {{-- Total Personil (Combined) --}}
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Personil</span>
            <div class="stat-icon-sm" style="background:var(--brand-bg);color:var(--brand);"><i class="ri-group-line"></i></div>
        </div>
        <div class="stat-value">{{ number_format($stats['total_personnel']) }}</div>
        <div class="stat-footer">Polri + PNS</div>
    </div>

    {{-- Sudah Input --}}
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Sudah Input</span>
            <div class="stat-icon-sm" style="background:var(--info-bg);color:var(--info);"><i class="ri-check-double-line"></i></div>
        </div>
        <div class="stat-value" style="color:var(--info);">{{ number_format($stats['personnel_submitted']) }}</div>
        <div class="stat-footer"><span class="up"><i class="ri-arrow-up-s-fill"></i> {{ $stats['fill_rate'] }}%</span> progres</div>
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

{{-- ═══ Fill Rate & System Info ═══ --}}
<div class="grid-3-1">
    {{-- Progres per Satker --}}
    <div class="card">
        <div class="card-head">
            <h3><i class="ri-building-2-line" style="margin-right:6px;color:var(--brand);"></i> Progres per Satker</h3>
            <div class="card-actions">
                <span class="badge badge-info">TA {{ $stats['fiscal_year'] }}</span>
            </div>
        </div>
        <div class="card-body flush">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px;text-align:center;">No</th>
                            <th>Satker</th>
                            <th style="width:80px;text-align:center;">POLRI</th>
                            <th style="width:80px;text-align:center;">PNS</th>
                            <th style="width:80px;text-align:center;">Jml</th>
                            <th style="width:80px;text-align:center;">Input</th>
                            <th style="width:140px;text-align:center;">Progres</th>
                            <th style="width:70px;text-align:center;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($satkerStats as $index => $s)
                        @php
                            $total = $s->total_personnel;
                            $done = $s->submitted_count;
                            $pct = $total > 0 ? round(($done / $total) * 100) : 0;
                            $barCls = $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red');
                            $badgeCls = $pct >= 80 ? 'badge-success' : ($pct >= 50 ? 'badge-warning' : 'badge-danger');
                        @endphp
                        <tr>
                            <td style="text-align:center;color:var(--slate-400);font-size:12px;">{{ $index + 1 }}</td>
                            <td style="font-weight:600;font-size:12px;">{{ $s->name }}</td>
                            <td style="text-align:center;color:var(--slate-500);font-size:12px;">{{ number_format($s->polri_count) }}</td>
                            <td style="text-align:center;color:var(--slate-500);font-size:12px;">{{ number_format($s->pns_count) }}</td>
                            <td style="text-align:center;font-weight:700;font-size:12px;">{{ number_format($total) }}</td>
                            <td style="text-align:center;font-size:12px;">{{ number_format($done) }}</td>
                            <td>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar {{ $barCls }}" style="width:{{ $pct }}%;"></div>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge {{ $badgeCls }}" style="font-size:10px;padding:2px 6px;">{{ $pct }}%</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" style="text-align:center;color:var(--slate-400);padding:32px;">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                    @if($satkerStats->isNotEmpty())
                    @php
                        $grandPolri = $satkerStats->sum('polri_count');
                        $grandPns = $satkerStats->sum('pns_count');
                        $grandTotal = $satkerStats->sum('total_personnel');
                        $grandDone = $satkerStats->sum('submitted_count');
                        $grandPct = $grandTotal > 0 ? round(($grandDone / $grandTotal) * 100) : 0;
                        $grandBarCls = $grandPct >= 80 ? 'green' : ($grandPct >= 50 ? 'yellow' : 'red');
                        $grandBadgeCls = $grandPct >= 80 ? 'badge-success' : ($grandPct >= 50 ? 'badge-warning' : 'badge-danger');
                    @endphp
                    <tfoot>
                        <tr style="background:var(--slate-50);border-top:2px solid var(--slate-200);">
                            <td style="text-align:center;"></td>
                            <td style="font-weight:700;">TOTAL</td>
                            <td style="text-align:center;font-weight:700;color:var(--slate-500);">{{ number_format($grandPolri) }}</td>
                            <td style="text-align:center;font-weight:700;color:var(--slate-500);">{{ number_format($grandPns) }}</td>
                            <td style="text-align:center;font-weight:700;">{{ number_format($grandTotal) }}</td>
                            <td style="text-align:center;font-weight:700;">{{ number_format($grandDone) }}</td>
                            <td>
                                <div class="progress" style="height:10px;">
                                    <div class="progress-bar {{ $grandBarCls }}" style="width:{{ $grandPct }}%;"></div>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge {{ $grandBadgeCls }}">{{ $grandPct }}%</span>
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Activities or Other Info --}}
    <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="card">
            <div class="card-head">
                <h3><i class="ri-history-line" style="margin-right:6px;color:var(--brand);"></i> User Terbaru</h3>
            </div>
            <div class="card-body flush">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentUsers as $ru)
                            <tr>
                                <td>
                                    <div style="display:flex;flex-direction:column;">
                                        <span style="font-weight:600;font-size:12px;">{{ $ru->name }}</span>
                                        <span style="font-size:10px;color:var(--slate-400);">{{ $ru->nrp_nip }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info" style="font-size:10px;">{{ $ru->roles->first()->name ?? '-' }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
