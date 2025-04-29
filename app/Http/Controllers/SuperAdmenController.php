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
use Illuminate\Http\Request;
use App\Models\Specialization;
use App\Models\Supplier_Product;
use App\Models\Transfer_Vehicle;
use App\Models\Werehouse_Product;
use App\Models\DistributionCenter;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\storeEmployeeRequest;
use App\Models\distribution_center_Product;

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


    public function create_new_warehouse(Request $request){
     
       $validated_values=$request->validate([
          "name"=>"required|max:128",
          "location"=>"required",
          "latitude"=>"required|numeric",
          "longitude"=>"required|numeric",
          "type_id"=>"required"
         
       ]);

       
       $warehouse=Warehouse::create($validated_values);
       return response()->json(["msg"=>"warehouse added","warehouse_data"=>$warehouse],201);

    }
     

     public function create_new_employe(storeEmployeeRequest $request){
      $request->validate([
         'image'=>'image|mimes:jpeg,png,jpg,gif|max:4096'
        ]);
      $validated_values=$request->validated();

      $password=Hash::make($validated_values["password"]);

      $validated_values['password']=$password;
      
         if($request->workable_type!=null){
               $validated_values['workable_type']="App\Models\\".$request->workable_type;
         }
         if ($request->image != null) {
            $image = $request->file('image');
            $image_path = $image->store('Products', 'public');
            $validated_values["img_path"]= 'storage/' . $image_path;
        }
        $employe=Employe::create($validated_values);
        $employe->specialization=$employe->specialization;
      return response()->json(["msg"=>"succesfuly adding"],201);
      }
    public function create_new_distribution_center(Request $request){
     
      $validated_values=$request->validate([
         "name"=>"required|max:128",
         "location"=>"required",
         "latitude"=>"required|numeric",
         "longitude"=>"required|numeric",
         "warehouse_id"=>"required|numeric"
        
      ]);

      
      $center=DistributionCenter::create($validated_values);
      return response()->json(["msg"=>" distribution_center added!","center_data"=>$center],201);

   }

    





     public function create_new_vehicle(Request $request){

      $validated_values=$request->validate([
         "name"=>"required",
         "expiration"=>"required|date",
         "producted_in"=>"required|date",
         "readiness"=>"required|numeric|min:0|max:1",
         "max_load"=>"required|numeric|min:1000",
         "location"=>"required",
         "latitude"=>"required|numeric",
         "longitude"=>"required|numeric",
         "type_id"=>"required",
         "import_jop_id"=>"required|integer"
              ]);

        $request->validate([
         'image'=>'image|mimes:jpeg,png,jpg,gif|max:4096'
        ]);
        if ($request->image != null) {
         $image = $request->file('image');
         $image_path = $image->store('Vehicles', 'public');
         $validated_values["img_path"]= 'storage/' . $image_path;
     }
     $vehicle=Vehicle::create($validated_values);

      return response()->json(["msg"=>"vehicle added","vehicle_data"=>$vehicle],201);
     }
     public function create_new_cargo(Request $request){
      $validated_values=$request->validate([
         "name"=>"required",
         "expiration"=>"required|date",
         "producted_in"=>"required|date",
         "readiness"=>"required|numeric|min:0|max:1",
         "max_load"=>"required|numeric|min:1000",
         "type_id"=>"required",
         "vehicle_id"=>"required|integer",
         "import_jop_id"=>"required|integer"
              ]); 

         $request->validate([
            'image'=>'image|mimes:jpeg,png,jpg,gif|max:4096'
         ]);

         if ($request->image != null) {
            $image = $request->file('image');
            $image_path = $image->store('Cargos', 'public');
            $validated_values["img_path"]= 'storage/' . $image_path;
        }
        $cargo=Cargo::creat($validated_values);

        return response()->json(["msg"=>"cargo added","cargo_data"=>$cargo],201);

     }
       
     public function create_new_supplier(Request $request){
      $validated_values=$request->validate([
       "comunication_way"=>"required",
       "identifier"=>"required",
       "country"=>"required"

      ]);
       $supplier=Supplier::create($validated_values);

       return response()->json(["msg"=>"supplier added","supplier_data"=>$supplier],201);

     }
     public function create_new_garage(Request $request){
      $validated_values=$request->validate([
        "type"=>"required|in:big,medium",
        "existable_id"=>"integer",
        "existable_type"=>"required_with:existable_id",
        "max_capacity"=>"required|integer",
        'location' => 'required_without:existable_id|max:255',
        'latitude' => 'required_without:existable_id|numeric',
        'longitude' => 'required_without:existable_id|numeric',

      ]);
       //the admin can add undependent garage then he is obligated to determine it's location 
       //else if he create it with an existable_id then cannot determine the location because 
       //the location is already exist with the existable place

      
        $garage=Garage::create($validated_values);

        return response()->json(["msg"=>"garage added","garage_data"=>$garage],201);
     }
     

     public function create_new_import_jop(Request $request){




     }


}


        
      
        
     
       
      
      
   


