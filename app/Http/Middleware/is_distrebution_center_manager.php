<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class is_distrebution_center_manager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        try {
            $admin = JWTAuth::parseToken()->authenticate('employe');


          } catch (TokenExpiredException $e) {
              return response()->json(['error' => $e->getMessage()], 401);
          } catch (TokenInvalidException $e) {
              return response()->json(['error' => $e->getMessage()], 401);
          } catch (JWTException $e) {
              return response()->json(['error' => $e->getMessage()], 401);
          }

          $token = $request->bearerToken();


        $payload = JWTAuth::getPayload($token);


        $isAdmin = $payload->get('specialization');

        if($isAdmin=='Distribution_center_admin' ||$isAdmin=='Warehouse_admin'||$isAdmin=='Super_admin'){

          return $next($request);
      }
          //return response()->json(["msg"=>" you re not admin"],401);

        return $next($request);
    }
}
