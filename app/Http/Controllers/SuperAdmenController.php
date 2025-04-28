<?php

namespace App\Http\Controllers;

use App\Http\Requests\storeEmployeeRequest;
use App\Models\Employe;
use Hash;
use Illuminate\Http\Request;
use App\Traits\CRUDTrait;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
class SuperAdmenController extends Controller
{
   use CRUDTrait;

   //first create a type to imprt the products type and warehouse type an etc
   public function create_new_specification(Request $request){
      //specification is type or specialization 
      $request->validate(["specification"=>"required|in:type,Specialization"]);
      
      $validated_values=$request->validate(["name"=>"required"]);

     $this->create_item("App\Models\\".$request->specification,$validated_values);

     return response()->json(["msg"=>"succesfuly adding"],201);
    }


    public function create_new_item(Request $request){
      $request->validate([ "item"=>"required|in:Warehouse,DistributionCenter"]);
       $validated_values=$request->validate([
          "name"=>"required|max:128",
          "location"=>"required",
          "latitude"=>"required",
          "longitude"=>"required",
          "type_id"=>"required"
         
       ]);

       $model="App\Models"."\\".$request->item;
       $this->create_item($model,$validated_values);
       return response()->json(["msg"=>"succesfuly adding"],201);

    }
     
     public function create_new_employe(storeEmployeeRequest $request){
      $validated_values=$request->validated();
      $password=Hash::make($validated_values["password"]);
      $validated_values['password']=$password;
         if($validated_values['workable_type']!=null){
               $validated_values['workable_type']="App\Models\\".$request->workable_type;
         }
      $this->create_item("App\Models\\Employe",$validated_values);
      
      return response()->json(["msg"=>"succesfuly adding"],201);
     }





     
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
