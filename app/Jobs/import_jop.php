<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class import_jop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $import_jop;
    public $validated_products;
    public $validated_vehicles;
    
    public function __construct($import_jop,$validated_products,$validated_vehicles)
    {
        $this->import_jop=$import_jop;
        $this->validated_vehicles=$validated_vehicles;
        $this->validated_products=$validated_products;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // if(!empty($this->validated_products)){
        // foreach($this->validated_products as $index=>$product){
        //   $product["import_job_id"]=$this->import_jop->id;
        //   Product::create($product);  
          
        // }

        // }
        // if(!empty($this->validated_vehicles)){
        //     foreach($this->validated_vehicles as $index=>$vehicle){
        //         $vehicle["import_job_id"]=$this->import_jop->id;
        //         $vehicle["location"]=$this->import_jop->location;
        //         $vehicle["latitude"]=$this->import_jop->latitude;
        //         $vehicle["longitude"]=$this->import_jop->longitude;
        //         Vehicle::create($vehicle);  
                
        //       }
        // }
    }
}
