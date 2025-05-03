<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use App\Models\Import_jop_product;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;


class importing_job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $import_jop;
    public $validated_products;
    public $validated_vehicles;
    
    public function __construct($import_jop,$validated_products,$validated_vehicles)
    {
        $this->import_jop=$import_jop;
        $this->validated_products = is_array($validated_products) ? array_values($validated_products) : [];
        $this->validated_vehicles = is_array($validated_vehicles) ? array_values($validated_vehicles) : [];
        
  
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
   
      
      if(!empty($this->validated_products)){
            foreach($this->validated_products as $index=>$product){
              Log::info("create product");
              $product["import_jop_id"]=$this->import_jop->id;
            
             
              Import_jop_product::create($product);  
              
            }
           
          }
         
         

        if(!empty($this->validated_vehicles)){
            foreach($this->validated_vehicles as $index=>$vehicle){
              Log::info("create vehicles");
              $vehicle["import_jop_id"]=$this->import_jop->id;
              $vehicle["latitude"]=$this->import_jop->latitude;
              $vehicle["longitude"]=$this->import_jop->longitude;
              $vehicle["location"]=$this->import_jop->location;
             
            Vehicle::create($vehicle);  
             
            }
          }
            
         
         
    }
}
