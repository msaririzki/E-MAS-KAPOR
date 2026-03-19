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
            </div>
        </div>
    </div>
    


    {{-- ═══ Stat Cards ═══ --}}
    <div class="stats-row stats-row-5">
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

        {{-- Sudah Isi Ukuran --}}
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

        {{-- Belum Isi Ukuran --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Belum Isi Ukuran</span>
                <div class="stat-icon-sm" style="background:#fef2f2;color:var(--danger);"><i class="ri-time-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--danger);">{{ number_format($stats['personnel_pending']) }}</div>
            <div class="stat-footer">Menunggu ukuran wajib</div>
        </div>
    </div>

    {{-- ═══ Chart Progres per Satker (Full Width) ═══ --}}
    <div class="card" style="margin-bottom:24px;">
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

    {{-- ═══ Secondary Widgets (Berbaris 3 Kolom) ═══ --}}
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
        
            {{-- Perlu Perhatian (Smart Alerts) --}}
            <div class="card alert-card" style="height:100%; display:flex; flex-direction:column;">
                <div class="card-head" style="background:var(--warning-bg); border-bottom:1px solid var(--border-color); border-radius:var(--radius-md) var(--radius-md) 0 0;">
                    <h3 style="color:var(--warning); display:flex; align-items:center;"><i class="ri-alarm-warning-fill" style="margin-right:8px; animation: pulse 2s infinite;"></i> Perlu Perhatian</h3>
                </div>
                <div class="card-body" style="padding:16px;">
                    @if($stats['personnel_pending'] > 0)
                        <div style="background:var(--bg-body); border-left:4px solid var(--warning); padding:12px 16px; border-radius:4px; margin-bottom:16px; border:1px solid var(--border-color); border-left-width:4px;">
                            <div style="font-weight:700; color:var(--text-main); font-size:14px; margin-bottom:4px;">{{ number_format($stats['personnel_pending']) }} Personil Belum Isi Ukuran</div>
                            <div style="font-size:12px; color:var(--text-muted);">Daftar ini hanya menghitung ukuran kapor yang wajib, tidak mencampur biodata umum.</div>
                        </div>
                        
                        <div style="font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase;">Daftar Acak:</div>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            @foreach($incompletePersonnel as $ip)
                                <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:var(--bg-body); border-radius:var(--radius-sm); border:1px solid var(--border-color);">
                                    <div style="display:flex; flex-direction:column;">
                                        <span style="font-weight:600; font-size:13px; color:var(--text-main);">{{ $ip->full_name }}</span>
                                        <span style="font-size:11px; color:var(--text-muted);">{{ $ip->satker->name ?? 'Tanpa Satker' }}</span>
                                    </div>
                                    <a href="{{ route('admin.personnel.index', ['search' => $ip->nrp ?? $ip->full_name]) }}" class="btn btn-outline" style="padding:4px 8px; font-size:11px; color:var(--brand); border-color:var(--brand);">Lengkapi</a>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('admin.personnel.index', ['status' => 'incomplete', 'incomplete_scope' => 'size_only']) }}" style="display:block; text-align:center; font-size:12px; font-weight:600; color:var(--brand); margin-top:16px; text-decoration:none;">Lihat Semua Ukuran Bermasalah &rarr;</a>
                    @else
                        <div style="text-align:center; padding:32px 16px;">
                            <i class="ri-checkbox-circle-fill" style="font-size:48px; color:var(--success); margin-bottom:16px; display:inline-block;"></i>
                            <div style="font-weight:700; color:var(--text-main); font-size:16px;">Semua Data Lengkap!</div>
                            <div style="font-size:13px; color:var(--text-muted); margin-top:4px;">Tidak ada tindakan yang diperlukan saat ini.</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Log Aktivitas Langsung (Live Timeline) --}}
            <div class="card" style="height:100%; margin-bottom:0; display:flex; flex-direction:column;">
                <div class="card-head">
                    <h3><i class="ri-history-line" style="margin-right:6px;color:var(--brand);"></i> Aktivitas Terkini</h3>
                </div>
                <div class="card-body">
                    <div style="position:relative; padding-left:24px;">
                        {{-- Garis Timeline --}}
                        <div style="position:absolute; left:7px; top:8px; bottom:0; width:2px; background:var(--border-color);"></div>
                        
                        @if(isset($activities) && count($activities) > 0)
                            @foreach($activities as $activity)
                                <div style="position:relative; margin-bottom:20px; padding-left:16px;">
                                    {{-- Titik --}}
                                    <div style="position:absolute; left:-22px; top:4px; width:12px; height:12px; border-radius:50%; background:var(--brand); border:2px solid var(--bg-card); box-shadow:0 0 0 1px var(--border-color);"></div>
                                    
                                    <div style="font-size:13px; color:var(--text-main); line-height:1.5;">
                                        <span style="font-weight:600;">{{ $activity->causer->name ?? 'System' }}</span> 
                                        {{ $activity->description }}
                                        @if($activity->subject_type)
                                            <span style="color:var(--brand); font-weight:500;">({{ class_basename($activity->subject_type) }})</span>
                                        @endif
                                    </div>
                                    <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                                        <i class="ri-time-line" style="vertical-align:middle;"></i> {{ $activity->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            @endforeach
                        @else
                            {{-- Mockup Data if no Spatie Activity --}}
                            <div style="position:relative; margin-bottom:20px; padding-left:16px;">
                                <div style="position:absolute; left:-22px; top:4px; width:12px; height:12px; border-radius:50%; background:var(--brand); border:2px solid var(--bg-card); box-shadow:0 0 0 1px var(--border-color);"></div>
                                <div style="font-size:13px; color:var(--text-main); line-height:1.5;">
                                    <span style="font-weight:600;">Superadmin</span> baru saja login ke dalam sistem.
                                </div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:4px;"><i class="ri-time-line"></i> Baru saja</div>
                            </div>
                            <div style="position:relative; margin-bottom:20px; padding-left:16px;">
                                <div style="position:absolute; left:-22px; top:4px; width:12px; height:12px; border-radius:50%; background:var(--info); border:2px solid var(--bg-card); box-shadow:0 0 0 1px var(--border-color);"></div>
                                <div style="font-size:13px; color:var(--text-main); line-height:1.5;">
                                    <span style="font-weight:600;">Bripda Rizky</span> berhasil mengimpor 150 data personil baru untuk Polres Bima.
                                </div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:4px;"><i class="ri-time-line"></i> 2 jam yang lalu</div>
                            </div>
                            <div style="position:relative; padding-left:16px;">
                                <div style="position:absolute; left:-22px; top:4px; width:12px; height:12px; border-radius:50%; background:var(--success); border:2px solid var(--bg-card); box-shadow:0 0 0 1px var(--border-color);"></div>
                                <div style="font-size:13px; color:var(--text-main); line-height:1.5;">
                                    <span style="font-weight:600;">Kombes Pol Satria</span> membuat <strong>Paket Anggaran TA 2026</strong>.
                                </div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:4px;"><i class="ri-time-line"></i> Kemarin</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Kepuasan & Testimoni --}}
            <div class="card" style="height:100%; margin-bottom:0; display:flex; flex-direction:column; gap:16px;">
                <div class="card-head" style="border-bottom:none; padding-bottom:0;">
                    <h3><i class="ri-heart-3-fill" style="margin-right:6px;color:var(--danger);"></i> Tingkat Kepuasan Pengguna</h3>
                </div>
                <div class="card-body pt-0">
                    <div style="display:flex; align-items:center; gap:20px; background:var(--bg-body); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color); margin-bottom:20px;">
                        <div style="background:var(--success-bg); color:var(--success); width:70px; height:70px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:700; flex-shrink:0;">
                            98%
                        </div>
                        <div>
                            <div style="font-size:16px; font-weight:700; color:var(--text-main); margin-bottom:4px;">Sangat Puas</div>
                            <div style="font-size:12px; color:var(--text-muted); line-height:1.4;">Berdasarkan masukan dan tingkat adopsi seluruh admin Satker dan personil di lingkup Polda NTB.</div>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div style="background:var(--bg-card); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); position:relative; overflow:hidden;">
                            <div style="position:absolute; top: -10px; right: 10px; font-size:60px; color:var(--bg-body); opacity:0.5; font-family:serif; pointer-events:none;">"</div>
                            <div style="font-size:12.5px; font-style:italic; color:var(--text-main); margin-bottom:12px; position:relative; z-index:1; line-height:1.5;">
                                "Sistem E-MAS KAPOR ini sangat memudahkan kami dalam melakukan pendataan ukuran kaporlap anggota secara real-time. Laporannya instan dan data yang dihasilkan sangat akurat."
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:36px; height:36px; border-radius:50%; background:var(--brand-bg); color:var(--brand); border:1px solid var(--border-color); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700;">
                                    KS
                                </div>
                                <div style="line-height:1.2;">
                                    <div style="font-size:13px; font-weight:600; color:var(--text-main);">Kombes Pol Satria</div>
                                    <div style="font-size:11px; font-weight:500; color:var(--text-muted);">Biro Logistik</div>
                                </div>
                            </div>
                        </div>

                        <div style="background:var(--bg-card); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); position:relative; overflow:hidden;">
                            <div style="position:absolute; top: -10px; right: 10px; font-size:60px; color:var(--bg-body); opacity:0.5; font-family:serif; pointer-events:none;">"</div>
                            <div style="font-size:12.5px; font-style:italic; color:var(--text-main); margin-bottom:12px; position:relative; z-index:1; line-height:1.5;">
                                "Aplikasi yang sangat revolusioner! Admin satker tidak perlu lagi merekap data ribuan personil menggunakan Excel yang memakan waktu berhari-hari. Cukup pantau dari dashboard."
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:36px; height:36px; border-radius:50%; background:var(--info-bg); color:var(--info); border:1px solid var(--border-color); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700;">
                                    BR
                                </div>
                                <div style="line-height:1.2;">
                                    <div style="font-size:13px; font-weight:600; color:var(--text-main);">Bripda Rizky</div>
                                    <div style="font-size:11px; font-weight:500; color:var(--text-muted);">Admin Satker</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
<style>
    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.1); }
        100% { opacity: 1; transform: scale(1); }
    }
    .quick-action-btn {
        flex: 1;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        padding: 16px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        transition: all 0.2s;
    }
    .quick-action-btn:hover {
        border-color: var(--brand);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); /* very light shadow */
    }
    .quick-action-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }
    .quick-action-text {
        font-weight: 600;
        font-size: 14px;
        color: var(--text-main);
    }
    .alert-card {
        margin-bottom:0; 
        border:1px solid var(--warning); 
        box-shadow:0 4px 6px -1px var(--warning-bg);
    }
