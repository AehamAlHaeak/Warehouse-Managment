<?php

namespace App\Jobs;

use Exception;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use App\Models\Specialization;
use App\Traits\AlgorithmsTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\Shortage_of_inventory_in_company;

class check_load_of_company_pr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
     use AlgorithmsTrait;
    protected $product_id;
    public function __construct($product_id)
    {
       $this->product_id=$product_id;
    }

    
    public function handle(): void
    {
        try{
           
             $product=Product::find($this->product_id);
          
            $product=$this->invintory_product_in_company($product);
               print_r($product);
            if( $product->load_on_company<= $product->max_load_on_company*0.3){
             $super_admin_spec=Specialization::where("name","super_admin")->first();
             $super_admin=$super_admin_spec->employees()->first();
             $product=$product->toArray();
             $notification=new Shortage_of_inventory_in_company($product);
             $this->send_not($notification,$super_admin);
            }

        }
      catch (\Throwable $e) {
            Log::error($e->getMessage());

        }
    }
}
