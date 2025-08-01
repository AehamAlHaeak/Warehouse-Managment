<?php

namespace App\Jobs;

use Exception;
use App\Models\Product;
use App\Models\Transfer;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Models\Specialization;
use App\Models\Containers_type;
use App\Models\Transfer_detail;
use App\Models\Import_operation;
use App\Traits\TransferTraitAeh;
use App\Models\Continer_transfer;
use Illuminate\Support\Facades\DB;
use App\Models\Import_op_container;
use Illuminate\Support\Facades\Log;
use App\Models\Imp_continer_product;
use Illuminate\Queue\SerializesModels;
use App\Models\Import_operation_product;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use App\Events\Send_Notification;
use App\Notifications\Importing_success;
use App\Notifications\Importing_failed;
class importing_op_prod implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use TransferTraitAeh;

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

          $employe = Specialization::where("name", "super_admin")->first()->employees()->first();
          DB::beginTransaction();
          try{
        if (empty($this->validated_products)) {
            return;
        }
        $destinations_loads_by_product = [];
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
                throw new Exception("No container type found for product ID: {$product["product_id"]}");
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

                    if ($distrebute["load"] <= $load) {
                        $load = $distrebute["load"];
                    }
                    Imp_continer_product::create([
                        "imp_op_cont_id" => $continer->id,
                        "imp_op_product_id" => $imported_product->id,
                        "load" => $load
                    ]);
                     if($distrebute["send_vehicles"]==true){
                       $destinations_loads_by_product["send_vehicles"][$distrebute["warehouse_id"]][$product["product_id"]][$continer->id] = $continer;
                     }
                     else{
                       $destinations_loads_by_product["dont_send_vehicles"][$distrebute["warehouse_id"]][$product["product_id"]][$continer->id] = $continer;
                    
                     }
                    
                    $distrebute["load"] -= $load;
                }
            }
        } if(array_key_exists("dont_send_vehicles",$destinations_loads_by_product)){
        foreach ($destinations_loads_by_product["dont_send_vehicles"] as $warehouse_id => $products) {
            $warehouse = Warehouse::find($warehouse_id);
            if (!$warehouse) {
                Log::error("No warehouse found for ID: {$warehouse_id}");
                 throw new Exception("No warehouse found for ID: {$warehouse_id}");
                continue;
            }
            foreach ($products as $product => $containers) {
                $product = Product::find($product);
                if (!$product) {
                    Log::error("No product found for ID: {$product}");
                    throw new Exception("No product found for ID: {$product}");
                    continue;
                }

                $transfer = Transfer::create([
                    "sourceable_type" => "App\\Models\\Import_operation",
                    "sourceable_id" => $this->import_operation->id,
                    "destinationable_type" => "App\\Models\\Warehouse",
                    "destinationable_id" => $warehouse_id,
                    "date_of_resiving" => now(),
                    "location" => $this->import_operation->location,
                    "latitude" => $this->import_operation->latitude,
                    "longitude" => $this->import_operation->longitude
                ]);
                $transfer_detail = Transfer_detail::create([
                    "status" => "in_QA",
                    "transfer_id" => $transfer->id
                ]);
                foreach ($containers as $continer) {
                    Continer_transfer::create([
                        "imp_op_contin_id" => $continer->id,
                        "transfer_detail_id" => $transfer_detail->id
                    ]);
                }
            }
        }
    }
    if(array_key_exists("send_vehicles",$destinations_loads_by_product)){
        foreach ($destinations_loads_by_product["send_vehicles"] as $warehouse_id => $products) {
            $warehouse = Warehouse::find($warehouse_id);
            if (!$warehouse) {
                 
                Log::error("No warehouse found for ID: {$warehouse_id}");
                 throw new Exception("No warehouse found for ID: {$warehouse_id}");
                continue;
            }
            foreach ($products as $product => $containers) {
                $product = Product::find($product);
                if (!$product) {
                    throw new Exception("No product found for ID: {$product}");
                    Log::error("No product found for ID: {$product}");
                    continue;
                }
                $containers = collect($containers);
                $this->import_operation = Import_operation::find($this->import_operation->id);
                $responce = $this->resive_transfers($this->import_operation, $warehouse, $containers);
                if (is_string($responce)) {
                    Log::error($responce);
                    throw new Exception($responce);
                }
            }
        }
    }
     
   
            $uuid = (string) Str::uuid();
            $notification = new Importing_success("product");

            $notify=DatabaseNotification::create([
                'id' => $uuid,
                'type' => get_class($notification),
                'notifiable_type' => get_class($employe),
                'notifiable_id' => $employe->id,
                'data' => $notification->toArray($employe),
                'read_at' => null,
            ]);
           $notification->id=$notify->id;
         event(new Send_Notification($employe,$notification));



    DB::commit(); 
    } catch (\Throwable $e) {
        DB::rollBack(); 
        Log::error("Transaction failed in import operation: " . $e->getMessage());
         $uuid = (string) Str::uuid();
            $notification = new Importing_failed("product",$e->getMessage());

            $notify=DatabaseNotification::create([
                'id' => $uuid,
                'type' => get_class($notification),
                'notifiable_type' => get_class($employe),
                'notifiable_id' => $employe->id,
                'data' => $notification->toArray($employe),
                'read_at' => null,
            ]);
         $notification->id=$notify->id;
         event(new Send_Notification($employe,$notification));
        throw $e; 
    }
    }
}
