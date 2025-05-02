<?php

namespace App\Traits;

use App\Models\Bill;
use App\Models\type;
use App\Models\User;
use App\Models\Cargo;
use App\Models\Garage;
use App\Models\Employe;
use App\Models\Product;
use App\Models\Vehicle;
use App\Models\Favorite;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Models\Bill_Detail;
use Illuminate\Http\Request;
use App\Models\Specialization;
use App\Models\Supplier_Product;
use App\Models\Transfer_Vehicle;
use App\Models\Werehouse_Product;
use App\Models\DistributionCenter;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Models\distribution_center_Product;

trait AlgorithmsTrait
{
public function create_token($object){
    $claims = [
        'id' => $object->id,
        'email' => $object->email,
        'phone_number' => $object->phone_number,
    ];
    
  
    if ($object->specialization) {
        $claims['specialization'] = $object->specialization->name;
    }
    $token = JWTAuth::claims($claims)->fromUser($object);
     return $token;
}


public function valedate_and_build(Request $request){
  
    $validated_products=null;
    $validated_vehicles=null;
  
    $errors_products=null;
    $errors_vehicles=null;



    foreach ($request->input('products', []) as $index => $product) {
     
     $validator = Validator::make($product, [
         "product_id"=>"required|integer",
         "expiration"=> "required|date",
         "producted_in"=>"required|date",
         "unit"=>"required",
         "price_unit"=>"required",
        
         
     ]);
   
     if ($validator->fails()) {
        $errors_products[$index] = [
           'at_product' => $product,
           'errors' => $validator->errors()->all()
          
         ];
  }else {
    
         $validated_products[] = $product;
     }
 }
 
 foreach ($request->input('vehicles', []) as $index => $vehicle) {
  //we will not add the import_jop_id because we will send the object when there is no errors
  //then the job in the queue will create the vehicle with the import_job location and with its id!
  //we will not create the import jop else if there is nooo any error else 
  //we will store the correct values in cach and request from admin to correct it!
  //at the same consept we dont ask the import_jop_id for the product because if there are any errpr 
  //the import job willnot be created!!
 
  $validator = Validator::make($vehicle,[
    "name" => "required",
     "expiration" => "required|date",
     "producted_in" => "required|date",
     "readiness" => "required|numeric|min:0|max:1",
     "max_load" => "required|numeric|min:1000",
     "type_id" => "required",
      
  ]);

  if ($validator->fails()) {
     $errors_vehicles[$index] = [
        'at_vehicle' => $vehicle,
        'errors' => $validator->errors()->all()
       
      ];
}else {
  $validated_vehicles[] = $vehicle;
  }
}


$Data=[];
$Data["products"]=$validated_products;
$Data["vehicles"]=$validated_vehicles;
$Data["errors_products"]=$errors_products;
$Data["errors_vehicles"]=$errors_vehicles;

return $Data;

}
}
