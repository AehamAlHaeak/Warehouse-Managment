<?php

namespace App\Jobs;

use Log;
use App\Models\Garage;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Bus\Queueable;
use App\Models\Specialization;
use App\Models\Import_operation;
use App\Models\DistributionCenter;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log as l;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
                    Vehicle::create($vehicleData);
                    break;
                }
            }
        }
    }
}
