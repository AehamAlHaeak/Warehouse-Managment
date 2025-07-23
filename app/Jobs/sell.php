<?php

namespace App\Jobs;

use App\Models\Continer_transfer;
use Exception;
use Throwable;
use App\Models\Invoice;
use App\Models\Vehicle;
use App\Models\Sell_detail;
use Illuminate\Bus\Queueable;
use App\Traits\AlgorithmsTrait;
use App\Traits\TransferTraitAeh;
use Illuminate\Support\Facades\DB;
use App\Models\Import_op_container;
use Illuminate\Support\Facades\Log;
use App\Models\Imp_continer_product;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\reserved_details;
class sell implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use AlgorithmsTrait;
    use TransferTraitAeh;

    protected $invoice_id;
    public function __construct($invoice_id)
    {
        $this->invoice_id = $invoice_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::beginTransaction();
        try {
            $invoice = Invoice::find($this->invoice_id);
            if (!$invoice) {
                throw new Exception("Invoice not found");
            }
            
            $transfer = $invoice->transfers->first();
            if ($transfer->date_of_resiving != null) {
                throw new Exception("Invoice already accepted");
            }
            $destination = $transfer->destinationable;
            $source = $transfer->sourceable;
            $transfer_details = $transfer->transfer_details;
            $invoice->status = "accepted";
            $invoice->job_id = null;
            $invoice->save();
            $transfer->date_of_resiving = now();
            $transfer->save();
            if ($invoice->type == "Un_transfered") {

                $transfer->date_of_finishing = now();
                $transfer->save();
                foreach ($transfer_details as $transfer_detail) {
                    
                    $reserved_loads = $transfer_detail->reserved_loads;
                    foreach ($reserved_loads as $reserved_load) {
                        Sell_detail::create([
                            "transfer_details_id" => $reserved_load->transfer_details_id,
                            "sold_load" => $reserved_load->reserved_load,
                            "imp_cont_prod_id" => $reserved_load->imp_cont_prod_id
                        ]);
                        $parent_load=$reserved_load->parent_load;
                         $continer=$parent_load->container;

                        $reserved_load->delete($reserved_load->id);
                        $inventory = $this->inventory_on_continer($continer);
                        if ($inventory["reserved_load"] == 0 && $inventory["remine_load"] == 0) {
                            $continer->status="sold";
                            $continer->save();
                            $posetion= $continer->posetion_on_stom;
                            $posetion->imp_op_contin_id=null;
                            $posetion->save();
                        }
                    }

                    $transfer_detail->status = "received";
                    $transfer_detail->save();
                }
            } else if ($invoice->type == "transfered") {
               
                while($transfer_details->isNotEmpty()){ 
                    $transfer_detail=$transfer_details->splice(0, 1)->first();
                    $continers = collect();
                    $continer_reserved=$transfer_detail->continers;
                   
                    foreach( $continer_reserved as $orginal_continer){
                        
                    
                    $loads = $orginal_continer->loads;
                    $inventory = $this->inventory_on_continer($orginal_continer);
                    $reserved_for_this_detail = 0;
                    $reserved_loads_to_detail=collect();
                    foreach ($loads as $load) {
                        $reserved_loads = $load->reserved_load;
                        foreach ($reserved_loads as $reserved_load) {

                            if ($reserved_load->transfer_details_id == $transfer_detail->id) {
                                $reserved_loads_to_detail->push($reserved_load);
                            }
                        }
                    }
                     $reserved_for_this_detail= $reserved_loads_to_detail->sum("reserved_load");
                    if ($reserved_for_this_detail != $inventory["reserved_load"] || $inventory["remine_load"]>0) {
                      
                         $new_continer=$this->divide_load($orginal_continer,$reserved_loads_to_detail);
                      
                    Continer_transfer::where("transfer_detail_id", $transfer_detail->id)->where("imp_op_contin_id",$orginal_continer->id)->delete();
                   $continers->push($new_continer);
                    }
                    else{
                        $continers->push($orginal_continer);
                    }

                }
                    $details=$this->resive_transfers($source,$destination,$continers);
                   
                    
                    if($details=="the vehicles is not enough for the load" || $details=="No containers to transfer"){
                     throw new \Exception($details);
                    }
                    foreach($details as $block){
                          $vehicle = Vehicle::find($block["vehicle_id"]);
                          $live_transfer=$vehicle->actual_transfer;
                          $live_transfer->invoice_id=$invoice->id;
                          $live_transfer->save();
                          $detail=$live_transfer->transfer_details()->where("vehicle_id",$block["vehicle_id"])->first();
                          $continers=$detail->continers;
                          
                         
                          foreach($continers as $continer){
                             $loads=$continer->loads;
                             foreach($loads as $load){
                               $reserved_loads=$load->reserved_load()->update([
                                  "transfer_details_id"=>$detail->id
                               ]);
                               
                             }
                          
                            
                             Continer_transfer::where("transfer_detail_id", $transfer_detail->id)->where("imp_op_contin_id",$continer->id)->delete();
                             
                            }
                        }
                    
                     Continer_transfer::where("transfer_detail_id", $transfer_detail->id)->delete();
                     reserved_details::where("transfer_details_id", $transfer_detail->id)->delete();
                    
                     $transfer_detail->delete();
                     
                  
                }
                 
            
             $transfer->delete($transfer->id);
         
            
            }






            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Transaction failed in import operation: " . $e->getMessage());
            throw $e;
        }
    }
}
