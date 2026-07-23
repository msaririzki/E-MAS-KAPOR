@extends('layouts.app')

@section('title', 'Manajemen Siswa')
@section('breadcrumb', 'Manajemen Siswa')

@section('content')
<div class="students-page">
    <header class="students-header">
        <div>
            <span class="students-eyebrow">DATA KAPOR</span>
            <h1>Manajemen Siswa</h1>
            <p>Angkatan siswa non-login untuk kebutuhan pengadaan dan SPPM.</p>
        </div>
        <button type="button" class="primary-action" onclick="document.getElementById('createBatchPanel').scrollIntoView({ behavior: 'smooth' })">
            <i class="ri-group-line"></i> Buat Angkatan
        </button>
    </header>

    <section class="student-metrics" aria-label="Ringkasan siswa">
        <article><i class="ri-folder-user-line red"></i><span>Angkatan Aktif</span><strong>{{ number_format($summary['batches']) }}</strong></article>
        <article><i class="ri-graduation-cap-line blue"></i><span>Total Siswa</span><strong>{{ number_format($summary['students']) }}</strong></article>
        <article><i class="ri-men-line green"></i><span>Siswa Pria</span><strong>{{ number_format($summary['male']) }}</strong></article>
        <article><i class="ri-women-line amber"></i><span>Siswa Wanita</span><strong>{{ number_format($summary['female']) }}</strong></article>
    </section>

    <section class="student-panel" id="createBatchPanel">
        <div class="panel-heading">
            <div class="heading-icon"><i class="ri-add-line"></i></div>
            <div><h2>Buat Angkatan Baru</h2><span>Satker tujuan: Polda NTB</span></div>
        </div>
        <form action="{{ route('admin.students.store') }}" method="POST" class="batch-form">
            @csrf
            <label class="field field-name"><span>Nama Angkatan</span><input name="name" value="{{ old('name') }}" placeholder="Contoh: Siswa Diktukba Gelombang I" required></label>
            <label class="field"><span>Tahun Anggaran</span><input type="number" name="fiscal_year" value="{{ old('fiscal_year', date('Y') + 1) }}" min="2020" max="2100" required></label>
            <label class="field"><span>Kelompok Pengadaan</span><select name="procurement_group" required><option value="">Pilih kelompok</option>@foreach(\App\Models\StudentBatch::GROUPS as $value => $label)<option value="{{ $value }}" @selected(old('procurement_group') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="field field-rank"><span>Pangkat / Jenis Siswa</span><select name="default_rank_id" required><option value="">Pilih pangkat</option>@foreach($ranks as $rank)<option value="{{ $rank->id }}" @selected((string) old('default_rank_id') === (string) $rank->id)>{{ $rank->name }} ({{ $rank->category }})</option>@endforeach</select></label>
            <label class="field"><span>Jabatan Default</span><input name="default_jabatan" value="{{ old('default_jabatan', 'SISWA') }}" required></label>
            <label class="field"><span>Bag/Fungsi Default</span><input name="default_bagian" value="{{ old('default_bagian', 'SISWA') }}" required></label>
            <label class="field"><span>Jumlah Pria</span><input type="number" name="male_count" value="{{ old('male_count', 0) }}" min="0" max="10000" required></label>
            <label class="field"><span>Jumlah Wanita</span><input type="number" name="female_count" value="{{ old('female_count', 0) }}" min="0" max="10000" required></label>
            <label class="field field-notes"><span>Catatan</span><input name="notes" value="{{ old('notes') }}" placeholder="Opsional"></label>
            <div class="form-submit"><button type="submit" class="primary-action" data-submit-loading><i class="ri-magic-line"></i><span>Buat Data Siswa</span></button></div>
        </form>
    </section>

    <section class="student-panel">
        <div class="panel-heading panel-heading-row">
            <div class="heading-main"><div class="heading-icon"><i class="ri-folders-line"></i></div><div><h2>Daftar Angkatan</h2><span>{{ number_format($batches->total()) }} angkatan ditemukan</span></div></div>
            <form method="GET" class="batch-filters">
                <label class="filter-search"><i class="ri-search-line"></i><input name="search" value="{{ request('search') }}" placeholder="Cari angkatan"></label>
                <select name="year" aria-label="Tahun"><option value="">Semua tahun</option>@foreach($availableYears as $year)<option value="{{ $year }}" @selected((string) request('year') === (string) $year)>T.A. {{ $year }}</option>@endforeach</select>
                <select name="status" aria-label="Status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="archived" @selected(request('status') === 'archived')>Diarsipkan</option></select>
                <button type="submit" title="Terapkan filter"><i class="ri-equalizer-2-line"></i></button>
            </form>
        </div>
        <div class="batch-table-wrap">
            <table class="batch-table">
                <thead><tr><th>Angkatan</th><th>Kelompok</th><th>Jumlah</th><th>Memiliki Data Ukuran</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td><a class="batch-name" href="{{ route('admin.students.show', $batch) }}">{{ $batch->name }}</a><small>{{ $batch->code }} · T.A. {{ $batch->fiscal_year }}</small></td>
                            <td><span class="group-badge">{{ $batch->procurement_group }}</span></td>
                            <td><strong>{{ number_format($batch->students_count) }}</strong><small>{{ $batch->male_count }} pria · {{ $batch->female_count }} wanita</small></td>
                            <td><div class="progress-meta"><span>{{ $batch->sized_count }}/{{ $batch->students_count }}</span><span>{{ $batch->students_count > 0 ? round(($batch->sized_count / $batch->students_count) * 100) : 0 }}%</span></div><div class="progress-track"><i style="width: {{ $batch->students_count > 0 ? min(100, ($batch->sized_count / $batch->students_count) * 100) : 0 }}%"></i></div></td>
                            <td><span class="status-badge {{ $batch->status }}">{{ $batch->status === 'active' ? 'Aktif' : 'Diarsipkan' }}</span></td>
                            <td><a class="row-action" href="{{ route('admin.students.show', $batch) }}" title="Buka angkatan"><i class="ri-arrow-right-line"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><i class="ri-group-line"></i><strong>Belum ada angkatan siswa</strong></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())<div class="panel-pagination">{{ $batches->links() }}</div>@endif
    </section>
