<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class ApiAuthenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            $this->authenticate($guards);
        } catch (AuthenticationException $e) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }

    protected function authenticate(array $guards)
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);
                return;
            }
        }

        throw new AuthenticationException('Unauthenticated.', $guards);
    }
}
