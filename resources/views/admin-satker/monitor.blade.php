@extends('layouts.app')

@section('title', 'Monitoring Pengisian Kapor')
@section('breadcrumb', 'Monitoring')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; letter-spacing: -.3px; color: #0F172A;">
                <i class="ri-eye-line" style="margin-right: 6px; color: var(--brand);"></i> Monitoring Pengisian Kapor
            </h1>
            <p style="font-size: 13px; color: #64748B; margin-top: 2px;">
                {{ $stats['satker_name'] }} — TA {{ $stats['fiscal_year'] }}
            </p>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="stats-row" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Personel</span>
            <div class="stat-icon-sm" style="background:var(--brand-bg);color:var(--brand);"><i class="ri-group-line"></i></div>
        </div>
        <div class="stat-value">{{ number_format($stats['total_personnel']) }}</div>
        <div class="stat-footer">Personel terdaftar</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Sudah Input</span>
            <div class="stat-icon-sm" style="background:var(--success-bg);color:var(--success);"><i class="ri-check-double-line"></i></div>
        </div>
        <div class="stat-value" style="color:var(--success);">{{ number_format($stats['personnel_submitted']) }}</div>
        <div class="stat-footer"><span class="up"><i class="ri-arrow-up-s-fill"></i> {{ $stats['fill_rate'] }}%</span> progres</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Belum Input</span>
            <div class="stat-icon-sm" style="background:#fef2f2;color:var(--danger);"><i class="ri-time-line"></i></div>
        </div>
        <div class="stat-value" style="color:var(--danger);">{{ number_format($stats['personnel_pending']) }}</div>
        <div class="stat-footer">Menunggu pengisian</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Progres</span>
            <div class="stat-icon-sm" style="background:var(--info-bg);color:var(--info);"><i class="ri-bar-chart-box-line"></i></div>
        </div>
        <div class="stat-value" style="color:var(--info);">{{ $stats['fill_rate'] }}%</div>
        <div class="stat-footer">Tingkat pengisian</div>
    </div>
</div>

{{-- Progress Bar --}}
<div class="card">
    <div class="card-body" style="padding: 16px 20px;">
        @php $pct = $stats['fill_rate']; @endphp
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
            <span style="font-size: 13px; font-weight: 600; color: #334155;">Progres Pengisian Data</span>
            <span style="font-size: 13px; font-weight: 700; color: {{ $pct >= 80 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)') }};">{{ $pct }}%</span>
        </div>
        <div class="progress" style="height: 14px; border-radius: 7px;">
            <div class="progress-bar {{ $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red') }}" style="width:{{ $pct }}%;"></div>
        </div>
        <div style="margin-top: 8px; font-size: 12px; color: #94A3B8;">
            {{ $stats['personnel_submitted'] }} dari {{ $stats['total_personnel'] }} personil telah mengisi data kapor.
        </div>
    </div>
</div>

{{-- Filter & Search --}}
<div class="card">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--slate-100); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <h3 style="font-size: 14px; font-weight: 700; color: #1E293B; display: flex; align-items: center; gap: 8px;">
            <i class="ri-list-check-2" style="color: var(--brand);"></i> Daftar Personel
        </h3>

        <form method="GET" action="{{ route('admin-satker.monitor') }}" style="display: flex; gap: 8px; align-items: center;">
            <div style="position: relative;">
                <i class="ri-search-line" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 14px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NRP..."
                    style="padding: 7px 12px 7px 32px; border: 1px solid var(--slate-200); border-radius: 8px; font-size: 13px; width: 220px; font-family: inherit;">
            </div>
            <select name="status" onchange="this.form.submit()"
                style="padding: 7px 12px; border: 1px solid var(--slate-200); border-radius: 8px; font-size: 13px; font-family: inherit; background: #fff; cursor: pointer;">
                <option value="">Semua Status</option>
                <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>✅ Sudah Input</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ Belum Input</option>
            </select>
            <button type="submit" class="btn btn-primary" style="padding: 7px 14px;">
                <i class="ri-search-line"></i> Cari
            </button>
            @if(request('search') || request('status'))
            <a href="{{ route('admin-satker.monitor') }}" class="btn btn-outline" style="padding: 7px 14px;">
                <i class="ri-close-line"></i> Reset
            </a>
            @endif
        </form>
    </div>

    <div class="card-body flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Personel</th>
                        <th>Pangkat / Gol</th>
                        <th>Jabatan</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($personnels as $idx => $p)
                    @php
                        $hasValidNrp = $p->nrp && !str_starts_with($p->nrp, 'TEMP-');
                        $isComplete = $p->kapor_sizes && $p->rank_id && $hasValidNrp;
                    @endphp
                    <tr>
                        <td style="color: #94A3B8; font-size: 12px;">{{ ($personnels->currentPage() - 1) * $personnels->perPage() + $idx + 1 }}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0; background: {{ $isComplete ? 'var(--success)' : '#94A3B8' }};">
                                    {{ strtoupper(substr($p->full_name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #1E293B; font-size: 13px;">{{ $p->full_name }}</div>
                                    <div style="font-size: 11.5px; color: #94A3B8;">{{ ($p->nrp && !str_starts_with($p->nrp, 'TEMP-')) ? $p->nrp : '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 13px; color: #334155;">{{ $p->rank->name ?? '—' }}</div>
                            <div style="font-size: 11.5px; color: #94A3B8;">{{ $p->golongan ?? $p->rank->category ?? '—' }}</div>
                        </td>
                        <td style="font-size: 13px; color: #475569;">{{ $p->jabatan ?? '—' }}</td>
                        <td style="text-align: center;">
                            @if($isComplete)
                                <span class="badge badge-success"><i class="ri-check-line"></i> Lengkap</span>
                            @else
                                <span class="badge badge-warning"><i class="ri-time-line"></i> Belum</span>
                                @php
                                    $missing = [];
                                    if (!$hasValidNrp) $missing[] = 'NRP/NIP';
                                    if (!$p->rank_id) $missing[] = 'Pangkat';
                                    if (!$p->kapor_sizes) $missing[] = 'Ukuran';
                                @endphp
                                <div style="font-size: 10px; color: #EF4444; margin-top: 2px;">
                                    {{ implode(', ', $missing) }}
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 48px; color: #94A3B8;">
                            <i class="ri-search-line" style="font-size: 36px; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                            Tidak ada data ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($personnels->hasPages())
        <div style="padding: 12px 20px; border-top: 1px solid var(--slate-100); display: flex; align-items: center; justify-content: space-between;">
            <div style="font-size: 12px; color: #94A3B8;">
                Menampilkan {{ $personnels->firstItem() }} - {{ $personnels->lastItem() }} dari {{ $personnels->total() }} personel
            </div>
            <div style="display: flex; gap: 4px;">
                @if($personnels->onFirstPage())
                    <span class="btn btn-outline btn-sm" style="opacity: 0.5; cursor: default;"><i class="ri-arrow-left-s-line"></i></span>
                @else
                    <a href="{{ $personnels->previousPageUrl() }}" class="btn btn-outline btn-sm"><i class="ri-arrow-left-s-line"></i></a>
                @endif

                <span class="btn btn-outline btn-sm" style="font-weight: 700;">{{ $personnels->currentPage() }} / {{ $personnels->lastPage() }}</span>

                @if($personnels->hasMorePages())
                    <a href="{{ $personnels->nextPageUrl() }}" class="btn btn-outline btn-sm"><i class="ri-arrow-right-s-line"></i></a>
                @else
                    <span class="btn btn-outline btn-sm" style="opacity: 0.5; cursor: default;"><i class="ri-arrow-right-s-line"></i></span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
