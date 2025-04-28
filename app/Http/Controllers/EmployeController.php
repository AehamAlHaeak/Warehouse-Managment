<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

class EmployeController extends Controller
{
    public function login_employe(Request $request){
        $validated_values=$request->validate([
           "email"=> "required|email",
           "password"=> "required",
        ]);
       
     $employee=\App\Models\Employe::where("email",$validated_values["email"])->first();
     if (!$employee || !Hash::check($validated_values['password'], $employee->password)) {
        return response()->json(["msg" => "Invalid email or password"], 401);
     }
     if($employee==null){
     return response()->json(["msg"=> "This email not found"],404);
        }
       $token= JWTAuth::claims([
        'id'=> $employee->id,
        'email'=> $employee->email,
        'phone_number'=> $employee->phone_number
       ])->fromUser($employee);
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
