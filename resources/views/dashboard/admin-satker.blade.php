@extends('layouts.app')

@section('title', 'Dashboard Admin Satker')
@section('page-title', 'Dashboard - ' . $stats['satker_name'])
@section('page-subtitle', 'Tahun Anggaran ' . $stats['fiscal_year'])



@section('content')
    <div class="stats-row stats-row-5">
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total POLRI</span>
                <div class="stat-icon-sm" style="background:var(--success-bg);color:var(--success);"><i
                        class="ri-team-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_polri']) }}</div>
            <div class="stat-footer">Data personel riil</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total PNS/P3K</span>
                <div class="stat-icon-sm" style="background:var(--warning-bg);color:var(--warning);"><i
                        class="ri-user-star-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_pns']) }}</div>
            <div class="stat-footer">Data personel riil</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Personil</span>
                <div class="stat-icon-sm" style="background:var(--brand-bg);color:var(--brand);"><i
                        class="ri-group-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_personnel']) }}</div>
            <div class="stat-footer">Polri + PNS</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Sudah Isi Ukuran</span>
                <div class="stat-icon-sm" style="background:var(--info-bg);color:var(--info);"><i
                        class="ri-check-double-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--info);">{{ number_format($stats['personnel_submitted']) }}</div>
            <div class="stat-footer"><span class="up"><i class="ri-arrow-up-s-fill"></i> {{ $stats['fill_rate'] }}%</span>
                progres</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Belum Isi Ukuran</span>
                <div class="stat-icon-sm" style="background:#fef2f2;color:var(--danger);"><i class="ri-time-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--danger);">{{ number_format($stats['personnel_pending']) }}</div>
            <div class="stat-footer">Menunggu ukuran wajib</div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); overflow: hidden;">
            <div class="card-header glass-header" style="background: rgba(var(--brand-rgb, 198, 40, 40), 0.03); border-bottom: 1px solid var(--border-color); padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: var(--brand-bg); color: var(--brand);">
                            <i class="ri-bar-chart-box-line" style="font-size: 18px;"></i>
                        </span>
                        Progres Ukuran - {{ $stats['satker_name'] }}
                    </h3>
                    <p style="margin: 4px 0 0 42px; font-size: 13px; color: var(--text-muted);">
                        Mendeteksi jumlah personil yang telah melengkapi ukuran kapor wajib.
                    </p>
                </div>
            </div>
            <div class="card-body" style="padding: 24px;">
                @php $pct = $stats['fill_rate']; @endphp

                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px;">
                    <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">
                        <span style="color: {{ $pct >= 80 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)') }}; font-size: 16px;">{{ $stats['personnel_submitted'] }}</span>
                        <span style="color: var(--text-muted); font-weight: 400; margin: 0 4px;">/</span>
                        {{ $stats['total_personnel'] }} Personil
                    </div>
                    <div style="font-size: 20px; font-weight: 800; color: {{ $pct >= 80 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)') }};">
                        {{ $pct }}%
                    </div>
                </div>

                <div class="progress" style="height: 16px; border-radius: 8px; background: var(--hover-bg); overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
                    <div class="progress-bar progress-bar-animated {{ $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red') }}"
                        style="width: {{ $pct }}%; height: 100%; transition: width 1s ease-in-out; background-image: linear-gradient(45deg, rgba(255,255,255,0.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.15) 75%, transparent 75%, transparent); background-size: 1rem 1rem;">
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); overflow: hidden;">
            <div class="card-header glass-header" style="background: var(--bg-card); border-bottom: 1px solid var(--border-color); padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: var(--danger-bg); color: var(--danger);">
                            <i class="ri-error-warning-line" style="font-size: 18px;"></i>
                        </span>
                        Personil Belum Isi Ukuran
                        @if(count($pendingPersonnel) > 0)
                        <span style="background: var(--danger); color: white; font-size: 11px; padding: 2px 8px; border-radius: 12px; margin-left: 8px;">Maks. 20 ditampilkan</span>
                        @endif
                    </h3>
                </div>
                <a href="{{ route('admin.personnel.index', ['status' => 'incomplete', 'incomplete_scope' => 'size_only']) }}" class="btn btn-outline" style="border-radius: 8px; font-size: 13px; font-weight: 600; padding: 8px 16px; border-color: var(--border-color); color: var(--text-main); display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                    Lihat Daftar Ukuran <i class="ri-arrow-right-line"></i>
                </a>
            </div>

            <div class="card-body flush">
                @if(count($pendingPersonnel) > 0)
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--hover-bg); border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; width: 50px;">No</th>
                                <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Personil</th>
                                <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">NRP / NIP</th>
                                <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Pangkat</th>
                                <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; width: 140px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingPersonnel as $idx => $p)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 16px 24px; font-size: 13px; color: var(--text-muted);">{{ $idx + 1 }}</td>
                                    <td style="padding: 16px 24px;">
                                        <div style="font-weight: 600; color: var(--text-main); font-size: 14px;">{{ $p->full_name }}</div>
                                    </td>
                                    <td style="padding: 16px 24px; font-size: 13px; font-family: 'SFMono-Regular', Consolas, monospace; color: var(--text-muted);">
                                        {{ $p->user?->nrp_nip ?: '—' }}
                                    </td>
                                    <td style="padding: 16px 24px; font-size: 13px; color: var(--text-muted);">
                                        {{ $p->rank->name ?? '—' }}
                                    </td>
                                    <td style="padding: 16px 24px;">
                                        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; background: var(--warning-bg); color: #B45309; border: 1px solid var(--warning-border); white-space: nowrap;">
                                            <i class="ri-time-line"></i> Belum Isi Ukuran
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @elseif($stats['total_personnel'] > 0)
                    <div style="text-align: center; padding: 60px 20px; background: var(--success-bg); border-radius: 0 0 var(--radius-lg) var(--radius-lg);">
                        <div class="pulse-success" style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #ffffff; color: var(--success); box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.1), 0 2px 4px -1px rgba(16, 185, 129, 0.06); margin-bottom: 16px;">
                            <i class="ri-checkbox-circle-fill" style="font-size: 32px;"></i>
                        </div>
                        <h4 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 700; color: #065F46;">Kerja Bagus!</h4>
                        <p style="margin: 0; font-size: 14px; color: #047857;">Semua personil di {{ $stats['satker_name'] }} telah mengisi ukuran wajib kapor.</p>
                    </div>
                @else
                    <div style="text-align: center; padding: 60px 20px; background: var(--bg-body); border-radius: 0 0 var(--radius-lg) var(--radius-lg);">
                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #ffffff; color: var(--slate-400); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-bottom: 16px;">
                            <i class="ri-folder-info-line" style="font-size: 32px;"></i>
                        </div>
                        <h4 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 700; color: var(--text-main);">Belum Ada Personil</h4>
                        <p style="margin: 0; font-size: 14px; color: var(--text-muted);">Tidak ada personil yang terdaftar pada {{ $stats['satker_name'] }}.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('styles')
<style>
    /* Card Hover Animations */
    .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        border: 1px solid var(--border-color) !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02) !important;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 20px -8px rgba(0,0,0,0.1) !important;
        border-color: rgba(var(--brand-rgb, 198,40,40), 0.3) !important;
    }
    .stat-card::after {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 4px;
        background: linear-gradient(90deg, var(--brand), var(--brand-light));
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .stat-card:hover::after {
        opacity: 1;
    }

    /* Progress Bar Animation */
    @keyframes progress-stripes {
        from { background-position: 1rem 0; }
        to { background-position: 0 0; }
    }
    .progress-bar-animated {
        animation: progress-stripes 1s linear infinite;
    }

    /* Table Improvements */
    .table tbody tr {
        transition: all 0.2s ease;
    }
    .table tbody tr:hover td {
        background-color: rgba(var(--brand-rgb, 198,40,40), 0.02);
    }

    /* Glassmorphism accents for headers */
    .glass-header {
        background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.4)) !important;
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255,255,255,0.6) !important;
    }
    
    /* Dynamic success badge pulse */
    @keyframes subtle-pulse {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .pulse-success {
        border-radius: 50%;
        animation: subtle-pulse 2s infinite;
    }
</style>
@endsection
