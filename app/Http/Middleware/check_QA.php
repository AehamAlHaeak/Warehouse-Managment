<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class check_QA
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

                if ($specialization == "super_admin" ||  $specialization == "QA") {

                    $employe = Auth::guard('employee')->user();
                    if ($employe) {
                        $request->merge([
                            "employe" => $employe,
                        ]);
                        
                        return $next($request);
                    } else {
                        try {

                            JWTAuth::invalidate(JWTAuth::getToken());

                            return response()->json(['message' => 'Successfully logged out']);
                        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
                            return response()->json(['msg' => 'Unauthorized - Invalid or missing employe token'], 401);
                        }
                        return response()->json(['msg' => 'Unauthorized - Invalid or missing employe token'], 401);
                    }
                }
            }
            try {

                JWTAuth::invalidate(JWTAuth::getToken());
            } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
                return response()->json(['msg' => 'Unauthorized - Invalid or missing employe token'], 401);
            }



            return response()->json(['msg' => 'Unauthorized - Invalid or missing employe token'], 401);
        } catch (\Exception $e) {
            return response()->json(['msg' => 'Token error: ' . $e->getMessage()], 401);
        }
    }
}
