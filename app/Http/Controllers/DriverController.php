<?php

namespace App\Http\Controllers;

use App\Models\Transfer_detail;
use Exception;
use Illuminate\Http\Request;
use App\Traits\AlgorithmsTrait;
use App\Traits\TransferTrait;
use App\Traits\TransferTraitAeh;
class DriverController extends Controller
{
    use TransferTraitAeh;
    public function show_my_curent_transfers(Request $request)
    {
        try{
        $driver = $request->employe;
        $vehicle = $driver->vehicle;
        $curent_transfer = $vehicle->actual_transfer;
        $source=$curent_transfer->sourceable;
        $curent_transfer->from=$source->location;
        $destination=$curent_transfer->destinationable;
        $curent_transfer->to=$destination->location;
        unset($curent_transfer["sourceable"]);
        unset($curent_transfer["destinationable"]);
        if ($curent_transfer) {
              $next_transfer = $curent_transfer->next_transfer;
              $next_transfer->from=$next_transfer->sourceable->location;
              $next_transfer->to=$next_transfer->destinationable->location;
              unset($next_transfer["sourceable"]);
              unset($next_transfer["destinationable"]);
            unset($curent_transfer["next_transfer"]);
            return response()->json(["msg" => "here the actual transfer deer!", "actual_transfer" => $curent_transfer, "next_transfer" => $next_transfer], 202);
        }

        return response()->json(["msg" => "you dont have tasks actualy"], 404);
    }
    catch(Exception $e){
        return response()->json(["msg" => "Error: ". $e->getMessage()], 500);
    }
}
    public function set_status_my_transfer(Request $request)
    {
        try {

            $driver = $request->employe;
            $vehicle = $driver->vehicle;
            $curent_transfer = $vehicle->actual_transfer;
            if ( $vehicle->transfer_id == null) {
                $vehicle->update([
                    "transfer_id" => null
                ]);
                return response()->json(["msg" => "now you dont have any transfers"], 202);
            }
            $next_transfer = $curent_transfer->next_transfer;
            $transfer_detail = $curent_transfer->transfer_details()->where("vehicle_id", $vehicle->id)->first();
            $continers = $transfer_detail->continers;

            if ($continers->isNotEmpty()) {
                
                if ($curent_transfer->destinationable_type == "App\\Models\\User") {
                    $transfer_detail->update([
                        "status" => "received"
                    ]);
                    foreach ($continers as $continer) {
                        $continer->update([
                            "status" => "sold"
                        ]);
                    }
                } else {

                    $transfer_detail->update([
                        "status" => "in_QA"
                    ]);
                }
            } else {
                $transfer_detail->update([
                    "status" => "received"
                ]);
            }
            $curent_trans_noy_finished = $curent_transfer->transfer_details()->where("status", "!=", "resived")->get()->count();
            if ($curent_trans_noy_finished == 0) {
                $curent_transfer->update([
                    "date_of_finishing" => now()
                ]);
            }
            if ($next_transfer) {
                $next_transfer->update([
                    "date_of_resiving" => now()
                ]);
                $curent_transfer = $next_transfer;

                $vehicle->update([
                    "transfer_id" => $next_transfer->id
                ]);
                $next_transfer = null;
            } else {
                $vehicle->update([
                    "transfer_id" => null
                ]);
            }
            return response()->json(["msg" => "your task status updated suucesfuly!", "your_status" => $transfer_detail->status, "actual_transfer" => $curent_transfer, "next_transfer" => $next_transfer,], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()]);
        }
    }
}
