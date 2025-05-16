<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\AlgorithmsTrait;
use App\Models\Import_operation;
use App\Models\Import_op_storage_md;
use App\Models\Positions_on_sto_m;


class import_storage_media implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use AlgorithmsTrait;
   protected $import_operation_id;
    protected $storage_media;
    public function __construct($import_operation_id,$storage_media)
    {
        $this->import_operation_id=$import_operation_id;
        $this->storage_media=$storage_media;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
         foreach($this->storage_media as $storage_element){
          
            for($count=0;$count<$storage_element["quantity"];$count++){
             
             
              $storage_unit=Import_op_storage_md::create([
               "storage_media_id"=>$storage_element["storage_media_id"],
               "import_operation_id"=> $this->import_operation_id,
                
            ]);
         
        
              $parent_storage_media=$storage_unit->parent_storage_media;
               $storage_unit->num_floors=$parent_storage_media->num_floors;
               $storage_unit->num_classes=$parent_storage_media->num_classes;
               $storage_unit->num_positions_on_class=$parent_storage_media->num_positions_on_class;
               
        
           $this->create_postions("App\\Models\\Positions_on_sto_m",$storage_unit,"imp_op_stor_id");
            }

         }

    }
}
