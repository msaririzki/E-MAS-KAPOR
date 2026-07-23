@extends('layouts.app')

@section('title', $studentBatch->name)
@section('breadcrumb')
    <a href="{{ route('admin.students.index') }}">Manajemen Siswa</a><span class="mx-2 text-slate-400">/</span><span>{{ $studentBatch->code }}</span>
@endsection

@section('content')
<div class="student-detail-page">
    <header class="detail-header">
        <div class="detail-title"><a href="{{ route('admin.students.index') }}" title="Kembali"><i class="ri-arrow-left-line"></i></a><div><span>{{ $studentBatch->code }}</span><h1>{{ $studentBatch->name }}</h1><p>{{ $studentBatch->satker->name }} · T.A. {{ $studentBatch->fiscal_year }} · {{ $studentBatch->procurement_group }}</p></div></div>
        <div class="detail-actions">
            <form method="POST" action="{{ route('admin.students.archive', $studentBatch) }}">@csrf @method('PATCH')<button class="secondary-action" type="submit" title="{{ $studentBatch->isArchived() ? 'Aktifkan angkatan' : 'Arsipkan angkatan' }}"><i class="{{ $studentBatch->isArchived() ? 'ri-refresh-line' : 'ri-archive-line' }}"></i></button></form>
            <a class="excel-action" href="{{ route('admin.students.export', $studentBatch) }}"><i class="ri-file-excel-2-line"></i> Unduh Excel</a>
        </div>
    </header>

    <section class="detail-metrics">
        <article><span>Total Siswa</span><strong>{{ number_format($metrics['total']) }}</strong><i class="ri-graduation-cap-line"></i></article>
        <article><span>Pria</span><strong>{{ number_format($metrics['male']) }}</strong><i class="ri-men-line"></i></article>
        <article><span>Wanita</span><strong>{{ number_format($metrics['female']) }}</strong><i class="ri-women-line"></i></article>
        <article><span>Ada Data Ukuran</span><strong>{{ number_format($metrics['sized']) }}</strong><i class="ri-ruler-2-line"></i></article>
    </section>

    <section class="student-operations">
        <article class="operation-panel add-panel">
            <div class="operation-heading"><span><i class="ri-user-add-line"></i></span><div><h2>Tambah Siswa</h2><p>Tambahkan permintaan baru ke angkatan yang sama.</p></div></div>
            <form action="{{ route('admin.students.add-students', $studentBatch) }}" method="POST" class="add-student-form" data-operation-form>
                @csrf
                <label><span>Pria</span><input type="number" name="male_count" min="0" max="10000" value="0" required></label>
                <label><span>Wanita</span><input type="number" name="female_count" min="0" max="10000" value="0" required></label>
                <button type="submit"><i class="ri-add-line"></i><span>Tambah</span></button>
            </form>
            <div class="default-profile"><i class="ri-shield-user-line"></i><span>{{ $studentBatch->defaultRank?->name ?: 'Pangkat belum ditentukan' }}</span><span>{{ $studentBatch->default_jabatan ?: 'SISWA' }}</span><span>{{ $studentBatch->default_bagian ?: 'SISWA' }}</span></div>
        </article>

        <article class="operation-panel quota-panel">
            <div class="operation-heading"><span><i class="ri-ruler-line"></i></span><div><h2>Bagikan Ukuran Berdasarkan Kuota</h2><p>Tulis ukuran dan jumlah siswa. Sisa kuota dihitung otomatis.</p></div></div>
            <form action="{{ route('admin.students.size-distribution', $studentBatch) }}" method="POST" id="sizeQuotaForm" data-total="{{ $metrics['total'] }}" data-male="{{ $metrics['male'] }}" data-female="{{ $metrics['female'] }}" data-operation-form>
                @csrf
                <div class="quota-controls">
                    <label><span>Jenis Ukuran</span><select name="size_key" id="quotaSizeKey" required>@foreach(\App\Services\StudentSizeDistributionService::SIZE_TYPES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
                    <label><span>Target Siswa</span><select name="gender" id="quotaGender"><option value="">Semua Siswa</option><option value="L">Pria</option><option value="P">Wanita</option></select></label>
                    <div class="quota-counter"><span>Terbagi</span><strong id="quotaAssigned">0</strong><small>dari <b id="quotaTarget">{{ $metrics['total'] }}</b> siswa</small></div>
                    <div class="quota-counter remaining"><span>Sisa</span><strong id="quotaRemaining">{{ $metrics['total'] }}</strong><small>belum mendapat ukuran</small></div>
                </div>
                <div class="quota-entries" id="quotaEntries">
                    @for($index = 0; $index < 3; $index++)
                        <div class="quota-entry">
                            <label><span>Ukuran</span><input name="entries[{{ $index }}][size]" placeholder="Contoh: 56 atau SD"></label>
                            <label><span>Jumlah</span><input type="number" name="entries[{{ $index }}][count]" min="0" max="10000" value="0"></label>
                            <button type="button" class="remove-quota" title="Hapus baris"><i class="ri-close-line"></i></button>
                        </div>
                    @endfor
                </div>
                <div class="quota-actions"><button type="button" class="add-quota-row" id="addQuotaRow"><i class="ri-add-line"></i> Tambah Ukuran</button><button type="submit" class="apply-quota"><i class="ri-check-double-line"></i><span>Terapkan Kuota</span></button></div>
            </form>
        </article>
    </section>

    <section class="size-summary-panel">
        <div class="size-summary-head"><div><h2>Rekap Pengisian Ukuran</h2><p>Ringkasan langsung dari seluruh personel siswa pada angkatan ini.</p></div><span>{{ $metrics['sized'] }}/{{ $metrics['total'] }} memiliki data ukuran</span></div>
        <div class="size-summary-grid">
            @foreach($sizeSummary as $summary)
                <article><div><strong>{{ $summary['label'] }}</strong><span>{{ $summary['filled'] }}/{{ $metrics['total'] }} terisi</span></div><div class="mini-progress"><i style="width: {{ $metrics['total'] > 0 ? min(100, ($summary['filled'] / $metrics['total']) * 100) : 0 }}%"></i></div><p>@forelse($summary['distribution'] as $size => $count)<span>{{ $size }} <b>{{ $count }}</b></span>@empty<em>Belum ada ukuran</em>@endforelse</p></article>
            @endforeach
        </div>
    </section>

    <section class="excel-band">
        <div class="excel-band-main"><span class="excel-icon"><i class="ri-file-excel-2-line"></i></span><div><h2>Perbarui Data Lengkap dari Excel</h2><p>Nama, pangkat, NRP/NIP, jabatan, bagian, jenis kelamin, ukuran, agama, dan keterangan.</p></div></div>
        <form action="{{ route('admin.students.import', $studentBatch) }}" method="POST" enctype="multipart/form-data" class="upload-form">
            @csrf
            <label class="file-picker"><i class="ri-attachment-2"></i><span id="studentFileName">Pilih file .xlsx</span><input type="file" name="file" accept=".xlsx,.xls" required onchange="document.getElementById('studentFileName').textContent=this.files[0]?.name||'Pilih file .xlsx'"></label>
            <button type="submit" class="upload-action" data-import-submit><i class="ri-upload-cloud-2-line"></i><span>Periksa Data</span></button>
        </form>
    </section>

    <section class="student-list-panel">
        <div class="list-toolbar">
            <div><h2>Daftar Siswa</h2><span>{{ number_format($students->total()) }} data pada hasil ini</span></div>
            <form method="GET" class="student-filters">
                <label><i class="ri-search-line"></i><input name="search" value="{{ request('search') }}" placeholder="Nama, NRP, atau kode"></label>
                <select name="gender"><option value="">Semua gender</option><option value="L" @selected(request('gender') === 'L')>Pria</option><option value="P" @selected(request('gender') === 'P')>Wanita</option></select>
                <select name="size_status"><option value="">Semua ukuran</option><option value="filled" @selected(request('size_status') === 'filled')>Ada data ukuran</option><option value="empty" @selected(request('size_status') === 'empty')>Masih kosong</option></select>
                <button title="Terapkan"><i class="ri-equalizer-2-line"></i></button>
            </form>
        </div>
        <div class="student-table-wrap">
            <table class="student-table">
                <thead><tr><th>Personel Siswa</th><th>NRP/NIP</th><th>Pangkat / Jabatan</th><th>JK</th><th>Kelompok</th><th>Ukuran Kapor</th><th>Keterangan</th></tr></thead>
                <tbody>
                    @forelse($students as $student)
                        @php($sizes = is_array($student->kapor_sizes) ? $student->kapor_sizes : [])
                        <tr>
                            <td><strong>{{ $student->full_name }}</strong><small>{{ $student->student_code }}</small></td>
                            <td><span class="nrp-value">{{ $student->nrp ?: '-' }}</span></td>
                            <td><strong>{{ $student->rank?->name ?: '-' }}</strong><small>{{ $student->jabatan ?: '-' }} &middot; {{ $student->bagian ?: '-' }}</small></td>
                            <td><span class="gender-badge {{ $student->gender === 'P' ? 'female' : 'male' }}">{{ $student->gender === 'P' ? 'Wanita' : 'Pria' }}</span></td>
                            <td><span class="group-badge">{{ $student->procurement_group }}</span></td>
                            <td>
                                @if($sizes !== [])<div class="size-list">@foreach($sizes as $key => $value)<span>{{ strtoupper(str_replace('_', ' ', $key)) }} <b>{{ $value }}</b></span>@endforeach</div>@else<span class="empty-size"><i class="ri-error-warning-line"></i> Belum diisi</span>@endif
                            </td>
                            <td>{{ $student->keterangan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-list"><i class="ri-user-search-line"></i><strong>Data siswa tidak ditemukan</strong></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())<div class="list-pagination">{{ $students->links() }}</div>@endif
    </section>
</div>

<style>
    .student-detail-page,.student-detail-page *{letter-spacing:0}.student-detail-page{display:flex;flex-direction:column;gap:17px;color:#172033}.detail-header{display:flex;justify-content:space-between;align-items:center;gap:18px}.detail-title{display:flex;align-items:center;gap:13px}.detail-title>a{width:36px;height:36px;border:1px solid #dfe5ec;border-radius:7px;background:#fff;color:#475569;display:grid;place-items:center;text-decoration:none}.detail-title span{color:#b91c1c;font-size:9px;font-weight:800}.detail-title h1{margin:2px 0;font-size:23px;font-weight:800}.detail-title p{margin:0;color:#7b8798;font-size:11px}.detail-actions{display:flex;align-items:center;gap:8px}.secondary-action,.excel-action{height:39px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer}.secondary-action{width:39px;border:1px solid #dce2e9;background:#fff;color:#64748b}.excel-action{padding:0 13px;background:#047857;color:#fff;font-size:11px;font-weight:800;text-decoration:none}.excel-action:hover{background:#065f46;color:#fff}.detail-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.detail-metrics article{position:relative;min-height:90px;padding:16px;border:1px solid #e5e9ef;border-radius:8px;background:#fff;overflow:hidden}.detail-metrics span{display:block;color:#7c899b;font-size:9px;font-weight:800;text-transform:uppercase}.detail-metrics strong{display:block;margin-top:5px;font-size:24px}.detail-metrics i{position:absolute;right:14px;top:19px;color:#d1d7df;font-size:25px}.excel-band{padding:14px 16px;border:1px solid #bbf7d0;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:space-between;gap:18px}.excel-band-main{display:flex;align-items:center;gap:11px}.excel-icon{width:37px;height:37px;border-radius:7px;background:#059669;color:#fff;display:grid;place-items:center;font-size:18px}.excel-band h2{margin:0;font-size:13px}.excel-band p{margin:3px 0 0;color:#59816d;font-size:10px}.upload-form{display:flex;align-items:center;gap:8px}.file-picker{height:38px;min-width:210px;padding:0 11px;border:1px dashed #86d7aa;border-radius:6px;background:#fff;display:flex;align-items:center;gap:7px;color:#567263;font-size:10px;cursor:pointer}.file-picker input{display:none}.upload-action{height:38px;padding:0 12px;border:0;border-radius:6px;background:#166534;color:#fff;display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:800;cursor:pointer}.student-list-panel{border:1px solid #e4e9ef;border-radius:8px;background:#fff;overflow:hidden}.list-toolbar{padding:14px 16px;border-bottom:1px solid #e9edf2;background:#fbfcfd;display:flex;align-items:center;justify-content:space-between;gap:15px}.list-toolbar h2{margin:0;font-size:14px}.list-toolbar>div>span{display:block;margin-top:2px;color:#8b96a5;font-size:9px}.student-filters{display:flex;align-items:center;gap:7px}.student-filters label{width:210px;height:35px;border:1px solid #dbe1e8;border-radius:6px;background:#fff;display:flex;align-items:center;gap:6px;padding:0 9px;color:#9aa5b4}.student-filters input{width:100%;border:0;outline:0;font-size:10px}.student-filters select{height:35px;padding:0 9px;border:1px solid #dbe1e8;border-radius:6px;background:#fff;color:#465467;font-size:10px}.student-filters button{width:35px;height:35px;border:0;border-radius:6px;background:#273244;color:#fff}.student-table-wrap{overflow:auto}.student-table{width:100%;min-width:960px;border-collapse:collapse}.student-table th{padding:10px 13px;background:#f8fafc;border-bottom:1px solid #e6ebf0;color:#768296;text-align:left;font-size:8px;text-transform:uppercase}.student-table td{padding:11px 13px;border-bottom:1px solid #edf1f4;color:#435064;font-size:10px;vertical-align:middle}.student-table tr:last-child td{border-bottom:0}.student-table td:first-child strong{display:block;color:#1f2937;font-size:11px}.student-table small{display:block;margin-top:3px;color:#94a3b8;font-size:8px}.nrp-value{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:#334155}.gender-badge,.group-badge{display:inline-flex;padding:4px 7px;border-radius:5px;font-size:8px;font-weight:800}.gender-badge.male{background:#eff6ff;color:#1d4ed8}.gender-badge.female{background:#fdf2f8;color:#be185d}.group-badge{background:#f3e8ff;color:#7e22ce}.size-list{display:flex;flex-wrap:wrap;gap:4px;max-width:390px}.size-list span{padding:3px 5px;border:1px solid #e3e8ee;border-radius:4px;background:#f8fafc;color:#748094;font-size:7px}.size-list b{margin-left:3px;color:#1e293b}.empty-size{color:#d97706;font-size:9px}.empty-list{padding:45px;display:grid;justify-items:center;gap:7px;color:#94a3b8}.empty-list i{font-size:26px}.list-pagination{padding:12px 15px;border-top:1px solid #edf1f4}
    @media(max-width:900px){.detail-metrics{grid-template-columns:repeat(2,1fr)}.excel-band,.list-toolbar{align-items:flex-start;flex-direction:column}.upload-form,.student-filters{width:100%}.file-picker,.student-filters label{flex:1}}
    @media(max-width:640px){.detail-header{align-items:flex-start;flex-direction:column}.detail-actions{width:100%}.excel-action{flex:1}.detail-metrics{grid-template-columns:1fr 1fr}.upload-form,.student-filters{display:grid;grid-template-columns:1fr}.file-picker,.student-filters label,.student-filters button{width:100%}.student-filters button{height:35px}}
</style>

<style>
    .student-operations{display:grid;grid-template-columns:minmax(280px,.72fr) minmax(520px,1.6fr);gap:12px}.operation-panel,.size-summary-panel{border:1px solid #e3e8ee;border-radius:8px;background:#fff;overflow:hidden}.operation-heading{padding:13px 15px;border-bottom:1px solid #edf1f4;background:#fbfcfd;display:flex;align-items:center;gap:10px}.operation-heading>span{width:34px;height:34px;border-radius:7px;background:#fef2f2;color:#b91c1c;display:grid;place-items:center;font-size:17px}.operation-heading h2,.size-summary-head h2{margin:0;font-size:13px}.operation-heading p,.size-summary-head p{margin:3px 0 0;color:#8793a4;font-size:9px}.add-student-form{padding:14px 15px;display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end}.add-student-form label,.quota-controls label,.quota-entry label{display:grid;gap:5px}.add-student-form label span,.quota-controls label span,.quota-entry label span{color:#68768a;font-size:8px;font-weight:800;text-transform:uppercase}.add-student-form input,.quota-controls select,.quota-entry input{width:100%;height:36px;border:1px solid #dbe2e9;border-radius:6px;background:#fff;padding:0 9px;color:#253044;font-size:10px;outline:none}.add-student-form input:focus,.quota-controls select:focus,.quota-entry input:focus{border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.06)}.add-student-form button,.apply-quota{height:36px;padding:0 11px;border:0;border-radius:6px;background:#b91c1c;color:#fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;gap:5px;cursor:pointer}.default-profile{margin:0 15px 14px;padding:9px 10px;border:1px solid #e7ebf0;border-radius:6px;background:#f8fafc;display:flex;align-items:center;gap:7px;flex-wrap:wrap;color:#68768a;font-size:8px}.default-profile i{color:#2563eb;font-size:14px}.default-profile span{padding-right:7px;border-right:1px solid #dce2e8}.default-profile span:last-child{border:0}.quota-panel form{padding:13px 15px}.quota-controls{display:grid;grid-template-columns:1.1fr 1fr .7fr .7fr;gap:8px;align-items:end}.quota-counter{height:54px;padding:7px 9px;border:1px solid #dbeafe;border-radius:6px;background:#eff6ff;display:grid;grid-template-columns:1fr auto;align-items:center}.quota-counter>span{color:#64748b;font-size:8px;font-weight:800;text-transform:uppercase}.quota-counter strong{color:#1d4ed8;font-size:17px}.quota-counter small{grid-column:1/3;color:#78879a;font-size:7px}.quota-counter.remaining{border-color:#fde68a;background:#fffbeb}.quota-counter.remaining strong{color:#b45309}.quota-entries{margin-top:10px;display:grid;grid-template-columns:repeat(3,1fr);gap:7px}.quota-entry{position:relative;padding:9px;border:1px solid #e4e9ef;border-radius:6px;background:#fbfcfd;display:grid;grid-template-columns:1fr 82px 25px;gap:6px;align-items:end}.remove-quota{width:25px;height:36px;border:0;background:transparent;color:#94a3b8;cursor:pointer}.quota-actions{margin-top:10px;display:flex;justify-content:space-between;gap:8px}.add-quota-row{height:34px;padding:0 10px;border:1px solid #dce3ea;border-radius:6px;background:#fff;color:#526174;font-size:9px;font-weight:800;cursor:pointer}.apply-quota{height:34px;background:#047857}.size-summary-head{padding:13px 15px;border-bottom:1px solid #edf1f4;background:#fbfcfd;display:flex;align-items:center;justify-content:space-between;gap:12px}.size-summary-head>span{padding:5px 8px;border-radius:5px;background:#f1f5f9;color:#64748b;font-size:8px;font-weight:800}.size-summary-grid{padding:12px;display:grid;grid-template-columns:repeat(3,1fr);gap:8px}.size-summary-grid article{padding:10px;border:1px solid #e6ebf0;border-radius:6px}.size-summary-grid article>div:first-child{display:flex;align-items:center;justify-content:space-between;gap:8px}.size-summary-grid strong{font-size:10px}.size-summary-grid article>div:first-child span{color:#7c8999;font-size:8px}.mini-progress{height:4px;margin-top:7px;border-radius:4px;background:#edf1f5;overflow:hidden}.mini-progress i{display:block;height:100%;background:#10b981}.size-summary-grid p{min-height:20px;margin:7px 0 0;display:flex;flex-wrap:wrap;gap:3px}.size-summary-grid p span{padding:3px 5px;border-radius:4px;background:#f1f5f9;color:#657386;font-size:7px}.size-summary-grid p b{color:#1f2937}.size-summary-grid p em{color:#a0a9b5;font-size:7px}
    @media(max-width:1050px){.student-operations{grid-template-columns:1fr}.quota-entries,.size-summary-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:700px){.quota-controls{grid-template-columns:1fr 1fr}.quota-entries,.size-summary-grid{grid-template-columns:1fr}.add-student-form{grid-template-columns:1fr 1fr}.add-student-form button{grid-column:1/3}.quota-entry{grid-template-columns:1fr 76px 25px}.size-summary-head{align-items:flex-start;flex-direction:column}}
</style>

<script>
(() => {
    document.querySelectorAll('[data-import-submit]').forEach((button) => button.closest('form')?.addEventListener('submit', () => {
        button.disabled = true;
        button.querySelector('i').className = 'ri-loader-4-line ri-spin';
        button.querySelector('span').textContent = 'Memeriksa...';
    }));

    document.querySelectorAll('[data-operation-form]').forEach((form) => form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        if (!button) return;
        button.disabled = true;
        const icon = button.querySelector('i');
        if (icon) icon.className = 'ri-loader-4-line ri-spin';
    }));

    const form = document.getElementById('sizeQuotaForm');
    const entries = document.getElementById('quotaEntries');
    const gender = document.getElementById('quotaGender');
    const sizeKey = document.getElementById('quotaSizeKey');
    const addButton = document.getElementById('addQuotaRow');
    if (!form || !entries || !gender || !sizeKey || !addButton) return;

    let rowIndex = entries.querySelectorAll('.quota-entry').length;
    const targetCount = () => gender.value === 'L' ? Number(form.dataset.male) : (gender.value === 'P' ? Number(form.dataset.female) : Number(form.dataset.total));
    const refreshQuota = () => {
        const assigned = Array.from(entries.querySelectorAll('input[type="number"]')).reduce((total, input) => total + Math.max(0, Number(input.value) || 0), 0);
        const target = targetCount();
        document.getElementById('quotaAssigned').textContent = assigned.toLocaleString('id-ID');
        document.getElementById('quotaTarget').textContent = target.toLocaleString('id-ID');
        const remaining = document.getElementById('quotaRemaining');
        remaining.textContent = (target - assigned).toLocaleString('id-ID');
        remaining.style.color = assigned > target ? '#dc2626' : '';
    };
    const bindRow = (row) => {
        row.querySelectorAll('input').forEach((input) => input.addEventListener('input', refreshQuota));
        row.querySelector('.remove-quota')?.addEventListener('click', () => {
            if (entries.querySelectorAll('.quota-entry').length === 1) {
                row.querySelectorAll('input').forEach((input) => input.value = input.type === 'number' ? '0' : '');
            } else {
                row.remove();
            }
            refreshQuota();
        });
    };
    entries.querySelectorAll('.quota-entry').forEach(bindRow);
    addButton.addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'quota-entry';
        row.innerHTML = `<label><span>Ukuran</span><input name="entries[${rowIndex}][size]" placeholder="Contoh: 56 atau SD"></label><label><span>Jumlah</span><input type="number" name="entries[${rowIndex}][count]" min="0" max="10000" value="0"></label><button type="button" class="remove-quota" title="Hapus baris"><i class="ri-close-line"></i></button>`;
        rowIndex += 1;
        entries.appendChild(row);
        bindRow(row);
    });
    gender.addEventListener('change', refreshQuota);
    sizeKey.addEventListener('change', () => {
        if (sizeKey.value === 'jilbab') gender.value = 'P';
        if (['kemeja', 'celana'].includes(sizeKey.value) && gender.value === '') gender.value = 'L';
        refreshQuota();
    });
    refreshQuota();
})();
</script>
@endsection
