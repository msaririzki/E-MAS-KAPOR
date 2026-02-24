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

        if ($isLocked === 'true' || $isLocked === '1') {
            abort(403, 'Sistem sedang dikunci oleh Administrator. Pengisian data kapor tidak dapat dilakukan saat ini.');
        }

        return $next($request);
    }
}
