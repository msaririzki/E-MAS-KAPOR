@extends('layouts.app')

@section('title', 'Dashboard Superadmin')
@section('breadcrumb', 'Dashboard')

@section('content')
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-header-row">
            <div>
                <h1>Dashboard</h1>
                <p>Selamat datang kembali. Berikut ringkasan data E-MAS KAPOR TA {{ $stats['fiscal_year'] }}.</p>
            </div>
            <div class="page-header-actions">
                {{-- Filter Tahun Anggaran --}}
                <div
                    style="display:flex;align-items:center;gap:8px;margin-right:8px;background:var(--input-bg);padding:4px 10px;border-radius:var(--radius-sm);border:1px solid var(--border-color);">
                    <i class="ri-calendar-line" style="color:var(--brand);"></i>
                    <select onchange="window.location.href='?year='+this.value"
                        style="border:none;outline:none;font-size:13px;font-weight:600;color:var(--text-main);cursor:pointer;background:transparent;">
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}" {{ $fiscalYear == $year ? 'selected' : '' }}>
                                TA {{ $year }} {{ $year == $defaultYear ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($fiscalYear != $defaultYear)
                    <span class="badge badge-warning" style="margin-right:12px;padding:8px 12px;"><i
                            class="ri-history-line"></i> Mode Arsip</span>
                @endif

                @if($stats['is_locked'])
                    <span class="btn btn-outline" style="cursor:default;"><span class="status-dot red"></span> Sistem
                        Terkunci</span>
                @else
                    <span class="btn btn-outline" style="cursor:default;"><span class="status-dot green"></span> Sistem
                        Aktif</span>
                @endif
                <a href="{{ route('superadmin.settings.index') }}" class="btn btn-primary"><i
                        class="ri-settings-3-line"></i> Pengaturan</a>
            </div>
        </div>
    </div>

    {{-- ═══ Stat Cards ═══ --}}
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

    {{-- ═══ Fill Rate & System Info ═══ --}}
    <div class="grid-3-1">
        {{-- Left Column --}}
        <div style="display:flex;flex-direction:column;gap:20px;">
            {{-- Chart Progres per Satker --}}
            <div class="card" style="margin-bottom:0;">
                <div class="card-head">
                    <h3><i class="ri-bar-chart-horizontal-line" style="margin-right:6px;color:var(--brand);"></i> Grafik
                        Progres per Satker</h3>
                </div>
                <div class="card-body" style="overflow-x: auto; max-height: 600px;">
                    <div id="chartWrapper" style="position: relative; width: 100%; min-width: 500px;">
                        <canvas id="satkerChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Progres per Satker Table --}}
            <div class="card">
                <div class="card-head">
                    <h3><i class="ri-building-2-line" style="margin-right:6px;color:var(--brand);"></i> Progres per Satker
                    </h3>
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
                                    <th style="width:80px;text-align:center;">Total</th>
                                    <th style="width:80px;text-align:center;">Input</th>
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
                                        <td style="text-align:center;color:var(--text-muted);font-size:12px;">{{ $index + 1 }}
                                        </td>
                                        <td style="font-weight:600;font-size:12px;">{{ $s->name }}</td>
                                        <td style="text-align:center;font-weight:700;font-size:12px;">
                                            {{ number_format($total) }}</td>
                                        <td style="text-align:center;font-size:12px;">{{ number_format($done) }}</td>
                                        <td style="text-align:center;">
                                            <span class="badge {{ $badgeCls }}"
                                                style="font-size:10px;padding:2px 6px;">{{ $pct }}%</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" style="text-align:center;color:var(--text-muted);padding:32px;">Belum ada
                                            data.</td>
                                    </tr>
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
                                    <tr style="background:var(--bg-body);border-top:2px solid var(--border-color);">
                                        <td style="text-align:center;"></td>
                                        <td style="font-weight:700;">TOTAL</td>
                                        <td style="text-align:center;font-weight:700;">{{ number_format($grandTotal) }}</td>
                                        <td style="text-align:center;font-weight:700;">{{ number_format($grandDone) }}</td>
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
        </div>

        {{-- Right Column --}}
        <div style="display:flex;flex-direction:column;gap:20px;">
            <div class="card" style="margin-bottom:0;">
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
                                                <span style="font-size:10px;color:var(--text-muted);">{{ $ru->nrp_nip }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"
                                                style="font-size:10px;">{{ $ru->roles->first()->name ?? '-' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Testimoni --}}
            <div class="card">
                <div class="card-head">
                    <h3><i class="ri-message-3-line" style="margin-right:6px;color:var(--brand);"></i> Testimoni</h3>
                </div>
                <div class="card-body">
                    <div
                        style="background:var(--bg-body); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:12px;">
                        <div style="font-size:13px; font-style:italic; color:var(--text-muted); margin-bottom:8px;">"Sistem
                            E-MAS KAPOR ini sangat memudahkan kami dalam melakukan pendataan ukuran kaporlap anggota secara
                            real-time. Cepat dan transparan."</div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div
                                style="width:30px; height:30px; border-radius:50%; background:var(--brand); color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700;">
                                KS</div>
                            <div style="font-size:12px; font-weight:600; color:var(--text-main); line-height:1.2;">Kombes
                                Pol Satria <br><span
                                    style="font-size:10px; font-weight:normal; color:var(--text-muted);">Biro Logistik</span>
                            </div>
                        </div>
                    </div>

                    <div
                        style="background:var(--bg-body); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color);">
                        <div style="font-size:13px; font-style:italic; color:var(--text-muted); margin-bottom:8px;">"Aplikasi
                            yang sangat membantu! Admin satker tidak perlu lagi merekap data personil secara manual
                            menggunakan Excel."</div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div
                                style="width:30px; height:30px; border-radius:50%; background:var(--info); color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700;">
                                BR</div>
                            <div style="font-size:12px; font-weight:600; color:var(--text-main); line-height:1.2;">Bripda
                                Rizky <br><span style="font-size:10px; font-weight:normal; color:var(--text-muted);">Admin
                                    Satker</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const satkerData = @json($satkerStats);

            const labels = satkerData.map(s => s.name);
            const dataPct = satkerData.map(s => {
                return s.total_personnel > 0 ? ((s.submitted_count / s.total_personnel) * 100).toFixed(1) : 0;
            });

            // Use dynamic colors based on threshold
            const backgroundColors = dataPct.map(pct => {
                if (pct >= 80) return '#10B981'; // green
                if (pct >= 50) return '#F59E0B'; // yellow
                return '#EF4444'; // red
            });

            const chartHeight = Math.max(300, satkerData.length * 24);
            document.getElementById('chartWrapper').style.height = chartHeight + 'px';

            const ctx = document.getElementById('satkerChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Progres Pengisian (%)',
                        data: dataPct,
                        backgroundColor: backgroundColors,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Makes the bar chart horizontal
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.raw + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function (value) {
                                    return value + '%';
                                }
                            }
                        },
                        y: {
                            ticks: {
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection