<?php

namespace App\Jobs;

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

     protected array $vehicles;
     protected int $supplierId;
    /**
     * Create a new job instance.
     */
    public function __construct(array $vehicles, int $supplierId)
    {
         $this->vehicles = $vehicles;
        $this->supplierId = $supplierId;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
               // create an importoperation for all vehicles
        $importOperation = Import_operation::create([
            'supplier_id' => $this->supplierId,
            'arrival_time' => now(),
            'location' => 'Warehouse',  // can specify the location
            'latitude' => 33.5,         // can specify these
            'longitude' => 36.3,
        ]);

        // create every vehicle independently but ith one import operation
        foreach ($this->vehicles as $vehicleData) {
            Vehicle::create([
                'name' => $vehicleData['name'],
                'expiration' => $vehicleData['expiration'],
                'producted_in' => $vehicleData['producted_in'],
                'readiness' => $vehicleData['readiness'],
                'max_load' => $vehicleData['max_load'],
                'location' => $vehicleData['location'],
                'latitude' => $vehicleData['latitude'],
                'longitude' => $vehicleData['longitude'],
                'img_path' => $vehicleData['img_path'] ?? null,
                'capacity' => $vehicleData['capacity'],
                'import_operation_id' => $importOperation->id, // connect the vehicle with the import operation
            ]);
        }
    }
}
