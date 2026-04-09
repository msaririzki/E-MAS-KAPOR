@extends('layouts.personil')

@section('title', 'Testimoni Personil')

@section('styles')
    <style>
        .alert {
            padding: 18px;
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }

        .alert {
            font-size: 13px;
            font-weight: 700;
            color: var(--success);
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .field,
        .item {
            margin-top: 14px;
            padding: 0;
            background: transparent;
            border: none;
        }

        .label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .control {
            width: 100%;
            min-height: 46px;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: #fff;
        }

        .control:focus {
            outline: none;
            border-color: #f87171;
            box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.12);
        }

        textarea.control {
            min-height: 120px;
            resize: vertical;
        }

        .error {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            color: var(--danger);
            font-weight: 700;
        }

        .rating-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 8px;
        }

        .rating-desc {
            font-size: 14px;
            font-weight: 700;
            color: #f59e0b;
        }

        .rating input {
            display: none;
        }

        .rating label {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--slate-300);
            background: var(--slate-50);
            cursor: pointer;
        }

        .rating label:hover,
        .rating label:hover~label,
        .rating input:checked~label {
            color: #f59e0b;
            background: #fff7ed;
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
            width: 100%;
            background: var(--brand);
            color: #fff;
        }

        .button-secondary {
            background: #fff;
            color: var(--text-main);
            border-color: var(--border-color);
        }

        .actions,
        .history {
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }

        .item strong {
            display: block;
            font-size: 13px;
        }

        .item small {
            display: block;
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .item p {
            margin-top: 10px;
            color: var(--text-main);
        }

        @media (min-width: 768px) {

            .actions,
            .history {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
@endsection

@section('content')
    <div class="page">
        @if (session('success_testimoni'))
            <div class="alert">{{ session('success_testimoni') }}</div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h2>Kirim testimoni</h2>
                <p>kirim testimoni Anda berdasarkan tutup kepala/tutup badan/tutup kaki</p>
            </div>
            <div class="panel-body">
                {{-- TODO: 
                    1. Pisahkan form testimoni menjadi 3 bagian: Tutup Kepala, Tutup Badan, dan Tutup Kaki.
                    2. Implementasikan alur wizard/step-by-step dimana setelah mengisi satu bagian, otomatis berlanjut ke bagian berikutnya.
                --}}
                <!-- TODO: Implement multi-step testimoni (Tutup Kepala -> Tutup Badan -> Tutup Kaki) -->
                <form action="{{ route('personil.testimoni.store') }}" method="POST">
                    @csrf

                    <div class="field">
                        <label class="label">Penilaian</label>
                        <div class="rating-container">
                            <div class="rating">
                                @for ($star = 5; $star >= 1; $star--)
                                    <input type="radio" id="rating-{{ $star }}" name="rating" value="{{ $star }}" {{ (int) old('rating', 5) === $star ? 'checked' : '' }}>
                                    <label for="rating-{{ $star }}" data-value="{{ $star }}"><i
                                            class="ri-star-fill"></i></label>
                                @endfor
                            </div>
                            <div id="rating-desc" class="rating-desc"></div>
                        </div>
                        @error('rating')<span class="error">{{ $message }}</span>@enderror
                    </div>

                    <div class="field">
                        <label class="label" for="message">Pesan</label>
                        <textarea id="message" name="message" class="control" required>{{ old('message') }}</textarea>
                        @error('message')<span class="error">{{ $message }}</span>@enderror
                    </div>

                    <div style="margin-top: 14px;">
                        <button type="submit" class="button">Kirim Testimoni</button>
                    </div>
                </form>
            </div>
        </section>

        @if ($recentTestimonials->isNotEmpty())
            <section class="panel">
                <div class="panel-header">
                    <h2>Terakhir dikirim</h2>
                </div>
                <div class="panel-body">
                    <div class="history">
                        @foreach ($recentTestimonials as $testimonial)
                            <div class="item">
                                <strong>{{ $testimonial->created_at->format('d M Y, H:i') }}</strong>
                                <small>Rating: {{ $testimonial->rating ?? 5 }}/5</small>
                                <p>{{ $testimonial->message }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <footer style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color); text-align: center;">
            <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                <a href="{{ route('dashboard') }}"
                    style="color: var(--text-muted); font-size: 13px; font-weight: 600;">Kembali ke Data</a>
                <span style="color: var(--slate-300);">•</span>
                <a href="{{ route('personil.kapor.history') }}"
                    style="color: var(--text-muted); font-size: 13px; font-weight: 600;">Riwayat Ukuran</a>
            </div>
        </footer>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('input[name="rating"]');
            const labels = document.querySelectorAll('.rating label');
            const desc = document.getElementById('rating-desc');

            const texts = {
                '5': 'Sangat Bagus',
                '4': 'Bagus',
                '3': 'Biasa',
                '2': 'Buruk',
                '1': 'Sangat Buruk'
            };

            function updateDesc(val) {
                if (desc && texts[val]) {
                    desc.textContent = texts[val];
                }
            }

            const checked = document.querySelector('input[name="rating"]:checked');
            if (checked) updateDesc(checked.value);

            inputs.forEach(input => {
                input.addEventListener('change', (e) => updateDesc(e.target.value));
            });

            labels.forEach(label => {
                label.addEventListener('mouseenter', () => updateDesc(label.dataset.value));
                label.addEventListener('mouseleave', () => {
                    const checked = document.querySelector('input[name="rating"]:checked');
                    if (checked) updateDesc(checked.value);
                });
            });
        });
    </script>
@endsection