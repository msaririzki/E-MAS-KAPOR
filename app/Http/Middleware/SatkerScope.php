<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SatkerScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->hasRole('admin_satker')) {
            // Store the satker_id in the config for easy access in models/controllers
            config(['app.user_satker_id' => $request->user()->satker_id]);
        }

        return $next($request);
    }
}
