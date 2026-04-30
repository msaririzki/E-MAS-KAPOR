@extends('layouts.personil')

@section('title', 'Riwayat Ukuran Kaporlap')



@section('content')
    @php
        $itemMap = [
            'topi' => 'Topi / Baret',
            'jilbab' => 'Jilbab',
            'kemeja' => 'Kemeja',
            'celana' => 'Celana / Rok',
            'jaket' => 'Jaket',
            'olahraga' => 'Olahraga',
            'sabuk' => 'Sabuk',
            'sepatu_dinas' => 'Sepatu Dinas',
            'sepatu_olahraga' => 'Sepatu Olahraga',
        ];
    @endphp

    <div class="page">
        <section class="panel">
            <div class="panel-header">
                <h1>Riwayat ukuran</h1>
                <p>Data terakhir yang tersimpan untuk akun Anda.</p>
            </div>
            <div class="panel-body">
                <div class="meta">
                    <div class="meta-item"><strong>Jabatan</strong><span>{{ $personnel->jabatan ?? '-' }}</span></div>
                    <div class="meta-item"><strong>Bag/Fungsi</strong><span>{{ $personnel->bagian ?? '-' }}</span></div>
                    <div class="meta-item">
                        <strong>Update</strong><span>{{ $personnel?->updated_at?->format('d M Y, H:i') ?? '-' }}</span>
                    </div>
                    <div class="meta-item">
                        <strong>Status</strong><span>{{ $isComplete ? 'Lengkap' : 'Belum lengkap' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>{{ $hasSubmitted ? 'Ukuran tersimpan' : 'Belum ada ukuran tersimpan' }}</h2>
                <p>{{ $hasSubmitted ? 'Gunakan data ini sebagai referensi sebelum memperbarui ukuran.' : 'Lengkapi data utama untuk mulai menyimpan data ukuran.' }}
                </p>
            </div>
            <div class="panel-body">
                @if ($hasSubmitted)
                    <div class="list">
                        @foreach ($itemMap as $key => $label)
                            @continue(empty($kaporSizes[$key]))
                            <div class="item">
                                <strong>{{ $label }}</strong>
                                <span>{{ $kaporSizes[$key] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <footer style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color); text-align: center;">
            <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                <a href="{{ route('dashboard') }}"
                    style="color: var(--text-muted); font-size: 13px; font-weight: 600;">Kembali ke Data</a>
                <span style="color: var(--slate-300);">•</span>
                <a href="{{ route('personil.testimoni.index') }}"
                    style="color: var(--text-muted); font-size: 13px; font-weight: 600;">Halaman Review</a>
            </div>
        </footer>
    </div>
@endsection

@section('styles')
<style>
        .meta,
        .list {
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }

        .list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .meta-item,
        .item {
            padding: 0;
            background: transparent;
            border: none;
        }

        .meta-item strong,
        .item strong {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .meta-item span,
        .item span {
            display: block;
            margin-top: 6px;
            font-size: 14px;
            font-weight: 700;
        }

        .button,
        .button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .button {
            background: var(--brand);
            color: #fff;
        }

        .button-secondary {
            background: #fff;
            color: var(--text-main);
            border-color: var(--border-color);
        }

        @media (min-width: 768px) {}
    </style>
@endsection
