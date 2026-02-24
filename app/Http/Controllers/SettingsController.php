<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\KaporSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'fiscal_year' => Setting::getValue('fiscal_year', date('Y')),
            'is_system_locked' => Setting::getValue('is_system_locked', 'false') === 'true',
            'app_name' => Setting::getValue('app_name', 'SI-KAPOR Polda NTB'),
        ];

        // Get submission stats per year for history
        $activeYear = $settings['fiscal_year'];

        $submissionStats = KaporSubmission::select('fiscal_year', DB::raw('count(*) as total'))
            ->groupBy('fiscal_year')
            ->orderBy('fiscal_year', 'desc')
            ->get()
            ->keyBy('fiscal_year');

        // Build a comprehensive list of years to show
        $yearsToShow = $submissionStats->keys()->toArray();
        if (!in_array($activeYear, $yearsToShow)) {
            $yearsToShow[] = $activeYear;
        }
        rsort($yearsToShow);

        $yearlyStats = [];
        foreach ($yearsToShow as $year) {
            $yearlyStats[] = (object)[
                'fiscal_year' => $year,
                'total' => $submissionStats[$year]->total ?? 0,
                'is_active' => $year == $activeYear,
                'status' => $year < $activeYear ? 'Selesai' : ($year == $activeYear ? 'Aktif' : 'Mendatang')
            ];
        }

        return view('superadmin.settings', compact('settings', 'yearlyStats'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'fiscal_year' => 'required|integer|min:2020|max:2099',
            'is_system_locked' => 'nullable|boolean',
        ]);

        Setting::setValue('app_name', $validated['app_name']);
        Setting::setValue('fiscal_year', $validated['fiscal_year']);
        Setting::setValue('is_system_locked', $request->has('is_system_locked') ? 'true' : 'false');

        return redirect()->back()->with('success', 'Pengaturan sistem berhasil diperbarui.');
    }

    /**
     * Transition to next fiscal year
     */
    public function nextYear(Request $request)
    {
        $currentYear = Setting::getValue('fiscal_year', date('Y'));
        $nextYear = $currentYear + 1;

        // 1. Lock current year (Optional, but good for safety)
        Setting::setValue('is_system_locked', 'true');

        // 2. Set new year
        Setting::setValue('fiscal_year', $nextYear);

        // 3. Keep system locked? Or unlock for new entries?
        // Usually, we keep it locked until admin is ready.

        return redirect()->back()->with('success', "Tahun Anggaran berhasil beralih ke $nextYear. Sistem saat ini terkunci untuk persiapan.");
    }
}
