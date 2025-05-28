<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
class check_super_admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       try {
        if (Auth::guard('employee')->check()) {
            $payload = JWTAuth::parseToken()->getPayload();

          
            $specialization = $payload->get('specialization');
            if($specialization == "super_admin" ){
              $request->merge([
                'employe' => Auth::guard('employee')->user(),
            ]);
             
            return $next($request);
            }
           
           
        }

        return response()->json(['message' => 'Unauthorized - Invalid or missing employe token'], 401);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Token error: ' . $e->getMessage()], 401);
    }
    }
}
