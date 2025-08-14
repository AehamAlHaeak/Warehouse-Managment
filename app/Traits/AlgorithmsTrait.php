<?php

namespace App\Traits;

use App\Models\type;
use App\Models\User;
use App\Models\Garage;
use App\Models\Employe;
use App\Models\Product;
use App\Models\Section;
use App\Models\Vehicle;
use App\Models\Favorite;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Models\Bill_Detail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Storage_media;
use App\Models\Specialization;
use Illuminate\Support\Carbon;
use App\Models\reserved_details;
use App\Models\Supplier_Product;
use App\Models\Transfer_Vehicle;
use App\Models\container_movments;
use App\Models\DistributionCenter;
use App\Models\Positions_on_sto_m;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Import_op_container;
use Illuminate\Support\Facades\Log;
use App\Models\Imp_continer_product;
use App\Models\Posetions_on_section;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\Distribution_center_Product;
use Illuminate\Notifications\DatabaseNotification;
use App\Events\Send_Notification;
trait AlgorithmsTrait
{
    public function create_token($object)
    {
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


    public function valedate_and_build(Request $request)
    {

        $validated_products = null;
        $validated_vehicles = null;

        $errors_products = null;
        $errors_vehicles = null;



        foreach ($request->input('products', []) as $index => $product) {

            $validator = Validator::make($product, [
                "product_id" => "required|integer",
                "expiration" => "required|date",
                "producted_in" => "required|date",
                "unit" => "required",
                "price_unit" => "required",
                "quantity" => "required"


            ]);

            if ($validator->fails()) {
                $errors_products[$index] = [
                    'at_product' => $product,
                    'errors' => $validator->errors()->all()

                ];
            } else {

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

            $validator = Validator::make($vehicle, [
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
            } else {
                $validated_vehicles[] = $vehicle;
            }
        }


        $Data = [];
        $Data["products"] = $validated_products;
        $Data["vehicles"] = $validated_vehicles;
        $Data["errors_products"] = $errors_products;
        $Data["errors_vehicles"] = $errors_vehicles;

        return $Data;
    }



    public function calculate($latitude1, $longitude1, $latitude2, $longitude2)
    {
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
            'metrics' => ['distance', 'duration'], //the time is by seconds then we will change it

        ]);
        //$data['distances'][0][1] the destance by meters
        $data = $response->json();

        if (isset($data)) {
            return $data; // distance by metirs
        } else {
            throw new \Exception('Failed to fetch distance: ' . json_encode($data));
        }
    }



























    public function calculate_the_nearest_location($model, $latitude, $longitude)
    {

        $items = $model::all();

        $distances = [];
        foreach ($items as $item) {
            $data = $this->calculate($item->latitude, $item->longitude, $latitude, $longitude);
            
            $item->distance = $data['distances'][0][1]; //0 1 are from 0 to one form sourece to dest
            $item->duration = $data["duration"][0][1];
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
    public function sort_the_near_by_location($items, $latitude, $longitude)
    {




        foreach ($items as $item) {
            $data = $this->calculate($item->latitude, $item->longitude, $latitude, $longitude);

            $item["distance"] = $data['distances'][0][1]; //0 1 are from 0 to one form sourece to dest
            $item->duration = $data['durations'][0][1] / 3600;
        }


        $sorted = $items->sortBy('distance')->values();


        return $sorted;
    }

    public function create_postions($model, $object, $foreignId_name)
    {

        for ($floor = 0; $floor < $object->num_floors; $floor++) {

            for ($class = 0; $class < $object->num_classes; $class++) {

                for ($positions_on_class = 0; $positions_on_class < $object->num_positions_on_class; $positions_on_class++) {

                    $model::create([
                        $foreignId_name => $object["id"],
                        "floor" => $floor,
                        "class" => $class,
                        "positions_on_class" => $positions_on_class
                    ]);
                }
            }
        }
    }




    public function calculate_areas($section)
    {
        $avilable_area_product = 0;

        $product = $section->product;
        $continer = $product->container;

        $storage_media = $product->storage_media;
        $storage_elements = $section->storage_elements;
        unset($section["product"]);
        unset($section["continer"]);
        unset($section["storage_media"]);
        unset($section["storage_elements"]);

        $actual_storage_elements_count = $storage_elements->count();
        $auto_rejected_load = 0;
        $section->max_storage_media_area = $section->num_floors * $section->num_classes * $section->num_positions_on_class;
        $section->avilable_storage_media_area = $section->max_storage_media_area - $actual_storage_elements_count;
        $section->max_capacity_products = $actual_storage_elements_count * $storage_media->num_floors * $storage_media->num_classes * $storage_media->num_positions_on_class * $continer->capacity;
        $selled_load = 0;
        $reserved_load = 0;
        $rejected_load = 0;
        $actual_load_product = 0;
        foreach ($storage_elements as $storage_element) {


            $posetions = $storage_element->posetions;
            foreach ($posetions as $posetion) {
                if ($posetion->imp_op_contin_id == null) {

                    $avilable_area_product += $continer->capacity;
                } else {
                    $container = $posetion->container;

                    $inventory = $this->inventory_on_continer($container);
                    $selled_load += $inventory["selled_load"];

                    $rejected_load += $inventory["rejected_load"];
                    $reserved_load += $inventory["reserved_load"];
                    /*'accepted', 'rejected', 'sold','auto_reject' */
                    if ($container->status == "accepted") {
                        $actual_load_product += $inventory["remine_load"];
                    } elseif ($container->status == "auto_reject") {
                        $auto_rejected_load += $inventory["remine_load"];
                    }
                }
            }
        }
        $section->selled_load = $selled_load;
        $section->rejected_load = $rejected_load;
        $section->reserved_load = $reserved_load;
        $section->actual_load_product = $actual_load_product;
        $section->avilable_area_product = $avilable_area_product;
        $section->auto_rejected_load = $auto_rejected_load;
        return $section;
    }

    public function calculate_ready_vehiscles($object, $product)
    {
        $garages = $object->garages;
        unset($object["garages"]);
        $activ_vehicles_count = 0;
        $avilable_vehicles_count = 0;
        $can_to_trans_load = 0;
        $continer = $product->container;

        foreach ($garages as $garage) {
            $avilable_vehicles_on_garage = $garage->vehicles()->where("product_id", $product->id)
                ->whereNull("transfer_id")->where("driver_id", "!=", Null)->get();
            $activ_vehicles_count += $garage->vehicles()->where("product_id", $product->id)
                ->where("transfer_id", "!=", null)->count();
            $avilable_vehicles_count += $avilable_vehicles_on_garage->count();
            $can_to_trans_load += $avilable_vehicles_on_garage->sum("capacity") * $continer->capacity;
        }

        $object->can_to_translate_load = $can_to_trans_load;
        $object->avilable_vehicles_count = $avilable_vehicles_count;
        $object->activ_vehicles_count = $activ_vehicles_count;
        return $object;
    }

    public function calculate_areas_of_vehicles($object)
    {
        $garage_of_type = $object->garages;
        unset($object["garages"]);
        $avilable_area_big = 0;
        $max_capacity_big = 0;
        $full_area_in_palce_big = 0;
        $avilable_area_medium = 0;
        $max_capacity_medium = 0;
        $full_area_in_palce_medium = 0;
        foreach ($garage_of_type as $garage) {
            $fullarea = $garage->vehicles->count();


            if ($garage->size_of_vehicle == "big") {
                $max_capacity_big += $garage->max_capacity;
                $full_area_in_palce_big += $fullarea;

                $avilable_area_big += $garage->max_capacity - $fullarea;
            } else {
                $max_capacity_medium += $garage->max_capacity;
                $full_area_in_palce_medium += $fullarea;
                $avilable_area_medium += $garage->max_capacity - $fullarea;
            }
        }
        $object->avilable_area_vehicles_big = $avilable_area_big;
        $object->max_capacity_vehicles_big = $max_capacity_big;
        $object->full_area_vehicles_big = $full_area_in_palce_big;
        $object->avilable_area_vehicles_medium = $avilable_area_medium;
        $object->max_capacity_vehicles_medium = $max_capacity_medium;
        $object->full_area_vehicles_medium = $full_area_in_palce_medium;
        return  $object;
    }

    public function calcute_areas_on_place_for_a_specific_product($object, $product_id)
    {
        $avilable_area_product = 0;
        $max_capacity_product = 0;
        $auto_rejected_load = 0;
        $selled_load = 0;
        $reserved_load = 0;
        $rejected_load = 0;
        $actual_load_product = 0;
        $avilable_storage_media_area = 0;
        $max_storage_media_area = 0;
        $sections_of_the_product_in_object = $object->sections()
            ->where('product_id', $product_id)
            ->select([
                'id',
                'name',
                'product_id',
                'num_floors',
                'num_classes',
                'num_positions_on_class',
                'average',
                'variance'
            ])
            ->get();

        foreach ($sections_of_the_product_in_object as $section) {


            $section = $this->calculate_areas($section);
            $avilable_area_product += $section->avilable_area_product;
            $max_capacity_product += $section->max_capacity_products;
            $auto_rejected_load += $section->auto_rejected_load;
            $avilable_storage_media_area +=  $section->avilable_storage_media_area;
            $max_storage_media_area +=  $section->max_storage_media_area;
            $selled_load += $section->selled_load;
            $reserved_load += $section->reserved_load;
            $rejected_load += $section->rejected_load;
            $actual_load_product += $section->actual_load_product;
        }

        $object->max_capacity_product = $max_capacity_product;
        $object->avilable_area_product = $avilable_area_product;

        $object->avilable_storage_media_area = $avilable_storage_media_area;

        $object->max_storage_media_area = $max_storage_media_area;
        $object->selled_load = $selled_load;
        $object->reserved_load = $reserved_load;
        $object->rejected_load = $rejected_load;
        $object->actual_load_product = $actual_load_product;
        $object->auto_rejected_load = $auto_rejected_load;
        return $object;
    }
    public function check_if_authorized_in_place($employe, $place)
    {

        $specializatiun = $employe->specialization;


        if ($specializatiun->name == "distribution_center_admin" || $specializatiun->name == "QA") {
            if (!$employe->workable->is($place)) {
                return false;
            }
        } elseif ($specializatiun->name == "warehouse_admin") {
            if (get_class($place) == Warehouse::class) {
                if (!$employe->workable->is($place)) {
                    return false;
                }
            } elseif (get_class($place) == DistributionCenter::class) {
                $working_place = $employe->workable;
                if (!$place->warehouse->is($working_place)) {
                    return false;
                }
            }
        }
        return true;
    }
    public function calculate_load_logs($load)
    {
        $logs = [];
        $logs["selled_load"] = $load->sell_load->sum('load');
        $logs["reserved_load"] = $load->reserved_load->sum('reserved_load');
        $logs["rejected_load"] = $load->rejected_load->sum("rejected_load");
        $logs["remine_load"] = $load->load - $logs["selled_load"] - $logs["reserved_load"] - $logs["rejected_load"];

        return $logs;
    }
    public function move_reserved_from_to($another_contents, $load)
    {

        foreach ($another_contents as $another_load) {
            $logs = $this->calculate_load_logs($another_load);

            $moved_reserved = min($logs["remine_load"], $load->reserved_load);
            reserved_details::create([
                "transfer_details_id" => $load->transfer_details_id,
                "reserved_load" => $moved_reserved,
                "imp_cont_prod_id" => $another_load->id
            ]);

            $load->reserved_load = $load->reserved_load - $moved_reserved;
            $load->save();
            if ($load->reserved_load == 0) {
                $load->delete($load->id);
            }
        }
        return $load;
    }
    public function inventory_on_continer($container)
    {
        $loads = $container->loads;
        unset($container["loads"]);

        $inventory = ["selled_load" => 0, "reserved_load" => 0, "rejected_load" => 0, "remine_load" => 0];
        foreach ($loads as $load) {
            $load_logs = $this->calculate_load_logs($load);

            $inventory["selled_load"] += $load_logs["selled_load"];

            $inventory["reserved_load"] += $load_logs["reserved_load"];
            $inventory["rejected_load"] += $load_logs["rejected_load"];
            $inventory["remine_load"] += $load_logs["remine_load"];
        }
        return $inventory;
    }
    public function inventry_product_in_place($product, $place)
    {

        $actual_load = 0;
        $max_load = 0;
        $auto_rejected_load = 0;
        $avilable_load = 0;
        $average = 0;
        $deviation = 0;
        $salled_load = 0;
        $rejected_load = 0;
        $reserved_load = 0;
        $sections = $place->sections()->where("product_id", $product->id)->get();
        unset($place->sections);

        foreach ($sections as $section) {
            $section = $this->calculate_areas($section);

            $actual_load +=  $section->actual_load_product;
            $max_load +=  $section->max_capacity_products;
            $avilable_load += $section->avilable_area_product;
            $auto_rejected_load += $section->auto_rejected_load;
            $date = Carbon::parse($section->created_at);

            $now = Carbon::now();
            $weeksPassed = $date->diffInWeeks($now);
            if ($weeksPassed != 0) {

                $deviation += sqrt($product->import_cycle / 7) * sqrt($section->variance / $weeksPassed);
            }

            $average += ($product->import_cycle / 7) * $section->average;


            $salled_load += $section->selled_load;
            $rejected_load += $section->rejected_load;
            $reserved_load += $section->reserved_load;
        }

        $product->actual_load = $actual_load;
        $product->max_load = $max_load;
        $product->avilable_load = $avilable_load;
        $product->average = $average;
        $product->deviation = $deviation;
        $product->salled_load = $salled_load;
        $product->rejected_load = $rejected_load;
        $product->reserved_load = $reserved_load;
        $product->auto_rejected_load = $auto_rejected_load;
        unset($product["sections"]);
        return $product;
    }






    public function move_reserved_from_container($storage_elements, $container, $un_wanted_ids = [])
    {
        Log::info("move_reserved_from_container called for container ID: " . $container->id);
        Log::info("Initial un_wanted_ids: " . json_encode($un_wanted_ids));

        $new_continers = [];
        $un_wanted_ids = array_unique(array_merge($un_wanted_ids, array_keys($new_continers)));

        $loads = $container->loads;
        Log::info("Number of loads in container " . $container->id . ": " . $loads->count());

        foreach ($loads as $load) {
            Log::info("  Processing load ID: " . $load->id);
            $res_loads = $load->reserved_load;
            Log::info("    Number of reserved loads for load ID " . $load->id . ": " . $res_loads->count());

            foreach ($res_loads as $res_load) {
                Log::info("      Processing reserved_load ID: " . $res_load->id . ", Current Quantity: " . $res_load->reserved_load);
                $targetTransferId = $res_load->transfer_details_id;
                if ($res_load->reserved_load <= 0) {
                    Log::info("        Reserved load ID " . $res_load->id . " is already 0 or less, skipping.");
                    continue;
                }

                $current_res_load_moved_completely = false;

                foreach ($storage_elements as $storage_element) {
                    Log::info("        Checking storage_element ID: " . $storage_element->id);
                    $containers_in_storage_element = $storage_element->impo_container()
                        ->where(function ($query) use ($targetTransferId) {
                            $query

                                ->whereDoesntHave('loads.reserved_load')

                                ->orWhereHas('loads.reserved_load', function ($subquery) use ($targetTransferId) {
                                    $subquery->where('transfer_details_id', '=', $targetTransferId);
                                });
                        })
                        ->with('imp_op_product')
                        ->get()
                        ->sortBy(function ($container) {
                            return $container->imp_op_product
                                ->pluck('expiration')
                                ->filter()
                                ->min();
                        })
                        ->values();

                    Log::info("          Found " . $containers_in_storage_element->count() . " containers in storage_element " . $storage_element->id);

                    foreach ($containers_in_storage_element as $continer_item) {
                        Log::info("            Checking potential replacement container ID: " . $continer_item->id);

                        if (in_array($continer_item->id, $un_wanted_ids) || array_key_exists($continer_item->id, $new_continers)) {
                            Log::info("              Container ID " . $continer_item->id . " is in un_wanted_ids or already used, skipping.");
                            continue;
                        }

                        if ($res_load->reserved_load <= 0) {
                            Log::info("              Reserved load ID " . $res_load->id . " fulfilled, breaking 2 loops.");
                            $current_res_load_moved_completely = true;
                            $res_load->delete($res_load->id);
                            break 2;
                        }

                        $contents_in_continer = \App\Models\Imp_continer_product::where("imp_op_cont_id", $continer_item->id)->get();
                        Log::info("              Found " . $contents_in_continer->count() . " contents in container " . $continer_item->id);

                        foreach ($contents_in_continer as $another_load) {
                            Log::info("                Checking content ID: " . $another_load->id . " in container " . $continer_item->id);
                            if ($res_load->reserved_load <= 0) {
                                Log::info("                  Reserved load ID " . $res_load->id . " fulfilled, breaking 3 loops.");
                                $current_res_load_moved_completely = true;
                                break 3;
                            }

                            $logs = $this->calculate_load_logs($another_load);
                            Log::info("                  calculate_load_logs returned remine_load: " . $logs["remine_load"]);

                            $moved_reserved = min($logs["remine_load"], $res_load->reserved_load);

                            if ($moved_reserved > 0) {
                                Log::info("                  Moving " . $moved_reserved . " units to container " . $continer_item->id);
                                reserved_details::create([
                                    "transfer_details_id" => $res_load->transfer_details_id,
                                    "reserved_load" => $moved_reserved,
                                    "imp_cont_prod_id" => $another_load->id
                                ]);

                                $res_load->reserved_load -= $moved_reserved;
                                $res_load->save();
                                Log::info("                  Reserved load ID " . $res_load->id . " remaining: " . $res_load->reserved_load);

                                $new_continers[$continer_item->id] = $continer_item->id;
                                $un_wanted_ids[] = $continer_item->id;
                                $un_wanted_ids = array_unique($un_wanted_ids);
                                Log::info("                  Added container " . $continer_item->id . " to new_continers/un_wanted_ids. Current new_continers: " . json_encode(array_values($new_continers)));
                            } else {
                                Log::info("                  Moved 0 units. Either remine_load is 0 or reserved_load is 0.");
                            }
                        }
                    }
                }
                if ($res_load->reserved_load > 0 && !$current_res_load_moved_completely) {
                    Log::warning("Could not move all reserved load for res_load ID: " . $res_load->id . " Remaining: " . $res_load->reserved_load . ". No more suitable containers found.");
                }
            }
        }
        Log::info("Finished move_reserved_from_container for container ID: " . $container->id . ". Returning new_continers: " . json_encode(array_values($new_continers)));
        return $new_continers;
    }
    public function reserve_product_in_place($place, $transfer_detail, $product, $quantity, $choise = "complete")
    {
        if ($quantity <= 0) {
            return "no quantity";
        }

        $sections = $place->sections()->where("product_id", $product->id)->get();

        $targetTransferId = $transfer_detail->id;
        $continer_of_reserving = [];
        foreach ($sections as $section) {
            $storage_elements = $section->storage_elements;


            foreach ($storage_elements as $storage_element) {
                if ($quantity == 0) {
                    break;
                }
                $containers_in_storage_element = null;
                if ($choise == "complete") {

                    $containers_in_storage_element = $storage_element->impo_container()
                        ->where(function ($query) use ($targetTransferId) {
                            $query

                                ->whereDoesntHave('loads.reserved_load')

                                ->orWhereHas('loads.reserved_load', function ($subquery) use ($targetTransferId) {
                                    $subquery->where('transfer_details_id', '=', $targetTransferId);
                                });
                        })
                        ->with('imp_op_product')
                        ->get()
                        ->sortBy(function ($container) {
                            return $container->imp_op_product
                                ->pluck('expiration')
                                ->filter()
                                ->min();
                        })
                        ->values();
                } else {

                    $containers_in_storage_element = $storage_element->impo_container()->where("status", "accepted")
                        ->with('imp_op_product')
                        ->get()
                        ->sortBy(function ($container) {
                            return $container->imp_op_product
                                ->pluck('expiration')
                                ->filter()
                                ->min();
                        })
                        ->values();
                }

                while ($quantity > 0 && $containers_in_storage_element->isNotEmpty()) {

                    $continer = $containers_in_storage_element->splice(0, 1)->first();

                    $loads = $continer->loads;

                    foreach ($loads as $load) {
                        $log = $this->calculate_load_logs($load);
                        $reserved = min($log["remine_load"], $quantity);
                        if ($reserved > 0) {
                            reserved_details::create([
                                "transfer_details_id" => $transfer_detail->id,
                                "reserved_load" => $reserved,
                                "imp_cont_prod_id" => $load->id
                            ]);
                        }
                        $quantity -= $reserved;
                        $continer_of_reserving[$continer->id] = $continer->id;
                        if ($quantity == 0) {
                            break 4;
                        }
                    }
                    if ($quantity == 0) {
                        break 3;
                    }
                }
                if ($quantity == 0) {
                    break 2;
                }
            }
        }
        if (!empty($continer_of_reserving)) {
            return $continer_of_reserving;
        } else {

            return "no enogh quantity in  this place to reserve";
        }
    }


    public function divide_load($last_continer,$reserved_loads_to_detail)
    {
        $parent_continer = $last_continer->parent_continer;
        $new_continer = Import_op_container::create([
            "container_type_id" => $parent_continer->id,
            "import_operation_id" => $last_continer->import_operation_id
        ]);
        foreach ($reserved_loads_to_detail as $reserved) {
            $parent_load = $reserved->parent_load;
            $parent_load->load -= $reserved->reserved_load;
            $parent_load->save();
            
            $same_load = $new_continer->loads()->where("imp_op_product_id", $parent_load->imp_op_product_id)->first();
            if ($same_load) {
                $same_load->load += $reserved->reserved_load;
                $same_load->save();
            } else {
                $same_load = Imp_continer_product::create([
                    "imp_op_cont_id" => $new_continer->id,
                    "imp_op_product_id" => $parent_load->imp_op_product_id,
                    "load" => $reserved->reserved_load
                ]);
            }
            $reserved->imp_cont_prod_id = $same_load->id;
            $reserved->save();
        }
        $posetion=$last_continer->posetion_on_stom;
        container_movments::create([
                        "imp_op_cont_id" => $new_continer->id,
                        "prev_position_id" => $posetion->id,

                        "moved_why" => "take loads from another continer ,the previos continer_id is : {$last_continer->id}"
                    ]);
        return $new_continer;
    }
    public function reserved_sold_on_load($transfer_detail){
        $sell_reserve=[];
        $sell_reserve["sold"]=$transfer_detail->sell_loads->sum('sold_load');
        
        $sell_reserve["reserved"]=$transfer_detail->reserved_loads->sum("reserved_load");
        unset($transfer_detail->sell_loads,$transfer_detail->reserved_loads);

        return $sell_reserve; 

    }

    public function send_not($notification,$dest){
        $uuid = (string) Str::uuid();
      $notify = DatabaseNotification::create([
                    'id' => $uuid,
                    'type' => get_class($notification),
                    'notifiable_type' => get_class($dest),
                    'notifiable_id' => $dest->id,
                    'data' => $notification->toArray($dest),
                    'read_at' => null,
                ]);
                $notification->id = $notify->id;
                event(new Send_Notification($dest, $notification));
    }
}
