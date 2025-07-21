<?php

namespace App\Jobs;

use App\Models\Vehicle;
use App\Models\Violation;
use Illuminate\Bus\Queueable;
use App\Models\Specialization;
use App\Traits\AlgorithmsTrait;
use App\Traits\TransferTraitAeh;
use App\Models\Continer_transfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Import_op_container;
use App\Models\reserved_details;

class TempViolation implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
  use AlgorithmsTrait;
  use TransferTraitAeh;
  /**
   * Create a new job instance.
   */
  protected $violation_id;


  public function __construct($violation_id)
  {
    $this->violation_id = $violation_id;
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {

    DB::BeginTransaction();
    try {
      $roles = ['warehouse_admin', 'distribution_center_admin', 'QA'];
      $violation = Violation::find($this->violation_id);
      $violation->status = "effected";
      $place_of_vio = $violation->violable;
      $violation->job_id = null;
      $meaning_in_source = collect();
      $meaning_in_destination = collect();

      $violation->save();

      $place_of_vio->readiness = 0.5;
      $place_of_vio->save();

      $continers = null;
      $source = null;
      $destination = null;

      if (get_class($place_of_vio) == "App\Models\Vehicle") {
        $driver = $place_of_vio->driver;
        unset($place_of_vio["driver"]);
        if ($place_of_vio->transfer_id != null) {

          $transfer = $place_of_vio->actual_transfer;
          unset($place_of_vio["actual_transfer"]);
          $source = $transfer->sourceable;

          $destination = $transfer->destinationable;

          $load_of_vehicle = $transfer->transfer_details()->where("vehicle_id", $place_of_vio->id)->first();
          $continers = $load_of_vehicle->continers;
          if ($continers->isNotEmpty()) {


            foreach ($continers as $continer) {
              $continer->status = 'auto_reject';
              $continer->save();
            }


            if (get_class($destination) == 'App\Models\User') {
              echo "it is user";
              $product_id = $place_of_vio->product_id;
              $avilable_sections = null;
              $avilable_sections = $source->sections()->where("product_id", $product_id)->get();

              $new_continers = [];

              foreach ($avilable_sections as $section) {
                $storage_elements = $section->storage_elements()->where("readiness", ">", 0.8)->get();
                $ids = $storage_elements->pluck('id');
                 echo ".....section _ id : " . $section->id . "\n\n";
             
                var_dump($ids);
                Log::info("section ID " . json_encode($section->id));
               
                $next_remine_load = collect();

                while ($continers->isNotEmpty()) {
                  $continer = $continers->splice(0, 1)->first();
                  echo "continer_id  " . $continer->id . "\n";

                  $new_ids = $this->move_reserved_from_container($storage_elements, $continer, array_values($new_continers));

                  if (empty($new_ids)) {
                    Log::warning("Container ID " . $continer->id . "  continer  cannot reserving in the section  ID " . $section->id);
                    $next_remine_load->push($continer);
                  }

                  var_dump($new_ids);
                  $new_continers = array_merge($new_continers, $new_ids);
                }
                

                $continers = $next_remine_load;
                if($continers->isEmpty()){
                 break ;
                }
              }
              if ($continers->isNotEmpty()) {
                Log::error("The destination does not have enough containers for all reservations. Remaining original containers: " . json_encode($continers->pluck('id')->toArray()));
                throw new \Exception("The destination does not have enough containers for all reservations.");
              }
              Log::info("Final new_continers after all sections processed: " . json_encode(array_values($new_continers)));
              var_dump($new_continers);
              $fetched_continers = collect();
              foreach ($new_continers as $continer_id) {
                $fetched_continer = Import_op_container::find($continer_id);
                $fetched_continers->push($fetched_continer);
              }
                    $last_continer= $fetched_continers->last();
                    $loads = $last_continer->loads;
                    $inventory = $this->inventory_on_continer($last_continer);
                    $reserved_for_this_detail = 0;
                    $reserved_loads_to_detail=collect();
                    foreach ($loads as $load) {
                        $reserved_loads = $load->reserved_load;
                        foreach ($reserved_loads as $reserved_load) {

                            if ($reserved_load->transfer_details_id == $load_of_vehicle->id) {
                                $reserved_loads_to_detail->push($reserved_load);
                            }
                        }
                    }
                     $reserved_for_this_detail= $reserved_loads_to_detail->sum("reserved_load");
                    if ($reserved_for_this_detail != $inventory["reserved_load"] || $inventory["remine_load"]>0) {
           
                         $new_continer=$this->cut_load($last_continer,$reserved_loads_to_detail);
                        

                   $last_continer=$fetched_continers->pop();
                    
                   $fetched_continers->push($new_continer);
                  
                  }
                 
              $transfer_details = $this->resive_transfers($source, $destination, $fetched_continers);
              
              
              
              $load_of_vehicle->status = "cut";
              $load_of_vehicle->save();
              $related_transfer = $transfer->next_transfer;
              $place_of_vio->transfer_id = $related_transfer->id;
              $place_of_vio->save();
              $detial_of_vehicle = $related_transfer->transfer_details()->where("vehicle_id", $place_of_vio->id)->first();
              $detial_of_vehicle->status = "under_work";
              Continer_transfer::where("transfer_detail_id", $load_of_vehicle->id)->update(["transfer_detail_id" => $detial_of_vehicle->id]);
              $detial_of_vehicle->save();

               
              if ($transfer_details != "No containers to transfer" && $transfer_details != "the vehicles is not enough for the load") {
                foreach ($transfer_details as $block) {
                  $vehicle = Vehicle::find($block["vehicle_id"]);
                  $actual_transfer = $vehicle->actual_transfer;
                  $new_detail_intrans = $actual_transfer->transfer_details()->where("vehicle_id", $block["vehicle_id"])->first();
                  foreach ($block["container_ids"] as $continer_id) {
                    reserved_details::where("transfer_details_id", $load_of_vehicle->id)->update(["transfer_details_id" => $new_detail_intrans->id]);
                  }

                }
              } else {

                echo $transfer_details; 
                throw new \Exception($transfer_details);
              }
              
            }
          } elseif ($continers->isEmpty()) {

            if (get_class($destination) == "App\Models\Import_operation") {


              $load_of_vehicle->status = "cut";
              $load_of_vehicle->save();
              $next_transfer = $transfer->prev_transfer;


              $place_of_vio = Vehicle::find($place_of_vio->id);
              $place_of_vio->transfer_id = $next_transfer->id;
              $place_of_vio->save();


              $detail_in_next = $next_transfer->transfer_details()->where("vehicle_id", $place_of_vio->id)->first();
              $continers = $detail_in_next->continers;
              unset($detail_in_next["continers"]);
              Continer_transfer::where("transfer_detail_id", $detail_in_next->id)->delete();
              $detail_in_next->status = "under_work";
              $detail_in_next->save();
              $this->resive_transfers($destination, $source, $continers);
            }
          }





          if (get_class($source) != 'App\Models\Import_operation' && get_class($source) != 'App\Models\User') {
            echo "here the source meaning\n";
            $meaning_in_source = $source->employees()
              ->whereHas('specialization', fn($query) => $query->whereIn('name', $roles))
              ->get();
          }

          if (get_class($destination) != 'App\Models\Import_operation' && get_class($destination) != 'App\Models\User') {
            echo "here the destination meaning \n";
            $meaning_in_destination = $destination->employees()
              ->whereHas('specialization', fn($query) => $query->whereIn('name', $roles))
              ->get();
          } elseif (get_class($destination) == 'App\Models\User') {
            //send notefication
          }
        } else {
          $garage = $place_of_vio->garage;
          $source = $garage->existable;
          $meaning_in_source = $source->employees()
            ->whereHas('specialization', fn($query) => $query->whereIn('name', $roles))
            ->get();
        }

        $super_admin = Specialization::where("name", "super_admin")->first()->employees()->first();
        //send not
        if ($meaning_in_source->isNotEmpty()) {
          foreach ($meaning_in_source as $employe) {
            //send not 
          }
        }
        if ($meaning_in_destination->isNotEmpty()) {
          foreach ($meaning_in_destination as $employe) {
            //send not 
          }
        }
      } elseif (get_class($place_of_vio) == "App\Models\Import_op_storage_md") {
        $continers = $place_of_vio->impo_container;
        $section = $place_of_vio->section()->first();
        unset($place_of_vio["impo_container"]);
        $destination = $section->existable;
        $meaning_in_destination = $destination->employees()
          ->whereHas('specialization', fn($query) => $query->whereIn('name', $roles))
          ->get();

        $super_admin = Specialization::where("name", "super_admin")->first()->employees()->first();
        //send not
        if ($meaning_in_destination->isNotEmpty()) {
          foreach ($meaning_in_destination as $employe) {
            //send not 
          }
        }
        foreach ($continers as $continer) {
          $continer->status = 'auto_reject';
          $continer->save();
        }
      }


      DB::commit();
    } catch (\Throwable $e) {
      DB::rollBack();
      Log::error("Transaction failed in import operation: " . $e->getMessage());
      throw $e;
    }
  }
}
