<?php

namespace App\Http\Controllers;

use App\Http\Requests\storeEmployeeRequest;
use App\Models\Employe;
use App\Models\Specialization;
use Hash;
use Illuminate\Http\Request;

class EmployeesController extends Controller
{
   public function login_employee(Request $request){
   $validated_values=$request->validate([
      "email"=> "required|email",
      "password"=> "required",
   ]);

$employee=Employe::where("email",$validated_values["email"])->first();
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

public function logout_employee(Request $request){
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
