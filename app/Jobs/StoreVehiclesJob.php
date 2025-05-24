<?php

namespace App\Jobs;

use App\Models\DistributionCenter;
use App\Models\Warehouse;
use App\Models\Garage;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Import_operation;

class StoreVehiclesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        // create an importoperation for all vehicles


        // create every vehicle independently but ith one import operation
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
            foreach ($garages as $garage) {
                $full_area =$garage->vehicles->count();
               
                    $vehicleData["garage_id"] = $garage->id;
                   
                  

            }

                Vehicle::create($vehicleData);


            
        }
    }
}
