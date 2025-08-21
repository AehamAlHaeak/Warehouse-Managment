<?php

namespace App\Jobs;

use App\Models\Employe;
use Illuminate\Bus\Queueable;
use App\Models\reject_details;
use App\Models\Specialization;
use App\Traits\AlgorithmsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\expiration_not;
use Illuminate\Queue\SerializesModels;
use App\Models\Import_operation_product;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class expiration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use AlgorithmsTrait;
    public $import_op_product;
    public function __construct($import_op_product)
    {
       
        $this->$import_op_product=$import_op_product;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {   DB::beginTransaction();
        try{
            $expired_quantity=0;
            $selled_load=0;
            $rejected_load=0;
            $import_op_prod=Import_operation_product::find($this->import_op_product);
            $loads=$import_op_prod->loads;
             $places=[];
            foreach($loads as $load){
              $logs=$this->calculate_load_logs($load); 
              $selled_load+=$logs["selled_load"];
              $rejected_load+=$logs["rejected_load"];
              if($logs["remine_load"]>0){
               $expired_quantity+=$logs["remine_load"];
               reject_details::create([

                "rejected_load" => $logs["remine_load"],
                "imp_cont_prod_id" =>$load->id,
                "why" => "expiration "
            ]);

               $continer=$load->continer;
               //'accepted', 'rejected', 'sold','auto_reject'
                  if($continer->status!="sold"){
                    $continer->status="rejected";
                    $continer->save();
                    $posetion=$continer->posetion_on_stom;
                    if($posetion){
                    $posetion->imp_op_contin_id=null;
                    $section=$posetion->section;
                    $place=$section->existable;
                    $places[$place->id]["place"]=$place;
                     if(!empty($places[$place->id])){
                         $places[$place->id]["expired_quantity"]+=$logs["remine_load"];
                         
                     }
                     else{
                         $places[$place->id]["expired_quantity"]=$logs["remine_load"];
                     }
                    
                    $posetion->save();
                    }
                  }
              }
            }
             $goal_spec_ids = Specialization::whereIn("name", ["warehouse_admin", "distribution_center_admin"])
                    ->pluck("id");
            foreach($places as $blok){
             $admins=$blok["place"]->employees()->whereIn("specialization_id", $goal_spec_ids)->get();
              $tmp_imp_prod=$import_op_prod;
               $tmp_imp_prod->expired_quantity=$blok["expired_quantity"];
             foreach($admins as $admin){
                 $notification=new expiration_not($tmp_imp_prod);
                 $this->send_not($notification,$admin);
             }
            }




             $import_op_prod->sold_load=$selled_load;
             $import_op_prod->rejected_load_before_expiration=$rejected_load;
             $import_op_prod->expired_quantity=$expired_quantity;
             $super_admin_spec_id = Specialization::where("name", "super_admin")->value("id");
                $super_admin = Employe::where("specialization_id", $super_admin_spec_id)->first();
                $notification=new expiration_not( $import_op_prod);
                 $this->send_not($notification,$super_admin);

        // DB::commit();
        }
        catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());
        }
    }
}
