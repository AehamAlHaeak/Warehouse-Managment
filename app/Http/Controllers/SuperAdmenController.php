<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\CRUDTrait;
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
     
     public function create_new_employe(Request $request){
      $validated_values=$request->validate([
       "name"=>"required",
       "email"=>"required|email",
       "password"=>"required|min:8",
       "phone_number"=>"required",
       "specialization_id"=>"required|integer",
       "salary"=>"required",
       "birth_day"=>"date",
       "country"=>"required",
       "start_time"=>"required",
       "work_hours"=>"required|integer|max:10",
       "workable_type"=>"in:Warehouse,DistributionCenter",
      "workable_id"=>"integer"
      ]);
         if($request->workable_type!=null){
               $validated_values['workable_type']="App\Models\\".$request->workable_type;
         }
      $this->create_item("App\Models\\Employe",$validated_values);

      return response()->json(["msg"=>"succesfuly adding"],201);
     }

   
   
}
