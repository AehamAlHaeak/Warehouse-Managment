<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\AlgorithmsTrait;
use App\Models\Storage_media;
class WarehouseController extends Controller
{
    use AlgorithmsTrait;
    public function showGarage($id)
    {
        $garage = Warehouse::find($id)->garages;
        return $garage;
    }

    public function showVehicles_OnGarage($garageid)
    {
        $vehicle = Vehicle::find($garageid)->vehicles;
        return $vehicle;
    }

    public function showprod_In_Warehouse($id)
    {
        $ware_prod = Warehouse::find($id)->supported_roduct;
        return  $ware_prod;
    }

    public function show_Storage_Md($id)
    {

        $warehouse = Warehouse::with('sections.posetions.storage_element.parent_storage_media')->findOrFail($id);

        $storageMedias = collect();

        foreach ($warehouse->sections as $section) {
            foreach ($section->posetions as $position) {
                if ($position->storage_element && $position->storage_element->parent_storage_media) {
                    $storageMedias->push($position->storage_element->parent_storage_media);
                }
            }
        }


        $storageMedias = $storageMedias->unique('id')->values();


        return response()->json([
            'warehouse' => $warehouse->only('id', 'name', 'location'),
             
        ]);
    }

    public function showEmployees($id)
    {
        $warehouse = Warehouse::with('employees')->findOrFail($id);

        return response()->json([
            'warehouse' => $warehouse->name,

        ]);
    }

    public function showType($id)
    {
        $warehouse = Warehouse::with('type')->findOrFail($id);

        return response()->json([
            'warehouse' => $warehouse->name,
            'type' => $warehouse->type,
        ]);
    }

    public function showSections($id)
    {
        $warehouse = Warehouse::with('sections')->findOrFail($id);

        return response()->json([
            'warehouse' => $warehouse->name,

        ]);
    }

    public function show_distrebution_centers_of_product($warehouse_id,$product_id){
        
       $warehous=Warehouse::find($warehouse_id);
       if(!$warehous){
        return response()->json(["msg"=>"warehouse not found"],404);

       }
       $product=Product::find($product_id);
       if(!$product){
               return response()->json(["msg"=>"product not found"],404);

       }
       $distribution_centers_of_product=[];
       
      $distributionCenters=$warehous->distribution_centers;
      if($distributionCenters->isEmpty())
      {
        return response()->json(["msg"=>"the warehouse dont have distribution centers"],404);
      }
      $i=0;
      foreach($distributionCenters as $distC){
         $has_a_section_of_product = $distC->sections()->where("product_id", $product_id)->get();
       
         $distC=$this->calcute_areas_on_place_for_a_specific_product($distC,$product_id);
         $distribution_centers_of_product[$i]=$distC;

         $i++;
      }
      if(empty($distribution_centers_of_product)){
              return response()->json(["msg"=>"the warehouse dont have distribution centers for this product"],202);
    }
      
      return response()->json(["msg"=>"here the disribution centers ",
      "distribution_centers"=> $distribution_centers_of_product],202);
    }


    public function show_distribution_centers_of_storage_media_in_warehouse($warehouse_id,$storage_media_id){
   $storage_media=Storage_media::find($storage_media_id);
   if(!$storage_media){
    return response()->json(["msg"=>"Storage_media not not found"],404);
   }
   $warehous=Warehouse::find($warehouse_id);
   if(!$warehous){
    return response()->json(["msg"=>"warehouse not found"],404);
   }
   $product=$storage_media->product;
    
  return $this->show_distrebution_centers_of_product($warehous->id,$product->id);
   
 }
}
