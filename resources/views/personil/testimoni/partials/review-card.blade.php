@php
    /** @var array $card */
    $allocation = $card['allocation'];
    $review = $card['review'];
    $isReadOnly = ! ($reviewPeriodStatus['is_open'] ?? true);
    $selectedStatus = old('allocation_id') == $allocation->id
        ? old('response_status', $review?->response_status ?? \App\Models\ItemReview::STATUS_REVIEWED)
        : ($review?->response_status ?? \App\Models\ItemReview::STATUS_REVIEWED);
    $selectedRating = old('allocation_id') == $allocation->id
        ? old('rating', $review?->rating)
        : $review?->rating;
    $selectedMessage = old('allocation_id') == $allocation->id
        ? old('message', $review?->comment)
        : $review?->comment;
    $statusClass = $review === null
        ? 'info'
        : ($review->response_status === \App\Models\ItemReview::STATUS_NOT_RECEIVED ? 'warning' : 'success');
    $helperMessage = ($isHistoricalYear ?? false)
        ? 'Tahun anggaran ini sudah menjadi arsip. Detail review hanya bisa dibaca sebagai riwayat.'
        : ($isReadOnly
            ? 'Periode review sedang ditutup. Form tampil dalam mode baca saja.'
            : 'Anda dapat memperbarui respons ini selama periode review masih berjalan.');
@endphp

<section class="d-panel review-card-item" data-searchable="{{ strtolower($card['item_name'].' '.$card['item_category'].' '.$card['package_name'].' '.($review?->response_label ?? '')) }}" data-editing="false">
    <div class="d-panel-body review-card-body">
        <div class="review-head">
            <div>
                <div class="review-item-name">{{ $card['item_name'] }}</div>
                <div class="review-meta">{{ $card['item_category'] ?: 'Tanpa kategori' }} • TA {{ $allocation->fiscal_year }} • {{ $card['size_label'] ?? 'Ukuran' }} {{ $card['size_value'] ?? '-' }}</div>
                @if($review?->updated_at)
                    <div class="review-meta">Diperbarui {{ $review->updated_at->translatedFormat('d M Y H:i') }}</div>
                @endif
            </div>
            <div style="display:grid;gap:6px;justify-items:start;">
                <span class="status-badge {{ $statusClass }}">
                    {{ $review?->response_label ?? 'Belum Direview' }}
                </span>
            </div>
        </div>

        <div class="helper-copy">Pilih <strong>Belum diterima</strong> jika distribusi belum sampai, atau beri bintang jika item sudah diterima.</div>

        <form action="{{ route('personil.testimoni.store') }}" method="POST" class="review-form">
            @csrf
            <input type="hidden" name="allocation_id" value="{{ $allocation->id }}">
            <input type="hidden" name="year" value="{{ $fiscalYear }}">

            <fieldset @disabled($isReadOnly || $review !== null) class="review-fieldset">
                <div class="field-group">
                    <span class="field-label">Status penerimaan</span>
                    <div class="status-choice-grid">
                        <label class="status-choice">
                            <input type="radio" name="response_status" value="{{ \App\Models\ItemReview::STATUS_REVIEWED }}" data-response-input {{ $selectedStatus === \App\Models\ItemReview::STATUS_REVIEWED ? 'checked' : '' }}>
                            <span class="status-choice-title">Diterima</span>
                        </label>
                        <label class="status-choice">
                            <input type="radio" name="response_status" value="{{ \App\Models\ItemReview::STATUS_NOT_RECEIVED }}" data-response-input {{ $selectedStatus === \App\Models\ItemReview::STATUS_NOT_RECEIVED ? 'checked' : '' }}>
                            <span class="status-choice-title">Belum diterima</span>
                        </label>
                    </div>
                </div>

                <div class="field-group" data-rating-wrap>
                    <span class="field-label">Rating</span>
                    <div class="star-picker" aria-label="Pilih rating bintang">
                        @for($star = 5; $star >= 1; $star--)
                            @php
                                $ratingLabel = match ($star) {
                                    5 => 'Sangat puas',
                                    4 => 'Puas',
                                    3 => 'Cukup puas',
                                    2 => 'Kurang puas',
                                    default => 'Tidak puas',
                                };
                            @endphp
                            <input type="radio" id="rating-{{ $allocation->id }}-{{ $star }}" name="rating" value="{{ $star }}" data-rating-input {{ (string) $selectedRating === (string) $star ? 'checked' : '' }}>
                            <label for="rating-{{ $allocation->id }}-{{ $star }}" title="{{ $star }} bintang - {{ $ratingLabel }}" aria-label="{{ $star }} bintang - {{ $ratingLabel }}">
                                <i class="ri-star-fill"></i>
                            </label>
                        @endfor
                    </div>
                </div>

                <label class="field-group">
                    <span class="field-label">Catatan</span>
                    <textarea name="message" rows="4" maxlength="2000" placeholder="Tulis pengalaman singkat Anda atau jelaskan jika item belum diterima." class="field-control field-textarea">{{ $selectedMessage }}</textarea>
                </label>
            </fieldset>

            <div class="review-actions">
                <span class="helper-copy">{{ $helperMessage }}</span>
                <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                    @if ($review && ! $isReadOnly && ! ($isHistoricalYear ?? false))
                        <button type="button" class="edit-toggle" data-edit-toggle>
                            <i class="ri-edit-line"></i> Edit
                        </button>
                    @endif
                    <button type="submit" class="submit-button {{ ($review || ($isHistoricalYear ?? false)) ? 'hidden' : '' }}" @disabled($isReadOnly)>
                    {{ $review ? 'Perbarui Respons' : 'Simpan Respons' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>