</div>

<style>
    .students-page,.students-page *{letter-spacing:0}.students-page{display:flex;flex-direction:column;gap:18px;color:#172033}.students-header{display:flex;align-items:center;justify-content:space-between;gap:18px}.students-eyebrow{color:#b91c1c;font-size:10px;font-weight:800}.students-header h1{margin:3px 0 4px;font-size:27px;font-weight:800}.students-header p{margin:0;color:#718096;font-size:13px}.primary-action{height:40px;padding:0 15px;border:0;border-radius:7px;background:#b91c1c;color:#fff;display:inline-flex;align-items:center;justify-content:center;gap:7px;font-size:12px;font-weight:800;cursor:pointer;text-decoration:none}.primary-action:hover{background:#991b1b;color:#fff}.student-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border:1px solid #e6eaf0;border-radius:8px;background:#fff;overflow:hidden}.student-metrics article{min-height:92px;padding:16px 18px;display:grid;grid-template-columns:38px 1fr;grid-template-rows:auto auto;column-gap:12px;border-right:1px solid #edf0f4}.student-metrics article:last-child{border:0}.student-metrics i{grid-row:1/3;width:38px;height:38px;border-radius:7px;display:grid;place-items:center;font-size:18px}.student-metrics i.red{color:#b91c1c;background:#fef2f2}.student-metrics i.blue{color:#2563eb;background:#eff6ff}.student-metrics i.green{color:#059669;background:#ecfdf5}.student-metrics i.amber{color:#d97706;background:#fffbeb}.student-metrics span{align-self:end;color:#7c899b;font-size:10px;font-weight:700;text-transform:uppercase}.student-metrics strong{align-self:start;font-size:22px}.student-panel{border:1px solid #e5e9ef;border-radius:8px;background:#fff;overflow:hidden}.panel-heading{padding:15px 17px;border-bottom:1px solid #edf0f4;background:#fbfcfd;display:flex;align-items:center;gap:11px}.panel-heading-row{justify-content:space-between}.heading-main{display:flex;align-items:center;gap:11px}.heading-icon{width:34px;height:34px;border:1px solid #fecaca;border-radius:7px;background:#fff5f5;color:#b91c1c;display:grid;place-items:center}.panel-heading h2{margin:0;font-size:14px;font-weight:800}.panel-heading span{display:block;margin-top:2px;color:#8a96a7;font-size:10px}.batch-form{padding:16px 17px;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;align-items:end}.field{display:flex;flex-direction:column;gap:6px;min-width:0}.field span{font-size:10px;color:#526174;font-weight:800;text-transform:uppercase}.field input,.field select,.batch-filters select{height:39px;width:100%;padding:0 10px;border:1px solid #dbe1e8;border-radius:6px;background:#fff;color:#253044;font-size:12px;outline:none}.field input:focus,.field select:focus,.batch-filters select:focus,.filter-search:focus-within{border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.07)}.field-name,.field-rank,.field-notes{grid-column:span 2}.form-submit{display:flex;justify-content:flex-end}.batch-filters{display:flex;align-items:center;gap:7px}.filter-search{height:36px;width:195px;border:1px solid #dbe1e8;border-radius:6px;display:flex;align-items:center;padding:0 9px;color:#9aa5b4}.filter-search input{border:0;outline:0;width:100%;font-size:11px}.batch-filters select{height:36px;width:auto}.batch-filters button{width:36px;height:36px;border:0;border-radius:6px;background:#273244;color:#fff;cursor:pointer}.batch-table-wrap{overflow:auto}.batch-table{width:100%;min-width:850px;border-collapse:collapse}.batch-table th{padding:10px 14px;background:#f8fafc;border-bottom:1px solid #e7ebf0;color:#738095;font-size:9px;text-align:left;text-transform:uppercase}.batch-table td{padding:13px 14px;border-bottom:1px solid #eef1f4;font-size:12px;vertical-align:middle}.batch-table tr:last-child td{border:0}.batch-table small{display:block;margin-top:3px;color:#8b96a6;font-size:9px}.batch-name{color:#202b3d;font-weight:800;text-decoration:none}.batch-name:hover{color:#b91c1c}.group-badge,.status-badge{display:inline-flex;padding:5px 8px;border-radius:5px;font-size:9px;font-weight:800}.group-badge{background:#eff6ff;color:#1d4ed8}.status-badge.active{background:#ecfdf5;color:#047857}.status-badge.archived{background:#f1f5f9;color:#64748b}.progress-meta{display:flex;justify-content:space-between;color:#738095;font-size:9px}.progress-track{width:140px;height:5px;margin-top:5px;border-radius:5px;background:#edf1f5;overflow:hidden}.progress-track i{display:block;height:100%;background:#10b981}.row-action{width:30px;height:30px;border:1px solid #e2e7ed;border-radius:6px;display:grid;place-items:center;color:#475569;text-decoration:none}.row-action:hover{border-color:#fecaca;color:#b91c1c}.empty-state{padding:48px;display:grid;justify-items:center;gap:8px;color:#94a3b8}.empty-state i{font-size:28px}.panel-pagination{padding:12px 15px;border-top:1px solid #edf0f4}
    @media(max-width:1100px){.student-metrics{grid-template-columns:repeat(2,1fr)}.student-metrics article:nth-child(2){border-right:0}.student-metrics article:nth-child(-n+2){border-bottom:1px solid #edf0f4}.batch-form{grid-template-columns:repeat(2,1fr)}.field-name,.field-rank,.field-notes,.form-submit{grid-column:span 2}.panel-heading-row{align-items:flex-start;flex-direction:column}.batch-filters{width:100%;flex-wrap:wrap}.filter-search{flex:1}}
    @media(max-width:640px){.students-header{align-items:flex-start;flex-direction:column}.students-header .primary-action{width:100%}.student-metrics{grid-template-columns:1fr}.student-metrics article{border-right:0;border-bottom:1px solid #edf0f4}.batch-form{grid-template-columns:1fr}.field-name,.field-rank,.field-notes,.form-submit{grid-column:auto}.form-submit .primary-action{width:100%}.batch-filters{display:grid;grid-template-columns:1fr 1fr}.filter-search{grid-column:span 2;width:100%}.batch-filters button{width:100%}}
</style>

<script>document.querySelectorAll('[data-submit-loading]').forEach(button=>button.closest('form')?.addEventListener('submit',()=>{button.disabled=true;button.querySelector('i').className='ri-loader-4-line ri-spin';button.querySelector('span').textContent='Membuat...'}));</script>
@endsection
