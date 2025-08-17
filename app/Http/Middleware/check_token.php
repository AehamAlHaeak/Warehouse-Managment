<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class check_token
{
    
    public function handle(Request $request, Closure $next)
    {
        
        if ($request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();
            if ($user) {
                
                return $next($request);
            }
           if( Auth::guard('employee')->check()){
            return $next($request);
           }
        }

        return response()->json([
            'message' => 'Invalid or missing token'
        ], 401);
    }
}
