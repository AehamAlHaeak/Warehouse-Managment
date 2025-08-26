<?php

namespace App\Traits;

use App\Models\type;
use App\Models\User;
use App\Models\Garage;
use App\Models\Product;
use App\Models\Vehicle;
use App\Models\Transfer;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use App\Models\Storage_media;
use App\Models\Containers_type;
use App\Models\Transfer_detail;
use App\Traits\AlgorithmsTrait;
use App\Models\Import_operation;
use App\Models\Continer_transfer;
use App\Models\Import_op_product;
use App\Models\container_movments;
use App\Models\DistributionCenter;
use App\Models\Positions_on_sto_m;
use App\Models\Import_op_container;
use App\Models\Posetions_on_section;
use App\Notifications\Take_new_task;

trait TransferTraitAeh
{

    use AlgorithmsTrait;
    public function load_vehicles($load_object_id, $without_load_id, $transfer_details, $status_load, $status_wo_load)
    {

        foreach ($transfer_details as $block) {

            $transfer_detail_l = Transfer_detail::create([
                "vehicle_id" => $block["vehicle_id"],
                "transfer_id" => $load_object_id,
                "status" => $status_load

            ]);
            Transfer_detail::create([
                "vehicle_id" => $block["vehicle_id"],
                "transfer_id" => $without_load_id,
                "status" => $status_wo_load
            ]);
            $vehicle = Vehicle::find($block["vehicle_id"]);
            $driver=$vehicle->driver;
          
            if ($status_wo_load == "under_work") {
                $vehicle->update([
                    "transfer_id" => $without_load_id,
                ]);
                
                $task=Transfer::find($without_load_id);
                $notification = new Take_new_task($task);
                $this->send_not($notification,$driver);
            } else {
                $vehicle->update([
                    "transfer_id" => $load_object_id,
                ]);
                $task=Transfer::find($load_object_id);
                $notification = new Take_new_task($task);
                $this->send_not($notification,$driver);
            }
            
           

            foreach ($block["container_ids"] as $continer_id) {
                $continer = Import_op_container::find($continer_id);
                $posetion = $continer->posetion_on_stom;
                if ($posetion) {
                    container_movments::create([
                        "imp_op_cont_id" => $continer_id,
                        "prev_position_id" => $posetion->id,

                        "moved_why" => "loaded"
                    ]);

                    $posetion->update([
                        "imp_op_contin_id" => null
                    ]);
                }
                Continer_transfer::create([
                    "transfer_detail_id" =>  $transfer_detail_l->id,
                    "imp_op_contin_id" => $continer_id
                ]);
            }
        }
        return "loading succesfully!";
    }


    public function unload($transfer_detail, $destination)
    {

        $continers = $transfer_detail->continers()
            ->whereDoesntHave('posetion_on_stom')
            ->whereNotIn('status', ['rejected', 'auto_reject'])
            ->get();
        if($continers->isEmpty()){
             return "already passed or dont have continers without posetions";
            }
        $parent_cont = $continers->first()->parent_continer;
        $product =  $parent_cont->product;

        $continers = $continers->pluck('id');

        $destination = $this->calcute_areas_on_place_for_a_specific_product($destination, $product->id);
        if ($destination->avilable_area_product < $continers->count()) {
            throw new \Exception("the destination is full");
        }

        $avilable_sections = $destination->sections()->where("product_id", $product->id)->get();
        while ($continers->isNotEmpty() && $destination->avilable_area_product > 0) {
            foreach ($avilable_sections as $section) {
                $storage_elaments = $section->storage_elements()->where("readiness", ">", 0.8)->get();

                foreach ($storage_elaments as $storage_element) {
                    try {
                        $avilablie_posetions = $storage_element->posetions()->whereNull("imp_op_contin_id")->orderBy("id", "desc")->get();
                    } catch (\Exception $e) {
                        return $e->getMessage();
                    }

                    foreach ($avilablie_posetions as $position) {

                        if ($continers->isEmpty()) {
                            break 3;
                        }


                        $continer_id = $continers->splice(0, 1)->first();
                        if ($position->imp_op_contin_id !== null) {

                            continue;
                        }
                        $position->imp_op_contin_id = $continer_id;
                        $position->save();
                    }
                }
            }
            $destination = $this->calcute_areas_on_place_for_a_specific_product($destination, $product->id);
        }
        if ($continers->isNotEmpty()) {
            throw new \Exception("the destination is full there are another load unloaded here");
        }


        return  $continers;
    }

