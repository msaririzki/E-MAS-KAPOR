<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventReadOnlyWrites
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isReadOnlyAdmin() && ! $request->isMethodSafe()) {
            abort(403, 'Akun Kabak Bekum bersifat read-only dan tidak dapat mengubah data.');
        }

        return $next($request);
    }
}
