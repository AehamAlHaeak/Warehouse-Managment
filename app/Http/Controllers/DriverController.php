<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\TransferTrait;
use App\Models\Specialization;
use App\Models\Transfer_detail;
use App\Notifications\Load_incoming;
use App\Traits\AlgorithmsTrait;
use App\Traits\TransferTraitAeh;
use Illuminate\Notifications\DatabaseNotification;
use App\Events\Send_Notification;

class DriverController extends Controller
{
    use TransferTraitAeh;
    public function show_my_curent_transfers(Request $request)
    {
        try {
            $driver = $request->employe;
            $vehicle = $driver->vehicle;
            if (!$vehicle) {
                return response()->json(["msg" => "you dont have vehicle"], 404);
            }
            if ($vehicle->transfer_id == null) {
                return response()->json(["msg" => "relax time", 404]);
            }
            $curent_transfer = $vehicle->actual_transfer;

            $source = $curent_transfer->sourceable;

            if (!($source instanceof \App\Models\User)) {
                $curent_transfer->from = $source->location;
            } else {
                $curent_transfer->from = $curent_transfer->location;
            }

            $destination = $curent_transfer->destinationable;
            if (!($destination instanceof \App\Models\User)) {
                $curent_transfer->to = $destination->location;
            } else {
                $curent_transfer->to = $curent_transfer->location;
            }
            $curent_transfer->sourceable_type = str_replace("App\\Models\\", "", $curent_transfer->sourceable_type);
            $curent_transfer->destinationable_type = str_replace("App\\Models\\", "", $curent_transfer->destinationable_type);
            unset($curent_transfer["location"]);
            unset($curent_transfer["sourceable"]);
            unset($curent_transfer["destinationable"]);

            if ($curent_transfer) {
                $next_transfer = $curent_transfer->next_transfer;
                unset($curent_transfer["next_transfer"]);
                if (get_class($destination) == "App\Models\Import_operation") {

                    $next_transfer = $curent_transfer->prev_transfer;
                    unset($curent_transfer["prev_transfer"]);
                }
                $transfer_detail_in_next = $next_transfer->transfer_details()->where("vehicle_id", $vehicle->id)->first();
                if ($transfer_detail_in_next->status == "received") {
                    $next_transfer = null;
                }
                if ($next_transfer != null) {
                    $source = $next_transfer->sourceable;

                    $destination = $next_transfer->destinationable;
                    if (!($source instanceof \App\Models\User)) {
                        $next_transfer->from = $source->location;
                    } else {
                        $next_transfer->from = $next_transfer->location;
                    }
                    if (!($destination instanceof \App\Models\User)) {
                        $next_transfer->to = $destination->location;
                    } else {
                        $next_transfer->to = $next_transfer->location;
                    }
                    $next_transfer->sourceable_type = str_replace("App\\Models\\", "", $next_transfer->sourceable_type);
                    $next_transfer->destinationable_type = str_replace("App\\Models\\", "", $next_transfer->destinationable_type);
                    unset($next_transfer["location"]);
                    unset($next_transfer["sourceable"]);
                    unset($next_transfer["destinationable"]);

                    unset($curent_transfer["next_transfer"]);
                }
                return response()->json(["msg" => "here the actual transfer deer!", "actual_transfer" => $curent_transfer, "next_transfer" => $next_transfer], 202);
            }

            return response()->json(["msg" => "you dont have tasks actualy"], 404);
        } catch (Exception $e) {
            return response()->json(["msg" => "Error: " . $e->getMessage()], 500);
        }
    }
    public function set_status_my_transfer(Request $request)
    {
        try {

            $driver = $request->employe;
            $vehicle = $driver->vehicle;
            $curent_transfer = $vehicle->actual_transfer;
            if ($vehicle->transfer_id == null) {
                $vehicle->update([
                    "transfer_id" => null
                ]);
                return response()->json(["msg" => "now you dont have any transfers"], 202);
            }
            $next_transfer = $curent_transfer->next_transfer;

            if ($curent_transfer->destinationable_type == "App\\Models\\Import_operation") {
                $next_transfer = $curent_transfer->prev_transfer;
                $transfer_detail_in_next = $next_transfer->transfer_details()->where("vehicle_id", $vehicle->id)->first();
            }

            $transfer_detail = $curent_transfer->transfer_details()->where("vehicle_id", $vehicle->id)->first();
           
            if ($transfer_detail->status == "in_QA") {
                return response()->json(["msg" => "this load is already in QA wait the Quality analist"], 400);
            }
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
                    $spec = Specialization::where("name", "QA")->first();
                    $destination = $curent_transfer->destinationable;
                    unset($curent_transfer->destinationable);
                    $Qa_admins = $destination->employees()->where("specialization_id", $spec->id)->get();
                    foreach ($Qa_admins as $employe) {
                        $uuid = (string) Str::uuid();
                        $notification = new Load_incoming($transfer_detail->id);

                        $notify = DatabaseNotification::create([
                            'id' => $uuid,
                            'type' => get_class($notification),
                            'notifiable_type' => get_class($employe),
                            'notifiable_id' => $employe->id,
                            'data' => $notification->toArray($employe),
                            'read_at' => null,
                        ]);
                        $notification->id = $notify->id;
                        event(new Send_Notification($employe, $notification));
                    }
                }
            } else {


                $transfer_detail->update([
                    "status" => "received"
                ]);
            }
            $curent_trans_not_finished = $curent_transfer->transfer_details()->where("status", "!=", "resived")->where("status", "!=", "cut")->get()->count();


            if ($curent_trans_not_finished == 0) {
                $curent_transfer->update([
                    "date_of_finishing" => now()
                ]);
            }
         
            if ($next_transfer) {
                 if ($transfer_detail->status != "in_QA") {
                $transfer_detail_in_next = $next_transfer->transfer_details()->where("vehicle_id", $vehicle->id)->first();

                if ($transfer_detail_in_next->status != "received" ) {

                    $next_transfer->update([
                        "date_of_resiving" => now()
                    ]);

                    $curent_transfer = $next_transfer;

                    $vehicle->update([
                        "transfer_id" => $next_transfer->id
                    ]);
                    $next_transfer = null;
                } 
                }
            } 
          
        else {
                 $next_transfer=null;
                $vehicle->update([
                    "transfer_id" => null
                ]);
            }
            unset($curent_transfer->next_transfer);
           
            return response()->json(["msg" => "your task status updated suucesfuly!", "your_status" => $transfer_detail->status, "actual_transfer" => $curent_transfer, "next_transfer" => $next_transfer], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()]);
        }
    }
}
