<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employe;
use Illuminate\Http\Request;
use App\Traits\AlgorithmsTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

class EmployeController extends Controller
{
   use AlgorithmsTrait;
    public function login_employe(Request $request){
        $validated_values=$request->validate([
           "email"=> "email",
           "password"=> "required",
           "phone_number"=>"numeric"
        ]);
      
       $employee = null;

   
       if (!empty($request->email)) {
         $employee = Employe::where('email', $request->email)->first();
       }
       
     
       if (!$employee && !empty($request->phone_number)) {
         $employee = Employe::where('phone_number', $request->phone_number)->first();
       }
       
    
       if (!$employee) {
           return response()->json(["msg" => "account is not exist"], 400);
       }
       
    
       $token=$this->create_token($employee);
       return response()->json(["msg" => "Logged in successfully", "token" => $token], 200);
     
     }

     public function logout_employe(Request $request){
        try{
        $token=JWTAuth::getToken();
        if($token){
           JWTAuth::invalidate();
           return response()->json(["msg"=> "Successfully Logged out  "],200);
        }
        return response()->json(["msg"=> "No Token Found"],400);
     }
     
      catch (\Exception $e) {
        return response()->json(["msg" => "Failed to logout, please try again later"], 500);
     }
     }
}
