<?php

namespace App\Jobs;

use App\Models\Import_op_container;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Import_operation_product;
use App\Models\Product;
use App\Models\Imp_continer_product;
use App\Models\Containers_type;
class importing_op_prod implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $import_operation;
    public $validated_products;


    /**
     * Create a new job instance.
     */
    public function __construct($import_operation, $validated_products)
    {
           $this->import_operation = $import_operation;
           $this->validated_products = $validated_products;
    }

    /**
     * Execute the job.
     */


public function handle(): void
{
    if (empty($this->validated_products)) {
        return;
    }

    foreach ($this->validated_products as $product) {
        $product['import_operation_id'] = $this->import_operation->id;

      
        if (!empty($product["special_description"])) {
            $product['imported_load'] = 1;
        }

        $distribution = $product["distribution"];
        unset($product["distribution"]);

        
        $imported_product = Import_operation_product::create($product);

        
        $parent_continer = Containers_type::where("product_id", $product["product_id"])->first();

        if (!$parent_continer) {
            Log::error("No container type found for product ID: {$product["product_id"]}");
            continue; 
        }

        foreach ($distribution as $distrebute) {
            $number_continers = ceil($distrebute['load'] / $parent_continer->capacity);

            for ($count = 0; $count < $number_continers; $count++) {
               
                $continer = Import_op_container::create([
                    "container_type_id" => $parent_continer->id,
                    "import_operation_id" => $this->import_operation->id
                ]);

                
                $load = $parent_continer->capacity;
                if (!empty($product["special_description"])) {
                    $load = 1;
                }

               
                Imp_continer_product::create([
                    "imp_op_cont_id" => $continer->id,
                    "imp_op_product_id" => $imported_product->id,
                    "load" => $load
                ]);
            }
        }
    }
}


     

}
