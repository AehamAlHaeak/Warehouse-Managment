<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Import_operation_product;

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
    // check if the produts is availabe(existable)
        if (!empty($this->validated_products)) {
            foreach ($this->validated_products as $product) {
                // إضافة import_operation_id إلى المنتج
                $product['import_operation_id'] = $this->import_operation->id;

                if(!empty($product["special_description"])){
                  $product['imported_load']=1;
                }
                Import_operation_product::create($product);
                Log::info("Product added to import_operation_product: " . json_encode($product));
            }
        }
    }
}
