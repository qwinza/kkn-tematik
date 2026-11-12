<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDosen
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isDosen() || ! $request->user()->prodi()->exists()) {
            abort(403, 'Akses ini khusus dosen pembimbing dengan program studi terdaftar.');
        }

        return $next($request);
    }
}
