@extends('layouts.app')

@section('title', 'Personel Bermasalah (Duplikat NRP)')
@section('breadcrumb', 'Personel Bermasalah')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title" style="color: #991B1B;">
                <i class="ri-error-warning-line"></i> Personel Bermasalah
            </h1>
            <p class="page-subtitle">Daftar personel dengan indikasi duplikasi NRP / NIP</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin.personnel.index') }}" class="btn btn-outline">
                <i class="ri-arrow-left-line"></i> Kembali ke Personel
            </a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
    <i class="ri-checkbox-circle-fill"></i> {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger" style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
    <i class="ri-error-warning-fill"></i> {{ session('error') }}
</div>
@endif

<style>
    .issue-group {
        background: #FFFFFF;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        margin-bottom: 24px;
        overflow: hidden;
        border: 1px solid #E5E7EB;
    }
    .issue-group-header {
        background: #F9FAFB;
        padding: 16px 20px;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .issue-group-body {
        padding: 0;
    }
    .issue-item {
        padding: 16px 20px;
        display: grid;
        grid-template-columns: 2fr 2fr 1.5fr 1.5fr;
        gap: 16px;
        align-items: center;
        border-bottom: 1px solid #F3F4F6;
        transition: background-color 0.2s;
    }
    .issue-item:last-child {
        border-bottom: none;
    }
    .issue-item:hover {
        background-color: #F8FAFC;
    }
    .avatar-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .avatar-circle {
        width: 40px; 
        height: 40px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 50%; 
        color: white; 
        flex-shrink: 0; 
        font-weight: 700; 
        font-size: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .nrp-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        background: #FEF2F2;
        border: 1px solid #FECACA;
        border-radius: 8px;
        color: #991B1B;
        font-weight: 700;
        font-size: 14px;
    }
    .btn-action-sm {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 6px;
        transition: all 0.2s;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
</style>

@if(isset($stats) && isset($topSatkers) && $stats['total_groups'] > 0)
<div style="max-width: 1200px; margin: 0 auto 24px auto;">
    <div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 20px;">
        <!-- Card 1: Total Ringkasan -->
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #E5E7EB; border-left: 4px solid #991B1B; display: flex; flex-direction: column; justify-content: center;">
            <div style="color: #6B7280; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Total Konfik Aktif</div>
            <div style="display: flex; align-items: baseline; gap: 8px;">
                <span style="font-size: 36px; font-weight: 800; color: #991B1B; line-height: 1;">{{ $stats['total_groups'] }}</span>
                <span style="color: #4B5563; font-size: 14px; font-weight: 600;">NRP</span>
            </div>
            <div style="margin-top: 12px; font-size: 12px; color: #6B7280; background: #FEF2F2; padding: 6px 10px; border-radius: 6px; display: inline-block;">
                Melibatkan <strong style="color: #991B1B;">{{ $stats['total_personnel'] }}</strong> personel
            </div>
        </div>

        <!-- Card 2: Top 5 Satker -->
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #E5E7EB;">
            <div style="color: #6B7280; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; display: flex; align-items: center; gap: 6px;">
                <i class="ri-bar-chart-grouped-line" style="font-size: 16px; color: #3B82F6;"></i> Top 5 Satker Terbanyak
            </div>
            @if($topSatkers->count() > 0)
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 12px;">
                    @foreach($topSatkers as $sat)
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 12px; border-radius: 8px; text-align: center; position: relative; overflow: hidden;">
                        <div style="position: absolute; top:0; left:0; width: 100%; height: 3px; background: #3B82F6;"></div>
                        <div style="font-size: 22px; font-weight: 800; color: #1E293B; margin-bottom: 4px;">{{ $sat['total'] }}</div>
                        <div style="font-size: 11px; color: #64748B; font-weight: 600; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;" title="{{ $sat['name'] }}">
                            {{ $sat['name'] }}
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; color: #9CA3AF; font-size: 13px; padding: 10px 0;">
                    Tidak ada satker yang mendominasi data duplikat saat ini.
                </div>
            @endif
        </div>
    </div>
</div>
@endif

<div class="issues-container" style="max-width: 1200px; margin: 0 auto; padding-bottom: 40px;">
    @php
        // Mengelompokkan data berdasarkan NRP
        $groupedIssues = [];
        foreach($personnels as $p) {
            $key = $p->nrp ?: 'TANPA_NRP';
            if(!isset($groupedIssues[$key])) {
                $groupedIssues[$key] = [];
            }
            $groupedIssues[$key][] = $p;
        }
    @endphp

    @forelse($groupedIssues as $nrp => $group)
        <div class="issue-group">
            <div class="issue-group-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="nrp-badge">
                        <i class="ri-fingerprint-line" style="font-size: 16px;"></i>
                        {{ $nrp === 'TANPA_NRP' ? 'TANPA NRP / BLANK' : $nrp }}
                    </div>
                    <span style="color: #6B7280; font-size: 13px; font-weight: 500;">
                        Ditemukan {{ count($group) }} data berkonflik
                    </span>
                </div>
            </div>
            
            <div class="issue-group-body">
                @foreach($group as $p)
                <div class="issue-item" style="{{ $p->nrp_issue_resolved_at ? 'opacity: 0.6; background: #F9FAFB;' : '' }}">
                    <!-- Kolom 1: Profil -->
                    <div class="avatar-wrapper">
                        <div class="avatar-circle" style="background-color: {{ ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899', '#14B8A6'][ord(substr($p->full_name, 0, 1)) % 7] }};">
                            {{ strtoupper(substr($p->full_name, 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #111827; font-size: 14px;">{{ $p->full_name }}</div>
                            <div style="font-size: 12px; color: #6B7280; margin-top: 2px;">
                                <i class="ri-map-pin-line" style="vertical-align: middle;"></i> {{ $p->satker->name ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <!-- Kolom 2: Jabatan & Pangkat -->
                    <div>
                        <div style="font-weight: 600; color: #374151; font-size: 13px;">{{ $p->rank->name ?? '—' }}</div>
                        <div style="font-size: 12px; color: #6B7280; margin-top: 2px;">
                            <i class="ri-briefcase-4-line" style="vertical-align: middle;"></i> {{ $p->jabatan ?? '—' }}
                        </div>
                    </div>

                    <!-- Kolom 3: Status / Catatan -->
                    <div>
                        @if($p->nrp_issue_resolved_at)
                            <div style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #D1FAE5; color: #065F46; border-radius: 20px; font-size: 11px; font-weight: 700;">
                                <i class="ri-check-line"></i> TERSESAIKAN
                            </div>
                        @else
                            <div style="font-size: 12px; color: #B45309; background: #FEF3C7; padding: 6px 10px; border-radius: 6px; border: 1px solid #FDE68A; line-height: 1.4;">
                                <i class="ri-error-warning-line" style="vertical-align: middle; margin-right: 2px;"></i>
                                {{ $p->nrp_issue_note ?? 'Duplikat terdeteksi' }}
                            </div>
                        @endif
                    </div>

                    <!-- Kolom 4: Aksi -->
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        @if(!$p->nrp_issue_resolved_at)
                        <form action="{{ route('admin.personnel.resolve-nrp', $p->id) }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="btn-action-sm" style="background: #10B981; color: white; box-shadow: 0 1px 2px rgba(16,185,129,0.2);" title="Tandai Selesai" onclick="return confirm('Tandai masalah ini sudah selesai?')">
                                <i class="ri-check-double-line"></i> Selesai
                            </button>
                        </form>
                        @endif
                        
                        <a href="{{ route('admin.personnel.index', ['search' => $p->full_name]) }}" class="btn-action-sm" style="background: #3B82F6; color: white; box-shadow: 0 1px 2px rgba(59,130,246,0.2);" title="Edit Data">
                            <i class="ri-edit-line"></i> Edit
                        </a>
                        
                        <form action="{{ route('admin.personnel.destroy', $p->id) }}" method="POST" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-action-sm" style="background: #EF4444; color: white; box-shadow: 0 1px 2px rgba(239,68,68,0.2);" onclick="return confirm('Hapus personel ini secara permanen?')" title="Hapus Data">
                                <i class="ri-delete-bin-line"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    @empty
        <div style="background: white; border-radius: 16px; padding: 60px 20px; text-align: center; border: 1px dashed #D1D5DB; margin-top: 20px;">
            <div style="width: 80px; height: 80px; background: #D1FAE5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="ri-shield-check-fill" style="font-size: 40px; color: #10B981;"></i>
            </div>
            <h3 style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px;">Semua Bersih!</h3>
            <p style="color: #6B7280; font-size: 14px;">Tidak ada indikasi duplikasi NRP / NIP pada personel Anda saat ini.</p>
        </div>
    @endforelse

    @if($personnels->total() > 0)
        <div style="margin-top: 24px;">
            <div style="background: white; border-radius: 12px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;">
                <div style="color: #6B7280; font-size: 13px;">
                    Menampilkan <strong>{{ $personnels->firstItem() ?? 0 }}</strong> hingga <strong>{{ $personnels->lastItem() ?? 0 }}</strong> dari <strong>{{ $personnels->total() }}</strong> baris
                </div>
                <div class="pagination-controls">
                    <a href="{{ $personnels->url(1) }}" class="page-btn {{ $personnels->onFirstPage() ? 'disabled' : '' }}">
                        <i class="ri-double-left-line"></i>
                    </a>
                    <a href="{{ $personnels->previousPageUrl() }}" class="page-btn {{ $personnels->onFirstPage() ? 'disabled' : '' }}">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                    <span class="page-info">Halaman <strong>{{ $personnels->currentPage() }}</strong> dari <strong>{{ $personnels->lastPage() }}</strong></span>
                    <a href="{{ $personnels->nextPageUrl() }}" class="page-btn {{ !$personnels->hasMorePages() ? 'disabled' : '' }}">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                    <a href="{{ $personnels->url($personnels->lastPage()) }}" class="page-btn {{ !$personnels->hasMorePages() ? 'disabled' : '' }}">
                        <i class="ri-double-right-line"></i>
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
