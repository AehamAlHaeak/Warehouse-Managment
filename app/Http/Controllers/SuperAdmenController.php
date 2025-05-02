<?php

namespace App\Http\Controllers;

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
use App\Traits\CRUDTrait;
use App\Models\Bill_Detail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Specialization;
use App\Models\Supplier_Product;
use App\Models\Transfer_Vehicle;
use App\Models\Werehouse_Product;
use App\Models\DistributionCenter;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\TypeResource;
use Illuminate\Support\Facades\Auth;
use App\Models\Import_jop_product;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\storeProductRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\storeEmployeeRequest;
use App\Models\distribution_center_Product;
use App\Models\Import_jop;

class SuperAdmenController extends Controller
{
   use CRUDTrait;

   //first create a type to imprt the products type and warehouse type an etc
   public function create_new_specification(Request $request)
   {
      //specification is type or specialization 
      $request->validate(["specification" => "required|in:type,Specialization"]);

      $validated_values = $request->validate(["name" => "required"]);

      $this->create_item("App\Models\\" . $request->specification, $validated_values);

      return response()->json(["msg" => "succesfuly adding"], 201);
   }


   public function create_new_warehouse(Request $request)
   {

      $validated_values = $request->validate([
         "name" => "required|max:128",
         "location" => "required",
         "latitude" => "required|numeric",
         "longitude" => "required|numeric",
         "type_id" => "required"

      ]);


      $warehouse = Warehouse::create($validated_values);
      return response()->json(["msg" => "warehouse added", "warehouse_data" => $warehouse], 201);

   }


   public function create_new_employe(storeEmployeeRequest $request)
   {
      $request->validate([
         'image'=>'image|mimes:jpeg,png,jpg,gif|max:4096'
      ]);
      $validated_values = $request->validated();

      $password = Hash::make($validated_values["password"]);

      $validated_values['password'] = $password;

      if ($request->workable_type != null) {
         $validated_values['workable_type'] = "App\Models\\" . $request->workable_type;
      }
      if ($request->image != null) {
         $image = $request->file('image');
         $image_path = $image->store('Products', 'public');
         $validated_values["img_path"] = 'storage/'.$image_path;
      }
      $employe = Employe::create($validated_values);
      $employe->specialization=$employe->specialization->name;
      return response()->json(["msg" => "succesfuly adding","employe_data"=>$employe], 201);
   }


   public function create_new_distribution_center(Request $request)
   {

      $validated_values = $request->validate([
         "name" => "required|max:128",
         "location" => "required",
         "latitude" => "required|numeric",
         "longitude" => "required|numeric",
         "warehouse_id" => "required|numeric"

      ]);


      $center = DistributionCenter::create($validated_values);
      return response()->json(["msg" => " distribution_center added!", "center_data" => $center], 201);

   }








   

   public function create_new_vehicle(Request $request)
   {

      $validated_values = $request->validate([
         "name" => "required",
         "expiration" => "required|date",
         "producted_in" => "required|date",
         "readiness" => "required|numeric|min:0|max:1",
         "max_load" => "required|numeric|min:1000",
         "location" => "required",
         "latitude" => "required|numeric",
         "longitude" => "required|numeric",
         "type_id" => "required",
         "import_jop_id"=>"required|integer"
      ]);


      $request->validate([
         'image' => 'image|mimes:jpeg,png,jpg,gif|max:4096'
      ]);
      if ($request->image != null) {
         $image = $request->file('image');
         $image_path = $image->store('Vehicles', 'public');

         $validated_values["img_path"]= 'storage/' . $image_path;
     }
     $vehicle=Vehicle::create($validated_values);

      return response()->json(["msg"=>"vehicle added","vehicle_data"=>$vehicle],201);
     }



    
       
     public function create_new_supplier(Request $request){
      $validated_values=$request->validate([
       "comunication_way"=>"required",
       "identifier"=>"required",
       "country"=>"required"]);

         
      
      $supplier =Supplier::create($validated_values);


      return response()->json(["msg" => "supplier added", "supplier_data" => $supplier], 201);
   }

  

   
     public function create_new_import_jop(Request $request){
      $validated_values=$request->validate([
         "supplier_id"=>"required|integer",
         "location"=>"required",
         "latitude"=>"required",
         "longitude"=>"required"
      ]);


        $validated_products=null;
        $validated_vehicles=null;
      
        $errors_products=null;
        $errors_vehicles=null;
   


        foreach ($request->input('products', []) as $index => $product) {
         echo "hellow";
         $validator = Validator::make($product, [
             "product_id"=>"required|integer",
             "expiration"=> "required|date",
             "producted_in"=>"required|date",
             "unit"=>"required",
             "price_unit"=>"required",
            
             
         ]);
         echo "hellow";
         if ($validator->fails()) {
            $errors_products[$index] = [
               'at_product' => $product,
               'errors' => $validator->errors()->all()
              
             ];
      }else {
         echo "hellow";
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
         "type_id" => "required"
          
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

  

if( $errors_vehicles!=null || $errors_vehicles!=null){

   $key=Str::uuid();
   Cache::putt($key, $validated_vehicles,now()->addMinutes(60));
  $key2=Str::uuid();
  Cache::putt($key2, $validated_products,now()->addMinutes(60));
//add the arrays to the cash if i will correct the errors

return response()->json(["msg"=>"the import isnot created there are errors",
"product_errors"=>$errors_products,
"errors_vehicles"=>$errors_vehicles
]);


}

$import_jop=Import_jop::create($validated_values);
if(!empty($validated_products)){
   foreach($validated_products as $index=>$product){
     $product["import_jop_id"]=$import_jop->id;
     print_r($import_jop);
     //
     print_r($validated_products);
     Import_jop_product::create($product);  
     
   }
}
return response()->json(["msg"=>"created"],201);
     }

     public function suppourt_new_product(Request $request){
      
             $validated_values=$request->validate([
                 "name"=>"required",
                 "description"=>"required",
                 "import_cycle"=>"string",
                 "average"=>"required",
                 "variance"=>"required",
                 "type_id"=>"required"

             ]);
             
             $product=Product::create($validated_values);

             return response()->json(["msg"=>"product created","product_data"=>$product],201);

      
     }
    



        
      
        
     
       
 


 

  
   }












