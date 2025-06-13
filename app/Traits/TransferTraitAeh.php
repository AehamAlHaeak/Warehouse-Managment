<?php

namespace App\Traits;

use App\Models\Vehicle;
use App\Models\Containers_type;
use App\Models\DistributionCenter;
use App\Models\Import_operation;
use App\Models\Product;
use App\Models\Storage_media;
use App\Models\Garage;
use App\Models\User;
use App\Models\Posetions_on_section;
use App\Models\Positions_on_sto_m;
use App\Models\Import_op_container;
use App\Models\Import_op_product;
use App\Models\type;
use App\Models\Transfer;
use App\Models\Transfer_detail;
use App\Models\Warehouse;
use App\Models\Continer_transfer;

trait TransferTraitAeh
{


    public function load_vehicles($load_object_id, $without_load_id, $transfer_details, $status_load, $status_wo_load)
    {
        foreach ($transfer_details as $block) {

            $transfer_detail = Transfer_detail::create([
                "vehicle_id" => $block["vehicle_id"],
                "transfer_id" => $load_object_id,
                "status" => $status_load

            ]);
            Transfer_detail::create([
                "vehicle_id" => $block["vehicle_id"],
                "transfer_id" => $without_load_id,
                "status" => $status_wo_load
            ]);
            foreach ($block["container_ids"] as $continer_id) {
                Continer_transfer::create([
                    "transfer_vehicle_id" => $transfer_detail->id,
                    "imp_op_contin_id" => $continer_id
                ]);
            }
        }
        return "loading succesfully!";
    }

    public function resive_transfers($source, $destination, $continers = null)
    {
        if ($continers->isEmpty()) {
            return "No containers to transfer";
        }
        $parint_continer = $continers[0]->parent_continer;

        $continer_type = $parint_continer->product->type;

        $avilable_vehicles_big = null;
        $avilable_vehicles_medium = null;
        if ($source instanceof \App\Models\Import_operation) {

            $big_garages = $destination->garages->where("size_of_vehicle", "big")->pluck("id");

            $medium_garages = $destination->garages->where("size_of_vehicle", "medium")->pluck("id");
        } else {

            $medium_garages = $source->garages->where("size_of_vehicle", "medium")->pluck("id");

            $big_garages = $source->garages->where("size_of_vehicle", "big")->pluck("id");
        }
        $avilable_vehicles_big = Vehicle::whereIn("garage_id", $big_garages)->whereNull("transfer_id")->where("type_id", $continer_type->id)->orderBy('capacity', 'desc')->get();


        $avilable_vehicles_medium = Vehicle::whereIn("garage_id", $medium_garages)->whereNull("transfer_id")->where("type_id", $continer_type->id)->orderBy('capacity', 'desc')->get();



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
                "sourceable_type" => get_class($destination),
                "sourceable_id" => $destination->id,
                "destinationable_type" => get_class($source),
                "date_of_resiving" => now(),
                "destinationable_id" => $source->id,
            ]);
            $related_transfer = Transfer::create([
                "sourceable_type" => get_class($source),
                "sourceable_id" => $source->id,
                "destinationable_type" => get_class($destination),
                "destinationable_id" => $destination->id,

            ]);

            $related_transfer->parent_trans = $parent_transfer->id;
            $related_transfer->save();
            $parent_transfer->related_trans = $related_transfer->id;
            $parent_transfer->save();

            if ($source instanceof \App\Models\Import_operation) {
                $this->load_vehicles($related_transfer->id,$parent_transfer->id, $transfer_details, "wait","under_work");
            } else {
                $this->load_vehicles($parent_transfer->id, $related_transfer->id,$transfer_details, "under_work","wait");
            }



            return $transfer_details;
        }

        return "the vehicles is not enugeht for the load";
    }
}
