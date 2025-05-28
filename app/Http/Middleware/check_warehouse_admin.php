<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class check_warehouse_admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   public function handle(Request $request, Closure $next)
{
    try {
        if (Auth::guard('employee')->check()) {
            $payload = JWTAuth::parseToken()->getPayload();

            $employeeId = $payload->get('id');
            $specialization = $payload->get('specialization');
            if($specialization=="super_admin" ||  $specialization=="warehouse_admin" ){
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
