<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemLock
{
    /**
     * Block kapor submissions when the system is locked by Superadmin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isLocked = Setting::getValue('is_system_locked', 'false');

        // 1. Cek kunci manual (Force Lock)
        if ($isLocked === 'true' || $isLocked === '1') {
            abort(403, 'Sistem sedang dikunci paksa oleh Administrator. Pengisian data kapor ditutup.');
        }

        // 2. Cek rentang tanggal (Periode Input)
        $startDateStr = Setting::getValue('input_start_date', date('Y-02-01'));
        $endDateStr = Setting::getValue('input_end_date', date('Y-08-31'));

        try {
            $startDate = \Carbon\Carbon::parse($startDateStr)->startOfDay();
            $endDate = \Carbon\Carbon::parse($endDateStr)->endOfDay();
            $now = now();

            if ($now->lessThan($startDate) || $now->greaterThan($endDate)) {
                abort(403, "Sistem terkunci. Saat ini berada di luar periode pengisian data KAPOR ({$startDate->format('d M Y')} s/d {$endDate->format('d M Y')}).");
            }
        } catch (\Exception $e) {
            // Jika parsing tanggal gagal (tidak valid), biarkan lanjut untuk mencegah error server
        }

        return $next($request);
    }
}
