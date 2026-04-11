<?php

namespace App\Http\Middleware;

use App\Support\PeriodGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReviewPeriodLock
{
    public function handle(Request $request, Closure $next): Response
    {
        $lockMessage = PeriodGate::resolveReviewLockMessage();

        if ($lockMessage !== null && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return PeriodGate::buildBlockedResponse($request, $lockMessage, PeriodGate::resolveReviewStatus());
        }

        return $next($request);
    }
}
