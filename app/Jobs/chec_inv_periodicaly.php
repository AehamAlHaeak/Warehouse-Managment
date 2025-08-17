<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class chec_inv_periodicaly implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    
    public function __construct()
    {
       
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            $products=Product::all();
            foreach($products as $product){
                dispatch(new check_load_of_company_pr($product->id));
            }

        }catch (\Throwable $e) {
            Log::error($e->getMessage());

        }
    }
}
