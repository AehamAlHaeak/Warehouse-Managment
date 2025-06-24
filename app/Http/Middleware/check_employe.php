<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class check_employe
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        try {
            if (Auth::guard('employee')->check()) {
                $request->merge([
                    'employe' => Auth::guard('employee')->user(),
                ]);
                return $next($request);
            }

            return response()->json(['msg' => 'Unauthorized - Invalid or missing employe token'], 401);
        } catch (\Exception $e) {
            return response()->json(['msg' => 'Token error: ' . $e->getMessage()], 401);
        }
    }
}
