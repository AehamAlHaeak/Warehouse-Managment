<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use App\Models\Specialization;
use App\Notifications\Product_informations;
use App\Traits\AlgorithmsTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class calculate_sold_quantity_freq implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use AlgorithmsTrait;
   
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
       try{
            $products=Product::all();
 $super_admin_spec=Specialization::where("name","super_admin")->first();
             $super_admin=$super_admin_spec->employees()->first();
             
            
            foreach($products as $product){
                $type=$product->type;
            unset($product->type);
            $sold_load=0;
            $warehouses=$type->warehouses;
            $dist_cs=$type->distribution_centers;
            foreach ($warehouses as $warehouse) {
                $sold_load+=$this->calculate_salled_quantity_prod_sence_fore($warehouse, $product, now()->subDays(30), now());
           
            }
            foreach ($dist_cs as $dist_c) {
                $sold_load+=$this->calculate_salled_quantity_prod_sence_fore($dist_c, $product,now()->subDays(30), now());
                
            }
            $product->sold_load=$sold_load;
            
            $product=$product->toArray();
             $notification=new Product_informations($product);
             $this->send_not($notification,$super_admin);
            }

        }catch (\Throwable $e) {
            Log::error($e->getMessage());

        }  
    }
}
