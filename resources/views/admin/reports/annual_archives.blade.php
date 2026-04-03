@extends('layouts.app')

@section('title', 'Arsip Final Tahunan')
@section('breadcrumb', 'Arsip Final Tahunan')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Arsip Final Tahunan</h1>
            <p>Unduh kembali snapshot final tahunan yang dibuat saat transisi ke tahun anggaran berikutnya.</p>
        </div>
    </div>
</div>

<div class="archive-grid">
    @forelse($archives as $year => $items)
        <div class="archive-card">
            <div class="archive-head">
                <div>
                    <div class="archive-year">TA {{ $year }}</div>
                    <div class="archive-meta">{{ optional($items->first()->generated_at)->format('d M Y H:i') ?? '-' }}</div>
                </div>
            </div>

            <div class="archive-body">
                @foreach($items as $archive)
                    <div class="archive-row">
                        <div>
                            <div class="archive-format">{{ strtoupper($archive->format) }}</div>
                            <div class="archive-file">{{ $archive->file_name }}</div>
                        </div>
                        <a href="{{ route('admin.reports.annual-archives.download', $archive) }}" class="btn btn-primary">
                            <i class="ri-download-2-line"></i> Unduh
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="empty-state">
            <i class="ri-archive-line"></i>
            <h3>Belum ada arsip tahunan</h3>
            <p>Arsip final akan terbentuk saat superadmin menyiapkan tahun anggaran berikutnya.</p>
        </div>
    @endforelse
</div>
@endsection

@section('styles')
<style>
    .archive-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 20px; }
    .archive-card { background: #fff; border: 1px solid #E5E7EB; border-radius: 16px; overflow: hidden; }
    .archive-head { padding: 18px 20px; border-bottom: 1px solid #F3F4F6; }
    .archive-year { font-size: 20px; font-weight: 800; color: #111827; }
    .archive-meta { margin-top: 4px; color: #6B7280; font-size: 13px; }
    .archive-body { padding: 16px 20px; display: grid; gap: 12px; }
    .archive-row { display: flex; justify-content: space-between; gap: 16px; align-items: center; padding: 12px 0; border-bottom: 1px solid #F9FAFB; }
    .archive-row:last-child { border-bottom: none; }
    .archive-format { font-size: 11px; font-weight: 800; color: #B91C1C; letter-spacing: .05em; }
    .archive-file { font-size: 13px; color: #374151; margin-top: 4px; }
</style>
@endsection
