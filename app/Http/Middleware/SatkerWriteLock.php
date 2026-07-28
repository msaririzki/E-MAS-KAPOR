<?php

namespace App\Http\Middleware;

use App\Support\PeriodGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SatkerWriteLock
{
    /**
     * Block data maintenance by admin satker while allowing personnel self-service.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->hasRole('admin_satker')
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
