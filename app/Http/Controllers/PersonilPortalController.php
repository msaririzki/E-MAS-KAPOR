<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Services\AuditLogger;
use App\Services\KaporRequirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PersonilPortalController extends Controller
{
    public function storeKapor(Request $request, KaporRequirementService $kaporRequirementService): RedirectResponse
    {
        $personnel = $request->user()?->personnel;

        if ($personnel === null) {
            return redirect()->route('dashboard')->with('error', 'Data personel Anda belum tersedia di sistem.');
        }

        $mode = $request->input('mode') === 'identity' ? 'identity' : 'sizes';
        $requiresJilbab = $personnel->gender === 'P'
            && strtoupper(trim((string) $personnel->religion)) === 'ISLAM';
        $requiresBagian = ($personnel->satker ?? $request->user()?->satker)?->recipientScope() === 'polres';

        $rules = [
            'jabatan' => 'required|string|max:255',
        ];

        if ($requiresBagian) {
            $rules['bagian'] = 'required|string|max:255';
        }

        if ($mode === 'sizes') {
            $rules = array_merge($rules, [
                'kemeja' => 'required|string',
                'celana' => 'required|string',
                'olahraga' => 'required|string',
                'jaket' => 'required|string',
                'topi' => 'required|string',
                'sabuk' => 'required|string',
                'sepatu_dinas' => 'required|string',
                'sepatu_olahraga' => 'required|string',
            ]);

            if ($requiresJilbab) {
                $rules['jilbab'] = 'required|string';
            }
        }

        $validated = $request->validate($rules);

        $previousIdentity = [
            'jabatan' => $personnel->jabatan,
            'bagian' => $personnel->bagian,
        ];

        $nextIdentity = [
            'jabatan' => $this->normalizeIdentityValue($validated['jabatan']),
            'bagian' => $requiresBagian
                ? $this->normalizeIdentityValue($validated['bagian'])
                : $personnel->bagian,
        ];

        $personnel->jabatan = $nextIdentity['jabatan'];
        $personnel->bagian = $nextIdentity['bagian'];

        if ($mode === 'sizes') {
            $sizePayload = collect($validated)
                ->except(['jabatan', 'bagian'])
                ->all();

            $currentSizes = is_array($personnel->kapor_sizes) ? $personnel->kapor_sizes : [];
            $personnel->kapor_sizes = $kaporRequirementService->sanitizeSubmittedSizes(
                array_merge($currentSizes, $sizePayload),
                $personnel->gender,
            );
        }

        $personnel->save();

        $this->logIdentityChanges($personnel, $previousIdentity, $nextIdentity);

        if ($mode === 'identity') {
            return redirect()->to(route('dashboard').'#ukuran-form')
                ->with('success', $requiresBagian
                    ? 'Data jabatan dan bag/fungsi tersimpan. Lanjutkan ke form ukuran kaporlap.'
                    : 'Data jabatan tersimpan. Lanjutkan ke form ukuran kaporlap.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Data ukuran Anda berhasil disimpan dan disinkronkan ke sistem.');
    }

    public function showHistory(Request $request, KaporRequirementService $kaporRequirementService): View
    {
        $personnel = $request->user()?->personnel;
        $kaporSizes = $personnel ? ($personnel->kapor_sizes ?? []) : [];
        $hasSubmitted = ! empty(array_filter((array) $kaporSizes));
        $isComplete = $personnel ? $kaporRequirementService->personnelHasAllRequiredSizes($personnel) : false;

        return view('personil.kapor.history', compact('kaporSizes', 'hasSubmitted', 'isComplete', 'personnel'));
    }

    public function showTestimoni(Request $request): View
    {
        $userId = $request->user()->id;

        // Fetch last batch of testimonials by this user (grouped by submission time)
        $recentTestimonials = Testimonial::query()
            ->where('user_id', $userId)
            ->latest()
            ->take(9) // 3 categories × 3 submissions
            ->get();

        // Cooldown check: find latest testimonial from this user
        $latestTestimonial = Testimonial::query()
            ->where('user_id', $userId)
            ->latest()
            ->first();

        $canSubmit = true;
        $cooldownEndsAt = null;
        $daysSinceLastSubmit = null;

        if ($latestTestimonial) {
            $cooldownEndsAt = $latestTestimonial->created_at->addDays(Testimonial::COOLDOWN_DAYS);
            $canSubmit = now()->greaterThanOrEqualTo($cooldownEndsAt);

            if (! $canSubmit) {
                $daysSinceLastSubmit = (int) now()->diffInDays($cooldownEndsAt, false);
            }
        }

        // Group recent testimonials by submission batch (same created_at minute)
        $groupedTestimonials = $recentTestimonials
            ->groupBy(fn (Testimonial $t): string => $t->created_at->format('Y-m-d H:i'));

        return view('personil.testimoni.index', compact(
            'recentTestimonials',
            'canSubmit',
            'cooldownEndsAt',
            'daysSinceLastSubmit',
            'latestTestimonial',
            'groupedTestimonials',
        ));
    }

    public function storeTestimoni(Request $request): RedirectResponse
    {
        $userId = $request->user()->id;

        // Enforce cooldown on backend
        $latestTestimonial = Testimonial::query()
            ->where('user_id', $userId)
            ->latest()
            ->first();

        if ($latestTestimonial) {
            $cooldownEndsAt = $latestTestimonial->created_at->addDays(Testimonial::COOLDOWN_DAYS);

            if (now()->lessThan($cooldownEndsAt)) {
                return redirect()->route('personil.testimoni.index')
                    ->with('error_testimoni', 'Anda sudah memberi testimoni baru-baru ini. Silakan tunggu hingga '.$cooldownEndsAt->format('d M Y').'.');
            }
        }

        $validated = $request->validate([
            'rating_tutup_kepala' => 'required|integer|min:1|max:5',
            'rating_tutup_badan' => 'required|integer|min:1|max:5',
            'rating_tutup_kaki' => 'required|integer|min:1|max:5',
            'message' => 'nullable|string|max:2000',
        ]);

        $message = $validated['message'] ?? '';
        $now = now();

        DB::transaction(function () use ($userId, $validated, $message, $now): void {
            foreach (Testimonial::CATEGORIES as $key => $label) {
                Testimonial::create([
                    'user_id' => $userId,
                    'category' => $key,
                    'message' => $message,
                    'rating' => $validated['rating_'.$key],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        return redirect()->route('personil.testimoni.index')
            ->with('success_testimoni', 'Terima kasih! Testimoni untuk ketiga kategori kapor berhasil dikirim.');
    }

    private function normalizeIdentityValue(string $value): string
    {
        return strtoupper(trim($value));
    }

    private function logIdentityChanges(object $personnel, array $previousIdentity, array $nextIdentity): void
    {
        if ($previousIdentity === $nextIdentity) {
            return;
        }

        AuditLogger::log(
            'Edit Referensi SDM Personil',
            'Data Personil',
            $personnel,
            $previousIdentity,
            $nextIdentity,
            'success',
            'Personil memperbarui jabatan atau bag/fungsi yang berasal dari import SDM Polda NTB.',
        );
    }
}
