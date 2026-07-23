@extends('layouts.app')

@section('title', 'Pratinjau Data Siswa')
@section('breadcrumb')
    <a href="{{ route('admin.students.index') }}">Manajemen Siswa</a><span class="mx-2 text-slate-400">/</span><a href="{{ route('admin.students.show', $studentBatch) }}">{{ $studentBatch->code }}</a><span class="mx-2 text-slate-400">/</span><span>Pratinjau</span>
@endsection

@section('content')
<div class="preview-page">
    <header class="preview-header"><div><span>PEMERIKSAAN EXCEL</span><h1>Pratinjau Perubahan Siswa</h1><p>{{ $studentBatch->name }} · {{ $studentBatch->code }}</p></div><div class="preview-actions"><form method="POST" action="{{ route('admin.students.import-cancel', $studentBatch) }}">@csrf<button class="cancel-button"><i class="ri-close-line"></i> Batalkan</button></form><form method="POST" action="{{ route('admin.students.import-confirm', $studentBatch) }}">@csrf<button class="confirm-button" @disabled($stats['error'] > 0) data-confirm-import><i class="ri-check-line"></i><span>Terapkan {{ number_format($stats['update']) }} Perubahan</span></button></form></div></header>

    <section class="preview-metrics">
        <article><i class="ri-file-list-3-line"></i><span>Total Baris</span><strong>{{ number_format($stats['total']) }}</strong></article>
        <article class="change"><i class="ri-refresh-line"></i><span>Akan Diubah</span><strong>{{ number_format($stats['update']) }}</strong></article>
        <article class="same"><i class="ri-checkbox-circle-line"></i><span>Tidak Berubah</span><strong>{{ number_format($stats['no_change']) }}</strong></article>
        <article class="error"><i class="ri-error-warning-line"></i><span>Bermasalah</span><strong>{{ number_format($stats['error']) }}</strong></article>
    </section>

    @if($stats['error'] > 0)<div class="error-notice"><i class="ri-error-warning-line"></i><div><strong>{{ number_format($stats['error']) }} baris perlu diperbaiki</strong><span>Batalkan pratinjau, perbaiki Excel, lalu unggah kembali.</span></div></div>@endif

    <section class="preview-panel">
        <div class="preview-panel-head"><h2>Hasil Pemeriksaan</h2><span>Seluruh baris harus valid sebelum diterapkan.</span></div>
        <div class="preview-table-wrap"><table class="preview-table"><thead><tr><th>Baris</th><th>Status</th><th>Kode Sistem</th><th>Nama / NRP</th><th>Pangkat / Kelompok</th><th>Jabatan / Bagian</th><th>JK</th><th>Ukuran</th><th>Catatan Pemeriksaan</th></tr></thead><tbody>
            @foreach($rows as $row)
                <tr class="row-{{ $row['status'] }}">
                    <td>{{ $row['row_number'] }}</td><td><span class="preview-status {{ $row['status'] }}">{{ $row['status'] === 'update' ? 'Diubah' : ($row['status'] === 'no_change' ? 'Tetap' : 'Error') }}</span></td><td><code>{{ $row['student_code'] ?: '-' }}</code></td><td><strong>{{ $row['name'] ?: '-' }}</strong><small>{{ $row['nrp'] ?: '-' }}</small></td><td><strong>{{ $row['rank_name'] ?: '-' }}</strong><small>{{ $row['procurement_group'] ?: '-' }}</small></td><td><strong>{{ $row['jabatan'] ?: '-' }}</strong><small>{{ $row['bagian'] ?: '-' }}</small></td><td>{{ $row['gender_label'] }}</td><td><span class="size-count">{{ count($row['sizes']) }} ukuran</span></td><td>@if($row['errors'] !== [])<div class="row-errors">@foreach($row['errors'] as $error)<span><i class="ri-close-circle-line"></i>{{ $error }}</span>@endforeach</div>@else<span class="valid-row"><i class="ri-checkbox-circle-line"></i> Valid</span>@endif</td>
                </tr>
            @endforeach
        </tbody></table></div>
    </section>
</div>

