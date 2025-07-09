<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Product;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use App\Models\Storage_media;
use Illuminate\Support\Carbon;
use App\Traits\AlgorithmsTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\ValidationException;
use App\Models\Import_op_container;
use App\Traits\TransferTraitAeh;

class WarehouseController extends Controller
{
    use AlgorithmsTrait;
    use TransferTraitAeh;
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

    public function show_distrebution_centers_of_product(Request $request,$warehouse_id, $product_id)
    {
        
        $warehous = Warehouse::find($warehouse_id);
        if (!$warehous) {
            return response()->json(["msg" => "warehouse not found"], 404);
        }
        $employe = $request->employe;
        if ($employe->specialization->name != "super_admin") {

            $authorized_in_place = $this->check_if_authorized_in_place($employe, $warehous);
            if (!$authorized_in_place) {
                return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
            }
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
            $has_a_section_of_product = $distC->sections()->where("product_id", $product_id)->exists();
             if($has_a_section_of_product){
            $distC = $this->calcute_areas_on_place_for_a_specific_product($distC, $product_id);
            $distribution_centers_of_product[$i] = $distC;
             }
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


    public function show_distribution_centers_of_storage_media_in_warehouse(Request $request,$warehouse_id, $storage_media_id)
    {
        $storage_media = Storage_media::find($storage_media_id);
        if (!$storage_media) {
            return response()->json(["msg" => "Storage_media not not found"], 404);
        }
        $warehous = Warehouse::find($warehouse_id);
        if (!$warehous) {
            return response()->json(["msg" => "warehouse not found"], 404);
        }
         $employe = $request->employe;
        if ($employe->specialization->name != "super_admin") {

            $authorized_in_place = $this->check_if_authorized_in_place($employe, $warehous);
            if (!$authorized_in_place) {
                return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
            }
        }


        $product = $storage_media->product;

        return $this->show_distrebution_centers_of_product($request,$warehous->id, $product->id);
    }
    public function send_products_from_To(Request $request)
    {
        try {
            try {
                $validated_values = $request->validate([
                    "source_type" => "required|in:Warehouse,DistributionCenter",    
                    "source_id" => "required",
                    "destination_type" => "required|in:Warehouse,DistributionCenter",
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

            $source = $this->calcute_areas_on_place_for_a_specific_product($source, $product->id);

            if ($validated_values["quantity"] > $source->actual_load_product) {
                return response()->json(["msg" => "the source dont have enough of this product"], 404);
            }

            $inventory_of_incoming = 0;
            $product_continer = $product->container;
            $transfers = $destination->resived_transfers()->whereNull("date_of_finishing")->get();
            foreach ($transfers as $transfer) {

                foreach ($transfer->transfer_details as $detail) {
                    $containers_count = $detail->continers()->where("container_type_id", $product_continer->id)->where("status", "accepted")->whereDoesntHave("posetion_on_stom")->count();

                    $inventory_of_incoming += $containers_count * $product_continer->capacity;
                }
            }
            $destination = $this->calcute_areas_on_place_for_a_specific_product($destination, $product->id);

            if ($validated_values["quantity"] > $destination->avilable_area_product) {
                return response()->json(["msg" => "the destination dont have enough space for this product"], 404);
            }
            $may_be_a_load_in_des = $inventory_of_incoming + $validated_values["quantity"];
            if ($may_be_a_load_in_des > $destination->avilable_area_product) {
                return response()->json([
                    "msg" => "The total of containers already incoming and the new quantity exceeds the available space in the destination."
                ], 409);
            }

            $sections_in_source = $source->sections()->where("product_id", $product->id)->get();
            $all_containers = collect();
            $seven_days_from_now = Carbon::now()->addDays(7);
            foreach ($sections_in_source as $section) {
                if ($validated_values["quantity"] <= 0) {
                    break;
                }
                $section_containers = collect();
                foreach ($section->storage_elements as $storage_element) {


                    $containers = $storage_element->continers()->whereDoesntHave('loads.reserved_load')
                        ->whereDoesntHave('loads.sell_load')
                        ->whereHas('loads', function ($query) use ($seven_days_from_now) {

                            $query->whereHas('impo_op_product', function ($q) use ($seven_days_from_now) {
                                $q->where('expiration', '<=', $seven_days_from_now);
                            });
                        })
                        ->with(['loads.impo_op_product' => function ($query) use ($seven_days_from_now) {

                            $query->where('expiration', '<=', $seven_days_from_now)
                                ->orderBy('expiration', 'asc');
                        }])
                        ->get();
                    $section_containers = $section_containers->merge($containers);
                }
                $section_containers = $section_containers->sortByDesc(function ($container) {
                    $inventory = $this->inventory_on_continer($container);
                    $container->remine_load = $inventory['remine_load'];
                    return $inventory['remine_load'];
                });
                foreach ($section_containers as $container) {


                    $all_containers->push($container);
                    $validated_values["quantity"] -= $container->remine_load;
                    if ($validated_values["quantity"] <= 0) {
                        break 2;
                    }
                }
            }
            if ($validated_values["quantity"] > 0) {
                return response()->json(["msg" => "you dont have enough containers"], 404);
            }
            $transfer_details = $this->resive_transfers($source, $destination, $all_containers);
            if ($transfer_details == "the vehicles is not enough for the load") {
                return response()->json(["msg" => $transfer_details], 400);
            } elseif ($transfer_details == "No containers to transfer") {
                return response()->json(["msg" => $transfer_details], 400);
            }

            return response()->json(["msg" => $transfer_details], 202);
        } catch (Exception $e) {
            return response()->json([

                'errors' => $e->getMessage(),
            ], 422);
        }
    }
    public function show_distrebution_centers_of_warehouse(Request $request, $warehouse_id)
    {
        try {
            $employe = $request->employe;
            $warehouse = warehouse::find($warehouse_id);
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe,      $warehouse);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "un authorized in this source"], 401);
                }
            }
            $distribution_centers = $warehouse->distribution_centers()->get([
                'id',
                'name',
                'location',
                'latitude',
                'longitude',

                'num_sections',
                'type_id'
            ]);


            if ($distribution_centers->isEmpty()) {
                return response()->json(["msg" => "distribution_centers not found"], 404);
            }
            return response()->json(["msg" => "here the centers", "distribuction_centers" => $distribution_centers], 202);
        } catch (Exception $e) {
            return response()->json([

                'errors' => $e->getMessage(),
            ], 422);
        }
    }
}
