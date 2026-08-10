@extends('layouts.app')

@section('title', 'Dashboard Admin Gudang')
@section('page-title', 'Dashboard Admin Gudang')
@section('page-subtitle', 'Tahun Anggaran ' . $fiscalYear)

@section('content')
    <div class="stats-row stats-row-4">
        {{-- Total Jenis Barang --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Jenis Barang</span>
                <div class="stat-icon-sm" style="background:var(--brand-bg);color:var(--brand);"><i
                        class="ri-archive-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($totalItemTypes) }}</div>
            <div class="stat-footer">Total item terdaftar</div>
        </div>

        {{-- Total Stok Tersedia --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Stok Tersedia</span>
                <div class="stat-icon-sm" style="background:var(--success-bg);color:var(--success);"><i
                        class="ri-stack-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--success);">{{ number_format($totalStock) }}</div>
            <div class="stat-footer">Jumlah barang di gudang</div>
        </div>

        {{-- Barang Keluar --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Barang Keluar</span>
                <div class="stat-icon-sm" style="background:var(--info-bg);color:var(--info);"><i
                        class="ri-inbox-unarchive-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--info);">{{ number_format($totalOutflow) }}</div>
            <div class="stat-footer">Total barang disalurkan</div>
        </div>

        {{-- Pengajuan Tertunda --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Pengajuan Tertunda</span>
                <div class="stat-icon-sm" style="background:#fef2f2;color:var(--danger);"><i class="ri-time-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--danger);">{{ number_format($totalPending) }}</div>
            <div class="stat-footer">Menunggu persetujuan</div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; margin-top: 24px;">
        {{-- Doughnut Chart --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="ri-pie-chart-2-line" style="margin-right:8px; color:var(--accent);"></i> Distribusi Barang</h3>
            </div>
            <div class="card-body" style="display: flex; justify-content: center; align-items: center; height: 300px;">
                @if($totalStock == 0 && $totalOutflow == 0 && $totalPending == 0)
                    <div style="color: var(--slate-400); text-align: center;">Belum ada data barang.</div>
                @else
                    <canvas id="distributionChart"></canvas>
                @endif
            </div>
        </div>

        {{-- Bar Chart --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="ri-bar-chart-horizontal-line" style="margin-right:8px; color:var(--brand);"></i> Top 5 Stok Terbanyak</h3>
            </div>
            <div class="card-body" style="height: 300px;">
                @if($topItems->isEmpty())
                    <div style="color: var(--slate-400); text-align: center; margin-top: 100px;">Belum ada data stok barang.</div>
                @else
                    <canvas id="topItemsChart"></canvas>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3><i class="ri-flashlight-line" style="margin-right:8px; color:var(--accent);"></i> Aksi Cepat</h3>
        </div>
        <div class="card-body" style="display:flex; gap:12px; flex-wrap:wrap;">
            <a href="{{ route('admin.warehouse-items.index') }}" class="btn btn-primary"><i class="ri-archive-line"></i> Data Gudang</a>
            <a href="{{ route('admin.warehouse-items.reports', ['type' => 'outflow']) }}" class="btn btn-outline"><i class="ri-file-chart-line"></i> Laporan Detail</a>
            <a href="{{ route('admin.warehouse-items.monitor-requests') }}" class="btn btn-outline"><i class="ri-mac-line"></i> Monitor Pengajuan</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($totalStock > 0 || $totalOutflow > 0 || $totalPending > 0)
                // Doughnut Chart (Distribution)
                const distCtx = document.getElementById('distributionChart').getContext('2d');
                new Chart(distCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Stok Tersedia', 'Barang Keluar', 'Pengajuan Tertunda'],
                        datasets: [{
                            data: [{{ $totalStock }}, {{ $totalOutflow }}, {{ $totalPending }}],
                            backgroundColor: [
                                '#10B981', // green for stock
                                '#3B82F6', // blue for outflow
                                '#EF4444'  // red for pending
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: { family: 'Inter', size: 12 },
                                    usePointStyle: true,
                                    padding: 20
                                }
                            }
                        },
                        cutout: '65%'
                    }
                });
            @endif

            @if($topItems->isNotEmpty())
                // Bar Chart (Top 5 Items)
                const topItemsCtx = document.getElementById('topItemsChart').getContext('2d');
                const itemLabels = {!! json_encode($topItems->pluck('name')) !!};
                const itemStocks = {!! json_encode($topItems->pluck('sizes_sum_stock')) !!};

                new Chart(topItemsCtx, {
                    type: 'bar',
                    data: {
                        labels: itemLabels,
                        datasets: [{
                            label: 'Jumlah Stok',
                            data: itemStocks,
                            backgroundColor: 'rgba(239, 68, 68, 0.8)', // brand color approx
                            borderRadius: 6,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y', // horizontal bar chart
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: { family: 'Inter', size: 12 }
                                }
                            },
                            y: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    font: { family: 'Inter', size: 12 }
                                }
                            }
                        }
                    }
                });
            @endif
        });
    </script>
@endsection