    public function resive_transfers($source, $destination, $continers = null)
    {

        if ($continers->isEmpty()) {
            return "No containers to transfer";
        }
        $parint_continer = $continers->first()->parent_continer;

        $continer_product = $parint_continer->product;

        $avilable_vehicles_big = null;
        $avilable_vehicles_medium = null;
        if ($source instanceof \App\Models\Import_operation) {

            $big_garages = $destination->garages->where("size_of_vehicle", "big")->pluck("id");

            $medium_garages = $destination->garages->where("size_of_vehicle", "medium")->pluck("id");
        } else {

            $medium_garages = $source->garages->where("size_of_vehicle", "medium")->pluck("id");

            $big_garages = $source->garages->where("size_of_vehicle", "big")->pluck("id");
        }
        $avilable_vehicles_big = Vehicle::whereIn("garage_id", $big_garages)
            ->whereNull("transfer_id")
            ->where("product_id", $continer_product->id)
            ->where("driver_id", "!=", null)
            ->where("readiness", ">", 0.8)
            ->orderBy('capacity', 'desc')
            ->get();

        $avilable_vehicles_medium = Vehicle::whereIn("garage_id", $medium_garages)
            ->whereNull("transfer_id")
            ->where("product_id", $continer_product->id)
            ->where("driver_id", "!=", null)
            ->where("readiness", ">", 0.8)
            ->orderBy('capacity', 'desc')
            ->get();


        $total_medium_capacity = $avilable_vehicles_medium->sum('capacity');

        $i = 0;
        $transfer_details = [];

        if ($avilable_vehicles_big != null || $avilable_vehicles_medium) {

            while ($avilable_vehicles_big->isNotEmpty() && $continers->isNotEmpty()) {
                $vehicle = $avilable_vehicles_big->shift();
                if ($continers->count() >= $vehicle->capacity / 2) {
                    $load_count = min($continers->count(), $vehicle->capacity);
                    $loaded_continers = $continers->splice(0, $load_count);
                    $transfer_details[$i] = [
                        "vehicle_id" => $vehicle->id,
                        "container_ids" => $loaded_continers->pluck('id')->toArray()
                    ];
                } else {
                    if ($total_medium_capacity <= $continers->count()) {
                        $load_count = min($continers->count(), $vehicle->capacity);
                        $loaded_continers = $continers->splice(0, $load_count);
                        $transfer_details[$i] = [
                            "vehicle_id" => $vehicle->id,
                            "container_ids" => $loaded_continers->pluck('id')->toArray()
                        ];
                    }
                }
                $i++;
            }

            while ($avilable_vehicles_medium->isNotEmpty() && $continers->isNotEmpty()) {

                $vehicle = $avilable_vehicles_medium->shift();

                $load_count = min($continers->count(), $vehicle->capacity);
                $loaded_continers = $continers->splice(0, $load_count);
                $transfer_details[$i] = [
                    "vehicle_id" => $vehicle->id,
                    "container_ids" => $loaded_continers->pluck('id')->toArray()
                ];
                $i++;
            }
        }
        if ($continers->isEmpty()) {

            $parent_transfer = Transfer::create([
                "sourceable_type" => get_class($source), //imp_op // war
                "sourceable_id" => $source->id,
                "destinationable_type" => get_class($destination), //warehouse with load user or dist
                "date_of_resiving" => now(),
                "destinationable_id" => $destination->id,

            ]);
            $related_transfer = Transfer::create([
                "sourceable_type" => get_class($destination), //warehouse user or dest 
                "sourceable_id" => $destination->id,
                "destinationable_type" => get_class($source), //imp_op without load  warehouse 
                "destinationable_id" => $source->id,
            ]);


            $related_transfer->parent_trans = $parent_transfer->id;
            $related_transfer->save();
            $parent_transfer->related_trans = $related_transfer->id;
            $parent_transfer->save();

            if ($source instanceof \App\Models\Import_operation) {
                $parent_transfer->update(["date_of_resiving" => null]);
                $related_transfer->update(["date_of_resiving" => now()]);
                $this->load_vehicles($parent_transfer->id, $related_transfer->id, $transfer_details, "wait", "under_work");
            } else {
                $this->load_vehicles($parent_transfer->id, $related_transfer->id, $transfer_details, "under_work", "wait");
            }


            return $transfer_details;
        }

        return "the vehicles is not enough for the load";
    }
}