</style>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Register DataLabels plugin globally for this page
            Chart.register(ChartDataLabels);

            const satkerData = @json($satkerStats);

            const labels = satkerData.map(s => s.name);
            const dataPct = satkerData.map(s => {
                return s.total_personnel > 0 ? ((s.submitted_count / s.total_personnel) * 100).toFixed(1) : 0;
            });
            const rawSubmitted = satkerData.map(s => s.submitted_count);
            const rawTotal = satkerData.map(s => s.total_personnel);

            // Use dynamic colors based on threshold
            const backgroundColors = dataPct.map(pct => {
                if (pct >= 80) return '#10B981'; // green
                if (pct >= 50) return '#F59E0B'; // yellow
                return '#EF4444'; // red
            });

            const chartHeight = Math.max(300, satkerData.length * 30); // increased row height for better label visibility
            document.getElementById('chartWrapper').style.height = chartHeight + 'px';

            const ctx = document.getElementById('satkerChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Progres Pengisian',
                        data: dataPct,
                        backgroundColor: backgroundColors,
                        borderRadius: 4,
                        // Passing the raw data so the datalabels formatter can access it
                        rawSubmitted: rawSubmitted,
                        rawTotal: rawTotal
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Makes the bar chart horizontal
                    layout: {
                        padding: {
                            right: 90 // Add right padding to prevent long labels from cutting off
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const idx = context.dataIndex;
                                    const submitted = context.dataset.rawSubmitted[idx];
                                    const total = context.dataset.rawTotal[idx];
                                    return `Input: ${submitted} / Total: ${total} (${context.raw}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: function(context) {
                                // Always show label outside the bar with a dark text color for high contrast
                                return '#334155';
                            },
                            anchor: 'end',
                            align: 'right',
                            offset: 4,
                            font: {
                                weight: 'bold',
                                size: 11
                            },
                            formatter: function(value, context) {
                                const idx = context.dataIndex;
                                const submitted = context.dataset.rawSubmitted[idx];
                                const total = context.dataset.rawTotal[idx];
                                return `${submitted}/${total} (${value}%)`;
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
                                font: { size: 11, weight: '500' }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
