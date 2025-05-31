<?php

namespace App\Traits;
use App\Models\Positions_on_sto_m;
use App\Models\Bill;
use App\Models\type;
use App\Models\User;
use App\Models\Storage_media;
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
use App\Models\Section;
use App\Models\DistributionCenter;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\Distribution_center_Product;
use App\Models\Posetions_on_section;
trait AlgorithmsTrait
{
public function create_token($object){
    $claims = [
        'id' => $object->id,
        'email' => $object->email,
        'phone_number' => $object->phone_number,
        'exp' => now()->addYear()
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
         "quantity"=>"required"
        
         
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



public function calculate($latitude1,$longitude1, $latitude2,$longitude2) {
    $apiKey = config('services.openrouteservice.key');
//36.2765, 33.5138, 37.0, 35.0
    $coordinates = [
        [$longitude1, $latitude1],  // نقطة البداية
        [$longitude2, $latitude2],  // نقطة النهاية
    ];

    $response = Http::withHeaders([
        'Authorization' => $apiKey,
        'Content-Type' => 'application/json',
    ])->post('https://api.openrouteservice.org/v2/matrix/driving-hgv', [
        'locations' => $coordinates,
        'metrics' => ['distance', 'duration'],//the time is by seconds then we will change it
        
    ]);
    //$data['distances'][0][1] the destance by meters
    $data = $response->json();

    if (isset($data)) {
        return $data; // المسافة بالأمتار
    } else {
        throw new \Exception('Failed to fetch distance: ' . json_encode($data));
    }


   
}



























public function calculate_the_nearest_location($model, $latitude, $longitude)
{

    $items = $model::all();

    $distances = [];
    foreach ($items as $item) {
          $data=$this->calculate($item->latitude, $item->longitude, $latitude, $longitude);
        $item->distance =$data['distances'][0][1];//0 1 are from 0 to one form sourece to dest
        $item->duration=$data["duration"][0][1];
        $distances[] = $item;
    }
    $leastdistance = $distances[0];
    foreach ($distances as $item) {

        if ($item->distance <=  $leastdistance->distance) {

            $leastdistance = $item;
        }
    }

    return $leastdistance;
}
public function sort_the_near_by_location($model, $latitude, $longitude)
{
   
    $items = $model::all();

    
    foreach ($items as $item) {
        $data=$this->calculate($item->latitude, $item->longitude, $latitude, $longitude);
        
        $item["distance"]=$data['distances'][0][1];//0 1 are from 0 to one form sourece to dest
        $item->duration=$data['durations'][0][1]/3600;
    }

  
    $sorted = $items->sortBy('distance')->values();

   
    return $sorted;
}

public function create_postions($model,$object,$foreignId_name){

    for($floor=0;$floor<$object->num_floors;$floor++){
        
             for($class=0;$class<$object->num_classes;$class++){
               
               for($positions_on_class=0;$positions_on_class<$object->num_positions_on_class;$positions_on_class++){
            
                $model::create([
                 $foreignId_name=>$object["id"],
                 "floor"=>$floor,
                 "class"=>$class,
                 "positions_on_class"=>$positions_on_class
             ]);
            
           
            
           }
           }
           }

}




 public function calculate_areas($section){
        $avilable_area=0;
         $max_capacity=0;
         $product=$section->product;
         $continer=$product->continer;
         $storage_media=$continer->storage_media;
         $storage_elements=$section->storage_elements;
             
             $max_capacity= $storage_elements->count();
            $max_capacity=$max_capacity*$storage_media->num_floors*$storage_media->num_classes*$storage_media->num_positions_on_class*$continer->capacity;
             
            foreach($storage_elements as $storage_element){

                 $avilable_area+=$storage_element->posetions->whereNull("imp_op_contin_id")->count();
          
            }
            $avilable_area=$avilable_area*$continer->capacity;

            $areas=[
                "avilable_area"=>$avilable_area,
               "max_capacity"=>$max_capacity
            ];
            return $areas;

    }

    public function calcute_areas_on_place_for_a_specific_product($object,$product_id){
       $avilable_area=0;
       $max_capacity=0;
         
    
        $sections_of_the_product_in_object=$object->sections->where("product_id",$product_id);
   
          
          foreach($sections_of_the_product_in_object as $section){
          
    
             $areas=$this->calculate_areas( $section);
              $avilable_area+=$areas["avilable_area"];
              $max_capacity+=$areas["max_capacity"];
          }
       
        $object->max_capacity=$max_capacity;
        $object->avilable_area=$avilable_area;
       unset($object["sections"]);
       return $object;
    }
}
