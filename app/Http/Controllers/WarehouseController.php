<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Product;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use App\Models\Storage_media;
use App\Traits\AlgorithmsTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\ValidationException;

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

    public function show_distrebution_centers_of_product($warehouse_id, $product_id)
    {

        $warehous = Warehouse::find($warehouse_id);
        if (!$warehous) {
            return response()->json(["msg" => "warehouse not found"], 404);
        }
        $product = Product::find($product_id);
        if (!$product) {
            return response()->json(["msg" => "product not found"], 404);
        }
        $distribution_centers_of_product = [];

        $distributionCenters = $warehous->distribution_centers;
        if ($distributionCenters->isEmpty()) {
            return response()->json(["msg" => "the warehouse dont have distribution centers"], 404);
        }
        $i = 0;
        foreach ($distributionCenters as $distC) {
            $has_a_section_of_product = $distC->sections()->where("product_id", $product_id)->get();

            $distC = $this->calcute_areas_on_place_for_a_specific_product($distC, $product_id);
            $distribution_centers_of_product[$i] = $distC;

            $i++;
        }
        if (empty($distribution_centers_of_product)) {
            return response()->json(["msg" => "the warehouse dont have distribution centers for this product"], 202);
        }

        return response()->json([
            "msg" => "here the disribution centers ",
            "distribution_centers" => $distribution_centers_of_product
        ], 202);
    }


    public function show_distribution_centers_of_storage_media_in_warehouse($warehouse_id, $storage_media_id)
    {
        $storage_media = Storage_media::find($storage_media_id);
        if (!$storage_media) {
            return response()->json(["msg" => "Storage_media not not found"], 404);
        }
        $warehous = Warehouse::find($warehouse_id);
        if (!$warehous) {
            return response()->json(["msg" => "warehouse not found"], 404);
        }
        $product = $storage_media->product;

        return $this->show_distrebution_centers_of_product($warehous->id, $product->id);
    }
    public function send_products_from_To(Request $request)
    {
        try {
            try {

                $validated_values = $request->validate([
                    "source_type" => "required|in:Warehouse,DistributionCenter",
                    "destination_type" => "required|in:Warehouse,DistributionCenter",
                    "source_id" => "required",
                    "destination_id" => "required",
                    "product_id" => "required|numeric",
                    "quantity" => "required|integer"
                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            $employe = $request->employe;
            $modelSource = "App\\Models\\" . $validated_values["source_type"];
            $modelDestination = "App\\Models\\" . $validated_values["destination_type"];
            $source = $modelSource::find($validated_values["source_id"]);
            $destination = $modelDestination::find($validated_values["destination_id"]);
            if (!$source) {
                return response()->json(["msg" => "source not found"], 404);
            }
            if (!$destination) {
                return response()->json(["msg" => "destination not found"], 404);
            }
            $product = Product::find($validated_values["product_id"]);
            if (!$product) {
                return response()->json(["msg" => "product not found"], 404);
            }
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $source);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "un authorized in this source"], 401);
                }
                $authorized_in_place = $this->check_if_authorized_in_place($employe, $destination);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "un authorized in this destination"], 401);
                }
            }
            $product = Product::find($validated_values["product_id"]);
            if (!$product) {
                return response()->json(["msg" => "product not found"], 404);
            }
            $has_source_this_product = $source->sections()->where("product_id", $product->id)->exists();
            if (!$has_source_this_product) {
                return response()->json(["msg" => "the source cannot send this product"], 404);
            }
            $has_dest_this_product = $destination->sections()->where("product_id", $product->id)->exists();
            if (!$has_dest_this_product) {
                return response()->json(["msg" => "the destination cannot resieve this product"], 404);
            }
            
             $source=$this->calcute_areas_on_place_for_a_specific_product($source, $product->id);
             if($validated_values["quantity"]>$source->actual_load_product){
                return response()->json(["msg" => "the source dont have enough of this product"], 404);
             }

             $destination=$this->calcute_areas_on_place_for_a_specific_product($destination, $product->id);
              
             if($validated_values["quantity"]>$destination->avilable_area_product){
                  return response()->json(["msg" => "the destination dont have enough space for this product"], 404);
             }
                $sections_in_source = $source->sections()->where("product_id", $product->id)->get();
                 $contaners=collect();
                foreach( $sections_in_source as $section ){
                   foreach($section->storage_elements as $storage_element){
                     
                   }
                }




            return response()->json(["msg" => "done"], 202);
        } catch (Exception $e) {
            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->getMessage(),
            ], 422);
        }
    }
}
