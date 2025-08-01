<?php

namespace App\Jobs;


use App\Models\Garage;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Models\Specialization;
use App\Traits\ViolationsTrait;
use App\Models\Import_operation;
use App\Events\Send_Notification;
use App\Models\DistributionCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use App\Notifications\Importing_failed;
use App\Notifications\Importing_success;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log as l;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
class StoreVehiclesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ViolationsTrait;
    protected $vehicles;
    protected $import_operation;
    protected $latitude;
    protected $longitude;
    protected $location;
    protected $id;
    /**
     * Create a new job instance.
     */
    public function __construct(array $vehicles, $id, $location, $longitude, $latitude)
    {
        $this->vehicles = $vehicles;
        $this->location = $location;
        $this->longitude = $longitude;
        $this->latitude = $latitude;
        $this->id = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $employe = Specialization::where("name", "super_admin")->first()->employees()->first();
          DB::beginTransaction();
          try{
        foreach ($this->vehicles as $vehicleData) {
            $vehicleData['import_operation_id'] = $this->id;
            $vehicleData["latitude"] = $this->latitude;
            $vehicleData["longitude"] = $this->longitude;
            $vehicleData["location"] = $this->location;

            $model = "App\\Models\\" . $vehicleData["place_type"];
            $place = $model::find($vehicleData["place_id"]);
            unset($vehicleData["place_type"]);
            unset($vehicleData["place_id"]);
            $garages = $place->garages;
            $driver_spec = Specialization::where('name', 'driver')->first();
            $avilable_drivers = $place->employees()
                ->where('specialization_id', $driver_spec->id)
                ->whereDoesntHave('vehicle')
                ->get();

            foreach ($garages as $garage) {
                if ($garage->max_capacity > $garage->vehicles->count() && $garage->size_of_vehicle == $vehicleData["size_of_vehicle"]) {

                    $vehicleData["garage_id"] = $garage->id;
                    if ($avilable_drivers->isNotEmpty()) {
                        $driver = $avilable_drivers->splice(0, 1)->first();
                        $vehicleData["driver_id"] = $driver->id;
                    }
                   $vehicle=Vehicle::create($vehicleData);
                   $this->reset_conditions_on_object($vehicle);
                    break;
                }
            }
        }
   $uuid = (string) Str::uuid();
            $notification = new Importing_success("Vehicles");

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
            $notification = new Importing_failed("Vehicles",$e->getMessage());

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
