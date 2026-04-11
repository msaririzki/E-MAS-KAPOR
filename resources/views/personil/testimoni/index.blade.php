@extends('layouts.personil')

@section('title', 'Testimoni Personil')

@section('styles')
    <style>
        .alert-success {
            padding: 16px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            color: var(--success);
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            padding: 16px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            color: var(--danger);
            border: 1px solid #fecaca;
            background: #fef2f2;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            padding: 16px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .field {
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
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.08);
        }

        textarea.control {
            min-height: 100px;
            resize: vertical;
        }

        .error {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            color: var(--danger);
            font-weight: 700;
        }

        /* ── Rating Category Row ── */
        .category-ratings {
            display: flex;
            flex-direction: column;
            gap: 0;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .category-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            gap: 12px;
            border-bottom: 1px solid var(--border-color);
            transition: background 0.15s;
        }

        .category-row:last-child {
            border-bottom: none;
        }

        .category-row:hover {
            background: var(--slate-50);
        }

        .category-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
            min-width: 0;
        }

        .category-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .category-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .rating-desc-inline {
            font-size: 12px;
            font-weight: 700;
            color: #f59e0b;
            min-width: 78px;
            text-align: right;
        }

        /* ── Star Rating ── */
        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 4px;
        }

        .rating input {
            display: none;
        }

        .rating label {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--slate-300);
            background: var(--slate-50);
            cursor: pointer;
            transition: all 0.15s;
        }

        .rating label:hover,
        .rating label:hover~label,
        .rating input:checked~label {
            color: #f59e0b;
            background: #fff7ed;
        }

        /* ── Submit Button ── */
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 48px;
            padding: 0 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            background: var(--brand);
            color: #fff;
            cursor: pointer;
            transition: all 0.2s;
            gap: 8px;
        }

        .button:hover {
            background: var(--brand-light);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(198, 40, 40, 0.2);
        }

        /* ── Cooldown State ── */
        .cooldown-banner {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 32px 20px;
            gap: 12px;
        }

        .cooldown-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            background: var(--info-soft);
            color: var(--info);
        }

        .cooldown-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-main);
        }

        .cooldown-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
            max-width: 400px;
        }

        .cooldown-date {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            color: var(--info);
            background: var(--info-soft);
            border: 1px solid rgba(37, 99, 235, 0.15);
        }

        /* ── History Cards ── */
        .history-batch {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .history-batch+.history-batch {
            margin-top: 12px;
        }

        .batch-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: var(--slate-50);
            border-bottom: 1px solid var(--border-color);
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
        }

        .batch-ratings {
            display: flex;
            flex-direction: column;
        }

        .batch-rating-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
        }

        .batch-rating-row:last-child {
            border-bottom: none;
        }

        .batch-cat-label {
            font-weight: 600;
            color: var(--text-main);
        }

        .batch-stars {
            display: flex;
            gap: 2px;
            color: #f59e0b;
            font-size: 14px;
        }

        .batch-message {
            padding: 12px 16px;
            border-top: 1px solid var(--border-color);
            font-size: 13px;
            color: var(--text-main);
            background: var(--slate-50);
            line-height: 1.6;
        }

        @media (max-width: 480px) {
            .category-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .category-right {
                width: 100%;
                justify-content: space-between;
            }

            .rating-desc-inline {
                min-width: auto;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page">
        @if (session('success_testimoni'))
            <div class="alert-success">
                <i class="ri-checkbox-circle-fill" style="font-size: 18px;"></i>
                {{ session('success_testimoni') }}
            </div>
        @endif

        @if (session('error_testimoni'))
            <div class="alert-error">
                <i class="ri-error-warning-fill" style="font-size: 18px;"></i>
                {{ session('error_testimoni') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert-error">
                <i class="ri-error-warning-fill" style="font-size: 18px;"></i>
                {{ session('error') }}
            </div>
        @endif

        @if (!($inputPeriodStatus['is_open'] ?? true))
            <div class="alert-info">
                <i class="ri-calendar-event-line" style="font-size: 18px;"></i>
                {{ $inputPeriodStatus['title'] }}. {{ $inputPeriodStatus['message'] }} Periode yang berlaku: {{ $inputPeriodStatus['period_label'] }}.
            </div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h2>Testimoni Kapor</h2>
                <p>Beri penilaian Anda untuk setiap bagian kaporlap yang diterima.</p>
            </div>
            <div class="panel-body">
                @if ($canSubmitNow)
                    <form action="{{ route('personil.testimoni.store') }}" method="POST">
                        @csrf

                        <div class="field">
                            <label class="label">Penilaian per Kategori</label>

                            <div class="category-ratings">
                                @php
                                    $categories = [
                                        'tutup_kepala' => [
                                            'label' => 'Tutup Kepala',
                                            'icon' => 'ri-shield-user-line',
                                            'bg' => '#eff6ff',
                                            'color' => '#2563eb',
                                        ],
                                        'tutup_badan' => [
                                            'label' => 'Tutup Badan',
                                            'icon' => 'ri-t-shirt-2-line',
                                            'bg' => '#f0fdf4',
                                            'color' => '#059669',
                                        ],
                                        'tutup_kaki' => [
                                            'label' => 'Tutup Kaki',
                                            'icon' => 'ri-footprint-line',
                                            'bg' => '#fff7ed',
                                            'color' => '#d97706',
                                        ],
                                    ];
                                @endphp

                                @foreach ($categories as $key => $cat)
                                    <div class="category-row">
                                        <div class="category-label">
                                            <div class="category-icon"
                                                style="background: {{ $cat['bg'] }}; color: {{ $cat['color'] }};">
                                                <i class="{{ $cat['icon'] }}"></i>
                                            </div>
                                            <span>{{ $cat['label'] }}</span>
                                        </div>
                                        <div class="category-right">
                                            <div class="rating" data-category="{{ $key }}">
                                                @for ($star = 5; $star >= 1; $star--)
                                                    <input type="radio" id="rating-{{ $key }}-{{ $star }}"
                                                        name="rating_{{ $key }}" value="{{ $star }}"
                                                        {{ (int) old('rating_' . $key, 5) === $star ? 'checked' : '' }}>
                                                    <label for="rating-{{ $key }}-{{ $star }}"
                                                        data-value="{{ $star }}">
                                                        <i class="ri-star-fill"></i>
                                                    </label>
                                                @endfor
                                            </div>
                                            <div class="rating-desc-inline" id="desc-{{ $key }}">Sangat Bagus</div>
                                        </div>
                                    </div>
                                    @error('rating_' . $key)
                                        <div style="padding: 4px 16px;">
                                            <span class="error">{{ $message }}</span>
                                        </div>
                                    @enderror
                                @endforeach
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="message">Pesan / Saran <span
                                    style="font-weight: 500; color: var(--text-muted);">(opsional)</span></label>
                            <textarea id="message" name="message" class="control"
                                placeholder="Ceritakan pengalaman Anda tentang kaporlap yang diterima...">{{ old('message') }}</textarea>
                            @error('message')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div style="margin-top: 18px;">
                            <button type="submit" class="button">
                                <i class="ri-send-plane-fill"></i>
                                Kirim Testimoni
                            </button>
                        </div>
                    </form>
                @elseif (!$canSubmit)
                    <div class="cooldown-banner">
                        <div class="cooldown-icon">
                            <i class="ri-time-line"></i>
                        </div>
                        <div class="cooldown-title">Testimoni sudah dikirim</div>
                        <div class="cooldown-desc">
                            Anda sudah mengirim testimoni pada
                            <strong>{{ $latestTestimonial->created_at->format('d M Y, H:i') }}</strong>.
                            Untuk menjaga kualitas umpan balik, testimoni berikutnya dapat dikirim setelah:
                        </div>
                        <div class="cooldown-date">
                            <i class="ri-calendar-check-line"></i>
                            {{ $cooldownEndsAt->format('d M Y') }}
                            @if ($daysSinceLastSubmit !== null)
                                ({{ $daysSinceLastSubmit + 1 }} hari lagi)
                            @endif
                        </div>
                    </div>
                @else
                    <div class="cooldown-banner">
                        <div class="cooldown-icon">
                            <i class="ri-lock-line"></i>
                        </div>
                        <div class="cooldown-title">Pengiriman testimoni sedang nonaktif</div>
                        <div class="cooldown-desc">
                            Anda masih bisa melihat riwayat testimoni sebelumnya, tetapi pengiriman testimoni baru akan dibuka kembali mengikuti status periode input yang aktif.
                        </div>
                        <div class="cooldown-date">
                            <i class="ri-calendar-check-line"></i>
                            {{ $inputPeriodStatus['period_label'] }}
                        </div>
                    </div>
                @endif
            </div>
        </section>

        @if ($groupedTestimonials->isNotEmpty())
            <section class="panel">
                <div class="panel-header">
                    <h2>Riwayat Testimoni</h2>
                    <p>Testimoni yang pernah Anda kirim sebelumnya.</p>
                </div>
                <div class="panel-body">
                    @foreach ($groupedTestimonials as $timestamp => $batch)
                        <div class="history-batch">
                            <div class="batch-header">
                                <span>{{ \Carbon\Carbon::parse($timestamp)->format('d M Y, H:i') }}</span>
                            </div>
                            <div class="batch-ratings">
                                @foreach ($batch as $testimonial)
                                    <div class="batch-rating-row">
                                        <span class="batch-cat-label">{{ $testimonial->category_label }}</span>
                                        <div class="batch-stars">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <i
                                                    class="{{ ($testimonial->rating ?? 5) >= $star ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                                            @endfor
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if (filled($batch->first()->message))
                                <div class="batch-message">
                                    <i class="ri-chat-quote-line"
                                        style="color: var(--text-muted); margin-right: 4px;"></i>
                                    {{ $batch->first()->message }}
                                </div>
                            @endif
                        </div>
                    @endforeach
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
        document.addEventListener('DOMContentLoaded', function() {
            const texts = {
                '5': 'Sangat Bagus',
                '4': 'Bagus',
                '3': 'Biasa',
                '2': 'Buruk',
                '1': 'Sangat Buruk'
            };

            document.querySelectorAll('.rating').forEach(ratingGroup => {
                const category = ratingGroup.dataset.category;
                const descEl = document.getElementById('desc-' + category);
                const inputs = ratingGroup.querySelectorAll('input[type="radio"]');
                const labels = ratingGroup.querySelectorAll('label');

                function updateDesc(val) {
                    if (descEl && texts[val]) {
                        descEl.textContent = texts[val];
                    }
                }

                // Initialize description
                const checked = ratingGroup.querySelector('input:checked');
                if (checked) updateDesc(checked.value);

                inputs.forEach(input => {
                    input.addEventListener('change', (e) => updateDesc(e.target.value));
                });

                labels.forEach(label => {
                    label.addEventListener('mouseenter', () => updateDesc(label.dataset.value));
                    label.addEventListener('mouseleave', () => {
                        const checked = ratingGroup.querySelector('input:checked');
                        if (checked) updateDesc(checked.value);
                    });
                });
            });
        });
    </script>
@endsection
