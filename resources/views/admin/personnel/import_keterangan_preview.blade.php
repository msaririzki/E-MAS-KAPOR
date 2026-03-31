@extends('layouts.app')

@section('title', 'Preview Import Keterangan')
@section('breadcrumb', 'Preview Import Keterangan')

@section('content')
<form action="{{ route('admin.personnel.import-keterangan-cancel') }}" method="POST" id="cancelForm">
    @csrf
</form>

<form action="{{ route('admin.personnel.import-keterangan-confirm') }}" method="POST" id="confirmForm">
    @csrf
</form>

<style>
    .ket-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    .ket-preview-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 18px;
    }
    .ket-preview-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 6px;
    }
    .ket-preview-number {
        font-size: 28px;
        font-weight: 800;
        color: #111827;
    }
    .ket-preview-shell {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        overflow: hidden;
    }
    .ket-preview-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1180px;
    }
    .ket-preview-table th,
    .ket-preview-table td {
        padding: 12px 14px;
        vertical-align: top;
        border-bottom: 1px solid #F3F4F6;
        font-size: 13px;
    }
    .ket-preview-table th {
        background: #F9FAFB;
        color: #6B7280;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        text-align: left;
        white-space: nowrap;
    }
    .ket-preview-scroll {
        overflow-x: auto;
    }
    .ket-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }
    .ket-badge-update { background: #DBEAFE; color: #1D4ED8; }
    .ket-badge-same { background: #F3F4F6; color: #4B5563; }
    .ket-badge-error { background: #FEE2E2; color: #B91C1C; }
    .ket-diff {
        display: grid;
        gap: 6px;
    }
    .ket-diff-row {
        display: grid;
        grid-template-columns: 108px 1fr 18px 1fr;
        gap: 6px;
        align-items: center;
        font-size: 12px;
    }
    .ket-diff-label { color: #6B7280; font-weight: 700; }
    .ket-diff-from,
    .ket-diff-to {
        border-radius: 8px;
        padding: 5px 8px;
        word-break: break-word;
    }
    .ket-diff-from { background: #FEF2F2; color: #991B1B; }
    .ket-diff-to { background: #ECFDF5; color: #047857; font-weight: 700; }
    .ket-warning-list,
    .ket-error-list {
        margin: 0;
        padding-left: 18px;
        display: grid;
        gap: 4px;
    }
    .ket-warning-list { color: #92400E; }
    .ket-error-list { color: #B91C1C; }
    .ket-muted { color: #9CA3AF; font-style: italic; }
    .ket-toolbar {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        align-items: center;
    }
</style>

<div class="ket-toolbar">
    <div>
        <h1 class="page-title" style="margin-bottom: 4px;">Preview Import Keterangan</h1>
        <p class="page-subtitle">Matching menggunakan ID personel. Sistem hanya akan memperbarui `keterangan_2`, `keterangan_3`, dan `keterangan_4`.</p>
    </div>

    <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" form="cancelForm" class="btn btn-outline">
            <i class="ri-close-line"></i> Batal
        </button>
        <button type="submit" form="confirmForm" class="btn btn-primary" @if(($stats['update'] ?? 0) === 0) disabled @endif>
            <i class="ri-check-line"></i> Konfirmasi Import
        </button>
    </div>
</div>

<div class="info-banner" style="margin-bottom:20px;">
    <i class="ri-information-line" style="font-size:20px; color:#2563EB;"></i>
    <div>
        Kolom selain `keterangan_2`, `keterangan_3`, dan `keterangan_4` hanya dipakai sebagai referensi visual.
        Jika ada perubahan pada kolom referensi di file, sistem akan memberi peringatan tetapi tetap memakai ID sebagai acuan utama.
    </div>
</div>

<div class="ket-preview-grid">
    <div class="ket-preview-card">
        <span class="ket-preview-label">Akan Diperbarui</span>
        <span class="ket-preview-number">{{ $stats['update'] ?? 0 }}</span>
    </div>
    <div class="ket-preview-card">
        <span class="ket-preview-label">Tidak Berubah</span>
        <span class="ket-preview-number">{{ $stats['no_change'] ?? 0 }}</span>
    </div>
    <div class="ket-preview-card">
        <span class="ket-preview-label">Error</span>
        <span class="ket-preview-number">{{ $stats['error'] ?? 0 }}</span>
    </div>
    <div class="ket-preview-card">
        <span class="ket-preview-label">Total Baris</span>
        <span class="ket-preview-number">{{ $stats['total'] ?? count($preview) }}</span>
    </div>
</div>

<div class="ket-preview-shell">
    <div class="ket-preview-scroll">
        <table class="ket-preview-table">
            <thead>
                <tr>
                    <th>Baris</th>
                    <th>Status</th>
                    <th>Referensi</th>
                    <th>Perubahan</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($preview as $row)
                    <tr>
                        <td>#{{ $row['row_num'] }}</td>
                        <td>
                            @php
                                $statusClass = match($row['status']) {
                                    'update' => 'ket-badge-update',
                                    'no_change' => 'ket-badge-same',
                                    default => 'ket-badge-error',
                                };
                                $statusLabel = match($row['status']) {
                                    'update' => 'Update',
                                    'no_change' => 'Tidak berubah',
                                    default => 'Error',
                                };
                            @endphp
                            <span class="ket-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td style="min-width:260px;">
                            <div><strong>ID:</strong> {{ $row['id'] ?: '—' }}</div>
                            <div><strong>Nama:</strong> {{ $row['full_name'] ?: '—' }}</div>
                            <div><strong>NRP/NIP:</strong> {{ $row['nrp_nip'] ?: '—' }}</div>
                            <div><strong>Satker:</strong> {{ $row['satker_name'] ?: '—' }}</div>
                            <div><strong>Pangkat:</strong> {{ $row['rank_name'] ?: '—' }}</div>
                            <div><strong>Jabatan:</strong> {{ $row['jabatan'] ?: '—' }}</div>
                            <div><strong>Bag/Fungsi:</strong> {{ $row['bagian'] ?: '—' }}</div>
                        </td>
                        <td style="min-width:380px;">
                            @if($row['status'] === 'error')
                                <span class="ket-muted">Baris ini tidak bisa diproses.</span>
                            @elseif($row['diff'] === [])
                                <span class="ket-muted">Tidak ada perubahan pada tiga kolom target.</span>
                            @else
                                <div class="ket-diff">
                                    @foreach($row['diff'] as $field => $change)
                                        <div class="ket-diff-row">
                                            <div class="ket-diff-label">{{ str_replace('_', ' ', strtoupper($field)) }}</div>
                                            <div class="ket-diff-from">{{ $change['from'] ?? '—' }}</div>
                                            <div style="text-align:center; color:#9CA3AF;">→</div>
                                            <div class="ket-diff-to">{{ $change['to'] ?? '—' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td style="min-width:280px;">
                            @if($row['status'] === 'error')
                                <ul class="ket-error-list">
                                    <li>{{ $row['error_message'] }}</li>
                                </ul>
                            @elseif(!empty($row['reference_warnings']))
                                <ul class="ket-warning-list">
                                    @foreach($row['reference_warnings'] as $warning)
                                        <li>{{ $warning }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="ket-muted">Tidak ada catatan tambahan.</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:32px; color:#6B7280;">Tidak ada baris yang bisa ditampilkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
