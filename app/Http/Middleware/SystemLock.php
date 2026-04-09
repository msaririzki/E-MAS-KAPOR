<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemLock
{
    /**
     * Block write actions when the system is locked by Superadmin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lockMessage = self::resolveLockMessage();

        if ($lockMessage !== null && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            abort(403, $lockMessage);
        }

        return $next($request);
    }

    public static function resolveLockMessage(): ?string
    {
        $isLocked = Setting::getValue('is_system_locked', 'false');

        if ($isLocked === 'true' || $isLocked === '1') {
            return 'Sistem sedang dikunci paksa oleh Administrator. Pengisian data kapor ditutup.';
        }

        $startDateStr = Setting::getValue('input_start_date', date('Y-02-01'));
        $endDateStr = Setting::getValue('input_end_date', date('Y-08-31'));

        try {
            $startDate = Carbon::parse($startDateStr)->startOfDay();
            $endDate = Carbon::parse($endDateStr)->endOfDay();
            $now = now();

            if ($now->lessThan($startDate) || $now->greaterThan($endDate)) {
                return "Sistem terkunci. Saat ini berada di luar periode pengisian data KAPOR ({$startDate->format('d M Y')} s/d {$endDate->format('d M Y')}).";
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
