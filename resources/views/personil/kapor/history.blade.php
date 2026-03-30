@extends('layouts.app')

@section('title', 'Riwayat Ukuran - SI-KAPOR')
@section('breadcrumb', 'Riwayat Ukuran')

@section('styles')
<style>
    /* ══ Mobile Responsive — Riwayat Ukuran ══ */
    @media (max-width: 768px) {
        .history-header h1 {
            font-size: 18px !important;
        }
        .history-header p {
            font-size: 13px !important;
        }
        .history-card-header {
            padding: 14px 16px !important;
        }
        .history-card-header h3 {
            font-size: 14px !important;
        }
        .history-table th,
        .history-table td {
            padding: 10px 12px !important;
            font-size: 12px !important;
        }
        .history-table .cell-category {
            font-size: 10px !important;
            padding: 3px 6px !important;
        }
        .history-table .cell-size-badge {
            min-width: 28px !important;
            height: 28px !important;
            font-size: 12px !important;
        }
        .history-action {
            text-align: center !important;
        }
        .history-action .btn {
            width: 100% !important;
            justify-content: center !important;
        }
        .history-empty {
            padding: 32px 16px !important;
        }
        .history-empty h3 {
            font-size: 17px !important;
        }
        .history-empty p {
            font-size: 13px !important;
        }
        .history-empty .btn {
            width: 100% !important;
            justify-content: center !important;
            padding: 12px 20px !important;
        }
        .history-empty .empty-icon {
            width: 60px !important;
            height: 60px !important;
        }
        .history-empty .empty-icon i {
            font-size: 30px !important;
        }
    }

    @media (max-width: 480px) {
        .history-header h1 {
            font-size: 16px !important;
        }
    }
</style>
@endsection

@section('content')
<div class="content">
    <div class="history-header" style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 700; color: var(--text-main); margin: 0 0 8px 0;">Riwayat Ukuran Anda</h1>
        <p style="color: var(--text-muted); margin: 0;">Melihat histori ukuran Kaporlap yang pernah Anda ajukan ke Satker Pusat.</p>
    </div>

    @if($hasSubmitted)
    <div class="card" style="border: none; box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); overflow: hidden; background: var(--bg-card);">
        <div class="card-header history-card-header" style="background: var(--bg-card); border-bottom: 1px solid var(--border-color); padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(var(--brand-rgb, 198,40,40), 0.1); color: var(--brand);">
                    <i class="ri-history-line" style="font-size: 18px;"></i>
                </span>
                Jejak Input Ukuran Terakhir
            </h3>
            <span style="background: var(--success-bg); color: var(--success); font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 12px; border: 1px solid var(--success-border);">
                <i class="ri-check-line"></i> Tersinkronisasi
            </span>
        </div>
        <div class="card-body flush">
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table history-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--hover-bg); border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">No</th>
                            <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Kategori</th>
                            <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Nama Item</th>
                            <th style="padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Ukuran Terinput</th>
                            <th style="padding: 14px 24px; text-align: right; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Waktu Diperbarui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $itemMap = [
                                'topi' => ['label' => 'TUTUP KEPALA', 'category' => 'Tutup Kepala', 'icon' => 'ri-spy-line'],
                                'jilbab' => ['label' => 'Jilbab', 'category' => 'Tutup Kepala', 'icon' => 'ri-spy-line'],
                                'kemeja' => ['label' => 'Kemeja (PDH/PDL)', 'category' => 'Tutup Badan', 'icon' => 'ri-t-shirt-line'],
                                'celana' => ['label' => 'Celana/Rok', 'category' => 'Tutup Badan', 'icon' => 'ri-t-shirt-line'],
                                'jaket' => ['label' => 'Jaket', 'category' => 'Tutup Badan', 'icon' => 'ri-t-shirt-line'],
                                'olahraga' => ['label' => 'T-Shirt/Olahraga', 'category' => 'Tutup Badan', 'icon' => 'ri-basketball-line'],
                                'sabuk' => ['label' => 'Sabuk', 'category' => 'Perlengkapan', 'icon' => 'ri-medal-line'],
                                'sepatu_dinas' => ['label' => 'Sepatu Dinas', 'category' => 'Tutup Kaki', 'icon' => 'ri-footprint-line'],
                                'sepatu_olahraga' => ['label' => 'Sepatu Olahraga', 'category' => 'Tutup Kaki', 'icon' => 'ri-footprint-line'],
                            ];
                            $idx = 0;
                        @endphp
                        @foreach($itemMap as $key => $meta)
                            @if(isset($kaporSizes[$key]) && !empty($kaporSizes[$key]))
                                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s;" onmouseover="this.style.background='var(--hover-bg)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 16px 24px; font-size: 13px; color: var(--text-muted);">{{ ++$idx }}</td>
                                    <td style="padding: 16px 24px;">
                                        <span class="cell-category" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: var(--info-bg); color: var(--info);">
                                            <i class="{{ $meta['icon'] }}"></i> {{ $meta['category'] }}
                                        </span>
                                    </td>
                                    <td style="padding: 16px 24px; font-size: 14px; font-weight: 600; color: var(--text-main);">{{ $meta['label'] }}</td>
                                    <td style="padding: 16px 24px;">
                                        <span class="cell-size-badge" style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border-radius: 6px; background: rgba(var(--brand-rgb, 198,40,40), 0.08); border: 1px solid rgba(var(--brand-rgb, 198,40,40), 0.2); font-size: 14px; font-weight: 800; color: var(--brand);">
                                            {{ $kaporSizes[$key] }}
                                        </span>
                                    </td>
                                    <td style="padding: 16px 24px; font-size: 13px; color: var(--text-muted); text-align: right;">{{ $personnel->updated_at->format('d M Y, H:i') }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="history-action" style="margin-top: 24px; text-align: right;">
        <a href="{{ route('personil.kapor.index') }}" class="btn btn-outline-primary" style="display: inline-flex; font-weight: 600; padding: 12px 24px; border: 1px solid var(--border-color); border-radius: 99px; color: var(--text-main); background: var(--bg-card); gap: 8px;">
            <i class="ri-edit-2-line"></i> Revisi/Update Ukuran
        </a>
    </div>

    @else
    <div class="history-empty" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 50px 24px; text-align: center;">
        <div class="empty-icon" style="width: 80px; height: 80px; background: rgba(56, 189, 248, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <i class="ri-history-line" style="font-size: 40px; color: var(--brand);"></i>
        </div>
        <h3 style="font-size: 20px; font-weight: 700; color: var(--text-main); margin: 0 0 12px;">Belum Ada Riwayat Laporan</h3>
        <p style="color: var(--text-muted); margin: 0 0 32px; font-size: 15px; max-width: 450px; margin-left: auto; margin-right: auto; line-height: 1.6;">
            Aset data kaporlap Anda masih kosong di basis data Pusat. Catatan rekam histori akan muncul di sini setelah Anda melengkapi form asupan awal.
        </p>
        <a href="{{ route('personil.kapor.index') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; font-weight: 600; border: none; background: var(--brand); color: #fff; border-radius: 99px; padding: 14px 32px; gap: 8px; font-size: 15px; box-shadow: 0 4px 14px rgba(var(--brand-rgb, 198,40,40), 0.4);">
            <i class="ri-pencil-ruler-2-line"></i> Buka Form Input Ukuran
        </a>
    </div>
    @endif
</div>
@endsection
