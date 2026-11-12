<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && ($user->isMahasiswa() || $user->isDpp())
            && $user->must_change_password
            && ! $request->routeIs('password.first-login.*', 'logout')
        ) {
            return redirect()->route('password.first-login.edit');
        }

        return $next($request);
    }
}