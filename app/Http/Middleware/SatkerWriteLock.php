<?php

namespace App\Http\Middleware;

use App\Support\PeriodGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SatkerWriteLock
{
    /**
     * Block write actions for admin_satker and personil when the global satker lock is active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->hasAnyRole(['admin_satker', 'personil'])
            && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
        ) {
            $lockMessage = PeriodGate::resolveSatkerLockMessage();

            if ($lockMessage !== null) {
                return PeriodGate::buildBlockedResponse($request, $lockMessage, PeriodGate::resolveSatkerStatus());
            }
        }

        return $next($request);
    }
}
