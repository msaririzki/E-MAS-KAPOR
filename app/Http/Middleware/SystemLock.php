<?php

namespace App\Http\Middleware;

use App\Support\PeriodGate;
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
        $lockMessage = PeriodGate::resolveInputLockMessage();

        if ($lockMessage !== null && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return PeriodGate::buildBlockedResponse($request, $lockMessage, PeriodGate::resolveInputStatus());
        }

        return $next($request);
    }

    public static function buildBlockedResponse(Request $request, ?string $lockMessage = null): Response
    {
        $lockMessage ??= PeriodGate::resolveInputLockMessage() ?? 'Aksi ini tidak dapat dilakukan saat ini.';

        return PeriodGate::buildBlockedResponse($request, $lockMessage, PeriodGate::resolveInputStatus());
    }

    public static function resolveStatus(): array
    {
        return PeriodGate::resolveInputStatus();
    }

    public static function resolveLockMessage(): ?string
    {
        return PeriodGate::resolveInputLockMessage();
    }
}
