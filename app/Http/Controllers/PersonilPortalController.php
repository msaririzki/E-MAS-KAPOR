<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Services\AuditLogger;
use App\Services\KaporRequirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $rules = [
            'jabatan' => 'required|string|max:255',
            'bagian' => 'required|string|max:255',
        ];

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
            'bagian' => $this->normalizeIdentityValue($validated['bagian']),
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
                ->with('success', 'Data jabatan dan bag/fungsi tersimpan. Lanjutkan ke form ukuran kaporlap.');
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
        $recentTestimonials = Testimonial::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->take(3)
            ->get();

        return view('personil.testimoni.index', compact('recentTestimonials'));
    }

    public function storeTestimoni(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        Testimonial::create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'rating' => $validated['rating'] ?? 5,
        ]);

        return redirect()->route('personil.testimoni.index')
            ->with('success_testimoni', 'Terima kasih atas tanggapan Anda! Testimoni berhasil dikirim.');
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