<style>
    .preview-page,.preview-page *{letter-spacing:0}.preview-page{display:flex;flex-direction:column;gap:17px;color:#172033}.preview-header{display:flex;align-items:center;justify-content:space-between;gap:16px}.preview-header>div:first-child>span{color:#b91c1c;font-size:9px;font-weight:800}.preview-header h1{margin:3px 0;font-size:24px}.preview-header p{margin:0;color:#7d8999;font-size:11px}.preview-actions{display:flex;gap:8px}.preview-actions form{display:flex}.cancel-button,.confirm-button{height:39px;padding:0 13px;border-radius:7px;display:flex;align-items:center;gap:6px;font-size:10px;font-weight:800;cursor:pointer}.cancel-button{border:1px solid #dce2e9;background:#fff;color:#526174}.confirm-button{border:0;background:#047857;color:#fff}.confirm-button:disabled{background:#cbd5e1;cursor:not-allowed}.preview-metrics{display:grid;grid-template-columns:repeat(4,1fr);border:1px solid #e4e9ef;border-radius:8px;background:#fff;overflow:hidden}.preview-metrics article{padding:14px 16px;display:grid;grid-template-columns:32px 1fr;column-gap:9px;border-right:1px solid #edf1f4}.preview-metrics article:last-child{border:0}.preview-metrics i{grid-row:1/3;width:32px;height:32px;border-radius:6px;background:#f1f5f9;color:#64748b;display:grid;place-items:center}.preview-metrics span{color:#8290a2;font-size:8px;font-weight:800;text-transform:uppercase}.preview-metrics strong{font-size:19px}.preview-metrics .change i{background:#eff6ff;color:#2563eb}.preview-metrics .same i{background:#ecfdf5;color:#059669}.preview-metrics .error i{background:#fef2f2;color:#dc2626}.error-notice{padding:12px 14px;border:1px solid #fecaca;border-radius:7px;background:#fff7f7;color:#b91c1c;display:flex;align-items:center;gap:10px}.error-notice>i{font-size:20px}.error-notice strong,.error-notice span{display:block;font-size:10px}.error-notice span{margin-top:2px;color:#a65a5a}.preview-panel{border:1px solid #e4e9ef;border-radius:8px;background:#fff;overflow:hidden}.preview-panel-head{padding:14px 16px;border-bottom:1px solid #e9edf2;background:#fbfcfd}.preview-panel-head h2{margin:0;font-size:13px}.preview-panel-head span{display:block;margin-top:2px;color:#8a96a6;font-size:9px}.preview-table-wrap{max-height:620px;overflow:auto}.preview-table{width:100%;min-width:1050px;border-collapse:collapse}.preview-table thead{position:sticky;top:0;z-index:2}.preview-table th{padding:9px 11px;background:#f1f5f9;border-bottom:1px solid #dfe5ec;color:#6d7a8e;font-size:8px;text-align:left;text-transform:uppercase}.preview-table td{padding:10px 11px;border-bottom:1px solid #edf1f4;font-size:9px;vertical-align:top}.preview-table tr.row-error{background:#fffafa}.preview-table strong,.preview-table small{display:block}.preview-table small{margin-top:2px;color:#8b96a7}.preview-table code{color:#475569;font-size:8px}.preview-status{display:inline-flex;padding:4px 6px;border-radius:4px;font-size:8px;font-weight:800}.preview-status.update{background:#eff6ff;color:#1d4ed8}.preview-status.no_change{background:#f1f5f9;color:#64748b}.preview-status.error{background:#fef2f2;color:#dc2626}.size-count{white-space:nowrap;color:#475569}.row-errors{display:grid;gap:3px;color:#b91c1c}.row-errors span{display:flex;gap:4px}.valid-row{color:#047857;white-space:nowrap}
    @media(max-width:800px){.preview-header{align-items:flex-start;flex-direction:column}.preview-actions{width:100%}.preview-actions form{flex:1}.cancel-button,.confirm-button{width:100%;justify-content:center}.preview-metrics{grid-template-columns:repeat(2,1fr)}.preview-metrics article:nth-child(2){border-right:0}.preview-metrics article:nth-child(-n+2){border-bottom:1px solid #edf1f4}}
</style>
<script>document.querySelectorAll('[data-confirm-import]').forEach(button=>button.closest('form')?.addEventListener('submit',()=>{button.disabled=true;button.querySelector('i').className='ri-loader-4-line ri-spin';button.querySelector('span').textContent='Menerapkan...'}));</script>
@endsection
